<?php
require("../../class/connect.php");
require("../../class/q_functions.php");
require("../../member/class/user.php");

eCheckCloseMods('pay');
$link = db_connect();
$empire = new mysqlquery();
$editor = 1;

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

$phome = $_GET['phome'];
if (!$phome) {
    $phome = getcvar('payphome');
}
$user = array();
$ddid = (int)getcvar('paymoneyddid');
$bgid = (int)getcvar('paymoneybgid');
$money = 0;
if ($_GET['money']) {
    $money = (float)$_GET['money'] / 100;
}
$orderid = $_GET['orderid'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonStr = file_get_contents('php://input');
    $notifyData = json_decode($jsonStr, true);
    $bapp_app_secret = $payr['paykey'];
    $money = (float)$notifyData['amount'] / 100;
    $orderid = $notifyData['order_id'];
    $calcSign = get_sign($bapp_app_secret, $notifyData);
    if ($calcSign != $notifyData['sign']) {
        echo 'SIGN ERROR';
        db_close();
        $empire = null;
        die();
    }
    if ($notifyData['order_state'] != 1) {
        echo 'ORDER STATE ERROR';
        db_close();
        $empire = null;
        die();
    }
    if ($notifyData['extra']) {
        $extra = json_decode($notifyData['extra'], true);
        $phome = $extra['phome'];
        $ddid = $extra['ddid'];
        $bgid = $extra['bgid'];
        $user[userid] = $extra['userid'];
        $user[username] = $extra['username'];
    }
} else {
    if (in_array($phome, array('PayToFen', 'PayToMoney', 'BuyGroupPay'))) {
        $user = islogin();
    }
}

if (!in_array($phome, array('PayToFen', 'PayToMoney', 'ShopPay', 'BuyGroupPay'))) {
    printerror('您来自的链接不存在', '', 1, 0, 1);
}

$paytype = 'bapp';
$payr = $empire->fetch1("select * from {$dbtbpre}enewspayapi where paytype='$paytype' limit 1");
if (!$payr['payid'] || $payr['isclose']) {
    printerror('您来自的链接不存在', '', 1, 0, 1);
}

include('../payfun.php');
if ($phome == 'PayToFen') {
    $pr = $empire->fetch1("select paymoneytofen,payminmoney from {$dbtbpre}enewspublic limit 1");
    $fen = floor($money) * $pr[paymoneytofen];
    $paybz = '购买点数: ' . $fen;
    PayApiBuyFen($fen, $money, $paybz, $orderid, $user[userid], $user[username], $paytype);
} elseif ($phome == 'PayToMoney') {
    $paybz = '存预付款';
    PayApiPayMoney($money, $paybz, $orderid, $user[userid], $user[username], $paytype);
} elseif ($phome == 'ShopPay') {
    include('../../data/dbcache/class.php');
    $paybz = '商城购买 [!--ddno--] 的订单(ddid=' . $ddid . ')';
    PayApiShopPay($ddid, $money, $paybz, $orderid, '', '', $paytype);
} elseif ($phome == 'BuyGroupPay') {
    include("../../data/dbcache/MemberLevel.php");
    PayApiBuyGroupPay($bgid, $money, $orderid, $user[userid], $user[username], $user[groupid], $paytype);
}

db_close();
$empire = null;

?>