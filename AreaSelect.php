<?php
require 'medoo.php';

class AreaSelect
{
    public $year;
    public $database;

    /**
     * 初始化数据库配置
     * AreaSelect constructor.
     * @param $option
     * @param int $year
     */
    public function __construct($option)
    {
        try {
            $this->database = new medoo($option);
        } catch (\Throwable $throwable) {
            exit("数据库配置错误");
        }
    }

    /**
     * 查询地区
     * author :Mochou
     * time :2021-5-31 16:16
     * @param $year
     * @param $position
     * @return false|string
     */
    public function select($year, $position = Null)
    {
        if (!is_numeric($position) && !empty($position)) {
            return $this->SendJSON(0, "position错误");
        }

        $str_len = strlen($position);
        if ($str_len == 0) {
            $where = [
                'city' => '00',
                'county' => '00',
                'township' => '000',
            ];
        } elseif ($str_len == 2) {
            //查省下面的市
            $where = [
                'province' => $position,
                'city[!]' => '00',
                'county' => '00',
                'township' => '000',
            ];
        } elseif ($str_len == 4) {
            //查省 市下面的区
            $where = [
                'province' => substr($position, 0, 2),
                'city' => substr($position, 2, 2),
                'county[!]' => '00',
                'township' => '000'
            ];

            //广东东莞有毒
            if (substr($position, 0, 2) == 44 && substr($position, 2, 2) == 19) {
                $where = [
                    'province' => substr($position, 0, 2),
                    'city' => substr($position, 2, 2),
                    'county' => '00',
                    'township[!]' => '000',
                    'village' => '000'
                ];
            }
        } elseif ($str_len == 6) {
            //查省 市 县 下面的乡
            $where = [
                'province' => substr($position, 0, 2),
                'city' => substr($position, 2, 2),
                'county' => substr($position, 4, 2),
                'township[!]' => '000',
                'village' => '000'
            ];
        } elseif ($str_len == 9) {
            //查省 市 县 乡 下面的村
            $where = [
                'province' => substr($position, 0, 2),
                'city' => substr($position, 2, 2),
                'county' => substr($position, 4, 2),
                'township' => substr($position, 6, 3),
                'village[!]' => '000'
            ];
        } elseif ($str_len == 12) {
            //查省 市 县 乡 村 的type代码
            $where = [
                'province' => substr($position, 0, 2),
                'city' => substr($position, 2, 2),
                'county' => substr($position, 4, 2),
                'township' => substr($position, 6, 3),
                'village' => substr($position, 9, 3)
            ];

        } else {
            return $this->SendJSON(0, "position错误");
        }

        $query = $this->database->select("$year", '*', ['AND' => $where]);

        return $this->SendJSON(1, "查询成功", $query);
    }

    /**
     * 返回JSON格式的数据
     * author :Mochou
     * time :2021-5-31 16:16
     * @param int $code
     * @param $msg
     * @param array $data
     * @return false|string
     */
    protected function SendJSON(int $code = 0, $msg, $data = [])
    {
        $result = [
            'code' => $code,
            'msg' => $msg,
            'time' => time(),
            'data' => $data,
        ];
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}

