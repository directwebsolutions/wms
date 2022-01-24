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
 * Email Engine
 *
 *   Send emails using the system core to process contact messages.
 *
 * @category    Core
 * @package     Emails
 * @author      MyBB 1.8
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 3.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */

if (!defined("MAIL_SSL")) {
	define("MAIL_SSL", 1);
}

if (!defined("MAIL_TLS")) {
	define("MAIL_TLS", 2);
}

class PHPMailer extends Mailer {

    public $additional_parameters = "";
    
    function send($wms) {
		$this->sendmail = @ini_get("sendmail_path");
		if ($this->sendmail) {
			$this->headers = str_replace("\r\n", "\n", $this->headers);
			$this->message = str_replace("\r\n", "\n", $this->message);
			$this->delimiter = "\n";
		}
		$this->sendmail_from = @ini_get("sendmail_from");
		if ($this->sendmail_from != $wms->config->email->admin_email) {
			@ini_set("sendmail_from", $wms->config->email->admin_email);
		}
		$dir = "/{$wms->config->general->admin_directory}/";
		$pos = strrpos($_SERVER["PHP_SELF"], $dir);
		if (defined("ADMIN_PANEL") && $pos !== FALSE) {
			$temp_script_path = $_SERVER['PHP_SELF'];
			$_SERVER["PHP_SELF"] = substr($_SERVER["PHP_SELF"], $pos + strlen($dir) - 1);
		}
        $sent = @mail($this->to, $this->subject, $this->message, trim($this->headers), $this->additional_parameters);
		$function_used = "mail()";
		if (defined("ADMIN_PANEL") && $pos !== FALSE) {
			$_SERVER["PHP_SELF"] = $temp_script_path;
		}
		if (!$sent) {
			$this->fatal_error("We were unable to send the email using the PHP {$function_used} function.");
			return FALSE;
		}
		return TRUE;
	}

}