<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\Lib;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class Logger
 * @package Avangard\lib
 */
Class Logger
{
    /**
     * Send log into Telegram
     *
     * @param \Exception $e
     */
    public static function log(\Exception $e)
    {
        $proxy_ip = '5.183.130.219';
        $proxy_port = 7400;
        $proxy_user = 'e7lVRI';
        $proxy_pass = 'MgWFFd1tnn';
        $proxy = "http://$proxy_user:$proxy_pass@$proxy_ip:$proxy_port";
//        $proxy = 'http://iwsva.avangard.ru:8080';

        $text = "New exception on server " . (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "localhost") . "\n" . print_r([$e->getFile(), 'error' => [$e->getCode(), $e->getMessage()]], true);

        $client = new Client(['proxy' => $proxy]);

        $url = 'https://api.telegram.org/bot917822968:AAE-f2kZxJ0Ua0As0TJEP4pi8Np7Xo2bMGs/sendMessage';

        try {
            $result = $client->request('POST', $url, ['body' => json_encode(["chat_id" => "-1001442293451", "text" => $text]), 'headers' => ['Content-Type' => 'application/json;charset=utf-8']]);
        } catch (GuzzleException $ex) {
//            print_r($e->getMessage());
        }
    }
}