<?php

namespace app\api\controller;

use app\admin\model\WriteoffModel;
use app\common\model\DeviceModel;
use app\common\model\NotifylogModel;
use app\api\validate\NotifylogValidate;
use app\api\validate\OrderdouyinValidate;
use app\common\model\OrderdouyinModel;
use app\common\model\OrderModel;
use think\Db;
use think\facade\Log;
use think\Request;
use think\Controller;
use Zxing\QrReader;

class Torder extends Controller
{

    /**
     * 核销商上传推单
     * @param Request $request
     * @return void
     */
    public function uploadOrder(Request $request)
    {
//        $param = $request->param();
        $data = @file_get_contents('php://input');
        $param = json_decode($data, true);
        Log::info('douyin upload order first!', $param);
        try {
            $validate = new OrderdouyinValidate();
            if (!$validate->check($param)) {
                return json(msg(-1, '', $validate->getError()));
            }

            //验签
            $writeOffModel = new WriteoffModel();
            $writeOff = $writeOffModel->where(['write_off_sign' => $param['write_off_sign']])->find();
            if (empty($writeOff)) {
                return json(msg(-1, '', '错误的核销商'));
            }
            $param['sign'] = $param['sign'];
            if (md5($param['write_off_sign'] . $param['order_no'] . $param['account'] . $param['total_amount'] . $param['limit_time'] . $param['notify_url'] . $writeOff['token']) != $param['sign']) {
                return json(msg(-1, '', 'fuck you!'));
            }
            $orderDouYinModel = new OrderdouyinModel();
            $addParam['add_time'] = date("Y-m-d H:i:s", time());
            $addParam['status'] = 0;
            $where['account'] = $param['account'];
            $where['order_no'] = $param['order_no'];
            $res = $orderDouYinModel->addOrder($where, $addParam);

            if ($res['code'] != 0) {
                return json(msg('-2', '', $res['msg']));
            }
            return json(msg('1', '', "success"));

        } catch (\Exception $e) {
            Log::error('uploadOrder error!', $param);
            return json(msg('-11', '', 'saveBase64toImg error!' . $e->getMessage()));
        }
    }
}