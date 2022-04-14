<?php

namespace app\shell;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use app\common\model\OrderdouyinModel;
use app\common\model\SystemConfigModel;
use think\Db;

class Prepareorder extends Command
{
    protected function configure()
    {
        $this->setName('Prepareorder')->setDescription('预先生成！');
    }

    /**
     * 定时生成订单
     * @param Input $input
     * @param Output $output
     * @return int|null|void
     */
    protected function execute(Input $input, Output $output)
    {
        $totalNum = 0;
        $successNum = 0;
        $errorNum = 0;
        $msg = "预拉开始";
        $db = new Db();
        try {
            //时间差  话单时间差生成订单时间差
//            $limitTime = SystemConfigModel::getTorderLimitTime();
            $limitTime = 900;
            $now = time();

//            getUseCookie
            $orderDouYinModel = new OrderdouyinModel();
            //下单金额
            $prepareWhere['status'] = 1;
            $prepareAmountList = $db::table("bsa_prepare_set")->where($prepareWhere)->select();
//            if (count($prepareAmountList) > 0) {
            if (!is_array($prepareAmountList) || count($prepareAmountList) == 0) {
                $output->writeln("Prepareorder:无预产任务");
            } else {
                foreach ($prepareAmountList as $k => $v) {

                    $v = $db::table("bsa_prepare_set")->where("id", $v['id'])->lock(true)->find();
//                    logs(json_encode(["total" => $v['prepare_num'], 'can_use_num' => $can_use_num, 'amount' => $v['order_amount'], "sql" => $db::table("bsa_torder_douyin")->getLastSql()]), 'prepareorderapicreateindex_log');
                    $can_use_num = $db::table("bsa_torder_douyin")
                        ->where('status', '=', 0)
                        ->where('url_status', '=', 0)
                        ->where('total_amount', '=', $v['order_amount'])
                        ->where('add_time', '>', time() - 600)
                        ->order("add_time asc")
                        ->count();
                    logs(json_encode(["total" => $v['prepare_num'], 'can_use_num' => $can_use_num, 'amount' => $v['order_amount'], "sql" => $db::table("bsa_torder_douyin")->getLastSql()]), 'prepareorderapicreateindex_log');

                    if (($v['prepare_num'] - $can_use_num) > 0) {
                        $res = $orderDouYinModel->createOrder($v, $v['prepare_num'] - $can_use_num);
                        logs(json_encode(['num' => ($v['prepare_num'] - $v['can_use_num']), 'amount' => $v['order_amount'], 'createOrderRes' => $res]), 'prepareorderapicreateOrder_log');

                        if ($res['code'] == 0 && $res['data'] > 0) {
                            $prepareSetWhere['id'] = $v['id'];
                            $db::table("bsa_prepare_set")->where("id", $v['id'])->update(['can_use_num' => $v['can_use_num'] + $res['data']]);
                            $db::commit();
                            $msg .= "金额:" . $v['order_amount'] . $res['msg'] . "(" . $res['data'] . "个)||--";
                        } else {
                            sleep(1);
                            $msg .= "失败金额:" . $v['order_amount'] . $res['msg'] . "(" . $res['data'] . "个)||--";
                        }
                    }

                }
            }

            $output->writeln("Prepareorder:预产单处理成功" . $msg);
        } catch (\Exception $exception) {
            $db::rollback();
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'Prepareorder_exception');
            $output->writeln("Prepareorder:浴场处理失败！" . $totalNum . "exception" . $exception->getMessage());
        } catch (\Error $error) {
            $db::rollback();
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'Prepareorder_error');
            $output->writeln("Prepareorder:浴场处理失败！！" . $totalNum . "error");
        }

    }
}