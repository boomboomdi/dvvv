<?php

namespace app\shell;

use app\admin\model\CookieModel;
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
        $this->setName('Prepareorder')->setDescription('预先生成，！');
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
            $limitTime = SystemConfigModel::getTorderLimitTime();
            $now = time();
            $lockLimit = $now - $limitTime;

            //获取CK
            $cookieModel = new CookieModel();
//            getUseCookie
            $orderDouYinModel = new OrderdouyinModel();
            //下单金额
            $prepareWhere['status'] = 1;
            $prepareAmountList = $db::table("bsa_prepare_set")->where($prepareWhere)->select();
            if (count($prepareAmountList) > 0) {
                foreach ($prepareAmountList as $k => $v) {
                    if ($v['prepare_num'] - $v['can_use_num'] > 0) {
                        for ($i = 1; $i < $v['prepare_num'] - $v['can_use_num']; $i++) {
                            $res = $orderDouYinModel->createOrder($v['order_amount'], $v['prepare_num'] - $v['can_use_num']);
//                            logs(json_encode(['num' => $v['prepare_num'] - $v['can_use_num'], 'amount' => $v['amount'], 'res' => json_encode($res)]), 'Prepareorderapi');

                            if ($res['code'] == 0) {
                                $prepareSetWhere['id'] = $v['id'];
                                $db::table("bsa_prepare_set")->where($prepareSetWhere)->update(['can_use_num' => $v['can_use_num'] + $res['data']]);
                                $msg .= $res['msg'] . "||";
                            } else {
                                $msg .= $res['msg'] . "||";
                            }
                        }
                    }
                }
            }
            $output->writeln("Prepareorder:预先生成||" . $msg);
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'Timedevice exception');
            $output->writeln("Prepareorder:总应强制超时订单数" . $totalNum . "exception");
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'Timedevice  error');
            $output->writeln("Prepareorder:总应强制超时订单数" . $totalNum . "error");
        }

    }
}