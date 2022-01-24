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
 * Global Functions
 *
 *   These functions are not class specific and can be called from anywhere
 *
 * @category    Core
 * @package     All Packages
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 3.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */
 
define("MAX_SERIALIZED_INPUT_LENGTH", 10240);
define("MAX_SERIALIZED_ARRAY_LENGTH", 256);
define("MAX_SERIALIZED_ARRAY_DEPTH", 5);

function run_task($task_id = 0, $force_run = FALSE) {
    global $db;
    if ($task_id > 0) {
        $task_query = $db->select("tasklist", array("tid" => $task_id, "is_active" => 1), "*", NULL, 1);
        if ($task_query) {
            $task_file = basename($task_query["task_file"], ".php");
            $task_name = $task_query["task_name"];
            $task_id = $task_query["tid"];
            if (!is_null($task_query["task_function"])) {
                $task_function = $task_query["task_function"];
            } else {
                $task_function = $task_name;
            }
            if (!file_exists(ROOT_DIR . "core/cronjobs/{$task_file}.php")) {
                $message = "[" . date(DATE_RFC2822, CURRENT_TIME) . "] [CRON] [FATAL_ERROR] The task `{$task_name}` (TaskID:{$task_id}) is missing the cron file and is unable to run. This task has been disabled automatically by the system.";
                $log = file_put_contents(ROOT_DIR . "logs/cron.log", $message.PHP_EOL , FILE_APPEND | LOCK_EX);
                $db->update("tasklist", array("is_active" => 0), array("tid" => $task_id));
                return FALSE;
            }
            include_once ROOT_DIR . "core/cronjobs/{$task_file}.php";
            if (function_exists($task_function)) {
                $data = array(
                    "task_id"   =>  $task_id,
                    "taskname"  =>  $task_name,
                    "lastrun"   =>  $task_query["last_executed"],
                    "logging"   =>  $task_query["task_logging"],
                    "minute"    =>  $task_query["task_minsbtwnrun"]
                );
                if ($force_run) {
                    $data["force_run"]   =  TRUE;
                }
    			$task_function($data);
    			return TRUE;
    		} else {
    		    $message = "[" . date(DATE_RFC2822, CURRENT_TIME) . "] [CRON] [FATAL_ERROR] The task `{$task_name}` (TaskID:{$task_id}) is calling a function that doesn't exist ('{$task_function}()') and is unable to run. This task has been disabled automatically by the system.";
                $log = file_put_contents(ROOT_DIR . "logs/cron.log", $message.PHP_EOL , FILE_APPEND | LOCK_EX);
                $db->update("tasklist", array("is_active" => 0), array("tid" => $task_id));
                return FALSE;
    		}
        } else {
            $message = "[" . date(DATE_RFC2822, CURRENT_TIME) . "] [CRON] [FATAL_ERROR] The scheduled task (TaskID:{$task_id}) is missing from the tasklist database and cannot be ran.";
            $log = file_put_contents(ROOT_DIR . "logs/cron.log", $message.PHP_EOL , FILE_APPEND | LOCK_EX);
            return FALSE;
        }
    } else {
        // Select all of the tasks from the tasklist database and then we can run them
        // otherwise return FALSE if there is no result (no set tasks).
        $task_query = $db->select("tasklist", array("run_with_daily" => 1, "is_active" => 1));
        if ($task_query) {
            if (array_key_exists(0, $task_query)) {
                foreach ($task_query as $task) {
                    $task_file = basename($task["task_file"], ".php");
                    $task_name = $task["task_name"];
                    $task_id = $task["tid"];
                    if (!is_null($task["task_function"])) {
                        $task_function = $task["task_function"];
                    } else {
                        $task_function = $task_name;
                    }
                    if (!file_exists(ROOT_DIR . "core/cronjobs/{$task_file}.php")) {
                        $message = "[" . date(DATE_RFC2822, CURRENT_TIME) . "] [CRON] [FATAL_ERROR] The task `{$task_name}` (TaskID:{$task_id}) is missing the cron file and is unable to run. This task has been disabled automatically by the system.";
                        $log = file_put_contents(ROOT_DIR . "logs/cron.log", $message.PHP_EOL , FILE_APPEND | LOCK_EX);
                        $db->update("tasklist", array("is_active" => 0), array("tid" => $task_id));
                        return FALSE;
                    }
                    include_once ROOT_DIR . "core/cronjobs/{$task_file}.php";
                    if (function_exists($task_function)) {
                        $data = array(
                            "task_id"   =>  $task_id,
                            "taskname"  =>  $task_name,
                            "lastrun"   =>  $task["last_executed"],
                            "logging"   =>  $task["task_logging"],
                            "minute"    =>  $task["task_minsbtwnrun"]
                        );
            			$task_function($data);
            		} else {
            		    $message = "[" . date(DATE_RFC2822, CURRENT_TIME) . "] [CRON] [FATAL_ERROR] The task `{$task_name}` (TaskID:{$task_id}) is calling a function that doesn't exist ('{$task_function}()') and is unable to run. This task has been disabled automatically by the system.";
                        $log = file_put_contents(ROOT_DIR . "logs/cron.log", $message.PHP_EOL , FILE_APPEND | LOCK_EX);
                        $db->update("tasklist", array("is_active" => 0), array("tid" => $task_id));
            		}
                }
            } else {
                $task_file = basename($task_query["task_file"], ".php");
                $task_name = $task_query["task_name"];
                $task_id = $task_query["tid"];
                if (!is_null($task_query["task_function"])) {
                    $task_function = $task_query["task_function"];
                } else {
                    $task_function = $task_name;
                }
                if (!file_exists(ROOT_DIR . "core/cronjobs/{$task_file}.php")) {
                    $message = "[" . date(DATE_RFC2822, CURRENT_TIME) . "] [CRON] [FATAL_ERROR] The task `{$task_name}` (TaskID:{$task_id}) is missing the cron file and is unable to run. This task has been disabled automatically by the system.";
                    $log = file_put_contents(ROOT_DIR . "logs/cron.log", $message.PHP_EOL , FILE_APPEND | LOCK_EX);
                    $db->update("tasklist", array("is_active" => 0), array("tid" => $task_id));
                    return FALSE;
                }
                include_once ROOT_DIR . "core/cronjobs/{$task_file}.php";
                if (function_exists($task_function)) {
                    $data = array(
                        "task_id"   =>  $task_id,
                        "taskname"  =>  $task_name,
                        "lastrun"   =>  $task_query["last_executed"],
                        "logging"   =>  $task_query["task_logging"],
                        "minute"    =>  $task_query["task_minsbtwnrun"]
                    );
        			$task_function($data);
        		} else {
        		    $message = "[" . date(DATE_RFC2822, CURRENT_TIME) . "] [CRON] [FATAL_ERROR] The task `{$task_name}` (TaskID:{$task_id}) is calling a function that doesn't exist ('{$task_function}()') and is unable to run. This task has been disabled automatically by the system.";
                    $log = file_put_contents(ROOT_DIR . "logs/cron.log", $message.PHP_EOL , FILE_APPEND | LOCK_EX);
                    $db->update("tasklist", array("is_active" => 0), array("tid" => $task_id));
        		}
            }
            return TRUE;
        }
        return FALSE;
    }
}

