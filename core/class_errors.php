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
 * Error Handler
 *
 *   Lets handle our errors on our own since the PHP one has some issues, plus
 *   we can make them look a lot prettier when we do it on our own :)
 *
 * @category    Core
 * @package     ErrorEngine
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 3.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */

// Define some custom error codes. You can additonally declare your own error
// types and error codes here as well for logging
if (!defined("E_RECOVERABLE_ERROR")) {
	define("E_RECOVERABLE_ERROR", 4096);
}

if (!defined("E_DEPRECATED")) {
	define("E_DEPRECATED", 8192);
}

if (!defined("E_USER_DEPRECATED")) {
	define("E_USER_DEPRECATED", 16384);
}

define("MANUAL_WARNINGS", 0);
define("WMS_SQL", 20);
define("WMS_TEMPLATE", 30);
define("WMS_GENERAL", 40);

class ErrorEngine {

    public $warnings = "";
    public $has_errors = FALSE;
    public $force_display_errors = FALSE;

    // Define the custom error types here as well - these correspond to the errors
    // just before the opening of the class as well
    public $custom_error_types = array(
        WMS_SQL,
        WMS_TEMPLATE,
        WMS_GENERAL
    );

    // Set the message for the errors on the logging as well
    public $error_types = array(
        E_ERROR					=> "Error",
        E_WARNING				=> "Warning",
        E_PARSE					=> "Parsing Error",
        E_NOTICE				=> "Notice",
        E_CORE_ERROR			=> "Core Error",
        E_CORE_WARNING			=> "Core Warning",
        E_COMPILE_ERROR			=> "Compile Error",
        E_COMPILE_WARNING		=> "Compile Warning",
        E_DEPRECATED			=> "Deprecated Warning",
        E_USER_ERROR			=> "User Error",
        E_USER_WARNING			=> "User Warning",
        E_USER_NOTICE			=> "User Notice",
        E_USER_DEPRECATED	 	=> "User Deprecated Warning",
        E_STRICT				=> "Runtime Notice",
        E_RECOVERABLE_ERROR		=> "Catchable Fatal Error",
        WMS_SQL 				=> "WMS SQL Error",
        WMS_TEMPLATE			=> "WMS Template Error",
        WMS_GENERAL 			=> "WMS Error"
    );

    // Ignore these types of errors from the logging - I personally wouldn't use
    // E_ALL on this since, we do want logging, but you can add it to ignore all
    public $ignore_types = array(
		E_DEPRECATED,
		E_NOTICE,
		E_USER_NOTICE,
		E_STRICT
	);

    // Initialize the error_handler (this class)
	function __construct() {
		$error_type = E_ALL;
		foreach ($this->ignore_types as $data) {
			$error_type = $error_type & ~$data;
		}
		error_reporting($error_type);
		set_error_handler(array(&$this, "error"), $error_type);
	}

    // Create a template to show the error in. We want this to be generic and not
    // require the template engine to display - as a caveat to that we will also
    // not support language files since we don't want the error to be in THAT file
	function show_warnings() {

        // If there is no warnings set, lets just return empty
		if (empty($this->warnings)) {
			return FALSE;
		}
		
		// If a manual warning has been set, lets print that to the screen now
		if (MANUAL_WARNINGS) {
			echo $this->warnings . "<br>\n";
			return TRUE;
		}
		
		return FALSE;
	}

    // Trigger the warning system and display a warning
    function trigger($message = "", $type = E_USER_ERROR) {
        
        // If no message was sent with the trigger, we will generate one
		if (!$message) {
			$message = "There was an error triggered by the system.";
		}

        // If the error is a system error, we can trigger it, otherwise we can
        // set it to one of our custom ones and send that instead
		if (in_array($type, $this->custom_error_types)) {
			$this->error($type, $message);
		} else {
			trigger_error($message, $type);
		}
	}

    // We can set a custom error now using this system. The PHP error messages SHOULD
    // use this to set and get errors, but it depends how the system is configured 
	function error($type, $message, $file = NULL, $line = 0, $allow_output = TRUE) {

	    // See if error_reporting is turned off
		if (error_reporting() == 0) {
			return TRUE;
		}

		// If the error is one of the ignored types, we can ignore it instead
		if (in_array($type, $this->ignore_types)) {
			return TRUE;
		}

        // Lets set the active errors to true and log the error as well
		$file = str_replace(ROOT_DIR, "", $file);
		$this->has_errors = TRUE;
        $this->log_error($type, $message, $file, $line);

        // Assuming we WANT to show the error, lets show it now. This can be turned
        // off if the error reporting is set to silent so it JUST makes a log
        if ($allow_output === TRUE) {
            
            // If the error is an SQL error, we print that using the output
            if ($type == WMS_SQL) {
                $this->output_error($type, $message, $file, $line);
            } else {
                // If the error is not a warning, use the output error, otherwise
                // we can do a soft warn using our own method
                if (sys_strpos(sys_strtolower($this->error_types[$type]), 'warning') === FALSE) {
					$this->output_error($type, $message, $file, $line);
				} else {
				    echo "There was an error loading content. Error Details: <br><pre>";
            	    print_r($message);
            	    echo "</pre><br>Found on line (" . $line . ") in file: " . $file ."<br>\n";
				}
            }
        }
        return TRUE;
	}
	
	// Log the error to our error.log file
	function log_error($type, $message, $file, $line) {
	    if ($type == WMS_SQL) {
			$message = "SQL Error: {$message['error_no']} - {$message['error']}\nQuery: {$message['query']}";
		}
		$message = str_replace('<?', '< ?', $message);
        $error_data = "<error>\n";
		$error_data .= "\t<dateline>" . CURRENT_TIME . "</dateline>\n";
		$error_data .= "\t<script>" . $file . "</script>\n";
		$error_data .= "\t<line>" . $line . "</line>\n";
		$error_data .= "\t<type>" . $type . "</type>\n";
		$error_data .= "\t<friendly_type>" . $this->error_types[$type] . "</friendly_type>\n";
		$error_data .= "\t<message>" . $message . "</message>\n";
		$error_data .= "</error>\n\n";
		@error_log($error_data, 0);
	}

    // Output the error as a message. We may eventually make this a little prettier
	function output_error($type, $message, $file, $line) {
	    echo "There was an error loading content. Error Details: <br><pre>";
	    print_r($message);
	    echo "</pre><br>Found on line (" . $line . ") in file: " . $file ."<br>\n";
	    exit(0);
	}

}