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
 * 500 - Internal Server Error
 *
 *   Built in internal server error for Apache loading the custom 500 script 
 *   using your sites specific loadout and generates a proper 500 header. This 
 *   needs to be set in your specific .htaccess to be used - we have included
 *   a mock file called editme.htaccess that you can edit to your specific setup
 *   for use
 *
 * @category    Error pages
 * @package     Errors
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 */

// You must define if direct access to this file is allowed, and then the current
// script location (this will be used for user tracking on the session handler)
define("ALLOW_ACCESS", TRUE);
define("CURRENT_SCRIPT", "500.php");

include_once("global.php");
$wms->templates->generate_error(500);
//$wms->templates->generate_error(500, "errors", "html");