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

class Mailer {

    public $to;
	public $show_errors = 1;
	public $from;
	public $from_named;
	public $return_email;
	public $subject;
	public $orig_subject;
	public $message;
	public $headers;
	public $charset = "utf-8";
	public $delimiter = "\r\n";
	public $parse_format = "text";
	public $data = "";
	public $code = 0;

    function get_from_email() {
        global $wms;
		if (trim($wms->config->email->send_from)) {
		    if (trim($wms->config->email->return_email)) {
			    $email = $wms->config->email->return_email;
		    } else {
		        $email = $wms->config->email->send_from;
		    }
		} else {
			$email = $wms->config->email->admin_email;
		}
		return $email;
	}

	function build_message($config, $to, $subject, $message, $from=  "", $charset="", $headers="", $format = "html", $message_text = "", $return_email = "") {
		$this->message = "";
		$this->headers = $headers;
		if ($from) {
			$this->from = $from;
			$this->from_named = $this->from;
		} else {
			$this->from = $this->get_from_email();
			$this->from_named = '"' . $this->utf8_encode($config->email->from_name) . '"';
			$this->from_named .= " <{$this->from}>";
		}
		if ($return_email) {
			$this->return_email = $return_email;
		} else {
			$this->return_email = $this->get_from_email();
		}
		$this->set_to($to);
		$this->set_subject($subject);
		if ($charset) {
			$this->set_charset($charset);
		}
		$this->parse_format = sys_strtolower($format);
		$this->set_common_headers();
		$this->set_message($message, $message_text);
	}

    function set_charset($charset) {
        global $wms;
		if (empty($charset)) {
			$this->charset = $wms->lang->settings->charset;
		} else {
			$this->charset = $charset;
		}
	}

    function set_message($message, $message_text = "") {
		$message = $this->cleanup_crlf($message);
		if ($message_text) {
			$message_text = $this->cleanup_crlf($message_text);
		}
		if ($this->parse_format == "html" || $this->parse_format == "both") {
			$this->set_html_headers($message, $message_text);
		} else {
			$this->message = $message;
			$this->set_plain_headers();
		}
	}
	
	function set_subject($subject) {
		$this->orig_subject = $this->cleanup($subject);
		$this->subject = $this->utf8_encode($this->orig_subject);
	}

	function set_to($to) {
		$to = $this->cleanup($to);
		$this->to = $this->cleanup($to);
	}

    function set_plain_headers() {
		$this->headers .= "Content-Type: text/plain; charset={$this->charset}{$this->delimiter}";
	}
	
	function set_html_headers($message, $message_text = "") {
		if (!$message_text && $this->parse_format == "both") {
			$message_text = strip_tags($message);
		}
		if ($this->parse_format == "both") {
			$mime_boundary = "=_NextPart" . md5(CURRENT_TIME);
			$this->headers .= "Content-Type: multipart/alternative; boundary=\"{$mime_boundary}\"{$this->delimiter}";
			$this->message = "This is a multi-part message in MIME format.{$this->delimiter}{$this->delimiter}";
			$this->message .= "--{$mime_boundary}{$this->delimiter}";
			$this->message .= "Content-Type: text/plain; charset=\"{$this->charset}\"{$this->delimiter}";
			$this->message .= "Content-Transfer-Encoding: 8bit{$this->delimiter}{$this->delimiter}";
			$this->message .= $message_text."{$this->delimiter}{$this->delimiter}";
			$this->message .= "--{$mime_boundary}{$this->delimiter}";
			$this->message .= "Content-Type: text/html; charset=\"{$this->charset}\"{$this->delimiter}";
			$this->message .= "Content-Transfer-Encoding: 8bit{$this->delimiter}{$this->delimiter}";
			$this->message .= $message."{$this->delimiter}{$this->delimiter}";
			$this->message .= "--{$mime_boundary}--{$this->delimiter}{$this->delimiter}";
		} else {
			$this->headers .= "Content-Type: text/html; charset=\"{$this->charset}\"{$this->delimiter}";
			$this->headers .= "Content-Transfer-Encoding: 8bit{$this->delimiter}{$this->delimiter}";
			$this->message = $message."{$this->delimiter}{$this->delimiter}";
		}
	}

	function set_common_headers() {
	    global $wms;
		$this->headers .= "From: {$this->from_named}{$this->delimiter}";
		if ($this->return_email) {
			$this->headers .= "Return-Path: {$this->return_email}{$this->delimiter}";
			$this->headers .= "Reply-To: {$this->return_email}{$this->delimiter}";
		}
		if (isset($_SERVER["SERVER_NAME"])) {
			$http_host = $_SERVER["SERVER_NAME"];
		} else if (isset($_SERVER["HTTP_HOST"])) {
			$http_host = $_SERVER["HTTP_HOST"];
		} else {
			$http_host = "unknown.local";
		}
		$msg_id = md5(uniqid(CURRENT_TIME, TRUE)) . "@" . $http_host;
		if ($wms->config->email->mail_message_id) {
			$this->headers .= "Message-ID: <{$msg_id}>{$this->delimiter}";
		}
		$this->headers .= "Content-Transfer-Encoding: 8bit{$this->delimiter}";
		$this->headers .= "X-Priority: 3{$this->delimiter}";
		$this->headers .= "X-Mailer: WMS{$this->delimiter}";
		$this->headers .= "MIME-Version: 1.0{$this->delimiter}";
	}

	function fatal_error($error) {
	    global $db;
		$mail_error = array(
			"subject" => $this->orig_subject,
			"message" => $this->message,
			"toaddress" => $this->to,
			"fromaddress" => $this->from,
			"dateline" => CURRENT_TIME,
			"error" => $error,
			"smtperror" => $this->data,
			"smtpcode" => (int) $this->code
		);
		$mail_error = json_encode($mail_error);
		$db->insert("errorlogging", array("etime" => CURRENT_TIME, "elocation" => "Mail Script", "uid" => 0, "emessage" => $mail_error));
	}

	function cleanup($string) {
		$string = str_replace(array("\r", "\n", "\r\n"), "", $string);
		$string = trim($string);
		return $string;
	}

	function cleanup_crlf($text) {
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\r", "\n", $text);
		$text = str_replace("\n", "\r\n", $text);
		return $text;
	}

	function utf8_encode($string) {
		if (strtolower($this->charset) == "utf-8" && preg_match("/[^\x20-\x7E]/", $string)) {
			$chunk_size = 47;
			$len = strlen($string);
			$output = "";
			$pos = 0;
            while ($pos < $len) {
				$newpos = min($pos + $chunk_size, $len);
				while (ord($string[$newpos]) >= 0x80 && ord($string[$newpos]) < 0xC0) {
					$newpos--;
				}
				$chunk = substr($string, $pos, $newpos - $pos);
				$pos = $newpos;
				$output .= " =?UTF-8?B?".base64_encode($chunk)."?=\n";
			}
			return trim($output);
		}
		return $string;
	}

}