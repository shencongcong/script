#!/bin/bash


#check root user
if [ $(id -u) != "0" ]
then
        echo "Not the root user! Try using sudo command!"
        exit 1
fi

netstat -anop | grep 0.0.0.0:80

if [ $? -eq 1 ]
then
       echo $(date +%T%n%F)" systemctl restart nginx " >> nginx.log
       systemctl start nginx
fi
#  0.0.0.0 根据实际的php-fpm.conf 中的配置 有可能是127.0.0.1
netstat -anop | grep 0.0.0.0:9000

if [ $? -eq 1 ]
then
       echo $(date +%T%n%F)" systemctl restart php-fpm " >> nginx.log
        # 更具实际的启动命令确定
       systemctl start php-fpm
fi


