<?php
declare(strict_types=1);

namespace App\%app_name%\Controllers;


class Main extends %app_name%
{

    function index()
    {
        $this->data['ep_ver'] = EP_VER;
        $this->data['url'] = $this->url('main', ['ver' => EP_VER]);
        $this->display();
    }

}