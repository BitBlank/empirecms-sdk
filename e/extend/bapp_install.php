<?php

function l($str)
{
    echo date("[Y-m-d h:i:s] ") . $str . "<br>";
}

l('正在为您安装B.app插件...');

require("../class/connect.php");
require("../class/q_functions.php");

$link = db_connect();
$empire = new mysqlquery();

$paytype = 'bapp';

l('正在配置 「其他 > 在线支付 > 管理支付接口 > 配置支付接口：bapp」');
$payr = $empire->fetch1("select * from {$dbtbpre}enewspayapi where paytype='$paytype' limit 1");
if ($payr) {
    l(json_encode($payr));
    l('B.app插件已经成功安装');
} else {
    $res = $empire->query("insert into {$dbtbpre}enewspayapi set paytype='$paytype',myorder=0,payfee='0',payuser='bapp app key',partner='',paykey='bapp app secret',paylogo='https://cdn.fwtqo.cn/static/img/20190613_48.png',paysay='B.app操作簡單，掃一掃即可完成支付，免礦工費，支持大額支付',payname='B.app',isclose=0,payemail='',paymethod=0");
    l('B.app插件安装结果:' . json_encode($res));
}

$lib_url = str_replace('/e/extend/bapp_install.php', '/e/payapi/ShopPay.php?paytype=' . $paytype, $_SERVER['REQUEST_URI']);

l('正在配置「商城 > 支付与配送 > 管理支付方式 > 修改支付方式：Bapp」');
l('当前URL路径 ' . $_SERVER['REQUEST_URI']);
l('在线支付地址 ' . $lib_url);

$shop_pay_fs = $empire->fetch1("select * from {$dbtbpre}enewsshoppayfs where payurl like '%paytype=$paytype%' limit 1");
if ($shop_pay_fs) {
    l(json_encode($shop_pay_fs));
    l('B.app商城支付已经成功配置');
} else {
    $res = $empire->query("insert into {$dbtbpre}enewsshoppayfs set payname='B.app',paysay='B.app支付',payurl='$lib_url',userpay=0,userfen=0,isclose=0,isdefault=0");
    l('B.app商城支付配置结果:' . json_encode($res));
}

l('');
l('B.app插件安装程序已完成');
l('<a href="/">返回首页</a>');

db_close();
$empire = null;