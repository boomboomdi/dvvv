<?php

namespace app\api\controller;

use app\admin\model\CookieModel;
use app\api\validate\OrderdouyindanValidate;
use app\api\validate\OrderinfoValidate;
use app\common\model\OrderdouyinModel;
use app\common\model\OrderModel;
use think\Db;
use think\facade\Log;
use think\Request;
use think\Controller;
use Zxing\QrReader;

class Orderdouyin extends Controller
{
    /**
     * 抖音下单四方正式入口
     * @param Request $request
     * @return void
     */
    public function order(Request $request)
    {
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);
        try {
            logs(json_encode(['message' => $message, 'line' => $message]), 'createOrder_fist');
            $validate = new OrderinfoValidate();
            if (!$validate->check($message)) {
                return apiJsonReturn(-1, '', $validate->getError());
            }
            $db = new Db();
            //验证商户
            $token = $db::table('bsa_merchant')->where('merchant_sign', '=', $message['merchant_sign'])->find()['token'];
            if (empty($token)) {
                return apiJsonReturn(10001, "商户验证失败！");
            }
            $sig = md5($message['merchant_sign'] . $message['order_no'] . $message['amount'] . $message['time'] . $token);
            if ($sig != $message['sign']) {
                logs(json_encode(['orderParam' => $message, 'doMd5' => $sig]), 'orderParam_signfail');
                return apiJsonReturn(10006, "验签失败！");
            }
            $orderFind = $db::table('bsa_order')->where('order_no', '=', $message['order_no'])->count();
            if ($orderFind > 0) {
                return apiJsonReturn(11001, "单号重复！");
            }

            //$user_id = $message['user_id'];  //用户标识
            // 根据user_id  未付款次数 限制下单 end

            $orderMe = guid12();
            for ($x = 0; $x <= 3; $x++) {
                $orderFind = $db::table('bsa_order')->where('order_me', '=', $orderMe)->find();
                if (empty($orderFind)) {
                    $orderMe = guid12();
                    break;
                } else {
                    continue;
                }
            }

            //1、入库
            $insertOrderData['merchant_sign'] = $message['merchant_sign'];  //商户
            $insertOrderData['order_no'] = $message['order_no'];  //商户订单号
            $insertOrderData['order_status'] = 3;  //  1、支付成功（下单成功）！2、支付失败（下单成功）！3、下单失败！4、等待支付（下单成功）！5、已手动回调。
            $insertOrderData['order_me'] = $orderMe; //本平台订单号
            $insertOrderData['amount'] = $message['amount']; //支付金额
            $insertOrderData['payable_amount'] = $message['amount'];  //应付金额
            $insertOrderData['payment'] = "douyin_ali"; //alipay
            $insertOrderData['add_time'] = time();  //入库时间
            $insertOrderData['notify_url'] = $message['notify_url']; //下单回调地址 notify url

            $orderModel = new \app\common\model\OrderModel();
            $createOrderOne = $orderModel->addOrder($insertOrderData);
            if (!isset($createOrderOne['code']) || $createOrderOne['code'] != '0') {
                return apiJsonReturn(10008, $createOrderOne['msg'] . $createOrderOne['code']);
            }
            //2、分配核销单
            $orderDouYinModel = new OrderdouyinModel();
            $getDouYinPayUrl['amount'] = $message['amount'];
            $getUseTorderUrlRes = $orderDouYinModel->getUseTorderUrl($getDouYinPayUrl, $orderMe);
            if ($getUseTorderUrlRes['code'] != 0) {
                //修改订单为下单失败状态。
                $updateOrderStatus['order_status'] = 3;
                $updateOrderStatus['update_time'] = time();
                $updateOrderStatus['order_desc'] = "下单失败|" . $getUseTorderUrlRes['msg'];
                $orderModel->where('order_no', $insertOrderData['order_no'])->update($updateOrderStatus);
                $lastSql = $orderModel->getLastSql();
                logs(json_encode(['getUseTorderUrlParam' => $message['amount'], 'getUseTorderUrlRes' => $getUseTorderUrlRes]), 'douyinorder_getUseTorderUrlRes');
                return apiJsonReturn(10010, $getUseTorderUrlRes['msg'], "");
            }

            $updateOrderStatus['order_status'] = 4;
            $updateOrderStatus['account'] = $getUseTorderUrlRes['data']['account'];
            $updateOrderStatus['studio_sign'] = $getUseTorderUrlRes['data']['write_off_sign'];
            $updateOrderStatus['qr_url'] = $getUseTorderUrlRes['data']['pay_url'];   //支付订单
            $updateOrderStatus['order_pay'] = $getUseTorderUrlRes['data']['order_pay']; //抖音订单
            $updateOrderStatus['order_desc'] = "下单成功|" . $getUseTorderUrlRes['msg'];
            $orderModel->where('order_no', '=', $insertOrderData['order_no'])->update($updateOrderStatus);
            return apiJsonReturn(10000, "下单成功", $updateOrderStatus['qr_url']);

        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(),
                'line' => $error->getLine(),
                'errorMessage' => $error->getMessage()
            ]), 'createOrderError');
            return json(msg(-22, '', 'create order error!' . $error->getMessage() . $error->getLine()));
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'douyin_order_exception');
            return json(msg(-11, '', 'create order Exception!' . $exception->getMessage() . $exception->getFile() . $exception->getLine()));
        }
    }

    public function testDouyinOrder()
    {
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);
        $createParam = [];
        if (isset($message['amount']) && is_float($message['amount'])) {
            $createParam['amount'] = $message['amount'];
        } else {
            $createParam['amount'] = 1;
        }
        $createParam['ck'] = "s_v_web_id=verify_l1ajlgnu_Ymx30kZZ_X9uJ_4fWe_9SOY_2hnr2ZGDdcIW;  passport_csrf_token=7b720517a639c63b2f9e93def8d8b51c;  passport_csrf_token_default=7b720517a639c63b2f9e93def8d8b51c;  d_ticket=4613d00a0d7cee49e5400f5cde943a0cea9a6;  n_mh=ZTZFiiWKrzklhzViHoddljtlDeDE1CwtPULaZy5Qnoo;  sso_auth_status=5e69e268b88b8737cd1abd7da22fc53d;  sso_auth_status_ss=5e69e268b88b8737cd1abd7da22fc53d;  sso_uid_tt=790456e9b3db8f990fab6904c307ba34;  sso_uid_tt_ss=790456e9b3db8f990fab6904c307ba34;  toutiao_sso_user=2c6b07168edbc9133a5c61075bfe4359;  toutiao_sso_user_ss=2c6b07168edbc9133a5c61075bfe4359;  uid_tt=790456e9b3db8f990fab6904c307ba34;  uid_tt_ss=790456e9b3db8f990fab6904c307ba34;  sid_tt=2c6b07168edbc9133a5c61075bfe4359;  sessionid=2c6b07168edbc9133a5c61075bfe4359;  sessionid_ss=2c6b07168edbc9133a5c61075bfe4359;  ttcid=16029a0e6cf94a0783115b09ddff479d21;  passport_auth_status=2b4819934a1fe404466e3c93d7b88f01%2C39c8ece5d0de8319169cdf3d2e921ca2;  passport_auth_status_ss=2b4819934a1fe404466e3c93d7b88f01%2C39c8ece5d0de8319169cdf3d2e921ca2;  sid_ucp_sso_v1=1.0.0-KGVmMWZlNzE2ZDk0YTk3MWUxMTlhMjIxM2U2YmQ0YzgyYjVjODYzOWEKHwjAr5CWlvTTAxCDkYaSBhiWTiAMMJj62PgFOAJA8QcaAmxmIiAyYzZiMDcxNjhlZGJjOTEzM2E1YzYxMDc1YmZlNDM1OQ;  ssid_ucp_sso_v1=1.0.0-KGVmMWZlNzE2ZDk0YTk3MWUxMTlhMjIxM2U2YmQ0YzgyYjVjODYzOWEKHwjAr5CWlvTTAxCDkYaSBhiWTiAMMJj62PgFOAJA8QcaAmxmIiAyYzZiMDcxNjhlZGJjOTEzM2E1YzYxMDc1YmZlNDM1OQ;  sid_guard=2c6b07168edbc9133a5c61075bfe4359%7C1648461956%7C5184000%7CFri%2C+27-May-2022+10%3A05%3A56+GMT;  sid_ucp_v1=1.0.0-KDliNGM3ZmE4ZjNjYWEyYjlkOWJkNjgxNzc3Y2JlMmIzMDY2ZWE3OWYKHwjAr5CWlvTTAxCEkYaSBhiWTiAMMJj62PgFOAJA8QcaAmxxIiAyYzZiMDcxNjhlZGJjOTEzM2E1YzYxMDc1YmZlNDM1OQ;  ssid_ucp_v1=1.0.0-KDliNGM3ZmE4ZjNjYWEyYjlkOWJkNjgxNzc3Y2JlMmIzMDY2ZWE3OWYKHwjAr5CWlvTTAxCEkYaSBhiWTiAMMJj62PgFOAJA8QcaAmxxIiAyYzZiMDcxNjhlZGJjOTEzM2E1YzYxMDc1YmZlNDM1OQ;  odin_tt=34334635d6a6a8c364c19ee740a5da612096c9d8917be3e495d70415ae2d465dfdaf38d782e7a55e6026356ca404b3090eb9946f090f42057d97b73362d3c475;  tt_scid=f.8YP8XlAJN-oZyDXg56yh4jgOdmHGTgl6hZcRcHIbZoE2aS8hqa6xRwwp.MWZUi3585;  MONITOR_WEB_ID=501d02a1-aac5-44e9-b579-7d2883001e75;  msToken=sfUpbhPxWfs7Oo8qNXK2nDEduQtDdWAQmzZEhrYQ-s7wyZiKYtLT-V0vb_BgKvuaFZ7AGPt4iGQ7GoEoYae5DjsJSKVrg9eEzMOCmvNPMCzU2agsIMODmD7h3yrzLT5x;  ttwid=1%7CKjghJAYvBrbaKjmZ6-KFD5N9RAVaTxkrKE7qJl8zfrs%7C1648647933%7C8544ccc36224113fef831f8cb73c8c38f533de1edc0d0cc3b0043459991a84ef;  msToken=t_2TgsXuhnmOhhsQD3TW3erdpaGbXQwCD9kLPuydY06C25zCAD0w0DHisbm5Fff4AlQSN-ve8NxCTrrdiocLLKgnJZv5vejkv0us9C7o9-iMkCoHMUZQFLZoRzdUyvnr; ";
        $createParam['account'] = 13283544162;
        $notifyResult = curlPostJson("http://127.0.0.1:23946/createOrder", $createParam);

        Log::log('1', "notify merchant order " . json_encode($notifyResult));
        $result = json_decode($notifyResult, true);
        var_dump($result);
        exit;
    }

    /**
     * 更新抖音预拉单订单
     * @return string|void
     */
    public function updatePrepareOrder0076()
    {
        $data = @file_get_contents('php://input');
        $data = json_decode($data, true);

        $returnCode = 3;
        $msg = "失败！";
        $db = new Db();
        $db::startTrans();
        $notifyResult = $data;
        try {
            $validate = new OrderdouyindanValidate();
            if (!$validate->check($notifyResult)) {
                logs(json_encode(['startTime' => date("Y-m-d H:i:s", time()), "notifyParam" => $notifyResult]), 'updatePrepareOrder_log');
                return "参数格式有误" . $validate->getError();
                return json(msg(-1, '', $validate->getError()));
            }
            logs(json_encode([
                    'postTime' => date("Y-m-d H:i:s", time()),
                    "notifyParam" => $notifyResult]
            ), 'updatePrepareOrder_log');

            $updateWhere['account'] = $notifyResult['account'];
            $updateWhere['order_no'] = (string)$notifyResult['order_no'];
            $updateWhere['weight'] = 1;
            $info = $db::table('bsa_torder_douyin')->where($updateWhere)->lock(true)->find();
            if (!$info) {
                $lastSql = $db::table("bsa_torder_douyin")->getLastSql();
                logs(json_encode(['startTime' => date("Y-m-d H:i:s", time()), "info" => $info, "lastSql" => $lastSql]), 'updatePrepareOrder_log');
                $db::rollback();
                return "暂无此催单";
//                return modelReMsg(-2, '', '暂无此催单！');
            }

            if (isset($notifyResult['code']) && $notifyResult['code'] == 0) {
                if (empty($notifyResult['order_id']) || empty($notifyResult['ali_url']) || empty($notifyResult['order_url'])) {
                    $db::rollback();
                    logs(json_encode(['message' => $notifyResult, 'order_pay' => "order_pay_no_null"]), 'updatePrepareOrder_log');
                    return "参数错误！";
                }
                $returnCode = 0;
                $msg = "下单成功！";
                //下单成功！
                $update['pay_url'] = $notifyResult['ali_url'];
                $update['check_url'] = $notifyResult['order_url'];
                $update['order_pay'] = (string)$notifyResult['order_id'];
                $update['use_times'] = $info['use_times'] + 1;
                $update['get_url_time'] = time();
                $update['status'] = 1;
                $update['url_status'] = 1;
                $update['order_status'] = 0;
                $update['order_desc'] = "预拉成功|等待匹配";
                $db::table("bsa_torder_douyin")->where($updateWhere)->update($update);
            }
            $cookieModel = new CookieModel();
            if (isset($notifyResult['code']) && $notifyResult['code'] == 1) {
                $returnCode = 1;
                $msg = "下单失败，ck失效！";
                //下单失败！
                $updateCookieWhere['account'] = $info['ck_account'];
                $updateCookieParam['status'] = 2;
                $cookieModel->editCookie($updateCookieWhere, $updateCookieParam);
            }
            if (isset($notifyResult['code']) && $notifyResult['code'] == 4) {
                $returnCode = 4;
                $msg = "下单失败，账号无法预拉！";
                //下单失败！
                $update['status'] = 2;  //推单使用状态终结
                $update['url_status'] = 1;  //已经请求
//                    $update['get_url_time'] = time();
                $update['order_status'] = 0;   //等待付款 --等待通知核销
                $update['order_desc'] = "拉单失败|" . $notifyResult['msg'];
                $db::table("bsa_torder_douyin")->where($updateWhere)->update($update);
                $db::commit();
            }
            $updateWeight['weight'] = 0;
            $updateWeightRes = $db::table("bsa_torder_douyin")->where($updateWhere)->update($updateWeight);
            if (!$updateWeightRes) {
                $db::rollback();
                return "update Torder fail";
                logs(json_encode(['order_no' => $notifyResult['order_no'], 'updateWeightRes' => $updateWeightRes]), 'updatePrepareOrder_log');
            }
            $db::commit();
            return "success";

        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(), 'line' => $error->getLine(), 'errorMessage' => $error->getMessage()]), 'douyin_order_error');

        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(), 'line' => $exception->getLine(), 'errorMessage' => $exception->getMessage()]), 'douyin_order_exception');
            return "order exception";
        }
    }

    /**
     * 接受回调数据
     * @return array|bool
     */
    public function callbackOrder0077()
    {
        $data = @file_get_contents('php://input');
        $message = json_decode($data, true);
        if (!isset($message['order_id']) || !isset($message['code'])) {
            return apiJsonReturn('-1', "格式有误！");
        }
        $orderDYModel = new OrderdouyinModel();
        $orderDYWhere['order_pay'] = $message['order_id'];
        $orderDYData = $orderDYModel->where($orderDYWhere)->find();
        if (empty($orderDYData)) {
            return apiJsonReturn('-2', "无此推单！");
        }
        if ($orderDYData['order_status'] == 1) {
            return apiJsonReturn('-3', "该订单已收到！");
        }
        try {
            //code==1  支付成功！
            if (isset($message['code']) && $message['code'] == 1) {
                $orderWhere['order_me'] = $orderDYData['order_me'];
                $orderDYUpdate['order_status'] = 1;  //匹配订单支付成功
                $orderDYUpdate['status'] = 2;   //推单改为最终结束状态
                $orderDYUpdate['pay_time'] = time();
                $orderDYUpdate['last_use_time'] = time();
                $orderDYUpdate['success_amount'] = $orderDYData['total_amount'];
                $orderDYUpdate['order_desc'] = "支付成功|匹配成功|待回调！";
                $orderDYUpdateRes = $orderDYModel->updateNotifyTorder($orderWhere, $orderDYUpdate);
                if (isset($orderDYUpdateRes['code']) && $orderDYUpdateRes['code'] == 0) {
                    $notifyWriteOffRes = $orderDYModel->orderDouYinNotifyToWriteOff($orderDYData);
                    if (!isset($notifyWriteOffRes['code']) || $notifyWriteOffRes['code'] != 0) {
                        logs(json_encode(['order_pay' => $orderDYData['order_pay'],
                                'notifyWriteOffRes' => $notifyWriteOffRes,
                                "time" => date("Y-m-d H:i:s", time())])
                            , 'callbackOrder0077');
                    } else {
                        //支付成功
                        $order = Db::table("bsa_order")->where($orderWhere)->find();
                        $orderModel = new OrderModel();
                        if ($order) {
                            $res = $orderModel->orderNotify($order);
                            if ($res) {
                                logs(json_encode(['order' => $order, 'orderNotifyRes' => $res, "sql" => Db::table("bsa_order")->getLastSql(), "time" => date("Y-m-d H:i:s", time())]), 'Timecheckdouyin_notify_log2');
                            }
                        }
                    }
                } else {
                    logs(json_encode([
                            'orderWhere' => $orderWhere,
                            'orderDYUpdate' => $orderDYUpdate,
                            '$orderDYUpdateRes' => $orderDYUpdateRes,
                        ]
                    ), 'updateNotifyTorderFail');
                }
            }
            //支付链接不可用
            if (isset($message['code']) && $message['code'] == 2) {
                $orderDYUpdate['last_use_time'] = time();
                $orderDYUpdate['status'] = 2;   //订单终结状态
                $orderDYUpdate['url_status'] = 2;   //订单已失效 以停止查询
                $orderDYUpdate['order_desc'] = "下单成功|匹配成功|订单失效";
                $orderDYModel->updateNotifyTorder($orderDYWhere, $orderDYUpdate);
            }

            return apiJsonReturn(0, "已收到通知！");
        } catch (\Exception $exception) {
            logs(json_encode(['file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'errorMessage' => $exception->getMessage()]
            ), 'callbackOrder0077Exception');
            return modelReMsg('-11', "", "回调失败" . $exception->getMessage());
        } catch (\Error $error) {
            logs(json_encode(['file' => $error->getFile(),
                    'line' => $error->getLine(),
                    'errorMessage' => $error->getMessage()]
            ), 'callbackOrder0077Error');
            return modelReMsg('-22', "", "回调失败" . $error->getMessage());

        }
    }
}