function sys_sendmail($wms, $to, $subject, $message, $from = "", $charset="UTF-8", $headers="", $keep_alive = FALSE, $format = "html", $message_text = "", $return_email = "") {
    $mail = &get_mailhandler($wms->config);
    if ($keep_alive == TRUE && isset($mail->keep_alive) && isset($wms->config->email->email_type) && $wms->config->email->email_type == "smtp") {
		require_once ROOT_DIR . "core/class_email.php";
		require_once ROOT_DIR . "core/class_email_smtp.php";
		if ($mail instanceof Mailer && $mail instanceof SMTP) {
			$mail->keep_alive = TRUE;
		}
	}
	$is_mail_sent = FALSE;
	$continue_process = TRUE;
	if (empty($message_text)) {
	    $message_text = strip_tags(str_replace(array("<i>", "</i>"), array("_", "_"), $message));
        $message_text = str_replace("|a", "<a", strip_tags(str_replace("<a", "|a", $message_text)));
	}
	if (empty($return_email)) {
	    if (!empty($wms->config->email->return_email)) {
	        $return_email = $wms->config->email->return_email;
	    } else {
	        $return_email = $wms->config->email->send_from;
	    }
	}
	$mail_parameters = array(
		"to"                =>  &$to,
		"subject"           =>  &$subject,
		"message"           =>  &$message,
		"from"              =>  &$from,
		"charset"           =>  &$charset,
		"headers"           =>  &$headers,
		"keep_alive"        =>  &$keep_alive,
		"format"            =>  &$format,
		"message_text"      =>  &$message_text,
		"return_email"      =>  &$return_email,
		"is_mail_sent"      =>  &$is_mail_sent,
		"continue_process"  =>  &$continue_process
	);
	$mail->build_message($wms->config, $to, $subject, $message, $from, $charset, $headers, $format, $message_text, $return_email);
	if ($continue_process) {
		$is_mail_sent = $mail->send();
	}
	return $is_mail_sent;
}

function &get_mailhandler($config, $use_buitlin = FALSE) {
	static $mailhandler;
	static $mailhandler_builtin;
	if ($use_buitlin) {
		if (!is_object($mailhandler_builtin)) {
			require_once ROOT_DIR . "core/class_email.php";
			if (isset($config->email->email_type) && $config->email->email_type == "smtp") {
				require_once ROOT_DIR . "core/class_email_smtp.php";
				$mailhandler_builtin = new SMTP();
			} else {
				require_once ROOT_DIR . "core/class_email_php.php";
				$mailhandler_builtin = new PHPMailer();
				if (!empty($config->email->mail_parameters)) {
					$mailhandler_builtin->additional_parameters = $config->email->mail_parameters;
				}
			}
		}
		return $mailhandler_builtin;
	}
	if (!is_object($mailhandler)) {
		require_once ROOT_DIR . "core/class_email.php";
		if (!is_object($mailhandler) || !($mailhandler instanceof Mailer)) {
			$mailhandler = &get_mailhandler($config, TRUE);
		}
	}
	return $mailhandler;
}

function check_password_conditions($new_pw, $repeated) {
    $_response = array();
    $_response["status"]    =   TRUE;
    $_response["e_message"] =   NULL;
    $_response["css"]       =   NULL;
    $uppercase              =   preg_match('@[A-Z]@', $new_pw);
    $lowercase              =   preg_match('@[a-z]@', $new_pw);
    $number                 =   preg_match('@[0-9]@', $new_pw);
    $specialChars           =   preg_match('@[^\w]@', $new_pw);
    if (empty($new_pw)) {
        $_response["status"]    =   FALSE;
        $_response["e_message"] =   "Invalid new password. Cannot be blank.";
        $_response["css"]       =   "failed";
        return $_response;
    }
    if ($new_pw != $repeated) {
        $_response["status"]    =   FALSE;
        $_response["e_message"] =   "Passwords do not match.";
        $_response["css"]       =   "failed";
        return $_response;
    }
    if (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($new_pw) < 8) {
        $_response["status"]    =   FALSE;
        $_response["e_message"] =   "New password doesn't meet requirements - Please include one Uppercase, Lowercase, Number, and Special Character";
        $_response["css"]       =   "failed";
        return $_response;
    }
    return $_response;
}

function get_execution_time() {
	static $time_start;
	$time = microtime(TRUE);
	if (!$time_start) {
		$time_start = $time;
		return;
	} else {
		$total = $time - $time_start;
		if($total < 0) $total = 0;
		$time_start = 0;
		return $total;
	}
}

function sort_menu_array(array &$elements, $parent_id = 0) {
    $_sorted = array();
    foreach ($elements as &$element) {
        if ($element["parent_id"] == $parent_id) {
            $children = sort_menu_array($elements, $element["id"]);
            if ($children) {
                $element["children"] = $children;
            }
            $_sorted[$element["id"]] = $element;
            unset($element);
        }
    }
    return $_sorted;
}

function construct_panel_menu(array $data, $usergroup, $is_user_admin, $slug = NULL, $load_additional_ul = TRUE) {
    $html = "";
    if ($load_additional_ul) {
        $html .= "\n<ul>\n";
    }
    foreach ($data as $_menu_item) {
        $load = FALSE;
        if (isset($_menu_item["required_usergroup"]) && !is_null($_menu_item["required_usergroup"])) {
            if (in_array($usergroup, explode(",", $_menu_item["required_usergroup"]))) {
                $load = TRUE;
            }
        }
        if (isset($_menu_item["user_restriction"]) && $_menu_item["user_restriction"] === 0) {
            $load = TRUE;
        }
        if ($is_user_admin) $load = TRUE;
        if ($load) {
            $html .= "<li>";
            $title = "";
            $active_slug = "";
            if (isset($_menu_item["id"]) && $_menu_item["id"] == $slug && $_menu_item["parent_id"] == 0) {
                $active_slug = " class=\"active\"";
            }
            if (isset($_menu_item["hover_title"])) {
                $title_text = unicode_htmlspecialchars($_menu_item["hover_title"], TRUE);
                $title .= " title=\"{$title_text}\"";
            }
            if (isset($_menu_item['is_link']) && isset($_menu_item["link"]) && $_menu_item["is_link"] > 0) {
                $link = unicode_htmlspecialchars($_menu_item['link']);
                $html .= "<a{$active_slug}{$title} href=\"{$link}\">" . $_menu_item["content"];
            }
            if (isset($_menu_item['children'])) {
                $html .= " <span style=\"font-size:11px;\"><i class=\"fas fa-chevron-circle-down\"></i></span></a>";
                $html .= construct_panel_menu($_menu_item["children"], $usergroup, $is_user_admin, $slug, TRUE);
            } else {
                $html .= "</a>";
            }
            $html .= "</li>\n";
        }
    }
    if ($load_additional_ul) {
        $html .= "</ul>\n";
    }
    return $html;
}

