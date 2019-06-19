<?php


namespace Stabunkow\ChinaStockData\Sources;

use Stabunkow\ChinaStockData\Exceptions\HttpException;
use Stabunkow\ChinaStockData\Exceptions\InvalidArgumentException;
use Stabunkow\ChinaStockData\Exceptions\TransformationException;

class IfengStock
{
    use Clientable;

    /**
     * 获取股指
     * @return array
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws TransformationException
     */
    public function getIndex()
    {
        $codes = ['000001', '399001', '399006'];

        return $this->getInfos($codes);
    }

    /**
     * 获取股票信息
     * @param $code
     * @return array
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws TransformationException
     */
    public function getInfo($code)
    {
        if (! $this->pregCode($code)) {
            throw new InvalidArgumentException('Invalid code format: ' . $code);
        }

        $reqCode = $this->formatCode($code);
        $url = "http://hq.finance.ifeng.com/q.php?l={$reqCode}";

        try {
            $response = $this->getHttpClient()->get($url)->getBody()->getContents();
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            $data = $this->transformResponse($response);
            $raw = $data[0];
            return $this->transformInfo($code, $raw);
        } catch (\Exception $e) {
            throw new TransformationException("Data transformation failed, stock {$code} may be closed or not exists.", $e->getCode(), $e);
        }
    }

    /**
     * 获取批量股票信息
     * @param $codes
     * @return array
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws TransformationException
     */
    public function getInfos($codes)
    {
        if (! is_array($codes)) {
            throw new InvalidArgumentException('Codes must be array.');
        }

        if (sizeof($codes) < 1) {
            throw new InvalidArgumentException('Codes size must be at least 1.');
        }

        foreach ($codes as $code) {
            if (! $this->pregCode($code)) {
                throw new InvalidArgumentException('Invalid code format: ' . $code);
            }
        }

        $q = '';
        foreach ($codes as $code) {
            $q .=  "{$this->formatCode($code)},";
        }

        $url = "http://hq.finance.ifeng.com/q.php?l=$q";
        try {
            $response = $this->getHttpClient()->get($url)->getBody()->getContents();
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            $data = $this->transformResponse($response);

            $infos = [];
            for ($i = 0; $i < count($data); $i++) {
                $infos[] = $this->transformInfo($code, $data[$i]);
            }

            return $infos;
        } catch (\Exception $e) {
            throw new TransformationException('Data transformation failed, stock may be closed or not exists.', $e->getCode(), $e);
        }
    }

    /**
     * 获取股票K线数据
     * @param $code
     * @return array
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws TransformationException
     */
    public function getKlineData($code)
    {
        if (! $this->pregCode($code)) {
            throw new InvalidArgumentException('Invalid code format: ' . $code);
        }

        $url = 'http://api.finance.ifeng.com/akdaily/?type=last&code=' . $this->formatCode($code);

        try {
            $response = $this->getHttpClient()->get($url)->getBody()->getContents();
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            $data = $this->transformRecordResponse($response);
            return array_map(function($raw) {
                return $this->transformRecordInfo($raw);
            }, $data);
        } catch (\Exception $e) {
            throw new TransformationException("Data transformation failed, stock {$code} may be closed or not exists.", $e->getCode(), $e);
        }
    }

    /**
     * 获取股票分时数据
     * @param $code
     * @return array
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws TransformationException
     */
    public function getTrendData($code)
    {
        if (! $this->pregCode($code)) {
            throw new InvalidArgumentException('Invalid code format: ' . $code);
        }

        $url = 'http://api.finance.ifeng.com/akmin?type=5&scode=' . $this->formatCode($code);

        try {
            $response = $this->getHttpClient()->get($url)->getBody()->getContents();
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            $data = $this->transformRecordResponse($response);

            return array_map(function($raw) {
                return $this->transformRecordInfo($raw);
            }, $data);
        } catch (\Exception $e) {
            throw new TransformationException("Data transformation failed, stock {$code} may be closed or not exists.", $e->getCode(), $e);
        }
    }

    /**
     * 获取股票K线图
     * @param $code
     * @return string
     */
    public function getKlineImg($code)
    {
        if (! $this->pregCode($code)) {
            throw new InvalidArgumentException('Invalid code format: ' . $code);
        }
        return "http://img.finance.ifeng.com/chart/kline/{$this->formatCode($code)}.gif?" . rand(1, 100000000);
    }

    /**
     * 获取股票分时图
     * @param $code
     * @return string
     * @throws InvalidArgumentException
     */
    public function getTrendImg($code)
    {
        if (! $this->pregCode($code)) {
            throw new InvalidArgumentException('Invalid code format: ' . $code);
        }
        return "http://img.finance.ifeng.com/chart/min/{$this->formatCode($code)}.gif?" . rand(1, 100000000);
    }

    /**
     * 股票代码格式验证
     * @param $code
     * @return false|int
     */
    protected function pregCode($code)
    {
        $pattern = '/^[0|3|6]\d{5}/';
        return preg_match($pattern, $code);
    }

    /**
     * 翻译获得的股票信息
     * @param $code
     * @param $raw
     * @return array
     */
    protected function transformInfo($code, $raw)
    {
        return [
            'code' => (string) $code,
            'last_px' => (string) $raw[0],
            'open_px' => (string) $raw[4],
            'preclose_px' => (string) $raw[1],
            'high_px' => (string) $raw[5],
            'low_px' => (string) $raw[6],
            'volume' => (string) $raw[9],
            'amount' => (string) $raw[10],
            'px_change' => (string) $raw[2],
            'px_change_rt' => (string) $raw[3],
            'BP' => [
                (string) $raw[11],
                (string) $raw[12],
                (string) $raw[13],
                (string) $raw[14],
                (string) $raw[15],
            ],
            'BV' => [
                (string) $raw[16],
                (string) $raw[17],
                (string) $raw[18],
                (string) $raw[19],
                (string) $raw[20],
            ],
            'SP' => [
                (string) $raw[21],
                (string) $raw[22],
                (string) $raw[23],
                (string) $raw[24],
                (string) $raw[25],
            ],
            'SV' => [
                (string) $raw[26],
                (string) $raw[27],
                (string) $raw[28],
                (string) $raw[29],
                (string) $raw[30],
            ],
            'time' => (string) $raw[35],
        ];
    }

    /**
     * 对获取数据进行初步转化
     * @param $response
     * @return array
     */
    protected function transformResponse($response)
    {
        $data = substr($response, 11, -3);
        return array_values(json_decode($data, true));
    }

    /**
     * 对股票图表数据进行转换
     * @param $response
     * @return mixed
     */
    protected function transformRecordResponse($response)
    {
        return json_decode($response, true)['record'];
    }

    /**
     * 翻译股票图表数据
     * @param $raw
     * @return array
     */
    protected function transformRecordInfo($raw)
    {
        return [
            'date' => date('Ymd', strtotime($raw[0])),
            'open_px' => $raw[1],
            'close_px' => $raw[3],
            'high_px' => $raw[2],
            'low_px' => $raw[4],
            'volume' => $raw[5]
        ];
    }

    /**
     * 转换请求股票参数
     * @param $code
     * @return string
     */
    protected function formatCode($code)
    {
        $symbol = (int) substr($code, 0, 1);
        if ($symbol < 6) {
            return 'sz'.$code;
        } else {
            return 'sh'.$code;
        }
    }
}