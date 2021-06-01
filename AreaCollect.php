<?php
//引入自动加载文件
require 'vendor/autoload.php';

use QL\QueryList;
use GuzzleHttp\Client;

class AreaCollect
{
    //文件目录
    public $outBase;
    //年份
    public $year;
    //省份ID
    public $provinceId;
    //总访问次数
    public $touch;
    //真实访问次数
    public $real_touch;
    //USER_AGENT 列表
    public $user_agent_list;
    //当前使用的USER_AGENT
    public $user_agent;
    //休眠列表
    public $requestSleep;

    /**
     * AreaCollect constructor.
     * @param int $year
     * @param string $path
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function __construct($year = 2020, $path = __DIR__)
    {
        $this->year = $year;
        $this->outBase = $path . "/" . $year . '/';

        //检查文件夹是否存在
        if (!file_exists($path)) {
            mkdir($path);
        }

        //检查年份采集文件夹是否存在
        if (!file_exists($this->outBase)) {
            $this->init_year_path();
        }

        /*初始化数据*/
        //user_agent列表
        $this->user_agent_list = [
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.163 Safari/535.1',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4385.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.79 Safari/537.36',
            'Baiduspider+(+http://www.baidu.com/search/spider.htm”)',
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Mozilla/5.0 (compatible; Yahoo! Slurp China; http://misc.yahoo.com.cn/help.html”)',
            'iaskspider/2.0(+http://iask.com/help/help_index.html”)',
            'Mozilla/5.0 (compatible; iaskspider/1.0; MSIE 6.0)',
            'Sogou web spider/3.0(+http://www.sogou.com/docs/help/webmasters.htm#07″)',
            'Mozilla/5.0 (compatible; YodaoBot/1.0; http://www.yodao.com/help/webmaster/spider/”; )',
            'msnbot/1.0 (+http://search.msn.com/msnbot.htm”)',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36 Edg/87.0.664.75'
        ];
        //采集休眠时间列表
        $this->requestSleep = [0, 50000, 0, 100000, 0, 150000, 0, 200000, 0, 250000, 0, 300000, 0];

        //采集地址次数
        $this->touch = 0;
        //真实访问次数
        $this->real_touch = 0;
        //随机一个user_agent
        $this->user_agent = array_rand($this->user_agent_list);
    }

    /**
     * 入口文件
     * author :Mochou
     * time :2021-5-28 16:32
     * @return array
     */
    public function index($list = NULL)
    {
        $province_list = json_decode(file_get_contents($this->outBase . 'province.json'), true);

        foreach ($province_list as $id => $province) {
            if (isset($list)) {
                if (in_array($id, $list)) {
                    $this->get_all_data($id . '.html');
                }
            } else {
                $this->get_all_data($id . '.html');
            }
        }

        return ['touch' => $this->touch, 'real_touch' => $this->real_touch];
    }

    /**
     * 获取某链接下的数据
     * author :Mochou
     * time :2021-5-28 16:33
     * @param $year
     * @param $url
     * @return array
     */
    protected function get_all_data($url)
    {
        $data = [];
        //解析url
        $str = substr($url, 0, strlen($url) - 5);
        $arr = explode("/", $str);
        if (count($arr) == 2) {
            $str = $arr[1];
        }

        $this->provinceId = substr($str, 0, 2);

        $count = strlen($str);

        if ($count == 2) {
            //那就是省 去扒这个省下面所有的市数据
            $data = $this->city($url);
        } else if ($count == 4) {
            //那就是市 去扒这个市下面所有的县数据
            $data = $this->county($url);
        } else if ($count == 6) {
            //那就是县 这时候要包含省的路径
            $data = $this->township(substr($str, 0, 2) . '/' . $url);
        } else if ($count == 9) {
            //那就是街道 这时候要包含省 县的路径
            //BUG 广东东莞是没有区的 直接到街道了 所以区那边是00 链接没
            if (substr($str, 4, 2) == '00') {
                $data = $this->village(substr($str, 0, 2) . '/' . substr($str, 2, 2) . '/' . $str . '.html');
            } else {
                $data = $this->village(substr($str, 0, 2) . '/' . substr($str, 2, 2) . '/' . $url);
            }

        }

        //封装数据
        $datas = [];
        foreach ($data as $value) {
            unset($value['href']);
            $datas[] = $value;
        }

        //写入文件
        $this->write($this->outBase . substr($str, 0, 2) . '/', $str, $datas);

        foreach ($data as $value) {
            if (!empty($value['href']))
                $this->get_all_data($value['href']);
        }

    }

    /**
     * 一级数据 省份
     * author :Mochou
     * time :2021-5-28 16:29
     * @return Array
     */
    protected function province()
    {
        $html = $this->curl_info('index.html');
        // 切片选择器
        $range = '.provincetable>.provincetr>td';
        // 采集规则
        $rules = [
            // 标题
            'name' => ['a', 'text'],
            // 链接地址
            'link' => ['a', 'href'],
        ];

        $data = QueryList::html($html)->rules($rules)->query()->range($range)->queryData();//多个数组

        //处理数据
        foreach ($data as $key => $v) {
            $id = explode('.', $v['link'])[0];
            if (empty($id)) {
                unset($data[$key]);
            } else {
                $data[$key]['id'] = $id;
            }
        }

        return $data;
    }

