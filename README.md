<h1 align="center"> china-stock-data </h1>

[![Build Status](https://travis-ci.org/stabunkow/china-stock-data.svg?branch=master)](https://travis-ci.org/stabunkow/china-stock-data)
![StyleCI build status](https://github.styleci.io/repos/192670611/shield) 


<p align="center"> 提供国内财经门户网站股票相关数据，目前有新浪和凤凰网的数据</p>


## 安装

```shell
$ composer require stabunkow/china-stock-data -vvv
```

## 使用

```php
use Stabunkow\ChinaStockData\Sources\SinaStock;

$s = new SinaStock(); // 使用新浪股票数据源
$s->getIndex(); // 获取股指
$s->getInfo('600027'); // 获取股票信息
$s->getInfos(['600027']); // 获取批量股票信息
$s->getKlineImg('600027'); // 获取股票K线图
$s->getKlineImgWeekly('600027'); // 获取股票K线图（周）
$s->getKlineImgMonthly('600027'); // 获取股票K线图（月）
$s->getTrendImg('600027'); // 获取股票分时图

use Stabunkow\ChinaStockData\Sources\IfengStock;

$s = new IfengStock(); // 使用凤凰网股票数据源
$s->getIndex(); // 获取股指
$s->getInfo('600027'); // 获取股票信息
$s->getInfos(['600027']); // 获取批量股票信息
$s->getKlineData('600027'); // 获取股票K线数据
$s->getTrendData('600027'); // 获取股票分时数据
$s->getKlineImg('600027'); // 获取股票K线图
$s->getTrendImg('600027'); // 获取股票分时图
```

## 注意事项

code 格式为股票数字代码，若股票退市或不存在将获取不到股票信息

不同门户网的可获得的股票信息内容有所不同

### 获取股票信息示例

```php
$s = new SinaStock();
$data = $s->getInfo('600027'); // 获取股票信息
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
```

显示

```json
{                              
    "code": "600027",          
    "name": "华电国际",            
    "last_px": "3.79",         
    "open_px": "3.82",         
    "preclose_px": "3.78",     
    "high_px": "3.83",         
    "low_px": "3.78",          
    "volume": "147511",        
    "amount": "56203547",      
    "px_change_rt": "0.26",    
    "BV": [                    
        "869200",              
        "1319700",             
        "534600",              
        "297800",              
        "513900"               
    ],                         
    "BP": [                    
        "3.79",                
        "3.78",                
        "3.77",                
        "3.76",                
        "3.75"                 
    ],                         
    "SV": [                    
        "1487400",             
        "1242800",             
        "853600",              
        "873100",              
        "664100"               
    ],                         
    "SP": [                    
        "3.8",                 
        "3.81",                
        "3.82",                
        "3.83",                
        "3.84"                 
    ],                         
    "time": "1560943800"       
}                                      
```

## 参考


[国内股票接口](https://houjianfang.com/2018/12/05/%E8%85%BE%E8%AE%AF%E8%82%A1%E7%A5%A8%E6%8E%A5%E5%8F%A3%E3%80%81%E5%92%8C%E8%AE%AF%E7%BD%91%E8%82%A1%E7%A5%A8%E6%8E%A5%E5%8F%A3%E3%80%81%E6%96%B0%E6%B5%AA%E8%82%A1%E7%A5%A8%E6%8E%A5%E5%8F%A3%E3%80%81/
)

[PHP 扩展包实战教程 - 从入门到发布](https://learnku.com/courses/creating-package/)

## License

MIT