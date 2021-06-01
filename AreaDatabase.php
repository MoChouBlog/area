<?php
require 'medoo.php';

class AreaDatabase
{
    public $year;
    public $path;
    public $database;

    public function __construct($option, $year = 2020, $path = __DIR__)
    {
        $this->year = $year;
        $this->path = $path . "/" . $year . '/';

        //检查文件夹是否存在
        if (!file_exists($this->path)) {
            exit("未找到文件夹");
        }

        try {
            $this->database = new medoo($option);
        } catch (\Throwable $throwable) {
            exit("数据库配置错误");
        }

        //如果数据库没年份表就创建
        $query = "CREATE TABLE `$this->year`  (
  `name` varchar(255) NOT NULL,
  `province` char(2)  NOT NULL COMMENT '省级(省、自治区、直辖市)',
  `city` char(2) NOT NULL COMMENT '市级(市、地区、自治州、盟)',
  `county` char(2) NOT NULL COMMENT '县级(自治县、县级市、旗、自治旗、市辖区、林区、特区)',
  `township` char(3) NOT NULL COMMENT '乡级(乡、镇、街道、类似乡级单位)',
  `village` char(3) NOT NULL COMMENT '村级(村民居委会、居民居委会、类似村民居委会、类似居民居委会)',
  `type` char(3) NOT NULL COMMENT '111->主城区 112->城乡结合区 121->镇中心区 122->镇乡结合区 123->特殊区域 210->乡中心区 220->村庄',
  PRIMARY KEY (`province`, `city`, `county`, `township`, `village`)
);";
        $this->database->query($query);

    }

    /**
     * 插入某省份的数据
     * author :Mochou
     * time :2021-5-12 16:34
     * @return \think\response\Json
     */
    public function insertData()
    {
        $data = [];
        $path = $this->path;
        //省份列表
        $province_list = json_decode(file_get_contents($path . 'province.json'), true);

        foreach ($province_list as $key => $value) {
            //插入数据库
            $data = [
                'name' => $value,
                'province' => $key,
                'city' => '00',
                'county' => '00',
                'township' => '000',
                'village' => '000',
                'type' => 0
            ];
            $this->database->insert($this->year, $data);

            $this->getnextinfo([$key]);
        }

    }

    /**
     * 根据position数据获取下面的数据
     * author :Mochou
     * time :2021-4-25 14:24
     * @param $position
     */
    protected function getnextinfo($position)
    {
        //省市区2位 街道居委会3位
        $map = [
            1 => [2, 2],
            2 => [4, 2],
            3 => [6, 3],
            4 => [9, 3]
        ];
        $count = count($position);
        $path = $this->path . $position[0];
        $file = $path . '\\' . implode($position) . '.json';

        if (is_file($file)) {
            $rst = [];//返回数据
            //当前地区的子分部
            $list = json_decode(file_get_contents($file), true);
            //循环递归
            foreach ($list as $value) {
                $str = substr($value['code'], $map[$count][0], $map[$count][1]);
                //当子也是00或者000的时候 再运行一次
                if ($str == '00' || $str == '000') {
                    $position[] = $str;
                    $count = count($position);
                    $str = substr($value['code'], $map[$count][0], $map[$count][1]);
                }
                $position_new = $position;
                $position_new[] = $str;//压入

                $codeArr = $position_new;//五级 没够就补0
                $codeArr_count = count($codeArr);
                if ($codeArr_count == 2) {
                    $codeArr[] = '00';
                    $codeArr[] = '000';
                    $codeArr[] = '000';
                } else if ($codeArr_count == 3) {
                    $codeArr[] = '000';
                    $codeArr[] = '000';
                } else if ($codeArr_count == 4) {
                    $codeArr[] = '000';
                }

                $data = [
                    'name' => $value['name'],
                    'province' => $codeArr[0],
                    'city' => $codeArr[1],
                    'county' => $codeArr[2],
                    'township' => $codeArr[3],
                    'village' => $codeArr[4],
                    'type' => isset($value['type']) ? $value['type'] : 0
                ];

                $rst[] = $data;

                $this->getnextinfo($position_new);
            }

            if (!empty($rst))
                $this->database->insert($this->year, $rst);

            return $rst;
        }

    }

    public function Area()
    {
        $year = (int)$_POST["year"];
        $position = $_POST["position"];

        if (!in_array($year, [2019, 2020])) {
            return $this->SendJSON(0, "年份错误");
        }

        if (!is_numeric($position) && !empty($position)) {
            return $this->SendJSON(0, "position错误");
        }

        $str_len = strlen($position);
        if ($str_len == 0) {
            $where = [
                ['city', '=', '00'],
                ['county', '=', '00'],
                ['township', '=', '000'],
            ];
        } elseif ($str_len == 2) {
            //查省下面的市
            $where = [
                ['province', '=', $position],
                ['city', '<>', '00'],
                ['county', '=', '00'],
                ['township', '=', '000'],
            ];
        } elseif ($str_len == 4) {
            //查省 市下面的区
            $where = [
                ['province', '=', substr($position, 0, 2)],
                ['city', '=', substr($position, 2, 2)],
                ['county', '<>', '00'],
                ['township', '=', '000'],
            ];

            //广东东莞有毒
            if (substr($position, 0, 2) == 44 && substr($position, 2, 2) == 19) {
                $where = [
                    ['province', '=', substr($position, 0, 2)],
                    ['city', '=', substr($position, 2, 2)],
                    ['county', '=', '00'],
                    ['township', '<>', '000'],
                    ['village', '=', '000']
                ];
            }
        } elseif ($str_len == 6) {
            //查省 市 县 下面的乡
            $where = [
                ['province', '=', substr($position, 0, 2)],
                ['city', '=', substr($position, 2, 2)],
                ['county', '=', substr($position, 4, 2)],
                ['township', '<>', '000'],
                ['village', '=', '000']
            ];
        } elseif ($str_len == 9) {
            //查省 市 县 乡 下面的村
            $where = [
                ['province', '=', substr($position, 0, 2)],
                ['city', '=', substr($position, 2, 2)],
                ['county', '=', substr($position, 4, 2)],
                ['township', '=', substr($position, 6, 3)],
                ['village', '<>', '000']
            ];
        } elseif ($str_len == 12) {
            //查省 市 县 乡 村 的type代码
            $where = [
                ['province', '=', substr($position, 0, 2)],
                ['city', '=', substr($position, 2, 2)],
                ['county', '=', substr($position, 4, 2)],
                ['township', '=', substr($position, 6, 3)],
                ['village', '=', substr($position, 9, 3)]
            ];
            $query = $this->database->select("$year")->where($where)->select();

            return $this->SendJSON(1, "查询成功", $query);
        } else {
            return $this->SendJSON(0, "position错误");
        }

        $query = Db::connect('Area')->table("$year")->where($where)->select();

        return $this->SendJSON(1, "查询成功", $query);
    }


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

