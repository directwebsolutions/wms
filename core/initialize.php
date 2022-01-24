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
 * Initialize.php
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

// Check if ALLOW_ACCESS has been defined on the file calling the engine
if (!defined("ALLOW_ACCESS")) {
    http_response_code(403);
	die("This file has not been defined for access rules and is currently unavailable.");
}

// If ALLOW_ACCESS has been set to false, block access to the system and end script
if (!ALLOW_ACCESS) {
    http_response_code(403);
	die("Direct access of this file has been blocked.");
}

// Define the ROOT_DIR variable. This will be used on all included filed from now on
if (!defined("ROOT_DIR")) {
	define("ROOT_DIR", dirname(dirname(__FILE__)) . "/");
}

// If the user hasn't set CURRENT_SCRIPT, we lable it as undefined for now
if (!defined("CURRENT_SCRIPT")) {
	define("CURRENT_SCRIPT", 'undefined');
}

// Load the script timer to track how long a script takes to load.
require_once ROOT_DIR . "core/class_timer.php";
$timer = new Timer();

// If the system doesn't support json_encode or json_decode, we will make it
if (!function_exists('json_encode') || !function_exists('json_decode')) {
	require_once ROOT_DIR . "core/class_json.php";
}

// Include the configuration file and set it as an object
if (file_exists(ROOT_DIR . "core/ini_configuration.php")) {
    require_once ROOT_DIR . "core/ini_configuration.php";
    $config = new Configuration;
} else {
    die("Configuration file is missing and cannot be found. System cannot continue.");
}

// If we can, lets set the systems timezone to the user defined timezone. If they
// have not set one or it is missing, we will just set it to GMT by default instead
if (function_exists("date_default_timezone_set")) {
    if (isset($config->general->default_timezone)) {
	    date_default_timezone_set($config->general->default_timezone); 
    } else {
        if (!ini_get("date.timezone")) {
            date_default_timezone_set("GMT");
        }
    }
}

// Set the CURRENT_TIME - this is used in a lot of scripts as well
if (!defined("CURRENT_TIME")) {
    define("CURRENT_TIME", time());
}

// Check that the install lock is not still in place - the installer should have
// removed this file but just in case, we will look. If it's there then the system
// either hasn't been installed yet or the file was left somehow.
if (file_exists(ROOT_DIR . "core/install.lock")) {
    die("The system has not been installed or the lock file still exists.");
}

// Include the global functions file. These are not script specific functions
require_once ROOT_DIR . "core/global_functions.php";

// Load the custom error_handler and set to display errors from configuration
require_once ROOT_DIR . "core/class_errors.php";
$error_handler = new ErrorEngine;
$error_handler->force_display_errors = $config->general->display_errors;

// Initialize the core engine of the WMS
require_once ROOT_DIR . "core/class_core.php";
$wms = new WMS;
$wms->config = &$config;
$wms->timer = &$timer;
$wms->errormanager = &$error_handler;

// If the admin panel location isn't set for some reason, we will default it
// back to the /panel/ directory
if (empty($wms->config->general->admin_directory)) {
	$wms->config->general->admin_directory = "panel";
}

// Import the datahandler - This is used during sessions and cookies as well as
// any othertime important system data needs to be implemented
require_once ROOT_DIR . "core/class_datahandler.php";

// Load the database handler and the specific type of database
require_once ROOT_DIR . "core/interface_database.php";
require_once ROOT_DIR . "core/db_" . $wms->config->database->type . ".php";
$db = new DatabaseInstance;
if (!extension_loaded($db->engine)) {
//	$wms->trigger_generic_error("sql_load_error");
}
define("TABLE_PREFIX", $wms->config->database->table_prefix);
$db->connect($wms->config->database->settings);
$db->set_table_prefix(TABLE_PREFIX);
$db->type = $wms->config->database->type;

// Import the language engine so we can have language versions for your website
// which makes translation of pages a lot easier as it can be done in batches
require_once ROOT_DIR . "core/class_language.php";
$language = new Lang;
$wms->lang = &$language;
$wms->lang->set_path(ROOT_DIR . "core/language");
$wms->lang->set_language($wms->config->general->language);

// Load the cookie handler class
require_once ROOT_DIR . "core/class_cookies.php";
$cookies = new CookieManager;
$wms->cookiemanager = &$cookies;
$wms->cookiemanager->parse_cookies($wms->config->cookies);

// Load the templating engine
require_once ROOT_DIR . "core/class_template.php";
$templates = new Templates($wms, $db);
$wms->templates = &$templates;

// Load the spiders class so we can detect and handle the creepy crawlers
require_once ROOT_DIR . "core/class_spiders.php";
$spiders = new Spiders;

// Some predefined date formats for easy use! :)
$date_formats = array(
	1 => "m-d-Y",
	2 => "m-d-y",
	3 => "m.d.Y",
	4 => "m.d.y",
	5 => "d-m-Y",
	6 => "d-m-y",
	7 => "d.m.Y",
	8 => "d.m.y",
	9 => "F jS, Y",
	10 => "l, F jS, Y",
	11 => "jS F, Y",
	12 => "l, jS F, Y",
	13 => "Y-m-d"
);

// Some predefined time formats for easy use! :)
$time_formats = array(
	1 => "h:i a",
	2 => "h:i A",
	3 => "H:i"
);

// Load the spiders class so we can detect and handle the creepy crawlers
require_once ROOT_DIR . "core/class_spiders.php";
$spiders = new Spiders;

// This is were we load the addon files you may want to install such as mods or
// your own addon extensions. We add them at the end so your system can tie into
// the system after it's all been initialized. Session hasn't been set yet so
// you will have to global to use those at the application layer, but in a custom
// script you can inject the session information there if you need too.

// Custom user defined functions
require_once ROOT_DIR . "core/user_defined_functions.php";