<?php
/**
 * Created by PhpStorm.
 * User: danielshen
 * Date: 2019/1/17
 * Time: 12:08
 */

require_once  './qy_wechat_base.php';

$status = file_get_contents('config.txt');
if($status == 1){
  echo json_encode('1001','活动开始');
}


$code = isset($_GET['code']) ? $_GET['code'] : '';
if ($code) {
  $qy_wechat_config = array(
    'corpid' => 'WW5d3c61e3ab7c7f34',
    'corpsecret' => 'fycnaOSfbScrYNc3Cufjgw1vbQSafi86QRDrjeGIRyE',
    'cachePrefix' => 'daily_report_cache',
  );
  $wechat = new QyWechat($qy_wechat_config['corpid'], $qy_wechat_config['corpsecret'], $qy_wechat_config['cachePrefix']);
  $result = $wechat->httpGet('/cgi-bin/user/getuserinfo', array(
    'access_token' => $wechat->getAccessToken(),
    'code' => $code
  ));

  if(isset($result['errcode'] ) && $result['errcode'] == 0) {
    $user_info = $wechat->httpGet('/cgi-bin/user/get', array(
      'access_token' => $wechat->getAccessToken(),
      'userid' => $result['UserId']
    ));
    if(isset($user_info['errcode'] ) && $user_info['errcode'] == 0) {
      echo $user_info['avatar'];
      $avatar = $user_info['avatar'];
      file_put_contents('test.log',$user_info['avatar'],8);
      $res = [
        'code'=>0,
        'res' => $avatar,
      ];
      echo json_encode($res);
    }else{
      file_put_contents('test.log','cgi-bin/user/get error',8);
      echo json_encode('1002','微信接口异常');
    }

  } else {
    //echo 'user get error';
    file_put_contents('test.log','user get error',8);
    echo json_encode('1002','微信接口异常');

  }
} else {
  $redirect_uri = urlencode('https://hlcc.123u.com/test.php');
  $oauth_url = sprintf('Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=WW5d3c61e3ab7c7f34&redirect_uri=%s&response_type=code&scope=snsapi_base&agentid=%d&state=123#wechat_redirect', $redirect_uri, 1000053 );
  header($oauth_url);
}


