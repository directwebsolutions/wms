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
 * Global.php
 *
 *   Used to initialize the entire WMS structure for the service.
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

// Set the base working directory to here
$working_directory = dirname(__FILE__);

// If this didn't return for whatever reason, lets set it to .
if (!$working_directory) {
    $working_directory = '.';
}

// Require the core initialize. This is where everthing else is included from
require_once $working_directory . "/core/initialize.php";

// Set the current page, which will be usefull for the session manager
$current_page = sys_strtolower(basename(CURRENT_SCRIPT));

// Make sure this isn't calling the command line interface, in which case we
// don't want to initialzie a session for the CLI.
if (!defined("NO_SESSION")) {

    // Load the Session Manager core and implement it
    require_once ROOT_DIR . "core/class_session.php";
    $session = new SessionManager;
    $session->init($current_page, $db, $wms, $spiders);

    // Load the session as an object under the WMS class to keep everything together
    $wms->session = &$session;

}