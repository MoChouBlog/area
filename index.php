<?php
$t1 = microtime(true);
ini_set('memory_limit', '20480M');
ini_set('max_execution_time', 0); //不限时

while (1) {
    $out = [];
    exec("php F:\Project\/area/collect.php", $out);

    var_dump($out);

    if ($out[0] != null) {
        $arr = json_decode($out[count($out) - 1], true);
        if ($arr['status']) {
            break;
        }
    }

    sleep(6);
}


$t2 = microtime(true);

echo "用时：" . round($t2 - $t1, 3) . "秒";
