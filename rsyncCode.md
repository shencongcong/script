#!/bin/bash

# 代码同步到多台服务器上
# rsync 的参数参考 http://blog.51cto.com/colderboy/132054
IPS="212.64.107.47"

lock_file="/var/lock/rsync_passport_huanle_com"

if [ -f $lock_file ]; then
    exit;
fi
touch $lock_file

for IP in $IPS
do
    rsync -arpz --exclude=".git" --exclude="Log"  /data/web/passport.huanle.com/*  root@$IP:/data/www/passport.huanle.com
done


rm -rf $lock_file

