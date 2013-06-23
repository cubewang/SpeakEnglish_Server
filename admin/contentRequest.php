<?php
Header("Content-Type: text/html; charset=utf-8");
/**
 * Created by JetBrains PhpStorm.
 * User: YuWei
 * Date: 12-9-13
 * Time: 上午11:45
 * To change this template use File | Settings | File Templates.
 */
error_reporting(~0);
ini_set('display_errors', 1);

require_once '../plugins/ApiAccount/saetv2.ex.class.php';

$params = array();
$content = $_POST['content'];

$username = empty($_POST['username']) ? null : trim($_POST['username']);
$password = empty($_POST['password']) ? null : trim($_POST['password']);
$address = empty($_POST['url']) ? null : trim($_POST['url']);
$file1Size = empty($_FILES['media1']['size']) ? null : trim($_FILES['media1']['size']);
$file2Size = empty($_FILES['media2']['size']) ? null : trim($_FILES['media2']['size']);

if ($file1Size != null || $file2Size != null) {
    $tmp1 = empty($_FILES['media1']['tmp_name']) ? null : trim($_FILES['media1']['tmp_name']);
    $tmp2 = empty($_FILES['media2']['tmp_name']) ? null : trim($_FILES['media2']['tmp_name']);
    $error1 = $_FILES['media1']['error'];
    $error2 = $_FILES['media2']['error'];
    if (($file1Size + $file2Size) >= 8388608) {
        //TODO 根据服务器返回error码做提示
        echo("<script type=\"text/javascript\">parent.callback('提交失败 ! : 文件不得大于8M')</script>");
    }
    if ($tmp1 == null && $tmp2 != null) {
        $params['media'] = "@" . $tmp2;
    } else {
        $params['media'] = "@" . $tmp1;
        $params['media2'] = "@" . $tmp2;
    }
}

$params['status'] = $content;
if (!empty($address)) $params['url'] = $address;

$sinaNet = new Net();
$resCode = $sinaNet->request($username, $password, $params);

returnResMsgByCode($resCode);

/**
 * 根据responseCode返回对应的错误信息
 * @param $code
 */
function returnResMsgByCode($code)
{
    $msg = null;
    switch ($code) {
        case 200:
            $msg = "提交成功 !";
            break;
        case 401 :
            $msg = "提交失败 ! : 原因用户名或密码错误";
            break;
        default :
            $msg = "提交失败 ! ";
    }
    echo("<script type=\"text/javascript\">parent.callback('$msg')</script>");
}

class Net
{
    function request($username, $password, $params)
    {
        $sina = new SaeTClientV2(null, null, null);
        $sina->oauth->decode_json = false;
        $sina->oauth->timeout = 3600;

        $headers = array();
        $info = $username . ":" . $password;
        $authorization = "Basic " . base64_encode($info);
        $headers[] = "Authorization: $authorization";

        $url = 'http://localhost/statusnet/index.php/api/statuses/update.json';
        $response = $sina->oauth->post($url, $params, true, $headers);

        $statuscode = $sina->oauth->http_code;

        return $statuscode;
    }
}

?>