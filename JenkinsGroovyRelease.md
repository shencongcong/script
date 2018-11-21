// 定义一些项目需要的配置文件
sshList = []
// 项目名称
program = "passport.huanle.com"
//  项目在服务器上部署的位置
webPath =  ["ready":"/data/web"]
// 项目的git库
gitUrl = ['passport.huanle.com':'git@git.intra.123u.com:php-projects/passport.huanle.com.git']
// ssh 的配置
sshNames = ["ready": "tc-ready"]



try{
    node() {
        // 拉取构建数据
        stage('PullData'){
            gitCheckOut('passport.huanle.com',branch)
        }
        // 构建发布代码
        stage('BuildCode'){
             buildPublishCode()
        }
        
        // 将构建的代码发布到不同的环境中
        stage('ReleaseCode'){
                        sshList.add(newSSHPublishers(ENV, branch))
                        sshPublisher continueOnError: true, failOnError: true,publishers: sshList
                        
                        sh """
                            rm -rf $WORKSPACE/${program}-publish.tar
                        """
        }
    }


}catch(err){
    echo "Caught an error :${err}"
    //currentBuild.result = "FAILURE"
}finally {
    // 无论是否捕获异常都会进入finally中
    //echo "currentBuild.result ${currentBuild.result}"
    //buildState = "end"
}



def gitCheckOut(name, branch) {
     sh """
            #判断文件夹是否存在
            if [ -d ${name} ] && [ "`ls -A ${name}/.git`" != "" ];then
                echo "workspace: ${name} ${branch} is checkouting"
                cd ${name} && 
                git fetch --all &&
                git checkout ${branch} &&
                git pull origin ${branch}
            else 
                echo "workspace: ${name} ${branch} is cloning"
                git clone ${gitUrl[name]} ${name} && cd ${name} &&  git checkout ${branch}
            fi
        """
}

def buildPublishCode(){
       sh """
            cd $WORKSPACE
                
            if [ -d ${program}-${branch} ];then
                rm -rf passport.huanle.com-${branch}
            fi
            
            mkdir ${program}-${branch} &&
            cd ${program}-${branch} &&
            cp -a -rf $WORKSPACE/${program}/. ./ &&
            rm -rf .git &&
            rm -f  .gitignore &&
            rm -f  .gitattributes &&
            cd .. &&
            tar czvf ${program}-publish.tar ${program}-${branch}
            if [ -d ${program}-${branch} ];then
                rm -rf ${program}-${branch}
            fi
        """
}

def newSSHPublishers(String env, String branch){

            return sshPublisherDesc(configName: "${sshNames[env]}", transfers: [sshTransfer(excludes: '', execCommand: """
            
            if [ "${env}" == "ready" ];then
                cp -rf /data/nfs/${program}-publish.tar /data/nfs/passport.huanle.com &&
                rm -rf /data/nfs/${program}-publish.tar
            fi
        
            if [ -d /data/nfs/${program}/${program}-${branch} ];then
                rm -rf data/nfs/${program}/${program}-${branch}
            fi
            
            if [ ! -d /data/nfs/${program} ];then
                mkdir -p /data/nfs/${program}
            fi
            
            cd /data/nfs/${program} &&
            tar zxvf ${program}-publish.tar &&
            rm -rf /data/nfs/${program}-publish.tar &&
            find /data/nfs/${program} -maxdepth 1 -type d -mtime +12|xargs rm -rf &&
            rm -rf ${webPath[env]}/${program} && 
            ln -s "/data/nfs/${program}/${program}-${branch}" ${webPath[env]}/${program}
            
            """, 
            execTimeout: 120000, flatten: false, makeEmptyDirs: false, noDefaultExcludes: false, patternSeparator: '[, ]+', 
            remoteDirectory: '', remoteDirectorySDF: false, removePrefix: '', sourceFiles: '*.tar', useAgentForwarding: true)], 
                usePromotionTimestamp: false, useWorkspaceInPromotion: false, verbose: false)
}
