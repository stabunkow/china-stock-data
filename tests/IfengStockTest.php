<?php


namespace Stabunkow\ChinaStockData\Tests;

use GuzzleHttp\Client;
use Mockery\Matcher\AnyArgs;
use PHPUnit\Framework\TestCase;
use Stabunkow\ChinaStockData\Exceptions\HttpException;
use Stabunkow\ChinaStockData\Exceptions\InvalidArgumentException;
use Stabunkow\ChinaStockData\Exceptions\TransformationException;
use Stabunkow\ChinaStockData\Sources\IfengStock;

class IfengStockTest extends TestCase
{
    public function testGetIndex()
    {
        $mock = \Mockery::mock(IfengStock::class);
        $mock->allows()->getIndex()->andReturn([[], [], []]);

        $s = new IfengStock();

        $this->assertSameSize($mock->getIndex(), $s->getIndex());
    }

    public function testGetInfoWithInvalidCode()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid code format: 60002');

        $s = new IfengStock();
        $s->getInfo('60002');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid code format: 400001');

        $s->getInfo('400001');
    }

    public function testGetInfoWithGuzzleRuntimeException()
    {
        $client = \Mockery::mock(Client::class);
        $client->allows()
            ->get(new AnyArgs()) // 由于上面的用例已经验证过参数传递，所以这里就不关心参数了。
            ->andThrow(new \Exception('request timeout')); // 当调用 get 方法时会抛出异常。

        $s = \Mockery::mock(IfengStock::class)->makePartial();
        $s->allows()->getHttpClient()->andReturn($client);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('request timeout');

        $s->getInfo('600027');
    }

    public function testGetInfoWithTransformationException()
    {
        $code = '600024';
        $this->expectException(TransformationException::class);
        $this->expectExceptionMessage("Data transformation failed, stock {$code} may be closed or not exists.");

        $s = new IfengStock();
        $s->getInfo($code);
    }

    public function testGetInfo()
    {
        $s = new IfengStock();
        $this->assertTrue(is_array($s->getInfo('600027')));
    }

    public function testGetInfosWithInvalidCodes()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Codes must be array.');

        $s = new IfengStock();
        $s->getInfos('600023');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Codes size must be at least 1.');
        $s->getInfos([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid code format: 500024');

        $codes = ['600027', '500024'];
        $s->getInfos($codes);
    }

    public function testGetInfos()
    {
        $mock = \Mockery::mock(IfengStock::class);
        $mock->allows()->getIndex()->andReturn([[]]);

        $s = new IfengStock();

        $this->assertSameSize($mock->getIndex(), $s->getInfos(['600027']));
    }

    public function testGetKlineData()
    {
        $s = new IfengStock();
        $this->assertTrue(is_array($s->getKlineData('600027')));
    }

    public function testGetTrendData()
    {
        $s = new IfengStock();
        $this->assertTrue(is_array($s->getTrendData('600027')));
    }

    public function testGetKlineImg()
    {
        $s = new IfengStock();
        $this->assertTrue(is_string($s->getKlineImg('600027')));
    }

    public function testGetTrendImg()
    {
        $s = new IfengStock();
        $this->assertTrue(is_string($s->getTrendImg('600027')));
    }
}