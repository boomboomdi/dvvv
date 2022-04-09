<?php
namespace app\api\validate;

use think\Validate;

class OrderinfoValidate extends Validate
{
//    {
//        "merchant_sign":"cest",
//        "order_no":"cest",
//        "order_pay":"cest",
//        "payment":"cest",
//        "amount":"cest",
//        "actual_amount":"cest",
//        "pay_time":"cest",
//        "sign":"cest"
//    }
    protected $rule =   [
        'merchant_sign'  => 'require|max:32',
//        'client_ip'  => 'require|ip',
        'order_no'   => 'require|length:32',
//        'order_pay' => 'require|length:32',
        'payment' => 'require',
        'amount' => 'require|float',
//        'actual_amount' => 'require|float',
        'time' => 'require',
        'sign' => 'require|length:32',
    ];

    protected $message  =   [
        'merchant_sign.require' => 'require merchant_sign',
        'merchant_sign.max' => 'merchant_sign format error',
        'order_no.require'     => 'require order_no',
        'order_no.length'     => 'order_no format error',
        'payment.require'   => 'require number',
        'amount.require'   => 'require amount',
        'amount.float'   => 'amount format float',
        'time.require'   => 'require pay_time',
        'time.integer'   => 'pay_time format error',
        'sign.require' => 'require sign',
        'sign.length' => 'sign format error',
    ];
}