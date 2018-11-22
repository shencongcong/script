// 定义一些项目需要的配置文件
sshList = []
// 项目名称
program = "passport.huanle.com"
name = "passport.huanle.com"
//  项目在服务器上部署的位置
webPath =  ["ready":"/data/web"]
//  打包的位置
nfsPath = ["ready":"/data/nfs"]
// 项目的git库
gitUrl = ['passport.huanle.com':'git@git.intra.123u.com:php-projects/passport.huanle.com.git']
// ssh 的配置
sshNames = ["ready": "tc-ready"]



try{
    node() {
        // 拉取构建数据
        stage('PullData'){
            gitCheckOut(branch)
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



def gitCheckOut(branch) {
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
                rm -rf ${program}-${branch}
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
                cp -rf ${nfsPath[env]}/${program}-publish.tar ${nfsPath[env]}/${program} &&
                rm -rf ${nfsPath[env]}/${program}-publish.tar
            fi
        
            if [ -d ${nfsPath[env]}/${program}/${program}-${branch} ];then
                rm -rf ${nfsPath[env]}/${program}/${program}-${branch}
            fi
            
            if [ ! -d ${nfsPath[env]}/${program} ];then
                mkdir -p ${nfsPath[env]}/${program}
            fi
            
            cd ${nfsPath[env]}/${program} &&
            tar zxvf ${program}-publish.tar &&
            rm -rf ${nfsPath[env]}/${program}-publish.tar &&
            find ${nfsPath[env]}/${program} -maxdepth 1 -type d -mtime +12|xargs rm -rf &&
            rm -rf ${webPath[env]}/${program} && 
            ln -s "${nfsPath[env]}/${program}/${program}-${branch}" ${webPath[env]}/${program}
            
            """, 
            execTimeout: 120000, flatten: false, makeEmptyDirs: false, noDefaultExcludes: false, patternSeparator: '[, ]+', 
            remoteDirectory: '', remoteDirectorySDF: false, removePrefix: '', sourceFiles: '*.tar', useAgentForwarding: true)], 
                usePromotionTimestamp: false, useWorkspaceInPromotion: false, verbose: false)
}
