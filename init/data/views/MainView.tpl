<?php
declare(strict_types=1);

namespace App\%app_name%\Views;


use EP\MVC\View;

class MainView extends View
{

    function index()
    {
        $this->renderTpl('main/ep_info');
    }

}