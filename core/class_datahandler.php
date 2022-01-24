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
 * Datahandler
 *
 *   Load data from the system datatable and parse into useable data.
 *
 * @category    Core
 * @package     SessionManager
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 3.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */
 
class DataHandler {

	public $data = array();
	public $is_validated = FALSE;
	public $errors = array();
	public $admin_override = FALSE;
	public $method;
	public $language_prefix = "";

	function __construct($method = "insert") {
		if ($method != "update" && $method != "insert" && $method != "get" && $method != "delete") {
			die("A valid method was not supplied to the data handler.");
		}
		$this->method = $method;
	}

	function set_data($data) {
		if (!is_array($data)) {
			return FALSE;
		}
		$this->data = $data;
		return TRUE;
	}

	function set_error($error, $data = "") {
		$this->errors[$error] = array(
			"error_code" => $error,
			"data" => $data
		);
	}

	function get_errors() {
		return $this->errors;
	}
	
	function set_validated($validated = TRUE) {
		$this->is_validated = $validated;
	}
	
	function get_validated() {
		if ($this->is_validated == TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	function verify_yesno_option(&$options, $option, $default = 1) {
		if ($this->method == "insert" || array_key_exists($option, $options)) {
			if (isset($options[$option]) && $options[$option] != $default && $options[$option] != "") {
				if ($default == 1) {
					$options[$option] = 0;
				} else {
					$options[$option] = 1;
				}
			} else if (@array_key_exists($option, $options) && $options[$option] == '') {
				$options[$option] = 0;
			} else {
				$options[$option] = $default;
			}
		}
	}

}