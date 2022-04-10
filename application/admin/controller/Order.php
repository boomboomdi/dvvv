<?php
/**
 * Created by PhpStorm.
 * User: bl
 * Date: 2020/12/20
 * Time: 12:57
 */

namespace app\admin\controller;

use app\admin\model\OrderModel;
use think\Db;

class Order extends Base
{
    //订单列表
    public function index()
    {
        if (request()->isAjax()) {

            $limit = input('param.limit');
            $orderId = input('param.order_id');
            $startTime = input('param.start_time');
            $endTime = input('param.end_time');

            $where = [];
            if (!empty($orderId)) {
                $where[] = ['order_id', 'like', $orderId . '%'];
            }
            if (!empty($startTime)) {
                $where[] = ['add_time', '>', strtotime($startTime)];
            }
            if (!empty($endTime)) {
                $where[] = ['add_time', '<', strtotime($endTime)];
            }
            $Order = new OrderModel();
            $list = $Order->getOrders($limit, $where);
            $data = $list['data'];
            foreach ($data as $key => $vo) {
                // 1、支付成功（下单成功）！2、支付失败（下单成功）！3、下单失败！4、等待支付（下单成功）！5、已手动回调。
                $data[$key]['add_time'] = date('Y-m-d H:i:s', $data[$key]['add_time']);
                if (!empty($data[$key]['pay_time']) && $data[$key]['pay_time'] != 0) {
                    $data[$key]['pay_time'] = date('Y-m-d H:i:s', $data[$key]['pay_time']);
                }
                if (!empty($data[$key]['update_time']) && $data[$key]['update_time'] != 0) {
                    $data[$key]['update_time'] = date('Y-m-d H:i:s', $data[$key]['update_time']);
                }
                if (!empty($data[$key]['add_time']) && $data[$key]['add_time'] != 0) {
                    $data[$key]['add_time'] = date('Y-m-d h:i:s', (int)$data[$key]['add_time']);
                }
                if (!empty($data[$key]['order_status']) && $data[$key]['order_status'] == '1') {
                    $data[$key]['order_status'] = '<button class="layui-btn layui-btn-success layui-btn-xs">付款成功</button>';
                }
                if (!empty($data[$key]['order_status']) && $data[$key]['order_status'] == '2') {

                    $data[$key]['order_status'] = '<button class="layui-btn layui-btn-danger layui-btn-xs">付款失败</button>';
                }
                if (!empty($data[$key]['order_status']) && $data[$key]['order_status'] == '3') {
                    $data[$key]['order_status'] = '<button class="layui-btn layui-btn-disabled layui-btn-xs">下单失败</button>';
                }
                if (!empty($data[$key]['order_status']) && $data[$key]['order_status'] == '4') {
                    $data[$key]['order_status'] = '<button class="layui-btn layui-btn-primary layui-btn-xs">等待支付</button>';
                }
                if (!empty($data[$key]['order_status']) && $data[$key]['order_status'] == '6') {
                    $data[$key]['order_status'] = '<button class="layui-btn layui-btn-primary layui-btn-xs">回调中···</button>';
                }
                if (!empty($data[$key]['order_status']) && $data[$key]['order_status'] == '5') {
                    $data[$key]['order_status'] = '<button class="layui-btn layui-btn-warm layui-btn-xs">等待支付</button>';
                }
//                $data[$key]['apiMerchantOrderDate'] = date('Y-m-d H:i:s', $data[$key]['apiMerchantOrderDate']);
//                $data[$key]['orderCreateDate'] = date('Y-m-d H:i:s', $data[$key]['orderCreateDate']);
            }
            $list['data'] = $data;
            if (0 == $list['code']) {
                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    /**
     * 手动回调
     * @return void
     */
    public function notify()
    {
        $order_no = input('param.order_no');
//        $param = input('post.');
        try {
            logs(json_encode(['notify' => "notify", 'order_no' => $order_no,]), 'notify_first');
            if (empty($order_no)) {
                return reMsg(-1, '', "回调错误！");
            }
            //查询订单
            $order = Db::table("bsa_order")->where("order_no", $order_no)->find();
            $orderModel = new \app\common\model\OrderModel();
            return $orderModel->orderNotify($order, 2);

        } catch (\Exception $exception) {
            logs(json_encode(['param' => $order_no, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'order_notify_exception');
            return json('20009', "通道异常" . $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['param' => $order_no, 'file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'order_notify_error');
            return json('20099', "通道异常" . $error->getMessage());
        }

    }
}