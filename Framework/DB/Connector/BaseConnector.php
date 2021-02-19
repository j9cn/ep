<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/11/27
 * Time: 18:34
 */
declare(strict_types=1);

namespace EP\DB\Connector;

use PDO;
use EP\I\PDOConnector;


abstract class BaseConnector implements PDOConnector
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * 合并用户输入的options
     *
     * @param array $default_options
     * @param array $options
     *
     * @return array
     */
    protected static function getOptions(array $default_options, array $options)
    {
        if (!empty($options)) {
            foreach ($options as $option_key => $option_val) {
                $default_options[$option_key] = $option_val;
            }
        }

        return $default_options;
    }

}