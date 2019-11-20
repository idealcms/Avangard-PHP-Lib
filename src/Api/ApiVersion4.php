<?php
/**
 * Copyright Bykovskiy Maxim. Avangard (c) 2019.
 */

namespace Avangard\Api;

use Avangard\Methods;

/**
 * Class ApiVersion4
 *
 * @package Avangard\Api
 */
class ApiVersion4 extends AbstractLoader
{
    /**
     * ApiVersion4 constructor.
     *
     * @param $shop_id
     * @param $shop_password
     * @param $shop_sign
     * @param $server_sign
     * @param $boxType
     * @param $boxAuth
     * @param $proxy
     */
    public function __construct($shop_id, $shop_password, $shop_sign, $server_sign, $boxType, $boxAuth, $proxy)
    {
        parent::__construct($shop_id, $shop_password, $shop_sign, $server_sign, $boxType, $boxAuth, $proxy);
    }

    use Methods\Orders;
    use Methods\Transactions;
    use Methods\Refunds;
    use Methods\Sale;
}