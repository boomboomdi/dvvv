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
        $msg = "";
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
            if (count($prepareAmountList) > 0) {
                foreach ($prepareAmountList as $k => $v) {
                    if (($v['prepare_num'] - $v['can_use_num']) > 0) {
//                        logs(json_encode(['totalNum' => $totalNum, 'prepareAmountList' => $prepareAmountList]), 'Prepareorderapi');
                        for ($i = 1; $i < ($v['prepare_num'] - $v['can_use_num']); $i++) {
                            $res = $orderDouYinModel->createOrder($v, ($v['prepare_num'] - $v['can_use_num']));
//                            logs(json_encode(['num' => $v['prepare_num'] - $v['can_use_num'], 'amount' => $v['order_amount'], 'res' => $res]), 'Prepareorderapi');

                            if ($res['code'] == 0 && $res['data'] > 0) {
                                $prepareSetWhere['id'] = $v['id'];
                                $db::table("bsa_prepare_set")->where($prepareSetWhere)->update(['can_use_num' => $v['can_use_num'] + $res['data']]);
                                $msg .= "金额:" . $v['order_amount'] . $res['msg'] . "(" . $res['data'] . "个)||/r/n";
                            } else {
                                $msg .= "金额:" . $v['order_amount'] . $res['msg'] . "(" . $res['data'] . "个)||/r/n";
                            }
                        }
                    }
                }
            }
            $output->writeln("Prepareorder:预产单处理成功".$msg);
        } catch (\Exception $exception) {
//            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'Prepareorder_exception');
            $output->writeln("Prepareorder:浴场处理失败！" . $totalNum . "exception" . $exception->getMessage());
        } catch (\Error $error) {
//            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'Prepareorder_error');
            $output->writeln("Prepareorder:浴场处理失败！！" . $totalNum . "error");
        }

    }
}