<?php

namespace Moloni\Api;

use Moloni\Curl;

class PaymentMethods
{
    public static function searchByName($name = '')
    {
        $paymentMethods = Curl::simple("paymentMethods/getAll");

        if (!empty($paymentMethods) && is_array($paymentMethods)) {
            foreach ($paymentMethods as $paymentMethod) {
                if ($paymentMethod['name'] === $name) {
                    return (int)$paymentMethod['payment_method_id'];
                }
            }
        }

        return 0;
    }

    public static function insert($name = '')
    {
        $props = [
            'name' => $name
        ];

        $insert = Curl::simple("paymentMethods/insert", $props);

        if (isset($insert['payment_method_id'])) {
            return (int)$insert['payment_method_id'];
        }

        return 0;
    }
}
