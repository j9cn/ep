使用内置脚本快速安装项目结构

git clone https://github.com/j9cn/ep.git

cd ep/init

php -f initProject.php `make` `PROJECT_NAME.APP_NAME[default: Admin]`

#eg:
`$ php -f initProject.php make epa`
```
 ..正在创建[ epa ]项目...
 ..D:\dev\php\epa\---> Ok
 ..创建目录[ D:\dev\php\epa\App\Admin ] ---> Ok
 ..创建目录[ D:\dev\php\epa\App\Admin\Controllers ] ---> Ok
 ..创建目录[ D:\dev\php\epa\App\Admin\Templates\default ] ---> Ok
 ..创建目录[ D:\dev\php\epa\App\Admin\Views ] ---> Ok
 ..创建目录[ D:\dev\php\epa\Config ] ---> Ok
 ..创建目录[ D:\dev\php\epa\htdocs\admin ] ---> Ok
 ..创建目录[ D:\dev\php\epa\Modules ] ---> Ok
 ..创建全局配置文件 [ D:\dev\php\epa\App\init.php ] ---> Ok
 ..创建APP配置文件 [ Admin.init.php ] ---> Ok
 ..创建文件 [ D:\dev\php\epa\htdocs\admin\index.php ] ---> Ok
 ..创建文件 [ D:\dev\php\epa\htdocs\admin\.htaccess ] ---> Ok
 ..创建文件 [ D:\dev\php\epa\project.php ] ---> Ok
 ..创建文件 [ D:\dev\php\epa\App\Admin\Controllers\Main.php ] ---> Ok
 ..创建文件 [ D:\dev\php\epa\App\Admin\Controllers\Admin.php ] ---> Ok
 ..创建文件 [ D:\dev\php\epa\App\Admin\Views\MainView.php ] ---> Ok
 ..创建文件 [ D:\dev\php\epa\App\Admin\Templates\default\default.layer.phtml ] ---> Ok
 ..创建文件 [ D:\dev\php\epa\App\Admin\Templates\default\main\ep_info.phtml ] ---> Ok
 ..创建文件 [ D:\dev\php\epa\Config\db.config.php ] ---> Ok
```

