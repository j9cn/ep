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
```
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

