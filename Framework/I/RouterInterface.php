<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/10/10
 * Time: 22:23
 */

namespace EP\I;


interface RouterInterface
{
    /**
     * @return mixed controller
     */
    function getController();

    /**
     * @return mixed action
     */
    function getAction();

    /**
     * @return mixed params
     */
    function getParams();
}