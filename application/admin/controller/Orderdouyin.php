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
            $order_no = input('param.order_no');
            $order_me = input('param.order_me');
            $order_pay = input('param.order_pay');
            $account = input('param.account');
            $startTime = input('param.start_time');

            $where = [];
            if (!empty($order_no)) {
                $where[] = ['order_no', '=', $order_no];
            }
            if (!empty($order_me)) {
                $where[] = ['order_me', '=', $order_me];
            }
            if (!empty($order_pay)) {
                $where[] = ['order_pay', '=', $order_me];
            }
            if (!empty($account)) {
                $where[] = ['account', '=', $account];
            }
            if (!empty($startTime)) {
//                $endTime = stototime($startTime,);
                $endTime = mktime(date("Y-m-d", $startTime));
                $where[] = ['add_time', 'between', [strtotime($startTime), strtotime($startTime . ' 23:59:59')]];
            }

            $writeOffNodeId = session("admin_role_id");
            if ($writeOffNodeId == 8) {
                $where['write_off_sign'] = ['=', session("admin_user_name")];   //默认情况下 登录名就是
            }
            $TorderModel = new Torderdouyinmodel();
            $list = $TorderModel->getTorders($limit, $where);
            $data = empty($list['data']) ? array() : $list['data'];
            foreach ($data as $key => $vo) {
//                $data[$key]['time'] = date('Y-m-d H:i:s', $data[$key]['add_time']) . "添加时间</br>";
//                $data[$key]['time'] = $data[$key]['time'] . date('Y-m-d H:i:s', $data[$key]['get_url_time']) . "更新时间:</br>";
//                $data[$key]['time'] = $data[$key]['time'] . date('Y-m-d H:i:s', $data[$key]['notify_time']);
                $data[$key]['add_time'] = date('Y-m-d H:i:s', $data[$key]['add_time']);
                $data[$key]['get_url_time'] = date('Y-m-d H:i:s', $data[$key]['get_url_time']);
                $data[$key]['limit_time'] = date('Y-m-d H:i:s', $data[$key]['limit_time_1']);
                $data[$key]['notify_time'] = date('Y-m-d H:i:s', $data[$key]['notify_time']);
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

}