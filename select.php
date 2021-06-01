<?php
require 'AreaSelect.php';
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

$AreaSelect = new AreaSelect($option);

//查询2020年全国的省份数据
echo $AreaSelect->select('2020');
//查询2020年四川省的市数据
echo $AreaSelect->select('2020', '51');
//查询2020年四川省成都市的区数据
echo $AreaSelect->select('2020', '5101');
//查询2020年四川省成都市锦江区下面第四级数据
echo $AreaSelect->select('2020', '510104');
//查询2020年四川省成都市锦江区春熙路街道下面第五级数据
echo $AreaSelect->select('2020', '510104022');
