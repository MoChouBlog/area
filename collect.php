<?php
require "./AreaCollect.php";
ini_set('memory_limit', '20480M');
ini_set('max_execution_time', 0); //不限时

$t1 = microtime(true);

//初始化  采集2018年的数据到 F:\Area目录
$Area = new AreaCollect('2018', 'F:\Area2');

//采集41 42 43 省份
$rst = $Area->index([41, 42, 43]);
//采集全国省份
//$rst = $Area->index();

$t2 = microtime(true);

echo "用时：" . round($t2 - $t1, 3) . "秒 共访问：" . $rst['touch'] . "次 其中真实访问:" . $rst['real_touch'] . "次";


