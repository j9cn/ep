<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/12/5
 * Time: 11:32
 */
define('PROJECT_PATH', __DIR__);

require "../frame.php";

EP::loadApp('project')->cliRun(2, [1, 'main.index']);