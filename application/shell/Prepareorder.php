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
            if (!empty($prepareAmountList)) {
                foreach ($prepareAmountList as $k => $v) {
                    if ($v['prepare_num'] - $v['can_user_num'] > 0) {
                        for ($i = 1; $i < $v['prepare_num'] - $v['can_user_num']; $i++) {
                            $res = $orderDouYinModel->createOrder($v['amount'], $v['prepare_num'] - $v['can_user_num']);
                            if ($res['code'] == 0) {
                                $db::table("bsa_prepare_set")->where($v['id'])->update(['can_user_num' => $v['can_user_num'] + 1]);
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