// This is a date function we offer for the calendar addon from default now. It
// returns a JSON object with a bunch of helpful date information like how many
// days in the current month, days in month before this one, day before today,
// etc, and is utilized in the statistics/analytics software as well.
function build_compiled_date_func($override_current_day = NULL, $override_current_month = NULL, $override_current_year = NULL) {
    if (!is_null($override_current_month) && is_int($override_current_month) && $override_current_month > 0 && $override_current_month < 13) {
        $month = $override_current_month;
    } else {
        $month = date("m");
    }
    if (!is_null($override_current_year) && is_int($override_current_year) && $override_current_year > 1969 && $override_current_year < 2100) {
        $year = $override_current_year;
    } else {
        $year = date("Y");
    }
    if (!is_null($override_current_day) && is_int($override_current_day) && $override_current_day > 0 && $override_current_day < (date("t", mktime(0, 0, 0, $month, 1, $year)) + 1)) {
        $day = $override_current_day;
    } else {
        $day = date("d");
    }
    $t_mdy = mktime(0, 0, 0, $month, $day, $year);
    $t_md1y = mktime(0, 0, 0, $month, 1, $year);
    $t_md1ym1 = mktime(0, 0, 0, $month, 1, ($year - 1));
    $t_md1yp1 = mktime(0, 0, 0, $month, 1, ($year + 1));
    $previous_months_year = $year;
    if (($month - 1) < 1) {
        $previous_month = 12;
        $previous_months_year = date("Y", $t_md1ym1);
    } else {
        $previous_month = (int) ($month - 1);
    }
    $next_months_year = $year;
    if (($month + 1) > 12) {
        $next_month = 1;
        $next_months_year = (int) ($year + 1);
    } else {
        $next_month = (int) ($month + 1);
    }
    $t_nm1y = mktime(0, 0, 0, $next_month, 1, $next_months_year);
    $t_pm1y = mktime(0, 0, 0, $previous_month, 1, $previous_months_year);
    if (function_exists(strtotimed)) {
        $yesterday = strtotime("yesterday", $t_mdy);
        $tomorrow = strtotime("tomorrow", $t_mdy);
        $previous_day = date("d", $yesterday);
        $previous_day_month = date("m", $yesterday);
        $previous_day_year = date("Y", $yesterday);
        $next_day = date("d", $tomorrow);
        $next_day_month = date("m", $tomorrow);
        $next_day_year = date("Y", $tomorrow);
    } else {
        // calculate the previous day stuff
        // If the current day, minus 1, is more than 0, this is easy; It's still
        // the same month and year obviously so we can just subtract one from the
        // day and return the rest
        if (($day - 1) > 0) {
            $previous_day = $day - 1;
            $previous_day_month = $month;
            $previous_day_year = $year;
        } else {
            if (($month - 1) > 0) {
                $previous_day = date("t", mktime(0, 0, 0, $month - 1, 1, $year));
                $previous_day_month = $month - 1;
                $previous_day_year = $year;
            } else {
                $previous_day = date("t", mktime(0, 0, 0, 12, 1, $year - 1));
                $previous_day_month = 12;
                $previous_day_year = $year - 1;
            }
        }
        
        // calculate the next day stuff
        // If the current day, plus 1, is less than total days this month, this is 
        // easy; It's still the same month and year obviously so we can just add
        // one to the day and return the rest
        if (($day + 1) <= date("t", $t_md1y)) {
            $next_day = $day + 1;
            $next_day_month = $month;
            $next_day_year = $year;
        } else {
            if (($month + 1) > 12) {
                $next_day = 1;
                $next_day_month = 1;
                $next_day_year = $year + 1;
            } else {
                $next_day = 1;
                $next_day_month = $month + 1;
                $next_day_year = $year;
            }
        }
    }
    $t_p_mdy = mktime(0, 0, 0, $previous_day_month, $previous_day, $previous_day_year);
    $t_n_mdy = mktime(0, 0, 0, $next_day_month, $next_day, $next_day_year);
    $t_ld = mktime(0, 0, 0, $month, date("t", $t_md1y), $year);
    $date_info = array(
        "day"   =>  array(
            "previous"                      =>  date("d", $t_p_mdy),
            "previous_nlz"                  =>  date("j", $t_p_mdy),
            "previous_name"                 =>  date("l", $t_p_mdy),
            "previous_name_short"           =>  date("D", $t_p_mdy),
            "previous_day_month"            =>  date("m", $t_p_mdy),
            "previous_day_month_nlz"        =>  date("n", $t_p_mdy),
            "previous_day_month_name"       =>  date("F", $t_p_mdy),
            "previous_day_month_name_short" =>  date("M", $t_p_mdy),
            "previous_day_year"             =>  $previous_day_year,
            "previous_day_year_nlz"         =>  date("y", $t_p_mdy),
            "current"                       =>  date("d", $t_mdy),
            "current_nlz"                   =>  date("j", $t_mdy),
            "current_name"                  =>  date("l", $t_mdy),
            "current_name_short"            =>  date("D", $t_mdy),
            "next"                          =>  date("d", $t_n_mdy),
            "next_nlz"                      =>  date("j", $t_n_mdy),
            "next_name"                     =>  date("l", $t_n_mdy),
            "next_name_short"               =>  date("D", $t_n_mdy),
            "next_day_month"                =>  date("m", $t_n_mdy),
            "next_day_month_nlz"            =>  date("n", $t_n_mdy),
            "next_day_month_name"           =>  date("F", $t_n_mdy),
            "next_day_month_name_short"     =>  date("M", $t_n_mdy),
            "next_day_year"                 =>  $next_day_year,
            "next_day_year_nlz"             =>  date("y", $t_n_mdy),
            "this_month"                    =>  date("t", $t_md1y),
            "month_before"                  =>  date("t", $t_pm1y),
            "month_after"                   =>  date("t", $t_nm1y)
        ),
        "month" =>  array(
            "starts_on"                 =>  date("N", $t_md1y), // Day of the week this starts on - 1 for monday, 7 for sunday
            "ends_on"                   =>  date("N", $t_ld), // Day of the week this ends on - 1 for monday, 7 for sunday
            "starts_on_day"             =>  date("l", $t_md1y),
            "starts_on_day_short"       =>  date("D", $t_md1y),
            "end_on_day"                =>  date("l", $t_ld),
            "end_on_day_short"          =>  date("D", $t_ld),
            "previous_month"            =>  date("m", $t_pm1y),
            "previous_month_nlz"        =>  date("n", $t_pm1y),
            "previous_month_name"       =>  date("F", $t_pm1y),
            "previous_month_name_short" =>  date("M", $t_pm1y),
            "current"                   =>  $month,
            "current_nlz"               =>  date("n", $t_md1y),
            "current_name"              =>  date("F", $t_md1y),
            "current_name_short"        =>  date("M", $t_md1y),
            "next_month"                =>  date("m", $t_nm1y),
            "next_month_nlz"            =>  date("n", $t_nm1y),
            "next_month_name"           =>  date("F", $t_nm1y),
            "next_month_name_short"     =>  date("M", $t_nm1y),
        ),
        "year"   =>  array(
            "before"        =>  date("Y", $t_md1ym1),
            "before_nlz"    =>  date("y", $t_md1ym1),
            "current"       =>  $year,
            "current_nlz"   =>  date("y", $t_md1y),
            "after"         =>  date("Y", $t_md1yp1),
            "after_nlz"     =>  date("y", $t_md1yp1),
            "leap_year"     =>  date("L", mktime(0, 0, 0, 1, 1, $year))
        )
    );
    return json_decode(json_encode($date_info));
}

