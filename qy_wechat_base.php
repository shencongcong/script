<?php

defined('CURL_SSLVERSION_DEFAULT') || define('CURL_SSLVERSION_DEFAULT', 0);

defined('CURL_SSLVERSION_TLSv1')   || define('CURL_SSLVERSION_TLSv1', 1);

defined('CURL_SSLVERSION_SSLv2')   || define('CURL_SSLVERSION_SSLv2', 2);

defined('CURL_SSLVERSION_SSLv3')   || define('CURL_SSLVERSION_SSLv3', 3);

class QyWechat
{
    const WECHAT_BASE_URL = 'https://qyapi.weixin.qq.com';

    /**
     * 数据缓存前缀
     * @var string
     */
    public $cachePrefix = 'cache_qywechat';

    /**
     * 数据缓存时长
     * @var int
     */
    public $cacheTime = 7000;

    /**
     * 返回错误码
     * @var array
     */
    public $lastError;

    /**
     * @var array
     */
    private $_accessToken;

    private $_jsapiTicket;

    public $corpid;

    public $corpsecret;

    private $redis;

    public function __construct($corpid = '', $corpsecret = '', $cachePrefix = '') {
       // require_once 'config.php';

//        $this->redis = new Redis();
//        $conn = $this->redis->connect($redis_config['host'], $redis_config['port']);
//
//        if (!$conn) {
//            $this->redis = false;
//        }

     /*   if ($corpid == '') {
            $this->corpid = $qy_wechat_config['corpid'];
            $this->corpsecret = $qy_wechat_config['corpsecret'];
        } else {
            $this->corpid = $corpid;
            $this->corpsecret = $corpsecret;
        }

        if ($cachePrefix != '') {
            $this->cachePrefix = $cachePrefix;
        }*/

        $this->corpid = $corpid;
        $this->corpsecret = $corpsecret;
    }

    /**
     * 请求微信服务器获取AccessToken
     * 返回值格式
     * [
     *      'access_token' => 'xxx',
     *      'expirs_in' => 7200,
     * ]
     */
    protected function requestAccessToken()
    {
        $result = $this->httpGet('/cgi-bin/gettoken', array(
            'corpid' => $this->corpid,
            'corpsecret' => $this->corpsecret,
        ));
        return isset($result['access_token']) ? $result['access_token'] : false;
    }

    protected function requestJsapiTicket()
    {
        $result = $this->httpGet('/cgi-bin/get_jsapi_ticket', array(
            'access_token' => $this->getAccessToken(),
        ));
        return isset($result['ticket']) ? $result['ticket'] : false;
    }

    /**
     * 获取AccessToken
     * @param bool $force 是否强制获取
     */
    public function getAccessToken($force = false)
    {
        $time = time();

        if ($this->_accessToken === null || $force) {
            $result = $this->_accessToken === null && !$force ? $this->getCache('access_token') : false;

            if ($result === false) {
                if (!($result = $this->requestAccessToken())) {
                    return false;
                }
                $this->setCache('access_token', $result, $this->cacheTime);
            }
            $this->_accessToken = $result;
        }

        return $this->_accessToken;
    }

    /**
     * 获取JS API的ticket
     * @param bool $force 是否强制获取
     */
    public function getJsTicket($force = false)
    {
        $time = time();

        if ($this->_jsapiTicket === null || $force) {
            $result = $this->_jsapiTicket === null && !$force ? $this->getCache('jsapi_ticket') : false;

            if ($result === false) {
                if (!($result = $this->requestJsapiTicket())) {
                    return false;
                }
                $this->setCache('jsapi_ticket', $result, $this->cacheTime);
            }
            $this->_jsapiTicket = $result;
        }

        return $this->_jsapiTicket;
    }

    /**
     * 微信数据缓存基本键值
     * @param $name
     * @return string
     */
    protected function getCacheKey($name)
    {
        return $this->cachePrefix . '_' . $name;
    }

    /**
     * 缓存微信数据
     * @param $name
     * @param $value
     * @param null $duration
     * @return bool
     */
    protected function setCache($name, $value, $duration = null)
    {
        $duration === null && $duration = $this->cacheTime;
        if ($this->redis) {
            $this->redis->set($this->getCacheKey($name), $value);
            $this->redis->expire($this->getCacheKey($name), $duration);

            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取微信缓存数据
     * @param $name
     */
    protected function getCache($name)
    {
        if ($this->redis) {
            return $this->redis->get($this->getCacheKey($name));
        } else {
            return false;
        }
    }

    public function parseHttpRequest(callable $callable, $url, $postOptions = null)
    {
        $result = call_user_func_array($callable, array($url, $postOptions));
        if (isset($result['errcode']) && $result['errcode']) {
            $this->lastError = $result;
        }

        return $result;
    }

    /**
     * Http基础库 使用该库请求微信服务器
     * @param $url
     * @param array $options
     * @return bool|mixed
     */
    protected function http($url, $options = array())
    {
        $defaultOptions = array(
            CURLOPT_URL => $url,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true,
        );

        if (stripos($url, 'https://') !== false) {
            $defaultOptions += array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1 // 微信官方屏蔽了ssl2和ssl3, 启用更高级的ssl
            );
        }
        $options += $defaultOptions;

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $content = curl_exec($curl);
        curl_close($curl);

        return json_decode($content, true) ?: false; // 正常加载应该是只返回json字符串
    }

    protected function httpBuildQuery($url, array $options)
    {
        if (stripos($url, 'http://') === false && stripos($url, 'https://') === false) {
            $url = self::WECHAT_BASE_URL . $url;
        }

        if (!empty($options)) {
            $url .= (stripos($url, '?') ? '&' : '?') . http_build_query($options);
        }

        return $url;
    }

    /**
     * Http Get请求
     * @param $url
     * @param array $options
     */
    public function httpGet($url, array $options = array())
    {
        $url = $this->httpBuildQuery($url, $options);

        return $this->http($url);
    }

    /**
     * Http Post请求
     * @param $url
     * @param array $postOptions
     * @param array $options
     */
    public function httpPost($url, array $postOptions, array $options = array())
    {
        $url = $this->httpBuildQuery($url, $options);

        return $this->http($url, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postOptions
        ));
    }

    /**
     * Http Raw请求
     * @param $url
     * @param array $postOptions
     * @param array $options
     */
    public function httpRaw($url, array $postOptions, array $options = array())
    {
        $url = $this->httpBuildQuery($url, $options);

        return $this->http($url, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => is_array($postOptions) ? json_encode($postOptions) : $postOptions
        ));
    }
}

?>