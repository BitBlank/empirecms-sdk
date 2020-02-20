<?php
if (!defined('InEmpireCMS')) {
    exit();
}

eCheckCloseMods('pay');

function get_client_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function get_sign($appSecret, $orderParam)
{
    $signOriginStr = '';
    ksort($orderParam);
    foreach ($orderParam as $key => $value) {
        if (empty($key) || $key == 'sign') {
            continue;
        }
        $signOriginStr = $signOriginStr . $key . "=" . $value . "&";
    }
    return strtolower(md5($signOriginStr . "app_secret=" . $appSecret));
}

function http_request($url, $method = 'GET', $params = [])
{
    $curl = curl_init();
    if ($method == 'POST') {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        $jsonStr = json_encode($params);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonStr);
    } else if ($method == 'GET') {
        $url = $url . "?" . http_build_query($params, '', '&');
    }
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    $output = curl_exec($curl);
    if (curl_errno($curl) > 0) {
        return [];
    }
    curl_close($curl);
    $json = json_decode($output, true);
    return $json;
}

$order_id = $ddno ? $ddno : time();
esetcookie("checkpaysession", $order_id, 0);
$phome = $_POST['phome'];
if (!in_array($phome, array('PayToFen', 'PayToMoney', 'ShopPay', 'BuyGroupPay'))) {
    printerror('您来自的链接不存在', '', 1, 0, 1);
}

$body = 'ECMS';
if ($phome == 'PayToFen') {
    $body = '购买点数';
} elseif ($phome == 'PayToMoney') {
    $body = '存预付款';
} elseif ($phome == 'ShopPay') {
    $body = '商城支付';
} elseif ($phome == 'BuyGroupPay') {
    $body = '购买充值类型';
}

$user = array();
if (in_array($phome, array('PayToFen', 'PayToMoney', 'BuyGroupPay'))) {
    $user = islogin();
}

$extra = json_encode(array(
    'phome' => $phome,
    'userid' => $user[userid],
    'username' => $user[username],
    'ddid' => (int)getcvar('paymoneyddid'),
    'bgid' => (int)getcvar('paymoneybgid')
));

$amount = (int)($money * 100);
$reqParam = array(
    'order_id' => $order_id,
    'amount' => $amount,
    'body' => $body,
    'notify_url' => $PayReturnUrlQz . "e/payapi/bapp/payend.php",
    'return_url' => $PayReturnUrlQz . "e/payapi/bapp/payend.php?phome=" . $phome . "&orderid=" . $order_id . "&money=" . $amount,
    'extra' => $extra,
    'order_ip' => get_client_ip(),
    'amount_type' => 'CNY',
    'time' => time() * 1000,
    'app_key' => $payr['payuser']
);

$sign = get_sign($payr['paykey'], $reqParam);
$reqParam['sign'] = $sign;

$err_msg = '';
$gotopayurl = null;
$res = http_request('https://bapi.app/api/v2/pay', 'POST', $reqParam);
if ($res && $res['code'] == 200) {
    $gotopayurl = $res['data']['pay_url'];
} else if ($res) {
    $err_msg = "code=" . $res['code'] . ";msg=" . $res['msg'];
}

?>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bapp Payment</title>
    <meta http-equiv="Cache-Control" content="no-cache"/>
</head>
<body>
<?php if ($gotopayurl) { ?>
    <script>
        window.location.href = '<?=$gotopayurl?>';
    </script>
<input type="button" style="font-size: 9pt" value="Bapp" name="v_action"
       onclick="self.location.href='<?= $gotopayurl ?>';">
<?php } else { ?>
    <h3>Unknown error</h3>
    <p><?= $err_msg ?></p>
<?php } ?>
</body>
</html>
