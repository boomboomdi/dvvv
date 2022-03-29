<?php
/**
 * Created by PhpStorm.
 * User: 75763
 * Date: 2018/12/15
 * Time: 19:53
 */

namespace app\api\controller;

use think\Db;
use think\Controller;
use think\Request;
use app\common\model\OrderModel;
use app\common\model\SystemConfigModel;

class Zfbpay extends Controller
{

    /**
     * 支付宝  当前
     * @param Request $request
     * @return bool|mixed
     */
    public function index(Request $request)
    {
        $message = $request->param();
        //订单号有误
        if (!isset($message['orderNo']) || empty($message['orderNo'])) {
            echo "订单号有误！";
            exit;
        }
        try {
            $orderModel = new OrderModel();
            $orderData = $orderModel
                ->where('order_me', '=', $message['orderNo'])
                ->where('order_status', '=', 4)
                ->find();
            if (empty($orderData)) {
                echo "请重新下单";
                exit;
            }

            //计算倒计时
            $now = time();
            $orderPayLimitTime = SystemConfigModel::getPayLimitTime();
            $orderPayLimitTime = $orderPayLimitTime - 60;
            $endTime = $orderData['add_time'] + $orderPayLimitTime;
            $countdownTime = $endTime - $now;
            if ($countdownTime < 0) {
                echo "订单超时，请重新下单！";
                exit;
            }

            //修改订单收款ip
            $ip = $request->ip();
            $updateData['show_order_ip'] = $ip;
            $orderModel->where('order_ne', '=', $message['orderNo'])->update($updateData);

            //展示金额
            $this->assign('payableAmountShow', $orderData['payable_amount']);
            $this->assign('countdownTime', $countdownTime);
            $payUrl = '"' . $orderData['qr_url'] . '"';
            $this->assign('orderUrl', $payUrl);
            $this->assign('orderNo', $message['orderNo']);
            return $this->fetch();
        } catch (\Exception $exception) {
            logs(json_encode(['message' => $message, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'order_index_exception');
            return apiJsonReturn('20009', "订单页面异常，请联系客服" . $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['message' => $message, 'file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'order_index_error');
            return apiJsonReturn('20099', "订单页面错误，请联系客服" . $error->getMessage());
        }

    }


}
