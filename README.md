使用内置脚本快速安装项目结构

git clone https://github.com/j9cn/ep.git

cd ep/init

php -f initProject.php `make` `PROJECT_NAME.APP_NAME[default: Admin]`

[@See 查看](initProject.md)

## 注意:
通过脚本建立的项目结构的DEV.php配置文件线上部署一定要删除
(默认情况下, 此文件已经存在.gitignore内,不会提交上去仓库)
如果直接上传代码到服务器一定要删除` ProjectPath/Config/DEV.php `

#### [DEV.php](Framework/Core/Develop.php#L17) 配置文件作用于开发环境
See [DEV.php](Framework/Core/Develop.php#L17)
```php
return [
    # 是否开启在模版中调用打印开发数据
    'debug' => true,
    # 是否在开发环境中自动创建模版
    'auto_create_tpl' => true,
    # 是否能自动在控制器(controllers)中创建请求的方法(action)
    'auto_add_action' => true,
    # 是否能自动创建控制器
    'auto_add_controller' => true
];
```

## 结构
```text
    ┌ EP/
    │     Framework/  #框架核心
    │     int/        #快速生成项目结构
    │         initProject.php  #安装脚本
    │     frame.php  #项目引用框架主要桥梁
    │    
    └ Project/
               App/
               .... Admin/
               ........... Controllers/  #业务逻辑控制器
               ........................ Admin.php
               ........................ Main.php    # 访问admin.website.com/ 相当于调用此逻辑里的function index() 
               ........... Templates/    #模版
               ........................ default/  #可通过业务逻辑控制器在不同场景显示不同模版
               ................................. main/
               ...................................... index.phtml  
               ................................. default.layer.phtml
               ................................. login.layer.phtml
               ................................. user.layer.phtml
               ........................ H5/       #可通过逻辑设置桌面或移动端模版
               ........................ english/  #也可以通过不同访问者设置不同语言模版
               ........................ spain/
               ........................ *more templates...
               ........... Views/
               .................. MainView.php  # 数据显示(过滤)控制器,可指定不同的模版, 模版里可调用里面的逻辑方法
               ........... Admin.init.php   #Admin独立优先配置可覆盖全局配置
               ........... init.php     #全局配置
               .... Web/
                         *...
               .... Api/
                         *...
               .... *more apps...
               Config/
               ...... db.config.php  #数据库配置文件
               ...... oss.config.php 
               ...... author.config.php  #各种第三方授权登录key
               ...... api.config.php  #各种接口授权登录key
               ...... *more config...
               CustomLibrary/   #自定义类库存放目录
               .............  CustomLibrary.php #使用命名空间 namespace CustomLibrary;
               .............  *more library...
               Modules/
               ....... SystemModule.php #数据模块及数据逻辑控制层
               ....... AccountModule.php #可供多APP共同调用,不用重复编写SQL
               ....... *more module...
               htdocs/
               ...... admin/ #Directory of Document root 配置web service入口根目录(拒绝访问逻辑层代码)
               ............ assets #静态文件存放目录 css,js,images
               ............ .htaccess #apache Rewrite
               ............ index.php #主入口文件, 引入 project.php ; EP::loadApp('Admin')->run();
                                       在不配置多个子域名的情况下可通过逻辑运行不同的APP
               ...... web/
               ...... api/
               ...... *more app...
               project.php  #在此文件引用ep/frame.php 桥接文件
               
```

