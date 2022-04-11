<?php

namespace app\shell;

use app\common\model\OrderdouyinModel;
use app\common\model\OrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\SystemConfigModel;
use app\common\model\NotifylogModel;
use think\Db;

class Timecheckdouyin extends Command
{
    protected function configure()
    {
        $this->setName('Timecheckdouyin')->setDescription('定时处理（抖音）查单回调!');
    }

    /**
     * 定时处理回调日志 修改订单状态  @param Input $input
     * @param Output $output
     * @return int|null|void
     * @todo
     */
    protected function execute(Input $input, Output $output)
    {
        try {
            $limit = 10;
            $limitTime = SystemConfigModel::getPayLimitTime();
            $now = time();
            $lockLimit = $now - $limitTime;
            $orderdouyinModel = new OrderdouyinModel();
            $orderModel = new OrderModel();
//            $notifyLogModel = new NotifylogModel();
//            $notifyLogWhere['status'] = 2;
            $where[] = ['add_time', 'between', [$lockLimit, $now - 20]];
            $where[] = ['order_status', '4'];
            //查询下单之前280s 到现在之前20s的等待付款订单
            $orderData = $orderdouyinModel->where('order_status', '<>', 1)
                ->where('notify_status', '=', 0)
                ->where('status', '=', 2)
                ->where('add_time', '<', $lockLimit)->select();
            $totalNum = count($orderData);
            if ($totalNum > 0) {
                foreach ($orderData as $k => $v) {
                    $getResParam['order_no'] = $v['order_pay'];
                    $getResParam['order_url'] = $v['check_url'];
                    $getOrderStatus = $orderdouyinModel->checkOrderStatus($getResParam);
                    if (isset($getOrderStatus['code']) && $getOrderStatus['code'] == 1) {
                        //支付成功
                        $orderWhere['order_pay'] = $v['order_pay'];
                        $orderWhere['order_me'] = $v['order_me'];
                        $orderWhere['status'] = 2;
                        $order = Db::table("bsa_order")->where($orderWhere)->find();
                        $orderModel->orderNotify($order);
                        $torderDouyinWhere['order_me'] = $v['order_me'];
                        $torderDouyinWhere['order_pay'] = $v['order_pay'];
                        $torderDouyinUpdate['order_status'] = 1;
                        $torderDouyinUpdate['status'] = 2;
                        $torderDouyinUpdate['pay_time'] = time();
                        $torderDouyinUpdate['last_use_time'] = time();
                        $torderDouyinUpdate['success_amount'] = $v['total_amount'];
                        $torderDouyinUpdate['order_desc'] = "支付成功|待回调";
                        $orderdouyinModel->updateNotifyTorder($torderDouyinWhere, $torderDouyinUpdate);
                        $orderdouyinModel->orderDouYinNotifyToWriteOff($v);
                    }
                    if ((strtotime($v['limit_time']) - time()) > $limitTime) {
                        $torderDouyinWhere['order_me'] = $v['order_me'];
                        $torderDouyinWhere['order_pay'] = $v['order_pay'];
                        $torderDouyinUpdate['order_status'] = 2;
                        $torderDouyinUpdate['status'] = 2;
                        $torderDouyinUpdate['order_desc'] = "支付超时|准备回调核销失败";
                        $orderdouyinModel->updateNotifyTorder($torderDouyinWhere, $torderDouyinUpdate);
                        $orderdouyinModel->orderDouYinNotifyToWriteOff($v);
                    }
                }

            }
            $output->writeln("Timecheckdouyin:订单总数" . $totalNum);
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'TimeouttorderNotify_exception');
            $output->writeln("Timecheckdouyin:订单总数" . $totalNum . "exception");
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'TimeouttorderNotify_error');
            $output->writeln("Timecheckdouyin:订单总数" . $totalNum . "error");
        }

    }
}