function format_time_duration($time) {
	if (!is_numeric($time)) {
		return "narp.";
	}
	if (round(1000000 * $time, 2) < 1000) {
		$time = number_format(round(1000000 * $time, 2))." μs";
	} else if (round(1000000 * $time, 2) >= 1000 && round(1000000 * $time, 2) < 1000000) {
		$time = number_format(round((1000 * $time), 2))." ms";
	} else {
		$time = round($time, 3)." seconds";
	}
	return $time;
}

// Function to generate a pepper to encrypt passwords - returns a new string
// every time. You can copy this down for your configuration file or run the
// generator from the admin CP and then copy the result from there.
function generate_pepper($length = 22) {
    $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $pepper = "";
    for ($i = 0; $i < $length; $i++) {
        $pepper .= $characters[rand(0, sys_strlen($characters) - 1)];
    }
    return $pepper;
}

function redirect($url) {
    header("HTTP/1.1 307 Temporary Redirect");
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
    header("Pragma: no-cache");
    header("location: " . $url, true, 307);
}

function get_ip($return_packaged = FALSE, $_ip = NULL) {
    if ($return_packaged) {
    	if (function_exists("inet_pton")) {
    		return @inet_pton($_ip);
    	} else {
    		$raw_ip = ip2long($_ip);
    		if ($raw_ip !== FALSE && $raw_ip != -1) {
    			return pack("N", $raw_ip);
    		}
    		$delim_count = substr_count($_ip, ":");
    		if ($delim_count < 1 || $delim_count > 7) {
    			return FALSE;
    		}
    		$raw_ip = explode(":", $_ip);
    		$rcount = count($raw_ip);
    		if (($doub = array_search("", $raw_ip, 1)) !== FALSE) {
    			$length = (!$doub || $doub == $rcount - 1 ? 2 : 1);
    			array_splice($raw_ip, $doub, $length, array_fill(0, 8 + $length - $rcount, 0));
    		}
    		$raw_ip = array_map("hexdec", $raw_ip);
    		array_unshift($raw_ip, "n*");
    		$raw_ip = call_user_func_array("pack", $raw_ip);
    		return $raw_ip;
    	}
    } else {
        $ip = "127.0.0.1";
        if (isset($_SERVER["REMOTE_ADDR"])) {
    	    $ip = sys_strtolower($_SERVER["REMOTE_ADDR"]);
    	    $addresses = array();
    	    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
    	        $addresses = explode(",", sys_strtolower($_SERVER["HTTP_X_FORWARDED_FOR"]));
        	} elseif(isset($_SERVER["HTTP_X_REAL_IP"])) {
        		$addresses = explode(",", sys_strtolower($_SERVER["HTTP_X_REAL_IP"]));
        	}
        	if (is_array($addresses)) {
        		foreach ($addresses as $_val) {
        			$_val = trim($_val);
        			if (sys_inet_ntop(get_ip(TRUE, $_val)) == $_val && !preg_match("#^(10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|192\.168\.|fe80:|fe[c-f][0-f]:|f[c-d][0-f]{2}:)#", $_val)) {
        				$ip = $_val;
        				break;
        			}
        		}
        	}
        } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = sys_strtolower($_SERVER["HTTP_CLIENT_IP"]);
        }
    }
    return $ip;
}

// Get the users IP address by their hostname instead
function get_ip_by_hostname($hostname) {
	$addresses = @gethostbynamel($hostname);
	if (!$addresses) {
		$result_set = @dns_get_record($hostname, DNS_A | DNS_AAAA);
		if ($result_set) {
			$addresses = array_column($result_set, "ip");
		} else {
			return FALSE;
		}
	}
	return $addresses;
}

