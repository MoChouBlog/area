<?php
require "./AreaCollect.php";
ini_set('memory_limit', '20480M');
ini_set('max_execution_time', 0); //不限时

$t1 = microtime(true);

//初始化  采集2018年的数据到 F:\Area目录
$Area = new AreaCollect('2013', 'F:\Area');

//读取json格式的文件
$province_list = json_decode(file_get_contents($Area->outBase . 'province.json'), true);

$file = scandir($Area->outBase);
foreach ($file as $key => $value) {
    if (is_numeric($value) && is_numeric($file[$key + 1])) {
        unset($province_list[$value]);
    }
}

$list = [];
foreach ($province_list as $key => $value) {
    $list[] = $key;
}


$rst = $Area->index($list);

echo json_encode(['status' => true, 'msg' => '采集完成'], JSON_UNESCAPED_UNICODE);

