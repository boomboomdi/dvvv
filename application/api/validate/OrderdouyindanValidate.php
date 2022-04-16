<?php

namespace app\api\validate;

use think\Validate;

class OrderdouyindanValidate extends Validate
{

    protected $rule = [
        'account' => 'require',
        'order_no' => 'require',
        'ali_url' => 'require',
        'order_url' => 'require',
        'order_id' => 'require',
    ];

    protected $message = [
        'account.require' => 'require write_off_sign',
        'order_no.require' => 'require order_no',
        'ali_url.require' => 'require ali_url',
        'order_url.require' => 'require order_url',
        'order_id.require' => 'require order_id',
    ];
//    protected $scene = [
//        'ping' => ['account', 'studio', 'device_desc', 'status', 'time'],
//        'order_info' => ['write_off_sign', 'account', 'order_no']
//    ];


}