    /**
     * 二级数据 城市
     * author :Mochou
     * time :2021-5-28 16:29
     * @param $year
     * @param $province
     * @return Array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function city($url)
    {
        $html = $this->curl_info($url);

        // 切片选择器
        $range = '.citytable>.citytr';
        // 采集规则
        $rules = [
            // 代码
            'code' => ['td:eq(0)', 'text'],
            // 链接地址
            'href' => ['td a', 'href'],
            // 名称
            'name' => ['td:eq(1)', 'text']
        ];

        $data = QueryList::html($html)->rules($rules)->query()->range($range)->queryData();//多个数组

        return $data;
    }

    /**
     * 三级数据 县
     * author :Mochou
     * time :2021-5-28 16:29
     * @param $year
     * @param $url
     * @return Array
     */
    protected function county($url)
    {
        $html = $this->curl_info($url);

        // 切片选择器
        $range = '.countytable>.countytr';
        // 采集规则
        $rules = [
            // 代码
            'code' => ['td:eq(0)', 'text'],
            // 链接地址
            'href' => ['td a', 'href'],
            // 名称
            'name' => ['td:eq(1)', 'text']
        ];

        $data = QueryList::html($html)->rules($rules)->query()->range($range)->queryData();//多个数组

        //跨级了
        if (empty($data)) {
            // 切片选择器
            $range = '.towntable>.towntr';
            // 采集规则
            $rules = [
                // 代码
                'code' => ['td:eq(0)', 'text'],
                // 链接地址
                'href' => ['td a', 'href'],
                // 名称
                'name' => ['td:eq(1)', 'text']
            ];

            $data = QueryList::html($html)->rules($rules)->query()->range($range)->queryData();//多个数组
        }

        return $data;
    }


    /**
     * 四级数据 街道
     * author :Mochou
     * time :2021-5-28 16:29
     * @param $year
     * @param $url
     */
    protected function township($url)
    {
        $html = $this->curl_info($url);

        // 切片选择器
        $range = '.towntable>.towntr';
        // 采集规则
        $rules = [
            // 代码
            'code' => ['td:eq(0)', 'text'],
            // 链接地址
            'href' => ['td a', 'href'],
            // 名称
            'name' => ['td:eq(1)', 'text']
        ];

        $data = QueryList::html($html)->rules($rules)->query()->range($range)->queryData();//多个数组

        return $data;
    }

    /**
     * 五级数据 居委会
     * author :Mochou
     * time :2021-5-28 16:29
     * @param $year
     * @param $url
     */
    protected function village($url)
    {
        $html = $this->curl_info($url);

        // 切片选择器
        $range = '.villagetable>.villagetr';
        // 采集规则
        $rules = [
            // 代码
            'code' => ['td:eq(0)', 'text'],
            // 链接地址
            'type' => ['td:eq(1)', 'text'],
            // 名称
            'name' => ['td:eq(2)', 'text']
        ];

        $data = QueryList::html($html)->rules($rules)->query()->range($range)->queryData();//多个数组

        return $data;
    }

    /**
     * 文件写入
     * author :Mochou
     * time :2021-5-28 16:29
     * @param $path
     * @param $name
     * @param $data
     */
    protected function write($path, $name, $data)
    {
        if (!file_exists($path)) {//检查文件夹是否存在
            mkdir($path);    //没有就创建一个新文件夹
        }

        if (is_file($path . $name . '.json')) {
            return true;
        }

        $handle = fopen($path . $name . '.json', 'w+');
        fwrite($handle, json_encode($data, JSON_UNESCAPED_UNICODE));
        fclose($handle);

        return true;
    }

    /** 请求数据
     * author :Mochou
     * time :2021-5-28 16:29
     * @param $url
     * @return false|string
     */
    protected function curl_info($url)
    {
        echo "当前访问:" . $url . "\n";
        //缓存
        $path = $this->outBase . 'cache/' . $this->provinceId . '/';
        $filename = str_replace("/", "-", $url);
        $this->touch++;
        if (is_file($path . $filename)) {
            return file_get_contents($path . $filename);
        }
        //好家伙 这边请求数据了
        if ($this->real_touch % 10 == 0) {
            usleep(array_rand($this->requestSleep));
            $this->user_agent = array_rand($this->user_agent_list);
        }
        $this->real_touch++;

        try {
            $url = 'http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/' . $this->year . '/' . $url;
            $client = new Client();
            $res = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => $this->user_agent,
                    'Accept-Encoding' => 'gzip, deflate, br',
                ]
            ]);
        } catch (\Throwable $throwable) {
            exit(json_encode(['message' => '被ban了，请稍后重试', 'status' => false], JSON_UNESCAPED_UNICODE));
        }

        $html = (string)$res->getBody();

        $html = iconv("gb2312", "utf-8//IGNORE", $html);

        //写入缓存
        if (!file_exists($path)) {//检查文件夹是否存在
            mkdir($path);    //没有就创建一个新文件夹
        }

        $handle = fopen($path . $filename, 'w+');
        fwrite($handle, $html);
        fclose($handle);

        return $html;
    }

    /**
     * 初始化某年的目录文件夹，以及省份数据
     * author :Mochou
     * time :2021-5-28 10:20
     * @param $year
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function init_year_path()
    {
        mkdir($this->outBase);    //创建年份文件夹
        mkdir($this->outBase . 'cache');    //创建缓存文件夹

        $provinces = $this->province();

        //封装全国省份数据
        $datas = [];
        foreach ($provinces as $value) {
            $datas[$value['id']] = $value['name'];
        }

        //写入文件
        $this->write($this->outBase, 'province', $datas);
    }
}

