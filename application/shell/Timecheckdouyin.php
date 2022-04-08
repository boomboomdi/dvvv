<?php

namespace app\shell;

use app\common\model\OrderdouyinModel;
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
            $orderModel = new OrderdouyinModel();
            $notifyLogModel = new NotifylogModel();
            $notifyLogWhere['status'] = 2;
            $where[] = ['add_time', 'between', [$lockLimit, $now - 20]];
            $where[] = ['order_status', '4'];
            //查询下单之前280s 到现在之前20s的等待付款订单
            $orderData = $orderModel->where($where)->select();
            $totalNum = count($orderData);
            if ($totalNum > 0) {
                foreach ($orderData as $k => $v) {
                       //请求查单接口
//                    $checkParam[''] = $v[''];
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