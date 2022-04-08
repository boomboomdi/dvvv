<?php

namespace app\api\controller;

use app\admin\model\WriteoffModel;
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
    public function uploadOrder()
    {
//        $aa = $request->param();
//        Log::log('douyin upload order first test!', $aa);

        $data = @file_get_contents("php://input");
//        $data = @file_get_contents('php://input');
        $param = json_decode($data, true);
//        var_dump($param);exit;
        Log::log('douyin upload order first!', $param);
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
            $returnData['code'] = 1;
            $returnData['order_no'] = $param['order_no'];

            return json(msg('1', '', "success"));

        } catch (\Exception $e) {
            Log::error('uploadOrder error!', $param);
            return json(msg('-11', '', 'saveBase64toImg error!' . $e->getMessage()));
        }
    }

    /**
     * 推单查询状态
     */
    public function orderInfo(Request $request)
    {
        $param = $request->param();
        $data = @file_get_contents('php://input');
        $param = json_decode($data, true);
        Log::info('douyin orderInfo first!', $param);
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
            if (md5($param['write_off_sign'] . $param['order_no'] . $param['account'] . $writeOff['token']) != $param['sign']) {
                return json(msg(-1, '', 'fuck you!'));
            }
            $orderDouYinModel = new OrderdouyinModel();
            $where['account'] = $param['account'];
            $where['order_no'] = $param['order_no'];
            $res = $orderDouYinModel->getTorderInfo($where);

            if ($res['code'] != 0) {
                return json(msg('-2', $where['order_no'], $res['msg']));
            }
//            $data['order_status'] = $res['data']['order_status']; // 0：等待付款(使用中)1：已付款2：未到账(使用中) 4：未使用
//            $data['success_amount'] = $res['data']['success_amount']; // 付款金额  1 整型
            return json(msg($res['data']['order_status'],  $where['order_no'], "查询成功！"));

        }catch (\Exception $exception) {
            logs(json_encode(['param' => $param, 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'orderInfo_exception');
            return apiJsonReturn('20009', "通道异常" . $exception->getMessage());
        }catch (\Exception $e) {
            Log::error('orderInfo error!', $param);
            return json(msg('-11', '', 'orderInfo error!' . $e->getMessage()));
        }
    }

}