<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use tool\Auth;

/**
 * 生产密码
 * @param $password
 * @return string
 */
function makePassword($password)
{

    return md5($password . config('whisper.salt'));
}

/**
 * cookie  去除 douyin  account
 * @param $cookie
 * @return false|int
 */
function getCookieAccount($cookie)
{
    $cookie = str_replace(" ", '', $cookie);
    $start = strpos($cookie, 'sessionid=');
    $return = substr($cookie, $start, 42);
    return $return;
}

/**
 * 检测密码
 * @param $dbPassword
 * @param $inPassword
 * @return bool
 */
function checkPassword($inPassword, $dbPassword)
{

    return (makePassword($inPassword) == $dbPassword);
}

/**
 * 获取mysql 版本
 * @return string
 */
function getMysqlVersion()
{

    $conn = mysqli_connect(
        config('database.hostname') . ":" . config('database.hostport'),
        config('database.username'),
        config('database.password'),
        config('database.database')
    );

    return mysqli_get_server_info($conn);
}

/**
 * 生成layui子孙树
 * @param $data
 * @return array
 */
function makeTree($data)
{

    $res = [];
    $tree = [];

    // 整理数组
    foreach ($data as $key => $vo) {
        $res[$vo['id']] = $vo;
        $res[$vo['id']]['children'] = [];
    }
    unset($data);

    // 查询子孙
    foreach ($res as $key => $vo) {
        if ($vo['pid'] != 0) {
            $res[$vo['pid']]['children'][] = &$res[$key];
        }
    }

    // 去除杂质
//    var_dump($res);exit;
    foreach ($res as $key => $vo) {
        if ($vo['pid'] == 0) {
//            var_dump($vo);
            $tree[] = $vo;
        }
    }
    unset($res);

    return $tree;
}

/**
 * 打印调试函数
 * @param $data
 */
function dump($data)
{

    echo "<pre>";
    print_r($data);
}

/**
 * 标准返回
 * @param $code
 * @param $data
 * @param $msg
 * @return \think\response\Json
 */
function reMsg($code, $data, $msg)
{

    return json(['code' => $code, 'data' => $data, 'msg' => $msg]);
}

/**
 * model返回标准函数
 * @param $code
 * @param $data
 * @param $msg
 * @return array
 */
function modelReMsg($code, $data, $msg)
{

    return ['code' => $code, 'data' => $data, 'msg' => $msg];
}


/**
 * 根据ip定位
 * @param $ip
 * @return string
 * @throws Exception
 */
function getLocationByIp($ip)
{
    $ip2region = new \Ip2Region();
    $info = $ip2region->btreeSearch($ip);

    $info = explode('|', $info['region']);

    $address = '';
    foreach ($info as $vo) {
        if ('0' !== $vo) {
            $address .= $vo . '-';
        }
    }

    return rtrim($address, '-');
}

/**
 * 按钮检测
 * @param $input
 * @return bool
 */
function buttonAuth($input)
{
    $authModel = Auth::instance();
    return $authModel->authCheck($input, session('admin_role_id'));
}

/**
 * json_return
 * @param null $code
 * @param null $msg
 * @param null $data
 * @return bool
 */
function apiJsonReturn($code = null, $msg = null, $data = null)
{
    if ($data == null) {
        $dataNow['code'] = $code;
        $dataNow['msg'] = $msg;

    } else {
        $dataNow['code'] = $code;
        $dataNow['msg'] = $msg;
        $dataNow['data'] = $data;
    }
    return json_encode($dataNow);
}

/**
 * 生成唯一订单号码
 * @return string 23
 */
function guidForSelf()
{
    if (function_exists('com_create_guid') === true)
        return str_replace('-', '', trim(com_create_guid(), '{}'));

    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s%s%s%s%s%s%s', str_split(bin2hex($data), 4));
//    $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
//    return $yCode[intval(date('Y')) - 2011] . $yCode[intval(date('Y')) - rand(2011, 2019)] . strtoupper(dechex(date('m'))) . date('YmdHi') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}

/**
 * 生成操作按钮
 * @param array $operate 操作按钮数组
 */

use think\Cookie;
use think\Db;
use \GatewayWorker\Lib\Gateway;

/**
 * 统一返回信息
 * @param $code
 * @param $data
 * @param $msge
 */
function msg($code, $data, $msg)
{
    return compact('code', 'data', 'msg');
}

function curlPost($url = '', $postData = '', $options = array())
{
    if (is_array($postData)) {
        $postData = http_build_query($postData);
    }

    $ch = curl_init();
    $headers = [
        "Content-Type: application/json;charset=UTF-8",
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
    if (!empty($options)) {
        curl_setopt_array($ch, $options);
    }
    //https请求 不验证证书和host
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function curlPostJson($url = '', $postData = '', $options = array())
{
    if (is_array($postData)) {
        $postData = json_encode($postData);
    }

    $ch = curl_init();
    $headers = [
        "Content-Type: application/json;charset=UTF-8",
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20); //设置cURL允许执行的最长秒数
    if (!empty($options)) {
        curl_setopt_array($ch, $options);
    }
    //https请求 不验证证书和host
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

//成功率
function makeSuccessRate($success, $total)
{
    if ($total == 0 || $success == 0) {
        return "0.00%";
    }
    $successRate = round(((int)$success * 100) / (int)$total, 2);
//    var_dump($successRate);exit;
    return $success . "/" . $total . "~" . (string)$successRate . "%";
}

/**
 * 生成唯一测试订单号 Q开头
 * @return string 12
 */
function guid12()
{
    return 'T' . date('YmdHi') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 4, 7), 1))), 0, 8);
}

/**
 * 日志写入
 * @param $data : 数据
 * @param $fileName : 写入哪个日志
 * @return mixed
 */
function logs($data = null, $fileName = null)
{
    if (is_null($data) || is_null($fileName)) {
        $out_arr['code'] = '400004';
        return $out_arr;
    }

    $path = __DIR__ . '/../runtime/' . 'log/' . $fileName;

    if (!is_dir($path)) {
        $mkdir_re = mkdir($path, 0777, TRUE);
    }

    $filePath = $path . "/" . date("Y-m-d", time());

    $time = date("Y-m-d H:i:s", time());
    file_put_contents($filePath, $time . " " . var_export($data, TRUE) . "\r\n\r\n", FILE_APPEND);
}

/**
 * 验证中文名
 * @param $str
 * @return bool
 */
function isChinese($str)
{
    //新疆等少数民族可能有·
    if (strpos($str, '·')) {
        //将·去掉，看看剩下的是不是都是中文
        $str = str_replace("·", '', $str);
        if (preg_match('/^[\x7f-\xff]+$/', $str)) {
            return true;//全是中文
        } else {
            return false;//不全是中文
        }
    } else {
        if (preg_match('/^[\x7f-\xff]+$/', $str)) {
            return true;//全是中文
        } else {
            return false;//不全是中文
        }
    }
}

//验证地址
function validateURL($URL)
{
    $pattern_1 = "/^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+.(com|org|net|dk|at|us|tv|info|uk|co.uk|biz|se)$)(:(\d+))?\/?/i";
    $pattern_2 = "/^(www)((\.[A-Z0-9][A-Z0-9_-]*)+.(com|org|net|dk|at|us|tv|info|uk|co.uk|biz|se)$)(:(\d+))?\/?/i";
    if (preg_match($pattern_1, $URL) || preg_match($pattern_2, $URL)) {
        return true;
    } else {
        return false;
    }
}
