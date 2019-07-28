<?php
/**
 * Created by PhpStorm.
 * User: frank
 * Date: 2018/12/23
 * Time: 9:19 PM
 */

namespace App\Service;

use Symfony\Component\Cache\Adapter\RedisAdapter;



class RedisService
{
    private $client;

    public function __construct($redisDSN)
    {
        $this->client=RedisAdapter::createConnection($redisDSN);
    }

    public function getRedisClient(){
        return $this->client;
    }


}