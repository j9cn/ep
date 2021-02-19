<?php
/**
 * Created by PhpStorm.
 * User: JH
 * Date: 2017/11/27
 * Time: 20:03
 */
declare(strict_types=1);

namespace EP\DB\SQLAssembler;




class MySQLAssembler extends SQLAssembler
{
    /**
     * @see SQLAssembler::parseCondition()
     *
     * @param $operator
     * @param $field
     * @param $field_config
     * @param $is_mixed_field
     * @param $condition_connector
     * @param $connector
     * @param $params
     *
     * @return array
     */
    function parseCondition(
        $operator, $field, $field_config, $is_mixed_field, $condition_connector, $connector, array &$params
    ) {
        $condition = array();
        switch ($connector) {
            case 'FIND_IN_SET':
                $condition[" {$condition_connector} "][] = sprintf('FIND_IN_SET(?, %s)', $field);
                $params[] = $field_config;
                break;

            case 'REGEXP':
                $condition[" {$condition_connector} "][] = sprintf('%s REGEXP(?)', $field);
                $params[] = $field_config;
                break;

            case 'INSTR':
                $condition[" {$condition_connector} "][] = sprintf('INSTR(%s, ?)', $field);
                $params[] = $field_config;
                break;

            default:
                $condition = parent::parseCondition($operator, $field, $field_config, $is_mixed_field,
                    $condition_connector, $connector, $params);
        }

        return $condition;
    }

    /**
     * @return string
     */
    function forUpdate()
    {
        return "FOR UPDATE ";
    }

    /**
     * @return string
     */
    function lockInShareMode()
    {
        return "LOCK IN SHARE MODE ";
    }
}