function fetch_ip_range($ip_address) {
	if (sys_strpos($ip_address, "*") !== FALSE) {
		if (sys_strpos($ip_address, ":") !== FALSE) {
			$upper = sys_str_replace("*", "ffff", $ip_address);
			$lower = sys_str_replace("*", "0", $ip_address);
		} else {
			$ip_bits = count(explode(".", $ip_address));
			if ($ip_bits < 4) {
				$replacement = str_repeat(".*", 4-$ip_bits);
				$ip_address = substr_replace($ip_address, $replacement, strrpos($ip_address, "*") + 1, 0);
			}
			$upper = sys_str_replace("*", "255", $ip_address);
			$lower = sys_str_replace("*", "0", $ip_address);
		}
		$upper = get_ip(TRUE, $upper);
		$lower = get_ip(TRUE, $lower);
		if ($upper === FALSE || $lower === FALSE) {
			return FALSE;
		}
		return array($lower, $upper);
	} else if (sys_strpos($ip_address, "/") !== FALSE) {
		$ipaddress = explode("/", $ip_address);
		$ip_address = $ipaddress[0];
		$ip_range = (int) $ipaddress[1];
		if (empty($ip_address) || empty($ip_range)) {
			return FALSE;
		} else {
			$ip_address = get_ip(TRUE, $ip_address);
			if (!$ip_address) {
				return FALSE;
			}
		}
		$ip_pack = $ip_address;
		$ip_pack_size = strlen($ip_pack);
		$ip_bits_size = $ip_pack_size*8;
		$ip_bits = "";
		for($i = 0; $i < $ip_pack_size; $i = $i + 1) {
			$bit = decbin(ord($ip_pack[$i]));
			$bit = str_pad($bit, 8, "0", STR_PAD_LEFT);
			$ip_bits .= $bit;
		}
		$ip_bits = substr($ip_bits, 0, $ip_range);
		$ip_lower_bits = str_pad($ip_bits, $ip_bits_size, "0", STR_PAD_RIGHT);
		$ip_higher_bits = str_pad($ip_bits, $ip_bits_size, "1", STR_PAD_RIGHT);
		$ip_lower_pack = "";
		for ($i = 0; $i < $ip_bits_size; $i = $i + 8) {
			$chr = substr($ip_lower_bits, $i, 8);
			$chr = chr(bindec($chr));
			$ip_lower_pack .= $chr;
		}
		$ip_higher_pack = "";
		for ($i = 0; $i < $ip_bits_size; $i = $i + 8) {
			$chr = substr($ip_higher_bits, $i, 8);
			$chr = chr( bindec($chr) );
			$ip_higher_pack .= $chr;
		}
		return array($ip_lower_pack, $ip_higher_pack);
	} 	else {
		return get_ip(TRUE, $ip_address);
	}
}

function sys_inet_ntop($ip) {
	if (function_exists("inet_ntop")) {
		return @inet_ntop($ip);
	} else {
		switch (strlen($ip)) {
			case 4:
				list(,$raw_ip) = unpack("N", $ip);
				return long2ip($raw_ip);
			case 16:
				$raw_ip = substr(chunk_split(bin2hex($ip), 4, ":"), 0, -1);
				return preg_replace(array("/(?::?\b0+\b:?){2,}/", "/\b0+([^0])/e"), array("::", '(int)"$1"?"$1":"0$1"'), $raw_ip);
		}
		return FALSE;
	}
}

// System specific string to lower, checks if MB is enabled for faster results
function sys_strtolower($string) {
	if (function_exists("mb_strtolower")) {
		$string = mb_strtolower($string);
	} else {
		$string = strtolower($string);
	}
	return $string;
}

// System specific string to upper, checks if MB is enabled for faster results
function sys_strtoupper($string) {
	if (function_exists("mb_strtoupper")) {
		$string = mb_strtoupper($string);
	} else {
		$string = strtoupper($string);
	}
	return $string;
}

// System specific string replace, checks if MB is enabled
function sys_str_replace($search, $replace, $string) {
    if (function_exists("mb_str_replace")) {
        return mb_str_replace($search, $replace, $string);
    } else {
        return str_replace($search, $replace, $string);
    }
}

// System specific string length, checks if MB is enabled for faster results. The
// default CHARSET is UTF-8, but you can change this with language packs by
// passing the system set charset on the function call
function sys_strlen($string, $charset = "utf-8") {
	$string = preg_replace("#&\#([0-9]+);#", "-", $string);
	if (strtolower($charset) == "utf-8") {
		$string = sys_str_replace(decimal_to_utf8(8238), "", $string);
		$string = sys_str_replace(decimal_to_utf8(8237), "", $string);
		$string = sys_str_replace(chr(0xCA), "", $string);
	}
	$string = trim($string);
	if (function_exists("mb_strlen")) {
		$string_length = mb_strlen($string);
	} else {
		$string_length = strlen($string);
	}
	return $string_length;
}

// System specific string integer position, checks if MB is enabled for faster results
function sys_stripos($haystack, $needle, $offset = 0) {
	if ($needle == "") {
		return FALSE;
	}
	if (function_exists("mb_stripos")) {
		$position = mb_stripos($haystack, $needle, $offset);
	} else {
		$position = stripos($haystack, $needle, $offset);
	}
	return $position;
}

// System specific string position, checks if MB is enabled for faster results
function sys_strpos($haystack, $needle, $offset = 0) {
	if ($needle == "") {
		return FALSE;
	}
	if (function_exists("mb_strpos")) {
		$position = mb_strpos($haystack, $needle, $offset);
	} else {
		$position = strpos($haystack, $needle, $offset);
	}
	return $position;
}

// System specific sub string, checks if MB is enabled for faster results
function sys_substr($string, $start, $length = NULL, $handle_entities = FALSE) {
    
    // If we want substring to handle htmlentities, we need to uncompress them
	if ($handle_entities) {
		$string = undo_htmlentities($string);
	}
	
	// See if MB is enabled and cut the string with that
	if (function_exists("mb_substr")) {
		if ($length != null) {
			$cut_string = mb_substr($string, $start, $length);
		} else {
			$cut_string = mb_substr($string, $start);
		}
	} else {
		if ($length != null) {
			$cut_string = substr($string, $start, $length);
		} else {
			$cut_string = substr($string, $start);
		}
	}
	
	// We need to recompress our unicode characters now
	if ($handle_entities) {
		$cut_string = unicode_htmlspecialchars($cut_string);
	}
	
	// Return the cut down string
	return $cut_string;
}

// Function to undo htmlentities properly
function undo_htmlentities($string) {
	$string = preg_replace_callback("~&#x([0-9a-f]+);~i", "unicharacter_callback", $string);
	$string = preg_replace_callback("~&#([0-9]+);~", "unicharacter_sub_callback", $string);
	$translate_table = get_html_translation_table(HTML_ENTITIES);
	$translate_table = array_flip($translate_table);
	return strtr($string, $translate_table);
}

function unicharacter_callback($matches) {
	return translate_unicharacter(hexdec($matches[1]));
}

function unicharacter_sub_callback($matches) {
	return translate_unicharacter($matches[1]);
}

function translate_unicharacter($character) {
	if ($character <= 0x7F) {
		return chr($character);
	} else if ($character <= 0x7FF) {
		return chr(0xC0 | $character >> 6) . chr(0x80 | $character & 0x3F);
	} else if ($character <= 0xFFFF) {
		return chr(0xE0 | $character >> 12) . chr(0x80 | $character >> 6 & 0x3F) . chr(0x80 | $character & 0x3F);
	} else if ($character <= 0x10FFFF) {
		return chr(0xF0 | $character >> 18) . chr(0x80 | $character >> 12 & 0x3F) . chr(0x80 | $character >> 6 & 0x3F) . chr(0x80 | $character & 0x3F);
	} else {
		return FALSE;
	}
}

