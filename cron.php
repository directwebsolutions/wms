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
 * Cron.php
 *
 *   Run automated worker tasks at set intervals.
 *
 * @category    Core
 * @package     All
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 3.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */

if (!defined("ALLOW_ACCESS")) {
    define("ALLOW_ACCESS", TRUE);
}
if (!defined("CURRENT_SCRIPT")) {
    define("CURRENT_SCRIPT", "cron.php");
}
$cli = FALSE;
if (PHP_SAPI) {
    $initializer = strtolower(trim(PHP_SAPI));
    if ($initializer == "cli") {
        $cli = TRUE;
        if (!defined("NO_SESSION")) {
            define("NO_SESSION", TRUE);
        }
    }
}
require_once("global.php");
if ($cli) {
    
    // Check if a specific argument was passed on the cron job, this will be used
    // for plugins and such to run their own cron tasks at set intervals outside
    // the daily task list
    if (isset($_SERVER["argc"])) {
        if ($_SERVER["argc"] == 2) {
            $task_id = intval($_SERVER["argv"][1]);
            $task = $db->select("tasks", array("tid" => $task_id), "tid", NULL, 1);
            if ($task) {
                run_task($task["tid"], TRUE);
            }
        }
    }
    
    // Run all of the tasks that can be ran given their current timeout. We can
    // allow addons to implement their own cron based tasks using this method. We
    // by default add a cron job to run once every hour to check for available
    // tasks to complete and then fire the run_task() command without any variables
    // to have the system loop through every task
    run_task();
    die();
}
$templates->generate_error(403);