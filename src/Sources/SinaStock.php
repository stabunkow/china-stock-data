<?php

/*
 * This file is part of the stabunkow/china-stock-data.
 *
 * (c) stabunkow<stabunkow@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Stabunkow\ChinaStockData\Sources;

use Stabunkow\ChinaStockData\Exceptions\HttpException;
use Stabunkow\ChinaStockData\Exceptions\InvalidArgumentException;
use Stabunkow\ChinaStockData\Exceptions\TransformationException;

class SinaStock
{
    use Clientable;

    /**
     * 获取股指.
     *
     * @return array
     *
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
     * 获取股票信息.
     *
     * @param $code
     *
     * @return array
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws TransformationException
     */
    public function getInfo($code)
    {
        if (!$this->pregCode($code)) {
            throw new InvalidArgumentException('Invalid code format: '.$code);
        }

        $reqCode = $this->formatReqCode($code);
        $url = "http://hq.sinajs.cn/list=$reqCode,s_$reqCode";

        try {
            $response = $this->getHttpClient()->get($url)->getBody()->getContents();
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            $data = $this->transformResponse($response);

            $raw1 = $this->transformRaw($data[0]);
            $raw2 = $this->transformRaw($data[1]);

            return $this->transformInfo($code, $raw1, $raw2);
        } catch (\Exception $e) {
            throw new TransformationException("Data transformation failed, stock {$code} may be closed or not exists.", $e->getCode(), $e);
        }
    }

    /**
     * 获取批量股票信息.
     *
     * @param $codes
     *
     * @return array
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     * @throws TransformationException
     */
    public function getInfos($codes)
    {
        if (!is_array($codes)) {
            throw new InvalidArgumentException('Codes must be array.');
        }

        if (count($codes) < 1) {
            throw new InvalidArgumentException('Codes size must be at least 1.');
        }

        foreach ($codes as $code) {
            if (!$this->pregCode($code)) {
                throw new InvalidArgumentException('Invalid code format: '.$code);
            }
        }

        $q = '';
        foreach ($codes as $code) {
            $q .= "{$this->formatReqCode($code)},s_{$this->formatReqCode($code)},";
        }
        $url = "http://hq.sinajs.cn/list=$q";

        try {
            $response = $this->getHttpClient()->get($url)->getBody()->getContents();
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            $data = $this->transformResponse($response);

            for ($i = 0; $i < count($data) - 1; $i += 2) {
                $raw1 = $this->transformRaw($data[$i]);
                $raw2 = $this->transformRaw($data[$i + 1]);

                $info = $this->transformInfo($codes[$i / 2], $raw1, $raw2);
                $infos[] = $info;
            }

            return $infos;
        } catch (\Exception $e) {
            throw new TransformationException('Data transformation failed, stock may be closed or not exists.', $e->getCode(), $e);
        }
    }

    /**
     * 获取股票K线图.
     *
     * @param $code
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getKlineImg($code)
    {
        if (!$this->pregCode($code)) {
            throw new InvalidArgumentException('Invalid code format: '.$code);
        }
        $reqCode = $this->formatReqCode($code);

        return "http://image.sinajs.cn/newchart/daily/n/{$reqCode}.gif?".rand(1, 100000000);
    }

    /**
     * 获取股票K线图（周）.
     *
     * @param $code
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getKlineImgWeekly($code)
    {
        if (!$this->pregCode($code)) {
            throw new InvalidArgumentException('Invalid code format: '.$code);
        }
        $reqCode = $this->formatReqCode($code);

        return "http://image.sinajs.cn/newchart/weekly/n/{$reqCode}.gif?".rand(1, 100000000);
    }

    /**
     * 获取股票K线图（月）.
     *
     * @param $code
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getKlineImgMonthly($code)
    {
        if (!$this->pregCode($code)) {
            throw new InvalidArgumentException('Invalid code format: '.$code);
        }
        $reqCode = $this->formatReqCode($code);

        return "http://image.sinajs.cn/newchart/monthly/n/{$reqCode}.gif?".rand(1, 100000000);
    }

    /**
     * 获取股票分时图.
     *
     * @param $code
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getTrendImg($code)
    {
        if (!$this->pregCode($code)) {
            throw new InvalidArgumentException('Invalid code format: '.$code);
        }
        $reqCode = $this->formatReqCode($code);

        return "http://image.sinajs.cn/newchart/min/n/{$reqCode}.gif?".rand(1, 100000000);
    }

    /**
     * 股票代码格式验证
     *
     * @param $code
     *
     * @return false|int
     */
    protected function pregCode($code)
    {
        $pattern = '/^[0|3|6]\d{5}/';

        return preg_match($pattern, $code);
    }

    /**
     * 对每行数据进行转化.
     *
     * @param $raw
     *
     * @return array[]|false|string[]
     */
    protected function transformRaw($raw)
    {
        $raw = explode('"', $raw)[1];

        return preg_split('/,/', $raw);
    }

    /**
     * 翻译获得的股票信息.
     *
     * @param $code
     * @param $raw1
     * @param $raw2
     *
     * @return array
     */
    protected function transformInfo($code, $raw1, $raw2)
    {
        return [
            'code' => (string) $code,
            'name' => $raw1[0],
            'last_px' => $this->formatNumber($raw1[3]),
            'open_px' => $this->formatNumber($raw1[1]),
            'preclose_px' => $this->formatNumber($raw1[2]),
            'high_px' => $this->formatNumber($raw1[4]),
            'low_px' => $this->formatNumber($raw1[5]),
            'volume' => $raw2[4],
            'amount' => $this->formatNumber($raw1[9]),
            'px_change_rt' => $raw2[3],
            'BV' => [
                $raw1[10],
                $raw1[12],
                $raw1[14],
                $raw1[16],
                $raw1[18],
            ],
            'BP' => [
                $this->formatNumber($raw1[11]),
                $this->formatNumber($raw1[13]),
                $this->formatNumber($raw1[15]),
                $this->formatNumber($raw1[17]),
                $this->formatNumber($raw1[19]),
            ],
            'SV' => [
                $raw1[20],
                $raw1[22],
                $raw1[24],
                $raw1[26],
                $raw1[28],
            ],
            'SP' => [
                $this->formatNumber($raw1[21]),
                $this->formatNumber($raw1[23]),
                $this->formatNumber($raw1[25]),
                $this->formatNumber($raw1[27]),
                $this->formatNumber($raw1[29]),
            ],
            'time' => (string) strtotime($raw1[30].' '.$raw1[31]),
        ];
    }

    /**
     * 对获取数据进行初步转化.
     *
     * @param $response
     *
     * @return array
     */
    protected function transformResponse($response)
    {
        $data = iconv('GBK', 'UTF-8', $response);

        return explode(';', $data);
    }

    /**
     * 对获取的数字进行单位转化.
     *
     * @param $number
     *
     * @return string
     */
    protected function formatNumber($number)
    {
        return (string) (floor($number * 100) / 100);
    }

    /**
     * 转换请求股票参数.
     *
     * @param $code
     *
     * @return string
     */
    protected function formatReqCode($code)
    {
        $symbol = (int) substr($code, 0, 1);
        if ($symbol < 6) {
            return 'sz'.$code;
        } else {
            return 'sh'.$code;
        }
    }
}