// Converts a decimal reference of a character to its UTF-8 equivalent
function decimal_to_utf8($source) {
	$destination = "";
	if ($source < 0) {
		return FALSE;
	} else if ($source <= 0x007f) {
		$destination .= chr($source);
	} else if($source <= 0x07ff) {
		$destination .= chr(0xc0 | ($source >> 6));
		$destination .= chr(0x80 | ($source & 0x003f));
	} else if ($source <= 0xffff) {
		$destination .= chr(0xe0 | ($source >> 12));
		$destination .= chr(0x80 | (($source >> 6) & 0x003f));
		$destination .= chr(0x80 | ($source & 0x003f));
	} else if ($source <= 0x10ffff) {
		$destination .= chr(0xf0 | ($source >> 18));
		$destination .= chr(0x80 | (($source >> 12) & 0x3f));
		$destination .= chr(0x80 | (($source >> 6) & 0x3f));
		$destination .= chr(0x80 | ($source & 0x3f));
	} else {
		return FALSE;
	}
	return $destination;
}

// Unicode specific htmlspecialchars
function unicode_htmlspecialchars($content, $skip_tags = FALSE) {
	$content = preg_replace("#&(?!\#[0-9]+;)#si", "&amp;", $content);
	if (!$skip_tags) {
    	$content = sys_str_replace("<", "&lt;", $content);
    	$content = sys_str_replace(">", "&gt;", $content);
	}
	return sys_str_replace("\"", "&quot;", $content);
}

// Get the location of the user
function get_location($ignore_variables = array(), $quick_return = FALSE) {
    
    // If the script has CURRENT_LOCATION set, we will return that right away
	if (defined("CURRENT_LOCATION")) {
		return CURRENT_LOCATION;
	}

    // If the script_name is set, we will set that as the location instead
	if (!empty($_SERVER["SCRIPT_NAME"])) {
		$location = unicode_htmlspecialchars($_SERVER["SCRIPT_NAME"]);
	} else if (!empty($_SERVER["PHP_SELF"])) {
		$location = unicode_htmlspecialchars($_SERVER["PHP_SELF"]);
	} else if (!empty($_ENV["PHP_SELF"])) {
		$location = unicode_htmlspecialchars($_ENV["PHP_SELF"]);
	} else if (!empty($_SERVER["PATH_INFO"])) {
		$location = unicode_htmlspecialchars($_SERVER["PATH_INFO"]);
	} else {
		$location = unicode_htmlspecialchars($_ENV["PATH_INFO"]);
	}

    // If you just want a quick and dirty response of the current location we can
    // flag the quick_return as TRUE and end it here
	if ($quick_return) {
		return $location;
	}
	
	// Sometimes we don"t want to pass specific variables on the location, for
	// obvious security reasons.
	if (!is_array($ignore_variables)) {
		$ignore_variables = array($ignore_variables);
	}

    // We may want to know the current query string they are viewing directly
    // for debugging and such so we will build a string here for that as well
	$parameters = array();
	if (isset($_SERVER["QUERY_STRING"])) {
		$current_query_string = $_SERVER["QUERY_STRING"];
	} else if (isset($_ENV["QUERY_STRING"])) {
		$current_query_string = $_ENV["QUERY_STRING"];
	} else {
		$current_query_string = "";
	}

    // Parse the query string and build a parameters array from the data
	parse_str($current_query_string, $current_parameters);
	foreach ($current_parameters as $name => $value) {
		if (!in_array($name, $ignore_variables)) {
			$parameters[$name] = $value;
		}
	}

    // If there is no parameters set, then we will just build a query string
	if (!empty($parameters)) {
		$location .= "?" . http_build_query($parameters, "", "&amp;");
	}
	
	// Now we can return a full query string with location information
	return $location;
}

function gzip_encode($contents, $level = 1) {
	if (function_exists("gzcompress") && function_exists("crc32") && !headers_sent() && !(ini_get("output_buffering") && sys_strpos(" " . ini_get("output_handler"), "ob_gzhandler"))) {
		$httpaccept_encoding = "";
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
			$httpaccept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'];
		}
		if (sys_strpos(" " . $httpaccept_encoding, "x-gzip")) {
			$encoding = "x-gzip";
		}
		if (sys_strpos(" " . $httpaccept_encoding, "gzip")) {
			$encoding = "gzip";
		}
		if (isset($encoding)) {
			header("Content-Encoding: $encoding");
			if (function_exists("gzencode")) {
				$contents = gzencode($contents, $level);
			} else {
				$size = strlen($contents);
				$crc = crc32($contents);
				$gzdata = "\x1f\x8b\x08\x00\x00\x00\x00\x00\x00\xff";
				$gzdata .= sys_substr(gzcompress($contents, $level), 2, -4);
				$gzdata .= pack("V", $crc);
				$gzdata .= pack("V", $size);
				$contents = $gzdata;
			}
		}
	}
	return $contents;
}

function random_str($length = 8, $complex = FALSE) {
	$set = array_merge(range(0, 9), range("A", "Z"), range("a", "z"));
	$str = array();
	if ($complex == TRUE) {
		$str[] = $set[sys_rand(0, 9)];
		$str[] = $set[sys_rand(10, 35)];
		$str[] = $set[sys_rand(36, 61)];
		$length -= 3;
	}
	for ($i = 0; $i < $length; ++$i) {
		$str[] = $set[sys_rand(0, 61)];
	}
	shuffle($str);
	return implode($str);
}

function sys_rand($min = 0, $max = PHP_INT_MAX) {
	if ($min === null || $max === null || $max < $min) {
		$min = 0;
		$max = PHP_INT_MAX;
	}
	if (version_compare(PHP_VERSION, "7.0", ">=")) {
		try {
			$result = random_int($min, $max);
		} catch (Exception $e) {
		}
		if (isset($result)) {
			return $result;
		}
	}
	$seed = secure_seed_rng();
	$distance = $max - $min;
	return $min + floor($distance * ($seed / PHP_INT_MAX) );
}

function secure_seed_rng() {
	$bytes = PHP_INT_SIZE;
	do {
		$output = secure_binary_seed_rng($bytes);
		if ($bytes == 4) {
			$elements = unpack("i", $output);
			$output = abs($elements[1]);
		} else {
			$elements = unpack("N2", $output);
			$output = abs($elements[1] << 32 | $elements[2]);
		}
	} while ($output > PHP_INT_MAX);
	return $output;
}

