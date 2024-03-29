# 中国行政区数据类

#### 介绍
中国行政区(不含港澳台) 省/市/县区/乡镇/乡的数据采集、采集的数据导入到数据库以及查询接口。


#### 安装教程

1.  git clone 本仓库
2.  cd 到仓库目录
3.  运行 composer install

#### 使用说明

##### 采集地区

- 文件：AreaCollect.php

- 实例化：new AreaCollect(年份, 绝对路径)

- 函数调用，index()，可入参省份ID，不入参即全省采集

- 更新时间：2021年6月1日

- demo：collect.php


##### 导入数据

- 文件：AreaDatabase.php

- 实例化：new AreaDatabase(数据库配置, 年份表, 采集数据存放的地址);

- 函数调用，insertData()

- 更新时间：2021年6月1日

- demo：database.php

##### 查询数据

- 文件：AreaSelect.php
- 实例化：new AreaSelect(数据库配置);
- 函数调用，select()，第一个入参的是地区，此参数必填，第二个是地区代码，不入参即获取该年全国省份数据
- 更新时间：2021年6月1日
- demo：select.php

##### 持久无间断采集

- 文件：AreaSelect.php collect_auto.php
- 实例化：new AreaSelect(数据库配置);
- 函数调用，select()，第一个入参的是地区，此参数必填，第二个是地区代码，不入参即获取该年全国省份数据
- 更新时间：2021年6月8日
- demo：index.php

##### 数据统计

- 2009-2020年，共计12年数据。
- 每年大约有60万的五级地区数据。
- 2020年有31个大省，市级323个，区级2933，乡级37330，村级574246
- 更新时间：2021年6月8日

##### 数据购买
- 任意一年10元，所有年份打包88元。

#### 捐赠

- 微信、支付宝

![微信收款码](https://image.pipihublog.com/20210601105403.png?imageView2/2/w/200/h/200)![支付宝收款码](https://image.pipihublog.com/20210601105404.png?imageView2/2/w/200/h/200)

#### 联系作者

![](https://image.pipihublog.com/20210601105522.jpg?imageView2/2/w/400/h/400)
