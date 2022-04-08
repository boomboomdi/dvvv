<?php

namespace app\shell;

use app\common\model\OrderdouyinModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\SystemConfigModel;
use app\common\model\NotifylogModel;
use think\Db;
use think\facade\Log;

class Timecheckdouyinhuadan extends Command
{
    protected function configure()
    {
        $this->setName('Timecheckdouyinhuadan')->setDescription('定时处理（抖音话单）回调!');
    }

    /**
     * 定时处理回调日志 修改订单状态  @param Input $input
     * @param Output $output
     * @return int|null|void
     * @todo
     */
    protected function execute(Input $input, Output $output)
    {
        $totalNum = 0;
        $orderData = [];
        try {
            $limit = 10;
            $limitTime = SystemConfigModel::getDouyinPayLimitTime();
            $now = time();
            $lockLimit = $now - $limitTime;
            $orderModel = new OrderdouyinModel();
            $where[] = ['order_status', "!=", '1'];
            $where[] = ['notify_status', "!=", '0'];
            $where[] = ['add_time', "<", $lockLimit];
            //查询下单之前280s 到现在之前20s的等待付款订单
//            $updateData = $orderModel->where('add_time', '<', $lockLimit)->where($updateDataWhere)->select();

            $orderData = $orderModel
                ->where('order_status', "!=", 1)
                ->where('notify_status', "!=", 0)
                ->where('add_time', "<", $lockLimit)
                ->limit($limit)->select()->toArray();
            $totalNum = count($orderData);
            if ($totalNum > 0) {
                foreach ($orderData as $k => $v) {
                    //请求查单接口
                    $orderModel->orderDouYinNotifyToWriteOff($v);
                }
            }
            $output->writeln("Timecheckdouyinhuadan:订单总数" . $totalNum);
        } catch (\Exception $exception) {
            Log::log('Timecheckdouyinhuadanexception!', "订单总数" . $totalNum . $exception->getLine() . $exception->getMessage());
            $output->writeln("Timecheckdouyinhuadan:订单总数" . $totalNum . "exception".json_encode($orderData));
        } catch (\Error $error) {
            Log::log('Timecheckdouyinhuadanerror!', "订单总数" . $totalNum . $error->getLine() . $error->getMessage());
            $output->writeln("Timecheckdouyinhuadan:订单总数" . $totalNum . "error");
        }

    }
}