function secure_binary_seed_rng($bytes) {
	$output = null;
	if (version_compare(PHP_VERSION, "7.0", ">=")) {
		try {
			$output = random_bytes($bytes);
		} catch (Exception $e) {
		}
	}
	if (strlen($output) < $bytes) {
		if (@is_readable("/dev/urandom") && ($handle = @fopen("/dev/urandom", "rb"))) {
			$output = @fread($handle, $bytes);
			@fclose($handle);
		}
	} else {
		return $output;
	}
	if (strlen($output) < $bytes) {
		if (function_exists("mcrypt_create_iv")) {
			if (DIRECTORY_SEPARATOR == "/") {
				$source = MCRYPT_DEV_URANDOM;
			} else {
				$source = MCRYPT_RAND;
			}
			$output = @mcrypt_create_iv($bytes, $source);
		}
	} else {
		return $output;
	}
	if (strlen($output) < $bytes) {
		if (function_exists("openssl_random_pseudo_bytes")) {
			if ((DIRECTORY_SEPARATOR == "/") || version_compare(PHP_VERSION, "5.3.4", ">=")) {
				$output = openssl_random_pseudo_bytes($bytes, $crypto_strong);
				if ($crypto_strong == FALSE) {
					$output = null;
				}
			}
		}
	} else {
		return $output;
	}
	if (strlen($output) < $bytes) {
		if (class_exists("COM")) {
			try {
				$CAPI_Util = new COM("CAPICOM.Utilities.1");
				if (is_callable(array($CAPI_Util, "GetRandom"))) {
					$output = $CAPI_Util->GetRandom($bytes, 0);
				}
			} catch (Exception $e) {
			}
		}
	} else {
		return $output;
	}
	if (strlen($output) < $bytes) {
		$unique_state = microtime().@getmypid();
		$rounds = ceil($bytes / 16);
		for ($i = 0; $i < $rounds; $i++) {
			$unique_state = md5(microtime().$unique_state);
			$output .= md5($unique_state);
		}
		$output = substr($output, 0, ($bytes * 2));
		$output = pack("H*", $output);
		return $output;
	} else {
		return $output;
	}
}

// System specific unserialize to see if MB is enabled for faster processing
function sys_unserialize($str) {
	if (function_exists("mb_internal_encoding") && (((int)ini_get("mbstring.func_overload")) & 2)) {
		$mbIntEnc = mb_internal_encoding();
		mb_internal_encoding("ASCII");
	}
	$out = _safe_unserialize($str);
	if (isset($mbIntEnc)) {
		mb_internal_encoding($mbIntEnc);
	}
	return $out;
}

// System specific serialize to see if MB is enabled for faster processing
function sys_serialize($value) {
	if (function_exists("mb_internal_encoding") && (((int)ini_get("mbstring.func_overload")) & 2)) {
		$mbIntEnc = mb_internal_encoding();
		mb_internal_encoding("ASCII");
	}
	$out = _safe_serialize($value);
	if (isset($mbIntEnc)) {
		mb_internal_encoding($mbIntEnc);
	}
	return $out;
}

function _safe_unserialize($str) {
	if (strlen($str) > MAX_SERIALIZED_INPUT_LENGTH) {
		return FALSE;
	}
	if (empty($str) || !is_string($str)) {
		return FALSE;
	}
	$stack = $list = $expected = array();
	$state = 0;
	while ($state != 1) {
		$type = isset($str[0]) ? $str[0] : "";
		if ($type == "}") {
			$str = substr($str, 1);
		} else if ($type == "N" && $str[1] == ";") {
			$value = null;
			$str = substr($str, 2);
		} else if ($type == "b" && preg_match("/^b:([01]);/", $str, $matches)) {
			$value = $matches[1] == "1" ? TRUE : FALSE;
			$str = substr($str, 4);
		} else if ($type == "i" && preg_match("/^i:(-?[0-9]+);(.*)/s", $str, $matches)) {
			$value = (int)$matches[1];
			$str = $matches[2];
		} else if ($type == "d" && preg_match("/^d:(-?[0-9]+\.?[0-9]*(E[+-][0-9]+)?);(.*)/s", $str, $matches)) {
			$value = (float)$matches[1];
			$str = $matches[3];
		} else if($type == "s" && preg_match('/^s:([0-9]+):"(.*)/s', $str, $matches) && substr($matches[2], (int)$matches[1], 2) == '";') {
			$value = substr($matches[2], 0, (int)$matches[1]);
			$str = substr($matches[2], (int)$matches[1] + 2);
		} else if ($type == "a" && preg_match("/^a:([0-9]+):{(.*)/s", $str, $matches) && $matches[1] < MAX_SERIALIZED_ARRAY_LENGTH) {
			$expectedLength = (int)$matches[1];
			$str = $matches[2];
		} else {
			return FALSE;
		}
		switch($state) {
			case 3:
				if ($type == "a") {
					if (count($stack) >= MAX_SERIALIZED_ARRAY_DEPTH) {
						return FALSE;
					}
					$stack[] = &$list;
					$list[$key] = array();
					$list = &$list[$key];
					$expected[] = $expectedLength;
					$state = 2;
					break;
				}
				if ($type != "}") {
					$list[$key] = $value;
					$state = 2;
					break;
				}
				return FALSE;
			case 2:
				if ($type == "}") {
					if (count($list) < end($expected)) {
						return FALSE;
					}
					unset($list);
					$list = &$stack[count($stack)-1];
					array_pop($stack);
					array_pop($expected);
					if (count($expected) == 0) {
						$state = 1;
					}
					break;
				}
				if ($type == "i" || $type == "s") {
					if (count($list) >= MAX_SERIALIZED_ARRAY_LENGTH) {
						return FALSE;
					}
					if (count($list) >= end($expected)) {
						return FALSE;
					}
					$key = $value;
					$state = 3;
					break;
				}
				return FALSE;
			case 0:
				if ($type == "a") {
					if (count($stack) >= MAX_SERIALIZED_ARRAY_DEPTH) {
						return FALSE;
					}
					$data = array();
					$list = &$data;
					$expected[] = $expectedLength;
					$state = 2;
					break;
				}
				if ($type != "}") {
					$data = $value;
					$state = 1;
					break;
				}
				return FALSE;
		}
	}
	if (!empty($str)) {
		return FALSE;
	}
	return $data;
}

function _safe_serialize($value) {
    if (is_null($value)) {
		return "N;";
	}
	if (is_bool($value)) {
		return "b:" . (int) $value . ";";
	}
	if (is_int($value)) {
		return "i:" . $value . ";";
	}
	if (is_float($value)) {
		return "d:" . sys_str_replace(",", ".", $value) . ";";
	}
	if (is_string($value)) {
		return "s:" . strlen($value) . ':"' . $value . '";';
	}
	if (is_array($value)) {
		$out = "";
		foreach ($value as $k => $v) {
			$out .= _safe_serialize($k) . _safe_serialize($v);
		}
		return "a:" . count($value) . ":{" . $out . "}";
	}
	return FALSE;
}

