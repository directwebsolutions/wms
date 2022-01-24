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

class SMTP extends Mailer {

    public $connection;
	public $username = "";
	public $password = "";
	public $helo = "localhost";
    public $authenticated = FALSE;
	public $timeout = 5;
	public $status = 0;
	public $port = 25;
	public $secure_port = 465;
	public $host = "";
	public $last_error = "";
	public $keep_alive = FALSE;
	public $use_tls = FALSE;
	
	function __construct() {
	    global $wms;
		$protocol = "";
		switch($wms->config->email->ssl_type) {
			case MAIL_SSL:
				$protocol = "ssl://";
				break;
			case MAIL_TLS:
				$this->use_tls = TRUE;
				break;
		}
		if (empty($wms->config->email->smtp_host)) {
			$this->host = @ini_get("SMTP");
		} else {
			$this->host = $wms->config->email->smtp_host;
		}
		$local_hosts = array("127.0.0.1", "::1", "localhost");
		if (!in_array($this->host, $local_hosts)) {
			if (function_exists("gethostname") && gethostname() !== FALSE) {
				$this->helo = gethostname();
			} else if (function_exists("php_uname")) {
				$helo = php_uname("n");
				if (!empty($helo)) {
					$this->helo = $helo;
				}
			} else if (!empty($_SERVER["SERVER_NAME"])) {
				$this->helo = $_SERVER["SERVER_NAME"];
			}
		}
		$this->host = $protocol . $this->host;

		if (empty($wms->config->email->smtp_port) && !empty($protocol) && !@ini_get("smtp_port")) {
			$this->port = $this->secure_port;
		} else if (empty($wms->config->email->smtp_port) && @ini_get("smtp_port")) {
			$this->port = @ini_get("smtp_port");
		} else if (!empty($wms->config->email->smtp_port)) {
			$this->port = $wms->config->email->smtp_port;
		}
		$this->username = $wms->config->email->smtp_username;
		$this->password = $wms->config->email->smtp_password;
	}

	function send() {
		if (!$this->connected()) {
			if (!$this->connect()) {
				$this->close();
			}
		}
		if ($this->connected()) {
			if (!$this->send_data("MAIL FROM:<{$this->from}>", 250)) {
				$this->fatal_error("The mail server does not understand the MAIL FROM command. Reason: ". $this->get_error());
				return FALSE;
			}
			$emails = explode(",", $this->to);
			foreach($emails as $to) {
				$to = trim($to);
				if (!$this->send_data("RCPT TO:<{$to}>", 250)) {
					$this->fatal_error("The mail server does not understand the RCPT TO command. Reason: ".$this->get_error());
					return FALSE;
				}
			}
			if ($this->send_data("DATA", 354)) {
				$this->send_data("Date: " . gmdate("r"));
				$this->send_data("To: " . $this->to);
				$this->send_data("Subject: " . $this->subject);
				if (trim($this->headers)) {
					$this->send_data(trim($this->headers));
				}
				$this->send_data("");
				$this->message = str_replace("\n.", "\n..", $this->message);
				$this->send_data($this->message);
			} else {
				$this->fatal_error("The mail server did not understand the DATA command");
				return FALSE;
			}
			if (!$this->send_data(".", 250)) {
				$this->fatal_error("Mail may not be delivered. Reason: " . $this->get_error());
			}
			if (!$this->keep_alive) {
				$this->close();
			}
			return TRUE;
		} else {
			return FALSE;
		}
	}

