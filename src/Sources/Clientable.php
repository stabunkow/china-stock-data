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

use GuzzleHttp\Client;

trait Clientable
{
    protected $guzzleOptions = [];

    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }
}
