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
        $this->setName('Timecheckdouyinhuadan')->setDescription('定时处理（抖音话单）查单回调!');
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
            $orderModel = new OrderdouyinModel();
            $where[] = ['order_status', "!=", '1'];
            $where[] = ['notify_status', "!=", '0'];
            $where[] = ['limit_time', "<", date('Y-m-d H:i:s', time())];
            //查询下单之前280s 到现在之前20s的等待付款订单
            $orderData = $orderModel->where($where)->limit(20)->select();
            $totalNum = count($orderData);
            if ($totalNum > 0) {
                foreach ($orderData as $k => $v) {
                    //请求查单接口
                    $orderModel->orderDouYinNotifyToWriteOff($v);
                }
            }
            $output->writeln("Timecheckdouyinhuadan:订单总数" . $totalNum);
        } catch (\Exception $exception) {
            Log::log('Timecheckdouyinhuadan!', "订单总数" . $totalNum);
            $output->writeln("Timecheckdouyinhuadan:订单总数" . $totalNum . "exception");
        } catch (\Error $error) {
            Log::log('Timecheckdouyinhuadan!', "订单总数" . $totalNum);
            $output->writeln("Timecheckdouyinhuadan:订单总数" . $totalNum . "error");
        }

    }
}