<?php
/**
 * Created by JetBrains PhpStorm.
 * User: YuWei
 * Date: 12-9-6
 * Time: 下午9:22
 * To change this template use File | Settings | File Templates.
 */
require_once('HttpRequestService.php');
class RenrenRestApiService extends HttpRequestService
{
    public $APIURL = 'http://api.renren.com/restserver.do'; //RenRen网的API调用地址，不需要修改
    public $APIKey = '9799b61fc602443690c5fad39e34bf9a'; //你的API Key，请自行申请
    public $SecretKey = 'e8e54cf6a70143328e2580e12f236555'; //你的API 密钥
    public $APIVersion = '1.0'; //当前API的版本号，不需要修改
    public $decodeFormat = 'json'; //默认的返回格式，根据实际情况修改，支持：json,xml

    private $_postFields = '';
    private $_params = array();
    private $_currentMethod;
    private static $_sigKey = 'sig';
    private $_sig = '';
    private $_call_id = '';

    private $_keyMapping = array(
        'api_key' => '',
        'method' => '',
        'v' => '',
        'format' => '',
    );

    public function __construct()
    {
        parent::__construct();

        if (empty($this->APIURL) || empty($this->APIKey) || empty($this->SecretKey)) {
            throw new exception('Invalid API URL or API key or Secret key, please check config.inc.php');
        }

    }

    /**
     * GET wrapper
     * @param method String
     * @param parameters Array
     * @return mixed
     */
    public function GET()
    {

        $args = func_get_args();
        $this->_currentMethod = trim($args[0]); #Method
        $this->paramsMerge($args[1])
            ->getCallId()
            ->setConfigToMapping()
            ->generateSignature();

        #Invoke
        unset($args);

        return $this->_GET($this->_config->APIURL, $this->_params);

    }

    /**
     * POST wrapper，基于curl函数，需要支持curl函数才行
     * @param method String
     * @param parameters Array
     * @return mixed
     */
    public function rr_post_curl()
    {

        $args = func_get_args();
        $this->_currentMethod = trim($args[0]); #Method
        $this->paramsMerge($args[1])
            ->getCallId()
            ->setConfigToMapping()
            ->generateSignature();

        #Invoke
        unset($args);

        return $this->_POST($this->APIURL, $this->_params);

    }

    /**
     * Generate signature for sig parameter
     * @param method String
     * @param parameters Array
     * @return RenRenClient
     */
    private function generateSignature()
    {
        $arr = array_merge($this->_params, $this->_keyMapping);
        ksort($arr);
        reset($arr);
        $str = '';
        foreach ($arr AS $k => $v) {
            $v = $this->convertEncoding($v, $this->_encode, "utf-8");
            $arr[$k] = $v; //转码，你懂得
            $str .= $k . '=' . $v;
        }

        $this->_params = $arr;
        $str = md5($str . $this->SecretKey);
        $this->_params[self::$_sigKey] = $str;
        $this->_sig = $str;

        unset($str, $arr);

        return $this;
    }


    /**
     * Parameters merge
     * @param $params Array
     * @modified by Edison tsai on 15:56 2011/01/13 for fix non-object bug
     * @return RenRenClient
     */
    private function paramsMerge($params)
    {
        $this->_params = $params;
        return $this;
    }

    /**
     * Setting mapping value
     * @modified by Edison tsai on 15:04 2011/01/13 for add call id & session_key
     * @return RenRenClient
     */
    private function setConfigToMapping()
    {
        $this->_keyMapping['api_key'] = $this->APIKey;
        $this->_keyMapping['method'] = $this->_currentMethod;
        $this->_keyMapping['v'] = $this->APIVersion;
        $this->_keyMapping['format'] = $this->decodeFormat;
        return $this;
    }

    private function setAPIURL($url)
    {
        $this->APIURL = $url;
    }

    /**
     * Generate call id
     * @author Edison tsai
     * @created 14:48 2011/01/13
     * @return RenRenClient
     */
    public function getCallId()
    {
        $this->_call_id = str_pad(mt_rand(1, 9999999999), 10, 0, STR_PAD_RIGHT);
        return $this;
    }

    public function rr_post_fopen()
    {

        $args = func_get_args();
        $this->_currentMethod = trim($args[0]); #Method
        $this->paramsMerge($args[1])
            ->getCallId()
            ->setConfigToMapping()
            ->generateSignature();

        #Invoke
        unset($args);

        return $this->_POST_FOPEN($this->APIURL, $this->_params);

    }

    public function rr_photo_post_fopen()
    {

        $args = func_get_args();
        $this->_currentMethod = trim($args[0]); #Method
        $this->paramsMerge($args[1])
            ->getCallId()
            ->setConfigToMapping()
            ->generateSignature();

        #Invoke
        $photo_files = $args[2];

        unset($args);
        return $this->_photoUpload($this->APIURL, $this->_params, $photo_files);

    }


}
