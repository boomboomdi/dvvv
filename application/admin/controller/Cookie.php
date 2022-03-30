<?php
/**
 * Created by PhpStorm.
 * User: NickBai
 * Email: 876337011@qq.com
 * Date: 2019/10/11
 * Time:  14:23
 */

namespace app\admin\controller;

use app\admin\model\CookieModel;
use app\admin\validate\CookieValidate;
use think\Validate;
use tool\Log;

class Cookie extends Base
{
    // cookie
    public function index()
    {
        if (request()->isAjax()) {

            $limit = input('param.limit');
            $account = input('param.account');

            $where = [];
            if (!empty($account)) {
                $where['account'] = ['=', $account];
            }
//            if (!empty($account)) {
//                $where[] = ['account', 'like', $account . '%'];
//            }
            $cookieModel = new CookieModel();
//            var_dump(session("admin_role_id"));
//            exit;
//            $studio = session("admin_role_id");
//            if ($studio == 7) {
//                $where['studio'] = ['=', session("admin_user_name")];   //默认情况下 登录名就是 工作室标识
////                $where[] = ['studio', "=", session("admin_user_name")];  //默认情况下 登录名就是 工作室标识
//            }
            $list = $cookieModel->getCookies($limit, $where);
            $data = empty($list['data']) ? array() : $list['data'];
            foreach ($data as $key => $vo) {

////                $data[$key]['add_time'] = date('Y-m-d H:i:s', $data[$key]['add_time']);
////                $data[$key]['heart_time'] = date('Y-m-d H:i:s', $vo['heart_time']);
////
////                if (!empty($data[$key]['qr_update_time']) && $data[$key]['qr_update_time'] != 0) {
////                    $data[$key]['update_time'] = date('Y-m-d H:i:s', $data[$key]['qr_update_time']);
////                }
//
//                //订单状态 :是否可用1：可用2：不可用（心跳正常且开启情况下是否可下单）
//                //设备状态：是否开启1：开启中2已关闭
//                //心跳2：离线  1在线
            }
            $list['data'] = $data;
            if (0 == $list['code']) {
                return json(['code' => 0, 'msg' => 'ok', 'count' => $list['data']->total(), 'data' => $list['data']->all()]);
            }

            return json(['code' => 0, 'msg' => 'ok', 'count' => 0, 'data' => []]);
        }

        return $this->fetch();
    }

