<?php
/**
 * Created by PhpStorm.
 * User: bl
 * Date: 2020/12/20
 * Time: 12:57
 */

namespace app\admin\controller;

use think\Db;
use tool\Log;

use app\admin\model\Torderdouyinmodel;

class Orderdouyin extends Base
{
    //推单列表
    public function index()
    {
        if (request()->isAjax()) {

            $limit = input('param.limit');
//            $apiMerchantOrderNo = input('param.apiMerchantOrderNo');
            $order_no = input('param.order_no');
            $startTime = input('param.start_time');
            $endTime = input('param.end_time');

            $where = [];
            if (!empty($order_no)) {
                $where[] = ['order_no', '=', $order_no];
            }
            if (!empty($startTime)) {
                $where[] = ['start_time', 'between', [$startTime, $startTime . ' 23:59:59']];
            }
            $writeOffNodeId = session("admin_role_id");
            if ($writeOffNodeId == 8) {
                $where['write_off_sign'] = ['=', session("admin_user_name")];   //默认情况下 登录名就是
//                $where[] = ['studio', "=", session("admin_user_name")];  //默认情况下 登录名就是
            }
            $TorderModel = new Torderdouyinmodel();
            $list = $TorderModel->getTorders($limit, $where);
            $data = empty($list['data']) ? array() : $list['data'];
            foreach ($data as $key => $vo) {
                if (!empty($data[$key]['status']) && $data[$key]['status'] == '4') {
                    $data[$key]['status'] = '<button class="layui-btn layui-btn-info layui-btn-xs">未使用</button>';
                }
                if ($data[$key]['status'] == '1') {
                    $data[$key]['status'] = '<button class="layui-btn layui-btn-success layui-btn-xs">付款成功</button>';
                } else if ($data[$key]['status'] == '2') {
                    $data[$key]['status'] = '<button class="layui-btn layui-btn-important layui-btn-xs">付款失败</button>';
                } else if ($data[$key]['status'] == '3') {
                    $data[$key]['status'] = '<button class="layui-btn layui-btn-success layui-btn-xs">已手动回调</button>';
                } else if ($data[$key]['status'] == '5') {
                    $data[$key]['status'] = '<button class="layui-btn layui-btn-danger layui-btn-xs">已失败回调</button>';
                } else if ($data[$key]['status'] == '6') {
                    $data[$key]['status'] = '<button class="layui-btn layui-btn-disabled layui-btn-xs">支付回调成功</button>';
                } else {
                    $data[$key]['status'] = '<button class="layui-btn layui-btn-disabled layui-btn-xs">等待付款</button>';
                }
            }
            $list['data'] = $data;
            if (0 == $list['code']) {
                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    /**
     * 删除推单
     * @return \think\response\Json
     */
    public function delTorder()
    {
        if (request()->isAjax()) {
            $tId = input('param.t_id');
            $torderModel = new Torderdouyinmodel();
            $res = $torderModel->delTorder($tId);
            Log::write("删除推单：" . $tId);
            return json($res);
        }
    }

    /**
     * 修改设备状态
     */
    public function changestatus()
    {
        $t_id = input('param.t_id');
        $TorderModel = new TorderModel();
        try {
            $list = $TorderModel
                ->where('t_id', '=', $t_id)->find();
            $torder = session('username');
            //在线设备可以修改启用与否
            if ($list['status'] != '4') {
                return json(msg(0, '', '已使用订单无法操作！'));
            }
            if ($list['status'] == '1') {
                $updateData['status'] = 2;
                $result = $TorderModel
                    ->where('t_id', '=', $t_id)
                    ->update($updateData);
                if ($result) {
                    return json(msg(0, '', '修改成功！,已禁用'));
                }
            } else {
                $updateData['status'] = 1;
                $result = $TorderModel
                    ->where('t_id', '=', $t_id)
                    ->update($updateData);
                if ($result) {
                    return json(msg(0, '', '修改成功！,已启用'));
                }
            }
        } catch (\Exception $e) {
            return json(msg(-2, '', $e->getMessage()));
        }
    }


}