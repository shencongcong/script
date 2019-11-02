
### 接口

1. 获取返回状态码
http://hlcc.123u.com/get_status.php

1:准备状态
2:起航状态
{"code":0,"res":"1"}

2. 设置状态码
http://hlcc.123u.com/set_status.php
参数 status
1 设置活动未开始
2 设置活动开始

3. 获取用户头像
http://hlcc.123u.com/act.php
code 0 正常
code 1001 活动未开始
code 1002 微信接口异常

### 脚本
restart_server.sh 监控nginx 和 php 如果挂了自己拉起