function validate_utf8_string($input, $allow_mb4 = TRUE, $return = TRUE) {
	if (!preg_match("##u", $input)) {
		$string = '';
		$len = strlen($input);
		for ($i = 0; $i < $len; $i++) {
			$c = ord($input[$i]);
			if ($c > 128) {
				if ($c > 247 || $c <= 191) {
					if ($return) {
						$string .= "?";
						continue;
					} else {
						return FALSE;
					}
				} else if ($c > 239) {
					$bytes = 4;
				} else if ($c > 223) {
					$bytes = 3;
				} else if ($c > 191) {
					$bytes = 2;
				}
				if (($i + $bytes) > $len) {
					if ($return) {
						$string .= "?";
						break;
					} else {
						return FALSE;
					}
				}
				$valid = TRUE;
				$multibytes = $input[$i];
				while ($bytes > 1) {
					$i++;
					$b = ord($input[$i]);
					if ($b < 128 || $b > 191) {
						if ($return) {
							$valid = FALSE;
							$string .= "?";
							break;
						} else {
							return FALSE;
						}
					} else {
						$multibytes .= $input[$i];
					}
					$bytes--;
				}
				if ($valid) {
					$string .= $multibytes;
				}
			} else {
				$string .= $input[$i];
			}
		}
		$input = $string;
	}
	if ($return) {
		if ($allow_mb4) {
			return $input;
		} else {
			return preg_replace("#[^\\x00-\\x7F][\\x80-\\xBF]{3,}#", "?", $input);
		}
	} else {
		if ($allow_mb4) {
			return TRUE;
		} else {
			return !preg_match("#[^\\x00-\\x7F][\\x80-\\xBF]{3,}#", $input);
		}
	}
}

function get_memory_usage() {
	if (function_exists('memory_get_peak_usage')) {
		return memory_get_peak_usage(TRUE);
	} else if (function_exists('memory_get_usage')) {
		return memory_get_usage(TRUE);
	}
	return FALSE;
}

function days_in_month($month = NULL, $year = NULL) {
	if (!($month)) {
		$month = date("n");
	}
	if (!($year)) {
		$year = date("Y");
	}
	$days = total_days_in_month($month, $year);
	return $days;
}

function total_days_in_month($month, $year){
    return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}

function user_exists($database, $uid) {
    $query = $database->select("users", array("uid" => $uid), "COUNT(*) as user", NULL, 1);
	if (isset($query["user"]) && $query["user"] == 1) {
		return TRUE;
	} else {
		return FALSE;
	}
}

function username_exists($database, $username) {
	return (bool) get_user_by_username($database, $username, TRUE);
}

function get_user_by_username($database, $config, $username, $login_return = FALSE, $return_bool = FALSE) {
    $username = sys_strtolower($username);
    $query = $database->select("users", array(array("field_name" => "username", "value" => $username), array("field_name" => "email", "operator" => "=", "value" => $username, "separator" => "OR")), "*", NULL, 1);
    if ($return_bool) {
        if ($query) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    if ($login_return) {
        if ($query) {
            $retrieved_hash = $query["password"];
            if (!$query["login_key"]) {
                $login_key = generate_loginkey();
                $database->update_query("users", array("login_key" => $login_key), "uid = " . $query["uid"]);
            }
            return array("found_user" => TRUE, "uid" => $query["uid"], "hashed_content" => $retrieved_hash);
        } else {
            return array("found_user" => FALSE);
        }
    }
    return $query;
}

function create_password($config, $plain_text, $encrypt_overide = NULL) {
    if (isset($config->security->use_pepper) && $config->security->use_pepper) {
        $encoded_password = hash_hmac("sha256", $plain_text, $config->security->pepper);
    } else {
        $encoded_password = $plain_text;
    }
    if (isset($config->security->encryption_mode)) {
        $encryption_mode = sys_strtolower($config->security->encryption_mode);
        $bcrypt_cost = 12;
        $argon_cost = 16;
        $argon_iterations = 3;
        $argon_threads = 1;
        if (isset($config->security->bcrypt_cost) && is_int($config->security->bcrypt_cost)) { $bcrypt_cost = $config->security->bcrypt_cost; }
        if (isset($config->security->argon_cost) && is_int($config->security->argon_cost)) { $argon_cost = $config->security->argon_cost; }
        if (isset($config->security->argon_iterations) && is_int($config->security->argon_iterations)) { $argon_iterations = $config->security->argon_iterations; }
        if (isset($config->security->argon_threads) && is_int($config->security->argon_threads)) { $argon_threads = $config->security->argon_threads; }
        if (isset($encrypt_overide)) {
            $encryption_mode = sys_strtolower($encrypt_overide);
        }
        switch ($encryption_mode) {
            case "argon2id":
                $options = [
                    "memory_cost" => $argon_cost,
                    "time_cost" => $argon_iterations, 
                    "threads" => $argon_threads
                ];
                $hashed_info = password_hash($encoded_password, PASSWORD_ARGON2ID, $options);
                break;
            case "argon2i":
                $options = [
                    "memory_cost" => $argon_cost,
                    "time_cost" => $argon_iterations, 
                    "threads" => $argon_threads
                ];
                $hashed_info = password_hash($encoded_password, PASSWORD_ARGON2I, $options);
                break;
            case "bcrypt":
                $options = [
                    "cost" => $bcrypt_cost,
                ];
                $hashed_info = password_hash($encoded_password, PASSWORD_BCRYPT, $options);
                break;
            default:
                $hashed_info = password_hash($encoded_password, PASSWORD_DEFAULT);
                break;
        }
        $hash = $hashed_info;
    } else {
        $hash = password_hash($encoded_password, PASSWORD_DEFAULT);
    }
    if ($hash) {
        return $hash;
    } else {
        die ("Error in the password encryption method employed in the system. Check your configuration settings.");
    }
}

function validate_password($config, $hashed_request, $posted_password) {
    if (isset($config->security->use_pepper) && $config->security->use_pepper) {
        $posted_password = hash_hmac("sha256", $posted_password, $config->security->pepper);
    }
	if (password_verify($posted_password, $hashed_request)) {
		return TRUE;
	} else {
		return FALSE;
	}
}

function generate_loginkey() {
	return random_str(50);
}

function update_loginkey($db, $uid) {
	$loginkey = generate_loginkey();
	$db->update("users", array("login_key" => $loginkey), array("uid" => $uid));
	return $loginkey;
}