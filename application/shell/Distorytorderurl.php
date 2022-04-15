<?php

namespace app\shell;

use app\common\model\OrderModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\OrderdouyinModel;
use app\common\model\SystemConfigModel;
use think\Db;

class Distorytorderurl extends Command
{
    protected function configure()
    {
        $this->setName('Distorytorderurl')->setDescription('销毁已拉单未支付推单！');
    }

    /**
     * 销毁已拉单未支付链接
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        $limitTime = 600;
        $now = time();
        $successNum = 0;
        $errorNum = 0;
        $lockLimit = $now - $limitTime;
        $orderdouyinModel = new OrderdouyinModel();
        $LimitStartTime = time() - $limitTime;
        $db = new Db();
        try {
            //查询下单之前600S
            //没有匹配订单（order_me =null）的
            //不管预拉与否 url_status = 1
            //录入时间小于当前时间600s之前 add_time
            //禁用
            $orderData = $orderdouyinModel
                ->where('order_status', '<>', 1)
                ->where('notify_status', '=', 0)
                ->where('order_me', '=', null)
//                ->where('url_status', '=', 1)
//                ->where('add_time', '>', 0)
                ->where('add_time', '<', $LimitStartTime)
                ->select();
            logs(json_encode(['orderData' => $orderData, "sql" => Db::table("bsa_torder_douyin")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'Distorytorderurl_log');

            $totalNum = count($orderData);
            if ($totalNum > 0) {
                foreach ($orderData as $k => $v) {
                    $prepareWhere['order_amount'] = $v['total_amount'];
                    $prepareWhere['status'] = 1;
                    $update1 = $db::table("bsa_prepare_set")->where($prepareWhere)
                        ->update([
                            "can_use_num" => Db::raw("can_use_num-1")
                        ]);
                    if (!$update1) {
                        logs(json_encode(['orderData' => $orderData, "sql" => Db::table("bsa_prepare_set")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'Distorytorderurl_prepare_set_fail_log');
                    }
                    //支付链接不可用
                    $torderDouyinWhere['t_id'] = $v['t_id'];
                    $torderDouyinUpdate['url_status'] = 2;   //订单已失效 以停止查询
                    $torderDouyinUpdate['status'] = 2;  ///推单改为最终结束状态 等待自动回调核销支付失败
                    $torderDouyinUpdate['order_desc'] = "预拉成功|匹配失败|准备核销回调";
                    $uodateTorderRes = $db::table("bsa_torder_douyin")->where($torderDouyinWhere)
                        ->update($torderDouyinUpdate);
                    if (!$uodateTorderRes) {
                        logs(json_encode(['orderData' => $v, "sql" => Db::table("bsa_torder_douyin")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'Distorytorderurl_uodateTorderRes_log');

                    }
                }
                $output->writeln("Distorytorderurl:预产单处理成功" . "成功处理:" . $successNum . "失败:" . $errorNum);

            }
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'Distorytorderurl_exception');
            $output->writeln("Distorytorderurl:销毁已拉单未支付链接！" . $totalNum . "exception" . $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'Distorytorderurl_error');
            $output->writeln("Distorytorderurl:销毁已拉单未支付链接！！" . $totalNum . "error");
        }

    }
}