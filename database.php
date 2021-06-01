<?php
require 'AreaDatabase.php';
set_time_limit(0);

$option = [
    // 必须配置项
    'database_type' => 'mysql',
    'database_name' => 'area',
    'server' => '127.0.0.1',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    // 可选参数
    'port' => 3306,
];
$AreaDatabase = new AreaDatabase($option, '2020', 'F:\Area');


$t1 = microtime(true);
$AreaDatabase->insertData();
$t2 = microtime(true);

echo "用时：" . round($t2 - $t1, 3) . "秒";