    function connect() {
		$this->connection = @fsockopen($this->host, $this->port, $error_number, $error_string, $this->timeout);
		if (function_exists("stream_set_timeout") && DIRECTORY_SEPARATOR != "\\") {
			@stream_set_timeout($this->connection, $this->timeout, 0);
		}
		if (is_resource($this->connection)) {
			$this->status = 1;
			$this->get_data();
			if (!$this->check_status("220")) {
				$this->fatal_error("The mail server is not ready, it did not respond with a 220 status message.");
				return FALSE;
			}
			if ($this->use_tls || (!empty($this->username) && !empty($this->password))) {
				$helo = "EHLO";
			} else {
				$helo = "HELO";
			}
			$data = $this->send_data("{$helo} {$this->helo}", 250);
			if (!$data) {
				$this->fatal_error("The server did not understand the {$helo} command");
				return FALSE;
			}
			if ($this->use_tls && preg_match("#250( |-)STARTTLS#mi", $data)) {
				if (!$this->send_data("STARTTLS", 220)) {
					$this->fatal_error("The server did not understand the STARTTLS command. Reason: ".$this->get_error());
					return FALSE;
				}
				if (!@stream_socket_enable_crypto($this->connection, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
					$this->fatal_error("Failed to start TLS encryption");
					return FALSE;
				}
				$data = $this->send_data("{$helo} {$this->helo}", 250);
				if (!$data) {
					$this->fatal_error("The server did not understand the EHLO command");
					return FALSE;
				}
			}
			if (!empty($this->username) && !empty($this->password)) {
				if (!preg_match("#250( |-)AUTH( |=)(.+)$#mi", $data, $matches)) {
					$this->fatal_error("The server did not understand the AUTH command");
					return FALSE;
				}
				if (!$this->auth($matches[3])) {
					return FALSE;
				}
			}
			return TRUE;
		} else {
			$this->fatal_error("Unable to connect to the mail server with the given details. Reason: {$error_number}: {$error_string}");
			return FALSE;
		}
	}

    function auth($auth_methods) {
		$auth_methods = explode(" ", trim($auth_methods));
		if (in_array("LOGIN", $auth_methods)) {
			if (!$this->send_data("AUTH LOGIN", 334)) {
				if ($this->code == 503) {
					return TRUE;
				}
				$this->fatal_error("The SMTP server did not respond correctly to the AUTH LOGIN command");
				return FALSE;
			}
			if (!$this->send_data(base64_encode($this->username), 334)) {
				$this->fatal_error("The SMTP server rejected the supplied SMTP username. Reason: " . $this->get_error());
				return FALSE;
			}
			if (!$this->send_data(base64_encode($this->password), 235)) {
				$this->fatal_error("The SMTP server rejected the supplied SMTP password. Reason: " . $this->get_error());
				return FALSE;
			}
		} else if (in_array("PLAIN", $auth_methods)) {
			if (!$this->send_data("AUTH PLAIN", 334)) {
				if ($this->code == 503) {
					return TRUE;
				}
				$this->fatal_error("The SMTP server did not respond correctly to the AUTH PLAIN command");
				return FALSE;
			}
			$auth = base64_encode(chr(0) . $this->username.chr(0) . $this->password);
			if (!$this->send_data($auth, 235)) {
				$this->fatal_error("The SMTP server rejected the supplied login username and password. Reason: ".$this->get_error());
				return FALSE;
			}
		} else if (in_array("CRAM-MD5", $auth_methods)) {
			$data = $this->send_data("AUTH CRAM-MD5", 334);
			if (!$data) {
				if ($this->code == 503) {
					return TRUE;
				}
				$this->fatal_error("The SMTP server did not respond correctly to the AUTH CRAM-MD5 command");
				return FALSE;
			}
			$challenge = base64_decode(substr($data, 4));
			$auth = base64_encode($this->username . " " . $this->cram_md5_response($this->password, $challenge));
			if (!$this->send_data($auth, 235)) {
				$this->fatal_error("The SMTP server rejected the supplied login username and password. Reason: ".$this->get_error());
				return FALSE;
			}
		} else {
			$this->fatal_error("The SMTP server does not support any of the AUTH methods that we support");
			return FALSE;
		}
		return TRUE;
	}

    function get_data() {
		$string = "";
		while ((($line = fgets($this->connection, 515)) !== FALSE)) {
			$string .= $line;
			if (substr($line, 3, 1) == " ") {
				break;
			}
		}
		$string = trim($string);
		$this->data = $string;
		$this->code = substr($this->data, 0, 3);
		return $string;
	}

    function connected() {
		if ($this->status == 1) {
			return TRUE;
		}
		return FALSE;
	}

    function send_data($data, $status_num = FALSE) {
		if ($this->connected()) {
			if (fwrite($this->connection, $data."\r\n")) {
				if ($status_num != FALSE) {
					$rec = $this->get_data();
					if ($this->check_status($status_num)) {
						return $rec;
					} else {
						$this->set_error($rec);
						return FALSE;
					}
				}
				return TRUE;
			} else {
				$this->fatal_error("Unable to send the data to the SMTP server");
				return FALSE;
			}
		}
		return FALSE;
	}

	function check_status($status_num) {
		if ($this->code == $status_num) {
			return $this->data;
		} else {
			return FALSE;
		}
	}

	function close() {
		if ($this->status == 1) {
			$this->send_data("QUIT");
			fclose($this->connection);
			$this->status = 0;
		}
	}

	function get_error() {
		if (!$this->last_error) {
			$this->last_error = "N/A";
		}
		return $this->last_error;
	}

	function set_error($error) {
		$this->last_error = $error;
	}

	function cram_md5_response($password, $challenge) {
		if (strlen($password) > 64) {
			$password = pack('H32', md5($password));
		}
		if (strlen($password) < 64) {
			$password = str_pad($password, 64, chr(0));
		}
		$k_ipad = substr($password, 0, 64) ^ str_repeat(chr(0x36), 64);
		$k_opad = substr($password, 0, 64) ^ str_repeat(chr(0x5C), 64);
		$inner = pack('H32', md5($k_ipad.$challenge));
		return md5($k_opad.$inner);
	}

}