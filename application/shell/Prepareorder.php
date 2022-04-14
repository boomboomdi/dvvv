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
        $db::startTrans();
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
                        $v = $db::table("bsa_prepare_set")->where("id", $v['id'])->lock(true)->find();
                        if ($v) {
                            logs(json_encode(['totalNum' => $totalNum, 'prepareAmountList' => $prepareAmountList]), 'prepareorderapi');

                            if(($v['prepare_num'] - $v['can_use_num'])>0){
                                $res = $orderDouYinModel->createOrder($v, ($v['prepare_num'] - $v['can_use_num']));
                                logs(json_encode(['num' => ($v['prepare_num'] - $v['can_use_num']), 'amount' => $v['order_amount'], 'createOrderRes' => $res]), 'prepareorderapi_res_log');

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

                        $db::commit();
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