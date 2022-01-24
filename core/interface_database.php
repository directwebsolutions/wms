<?php
/**
 *                 ________  __      __  _________
 *                 \______ \/  \    /  \/   _____/
 *                   |    |  \   \/\/   /\_____  \
 *                   |    `   \        / /        \
 *                  /_______  /\__/\  / /_______  /
 *                          \/      \/          \/
 *             WMS - Website Management Software (c) 2022
 *                      by Direct Web Solutions
 *
 * DatabaseInterface
 *
 *   Control your database implementation - This is not specific to a certain
 *   database type and is just a generic controller to manage data to the
 *   specific database type
 *
 * @category    Core
 * @package     DatabaseManager
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 3.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */

interface DatabaseInterface {

    function connect($config);
    function get_execution_time();
    function set_table_prefix($prefix);
    function close();
    function escape_string($string);
    function escape_binary($string);
    function unescape_binary($string);
    function select_all_from($table, $use_prefix = "");
    function query($query, $bind_components);
    function select($table, $bind_components, $select = "", $orderby = "", $limit_results = "", $use_prefix = "");
    function insert($table, $bind_components, $ignore = "", $use_prefix = "");
    function delete($table, $where = "", $limit = "", $use_prefix = "", $wipe_table = "");
    function update($table, $bind_components, $where = "", $limit = "", $use_prefix = "");
    function error_number();
    function error_string();
    function error($string="");
    function affected_rows();

}
