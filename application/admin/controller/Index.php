<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/2/17
 * Time: 11:33 AM
 */

namespace app\admin\controller;

use app\admin\model\Admin;
use think\App;
use think\Db;
use tool\Auth;

class Index extends Base
{
    public function index()
    {
        $authModel = new Auth();
        $menu = $authModel->getAuthMenu(session('admin_role_id'));
        $this->assign([
            'admin_user_name' => session("admin_user_name"),
            'menu' => $menu
        ]);

        return $this->fetch();
    }

    public function index2()
    {
        //baseAdmin!@#-

        $pas = makePassword("admin");
        var_dump($pas);
        exit;
    }

    public function home()
    {
        $db = new Db();
        //订单数量   //
        $orderNum = $db::table("bsa_order")->count();
        //回调数量
        $payOrderNum = $db::table("bsa_order")->where('order_status', '=', 1)->count();
        //手动回调数量
        $notifyPayOrderNum = $db::table("bsa_order")->where('order_status', '=', 5)->count();
        $successOrderRate = makeSuccessRate(((int)$payOrderNum + (int)$notifyPayOrderNum), (int)$orderNum);
        //回调金额
        $payOrderAmount = $db::table("bsa_order")->where('order_status', '=', 1)->sum('actual_amount');
        $handPayOrderAmount = $db::table("bsa_order")->where('order_status', '=', 5)->sum('actual_amount');
        $payOrderAmount = $payOrderAmount + $handPayOrderAmount;
        //核销单总量
        $tOrderNum = $db::table("bsa_torder_douyin")->count();

        $payOrderTOrderNum = $db::table("bsa_torder_douyin")->where('order_status', '=', 1)->count();
        //可下单数量
        $canOrderTOrderNum = $db::table("bsa_torder_douyin")
            ->where('url_status', '=', 1)
            ->where('order_me', '=', null)
            ->where('status', '=', 1)
            ->where('get_url_time', '>', time() - 180)
            ->where('get_url_time', '<', time())->count();
        //可预拉数量
        $canPrepareTOrderNum = $db::table("bsa_torder_douyin")
            ->where('status', '=', 0)
            ->where('get_url_time', '=', 0)
            ->count();
        //预拉中数量
        $preparingTOrderNum = $db::table("bsa_torder_douyin")->where('weight', '=', 1)->count();

        //使用数量
        //成功支付量
        $this->assign([
            'tp_version' => App::VERSION,
            'endTime' => date("Y-m-d H:i:s"),
            'orderNum' => $orderNum,
            'payOrderNum' => $payOrderNum,
            'payOrderTOrderNum' => $payOrderTOrderNum,
            'successOrderRate' => $successOrderRate,
            'notifyPayOrderNum' => $notifyPayOrderNum,
            'payOrderAmount' => $payOrderAmount,
            'tOrderNum' => $tOrderNum,
            'canOrderTOrderNum' => $canOrderTOrderNum,
            'canPrepareTOrderNum' => $canPrepareTOrderNum,
            'preparingTOrderNum' => $preparingTOrderNum,
        ]);

        return $this->fetch();
    }

    // 修改密码
    public function editPwd()
    {
        if (request()->isPost()) {

            $param = input('post.');

            if ($param['new_password'] != $param['rep_password']) {
                return json(['code' => -1, 'data' => '', 'msg' => '两次密码输入不一致']);
            }

            // 检测旧密码
            $admin = new Admin();
            $adminInfo = $admin->getAdminInfo(session('admin_user_id'));

            if (0 != $adminInfo['code'] || empty($adminInfo['data'])) {
                return json(['code' => -2, 'data' => '', 'msg' => '管理员不存在']);
            }

            if (!checkPassword($param['password'], $adminInfo['data']['admin_password'])) {
                return json(['code' => -3, 'data' => '', 'msg' => '旧密码错误']);
            }

            $admin->updateAdminInfoById(session('admin_user_id'), [
                'admin_password' => makePassword($param['new_password'])
            ]);

            return json(['code' => 0, 'data' => '', 'msg' => '修改密码成功']);
        }

        return $this->fetch('pwd');
    }
}
