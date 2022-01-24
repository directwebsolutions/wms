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
 * Login Script
 *
 *   This script works with the Process.php script to handle user logins.
 *
 * @category    Core
 * @package     All
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 2.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */

// You must define if direct access to this file is allowed, and then the current
// script location (this will be used for user tracking on the session handler).
// We also have to check if it's already been defined as this can be called by
// another script and we don't want to throw any errors for redefining a constant
if (!defined("ALLOW_ACCESS")) {
    define("ALLOW_ACCESS", TRUE);
}

if (!defined("CURRENT_SCRIPT")) {
    define("CURRENT_SCRIPT", "login.php");
}

// Include the global script to enable the WMS engine in the background
require_once("global.php");

// Check if the user is already logged in first
if (isset($wms->user) && $wms->user->uid > 0) {
    $wms->templates->generate_error(409);
} else {
    if (isset($_GET["do"]) && $_GET["do"] == "forgot") {
        $wms->templates->forgot();
    } else {
        $wms->templates->login($wms->lang->data->templates->login_title, "information");
    }
    $wms->close($db, $templates);
}