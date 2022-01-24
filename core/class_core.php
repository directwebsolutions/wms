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
 * Core Engine
 *
 *   The core engine of everything that is WMS
 *
 * @category    Core
 * @package     Engine
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 3.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */
 
class WMS {

    public $version = "3.0.0";
    public $version_code = 3000;
    public $current_working_directory = ".";
    public $cookies = array();
    public $user = array();
	public $usergroup = array();
	public $config = array();
	public $session = array();
	public $timer = array();
	public $magicquotes = 0;
	const INPUT_STRING = 0;
	const INPUT_INT = 1;
	const INPUT_ARRAY = 2;
	const INPUT_FLOAT = 3;
	const INPUT_BOOL = 4;
	public $ignore_clean_variables = array();
	
	public $binary_fields = array(
		"sessions" => array("ip" => FALSE),
		"users" => array("lastip" => FALSE)
	);

	// These are the type of variables your system considers clean
	public $clean_variables = array(
	    // The following must have an textual type to be "clean" and will
	    // only	accept lowercase a-z
		"a-z" => array(
			"sortby", "order"
		),
	    // The following must have an int type to be "clean" and will only
	    // accept an integer on database input
		"int" => array(
			"pid", "uid", "id", "sid", "tid"
		),
		// The following can take positions
		"pos" => array(
			"page", "perpage"
		)
	);
	
	function __construct() {
	    $protected = array("_GET", "_POST", "_SERVER", "_COOKIE", "_FILES", "_ENV", "GLOBALS");
	    foreach ($protected as $var) {
			if (isset($_POST[$var]) || isset($_GET[$var]) || isset($_COOKIE[$var]) || isset($_FILES[$var])) {
				die("Hacking attempt");
			}
		}
		if (defined("IGNORE_CLEAN_VARS")) {
			if (!is_array(IGNORE_CLEAN_VARS)) {
				$this->ignore_clean_variables = array(IGNORE_CLEAN_VARS);
			} else {
				$this->ignore_clean_variables = IGNORE_CLEAN_VARS;
			}
		}
		if (version_compare(PHP_VERSION, '6.0', '<')) {
			if (@get_magic_quotes_gpc()) {
				$this->magicquotes = 1;
				$this->strip_slashes_array($_POST);
				$this->strip_slashes_array($_GET);
				$this->strip_slashes_array($_COOKIE);
			}
			@set_magic_quotes_runtime(0);
			@ini_set("magic_quotes_gpc", 0);
			@ini_set("magic_quotes_runtime", 0);
		}
		$this->parse_incoming($_GET);
		$this->parse_incoming($_POST);
		if (isset($_SERVER["REQUEST_METHOD"])) {
    		if ($_SERVER["REQUEST_METHOD"] == "POST") {
    			$this->request_method = "post";
    		} else if ($_SERVER["REQUEST_METHOD"] == "GET") {
    			$this->request_method = "get";
    		}
		} else {
		    $this->request_method = "cli";
		}
		if (@ini_get("register_globals") == 1) {
			$this->unset_globals($_POST);
			$this->unset_globals($_GET);
			$this->unset_globals($_FILES);
			$this->unset_globals($_COOKIE);
		}
		$this->clean_input();
		if (isset($this->input['intcheck']) && $this->input['intcheck'] == 1) {
			die("&#077;&#089;&#066;&#066;");
		}
	}

    // Check the incoming information to sort and verify it as an array
	function parse_incoming($array) {
		if (!is_array($array)) {
			return;
		}
		foreach ($array as $key => $val) {
			$this->input[$key] = $val;
		}
	}

    // Remove slashes from an array
	function strip_slashes_array(&$array) {
		foreach ($array as $key => $val) {
			if (is_array($array[$key])) {
				$this->strip_slashes_array($array[$key]);
			} else {
				$array[$key] = stripslashes($array[$key]);
			}
		}
	}

    // Unset global variables - we dont want them
	function unset_globals($array) {
		if (!is_array($array)) {
			return;
		}
		foreach(array_keys($array) as $key) {
		    // Unset twice because PHP can be bitchy and want it a second time..
			unset($GLOBALS[$key]);
			unset($GLOBALS[$key]);
		}
	}

    // Clean the variables up to prevent hacking attempts
	function clean_input() {
		foreach ($this->clean_variables as $type => $variables) {
			foreach ($variables as $var) {
				if (in_array($var, $this->ignore_clean_variables)) {
					continue;
				}
				if (isset($this->input[$var])) {
					switch($type) {
						case "int":
							$this->input[$var] = $this->get_input($var, WMS::INPUT_INT);
							break;
						case "a-z":
							$this->input[$var] = preg_replace("#[^a-z\.\-_]#i", "", $this->get_input($var));
							break;
						case "pos":
							if (($this->input[$var] < 0 && $var != "page") || ($var == "page" && $this->input[$var] != "last" && $this->input[$var] < 0))
								$this->input[$var] = 0;
							break;
					}
				}
			}
		}
	}

	function get_input($name, $type = WMS::INPUT_STRING) {
	    switch($type) {
	        case WMS::INPUT_ARRAY:
				if (!isset($this->input[$name]) || !is_array($this->input[$name])) {
					return array();
				}
				return $this->input[$name];
			case WMS::INPUT_INT:
				if (!isset($this->input[$name]) || !is_numeric($this->input[$name])) {
					return 0;
				}
				return (int) $this->input[$name];
			case WMS::INPUT_FLOAT:
				if (!isset($this->input[$name]) || !is_numeric($this->input[$name])) {
					return 0.0;
				}
				return (float) $this->input[$name];
			case WMS::INPUT_BOOL:
				if (!isset($this->input[$name]) || !is_scalar($this->input[$name])) {
					return false;
				}
				return (bool) $this->input[$name];
			default:
				if (!isset($this->input[$name]) || !is_scalar($this->input[$name])) {
					return '';
				}
				return $this->input[$name];
	    }
	}

	function trigger_generic_error($code) {
		global $error_handler;
		switch($code) {
			case "custom_error_code":
				$message = "You can set your own error codes to be called in the event they are required here.";
				$error_code = WMS_CUSTOM_ERROR;
				break;
			default:
				$message = "WMS has experienced an internal error.";
				$error_code = WMS_GENERAL;
		}
		$error_handler->trigger($message, $error_code);
	}
	
	function close($db, $templates) {
	    unset($templates->cache);
	    if (is_resource($db)) {
	        $db->close();
	    }
	    die();
	}
	
	function __destruct() {
		if (function_exists("run_shutdown")) {
			run_shutdown();
		}
	}

}