    // 添加Cookie
    public function addCookie()
    {
        if (request()->isPost()) {

            $param = input('post.');

            $cookie = new CookieModel();
            $validate = new CookieValidate();
            $param['add_time'] = time();
            $param['last_use_time'] = time();
            if (!$validate->check($param)) {
                return ['code' => -1, 'data' => '', 'msg' => $validate->getError()];
            }
            $updateNum = 0;
            $newNum = 0;
            $total = 0;
//            $cookieContentsArray = explode(PHP_EOL, $param['cookie_contents']);
            $cookieContentsArray = explode("\n", $param['cookie_contents']);
            if (is_array($cookieContentsArray)) {
                foreach ($cookieContentsArray as $key => $v) {
                    $v = 's_v_web_id=verify_l1ajlgnu_Ymx30kZZ_X9uJ_4fWe_9SOY_2hnr2ZGDdcIW;  passport_csrf_token=7b720517a639c63b2f9e93def8d8b51c;  passport_csrf_token_default=7b720517a639c63b2f9e93def8d8b51c;  d_ticket=4613d00a0d7cee49e5400f5cde943a0cea9a6;  n_mh=ZTZFiiWKrzklhzViHoddljtlDeDE1CwtPULaZy5Qnoo;  sso_auth_status=5e69e268b88b8737cd1abd7da22fc53d;  sso_auth_status_ss=5e69e268b88b8737cd1abd7da22fc53d;  sso_uid_tt=790456e9b3db8f990fab6904c307ba34;  sso_uid_tt_ss=790456e9b3db8f990fab6904c307ba34;  toutiao_sso_user=2c6b07168edbc9133a5c61075bfe4359;  toutiao_sso_user_ss=2c6b07168edbc9133a5c61075bfe4359;  uid_tt=790456e9b3db8f990fab6904c307ba34;  uid_tt_ss=790456e9b3db8f990fab6904c307ba34;  sid_tt=2c6b07168edbc9133a5c61075bfe4359;  sessionid=2c6b07168edbc9133a5c61075bfe4359;  sessionid_ss=2c6b07168edbc9133a5c61075bfe4359;  ttcid=16029a0e6cf94a0783115b09ddff479d21;  passport_auth_status=2b4819934a1fe404466e3c93d7b88f01%2C39c8ece5d0de8319169cdf3d2e921ca2;  passport_auth_status_ss=2b4819934a1fe404466e3c93d7b88f01%2C39c8ece5d0de8319169cdf3d2e921ca2;  sid_ucp_sso_v1=1.0.0-KGVmMWZlNzE2ZDk0YTk3MWUxMTlhMjIxM2U2YmQ0YzgyYjVjODYzOWEKHwjAr5CWlvTTAxCDkYaSBhiWTiAMMJj62PgFOAJA8QcaAmxmIiAyYzZiMDcxNjhlZGJjOTEzM2E1YzYxMDc1YmZlNDM1OQ;  ssid_ucp_sso_v1=1.0.0-KGVmMWZlNzE2ZDk0YTk3MWUxMTlhMjIxM2U2YmQ0YzgyYjVjODYzOWEKHwjAr5CWlvTTAxCDkYaSBhiWTiAMMJj62PgFOAJA8QcaAmxmIiAyYzZiMDcxNjhlZGJjOTEzM2E1YzYxMDc1YmZlNDM1OQ;  sid_guard=2c6b07168edbc9133a5c61075bfe4359%7C1648461956%7C5184000%7CFri%2C+27-May-2022+10%3A05%3A56+GMT;  sid_ucp_v1=1.0.0-KDliNGM3ZmE4ZjNjYWEyYjlkOWJkNjgxNzc3Y2JlMmIzMDY2ZWE3OWYKHwjAr5CWlvTTAxCEkYaSBhiWTiAMMJj62PgFOAJA8QcaAmxxIiAyYzZiMDcxNjhlZGJjOTEzM2E1YzYxMDc1YmZlNDM1OQ;  ssid_ucp_v1=1.0.0-KDliNGM3ZmE4ZjNjYWEyYjlkOWJkNjgxNzc3Y2JlMmIzMDY2ZWE3OWYKHwjAr5CWlvTTAxCEkYaSBhiWTiAMMJj62PgFOAJA8QcaAmxxIiAyYzZiMDcxNjhlZGJjOTEzM2E1YzYxMDc1YmZlNDM1OQ;  odin_tt=34334635d6a6a8c364c19ee740a5da612096c9d8917be3e495d70415ae2d465dfdaf38d782e7a55e6026356ca404b3090eb9946f090f42057d97b73362d3c475;  tt_scid=f.8YP8XlAJN-oZyDXg56yh4jgOdmHGTgl6hZcRcHIbZoE2aS8hqa6xRwwp.MWZUi3585;  MONITOR_WEB_ID=501d02a1-aac5-44e9-b579-7d2883001e75;  msToken=sfUpbhPxWfs7Oo8qNXK2nDEduQtDdWAQmzZEhrYQ-s7wyZiKYtLT-V0vb_BgKvuaFZ7AGPt4iGQ7GoEoYae5DjsJSKVrg9eEzMOCmvNPMCzU2agsIMODmD7h3yrzLT5x;  ttwid=1%7CKjghJAYvBrbaKjmZ6-KFD5N9RAVaTxkrKE7qJl8zfrs%7C1648647933%7C8544ccc36224113fef831f8cb73c8c38f533de1edc0d0cc3b0043459991a84ef;  msToken=t_2TgsXuhnmOhhsQD3TW3erdpaGbXQwCD9kLPuydY06C25zCAD0w0DHisbm5Fff4AlQSN-ve8NxCTrrdiocLLKgnJZv5vejkv0us9C7o9-iMkCoHMUZQFLZoRzdUyvnr; ';
                    $getCookieAccount = getCookieAccount($v);
//                    var_dump($getCookieAccount);
//                    exit;
                    if ($getCookieAccount) {
                        $addCookieParam['last_use_time'] = date("Y-m-d H:i:S", time());
                        $addCookieParam['cookie'] = $v;
                        $addCookieParam['cookie_sign'] = $param['cookie_sign'];
                        $addCookieParam['account'] = $getCookieAccount;
                        $res = $cookie->addCookie($addCookieParam);
                        //更新+1
                        if ($res['code'] == 1) {
                            $updateNum++;
                        }
                        //新增+1
                        if ($res['code'] == 0) {
                            $newNum++;
                        }
                    }
                    $total++;
                }
            }
//            $return = modelReMsg(0, '', '总：' . $total . "其中新增：" . $newNum . "覆盖：" . $updateNum);
            Log::write($param['cookie_sign'] . ',添加COOKIES：总：' . $total . "其中新增：" . $newNum . "覆盖：" . $updateNum);

            return json(modelReMsg(0, '', '总：' . $total . "其中新增：" . $newNum . "覆盖：" . $updateNum));
        }

        return $this->fetch('add');
    }
}