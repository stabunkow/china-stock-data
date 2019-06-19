<?php


namespace Stabunkow\ChinaStockData\Sources;

use GuzzleHttp\Client;

Trait Clientable
{
    protected $guzzleOptions = [];

    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }
}