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
 * Session Manager
 *
 *   Since PHP Sessions do not have as robust functions, and cannot be sent
 *   easily between browsers, we will build and handle our own sessions
 *   in WMS to give us a bit more control over the session information
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
 
class SessionManager {

    // Set the public package items to be called from elsewhere
    public $session_id = 0;
    public $userid = 0;
    public $useragent = "";
    public $user_ip = 0;
    public $packaged_ip = 0;
    public $user_location = "";
    public $user_fullpath = "";
    public $user_script = "";
    public $language = "english";
    public $is_spider = FALSE;
    public $is_admin = FALSE;
    public $login_timeout = 0;
    public $login_attempts = 0;
    public $sessiondata = array();

    // Initialize the session manager - This is called on every page
    function init($current_page, $database, $wms, $data_handler) {

        // Set the user IP address and packaged IP addresses
        $this->user_ip = get_ip();
        $this->packaged_ip = get_ip(TRUE, $this->user_ip);
        if (isset($_SERVER["REQUEST_URI"])) {
            $this->user_location = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        } else {
            $this->user_location = get_location();
        }
        $this->user_script = $current_page;
        $this->user_fullpath = get_location();

        // Get the users USER_AGENT from client, if it is supplied
		if (isset($_SERVER["HTTP_USER_AGENT"])) {
		    $this->useragent = $_SERVER["HTTP_USER_AGENT"];
		} else {
		    $this->useragent = "Undefined: No user agent set";
		}
		
        // Run the data processor for spiders and load them into an array
        $spider_list = $data_handler->read($database);
        if (is_array($spider_list))	{
            foreach ($spider_list as $spider) {
                if (sys_strpos(sys_strtolower($this->useragent), sys_strtolower($spider["useragent"])) !== FALSE) {
                    // This user is a spider - before we even make them a session
                    // lets instead load them as a spider so they don't touch the
                    // session table
                    $this->load_webcrawlers($database, $wms, $spider["sid"]);
                }
            }
        }

        // Check if the session cookie is set - if it is we will disect that and
        // decide if we can use it or if we need to set a new one
        if (isset($wms->cookiemanager->cookies[$wms->config->session->session_name]) && !($this->is_spider)) {

            // The cookie is set, so lets set this to the session ID for now and see
            // if it exists in our database!
            $session_identifier = $wms->cookiemanager->cookies[$wms->config->session->session_name];
            
		    // Make sure this session ID is NOT a spider or a bot
		    if (sys_substr($session_identifier, 3, 1) !== "=") {

		        // Load the session ID from the database if it exists and the 
		        // destroy flag hasn't been set on the session manager
				$session_data = $database->select("sessions", array("sid" => $session_identifier, "destroy_me" => 0), "*", NULL, 1);

				// the session has a result, and the SID is set, we can set the
				// session on scope of the class from the db result as we trust that more
				if (isset($session_data) && $session_data["sid"]) {
					$this->session_id = $session_data["sid"];
				    $this->login_timeout = $session_data["lockout_time"];
			        $this->login_attempts = $session_data["login_attempts"];
			        $this->sessiondata = $session_data["user_data"];
			        if (!is_null($session_data["default_language"]) && !empty($session_data["default_language"])) {
			            $this->language = $session_data["default_language"];
			        }
				}

		    } else {
		        $this->session_id = $session_identifier;
		    }
        }

        // If the userinfo cookie is set, we can compare it to the session data. If
        // there is no session data, this might be an old cookie so we will get rid
        // of the cookie since there no matching session
        if (isset($wms->cookiemanager->cookies["wcid"]) && !empty($wms->cookiemanager->cookies["wcid"])) {
            if (isset($session_data) && is_array($session_data)) {
                // Get the account details from the userinfo cookie - limit the result
                // to two results maximum since there shouldn't be more than that
    			$account_details = explode("_", $wms->cookiemanager->cookies["wcid"], 2);

                // If the information matches, load a user, otherwise unset the cookie
                // and we will continue to loading the user as a guest. Sending 
                // user_id and login_key as variables on this cookie
    			if ($account_details[0] == $session_data["uid"]) {
    			    $this->load_user_account($database, $wms, $account_details[0], $account_details[1]);
    			} else {
    			    $wms->cookiemanager->unset_cookie($wms->config, "wcid");
    			}
            } else {
                $wms->cookiemanager->unset_cookie($wms->config, "wcid");
            }
		}

        // No user id, hasn't been sent to load as a user, it's most likely safe
        // to assume this is a guest and load them as a guest account now
		if ($this->userid == 0 && !$this->is_spider) {
            $this->load_guest_account($database, $wms);
		}
		
		if ($this->session_id && (!isset($wms->cookiemanager->cookies[$wms->config->session->session_name]) || $wms->cookiemanager->cookies[$wms->config->session->session_name] != $this->session_id) && $this->is_spider != TRUE) {
			$wms->cookiemanager->set_cookie($wms->config, $wms->config->session->session_name, $this->session_id, -1, TRUE, TRUE);
		}
    }
    
    function load_webcrawlers($database, $wms, $spider_id) {

        // Load the spider information from the database of spiders
        $spider_info = $database->select("spiders", array("sid" => $spider_id), "*", NULL, 1);

		if (isset($spider_info)) {
		    
            // Global Language, since the bots can have specific language and we dont
            // need to pass the entire language core here and set this is a spider to 
            // active in the scope of the script
		    $this->is_spider = TRUE;
		    
            // The spider exists on our spider table, so lets set some variables
            // and send that off to the user handler
            $user_info_array = array();
    		$user_info_array["uid"] = 0;
            $user_info_array["username"] = $spider_info["name"];
            $user_info_array["email"] = "None set";
            $user_info_array["avatar"] = "default";
            if (isset($spider_info["usergroup"])) {
                $user_info_array["usergroup"] = $spider_info["usergroup"];
            } else {
                $user_info_array["usergroup"] = 0;
            }
    		$user_info_array["invisible_user"] = $spider_info["invisible"];
    		$user_info_array["last_active"] = CURRENT_TIME;
    		if ($spider_info["default_language"] && $wms->lang->language_exists($spider_info["default_language"])) {
    		    $user_info_array["language"] = $spider_info["default_language"];
    		    $wms->lang->set_language($spider_info["default_language"]);
    		} else {
    		    $user_info_array["language"] = $wms->config->general->language;
    		}
    		$user_group = $database->select("usergroups", array("gid" => $user_info_array["usergroup"], "is_active" => 1), "*", NULL, 1);
            if ($user_group) {
                if ($user_group["is_admin"]) {
                    $this->is_admin = TRUE;
                }
                $wms->usergroup = json_decode(json_encode($user_group));
            } else {
                $wms->errormanager->trigger("Unable to load your usergroup information. Please notify the website owner.", WMS_SQL);
            }
            // If the last visit has been more than 2 minutes ago, update the log
            // on their db entry to show when the spider was last active on the site
    		if ($spider_info["lastvisit"] < CURRENT_TIME - 120) {
			    $database->update("spiders", array("lastvisit" => CURRENT_TIME), array("sid" => $spider_id));
            }
            
            $this->session_id = "bot=" . $spider_info["name"];
            $wms->user = json_decode(json_encode($user_info_array));
            if (!defined("NO_UPDATE_SESSION")) {
                $this->create_session($database, $wms->config->session, $wms->user->uid);
            }
		} else {
            return FALSE;
		}

    }

    function load_guest_account($database, $wms) {

        // Create an array for the user data to be loaded into and sent off
        $user_data = array();
		$user_info_array["uid"] = 0;
        $user_info_array["username"] = "Guest";
        $user_info_array["email"] = "None set";
        $user_info_array["avatar"] = "default";
        $user_info_array["usergroup"] = 0;
		$user_info_array["invisible_user"] = 0;
		$user_info_array["last_active"] = CURRENT_TIME;
        if ($wms->lang->language_exists($this->language)) {
		    $user_info_array["language"] = $this->language;
		    $wms->lang->set_language($this->language);
		} else {
		    $user_info_array["language"] = $wms->config->general->language;
		}
		$user_group = $database->select("usergroups", array("gid" => $user_info_array["usergroup"], "is_active" => 1), "*", NULL, 1);
        if ($user_group) {
            if ($user_group["is_admin"]) {
                $this->is_admin = TRUE;
            }
            $wms->usergroup = json_decode(json_encode($user_group));
        } else {
            $wms->errormanager->trigger("Unable to load your usergroup information. Please notify the website owner.", WMS_SQL);
        }
        $wms->user = json_decode(json_encode($user_info_array));
        if (!empty($this->session_id)) {
            if (!defined("NO_UPDATE_SESSION")) {
                $this->update_session($database, $this->session_id);
            }
        } else {
            if (!defined("NO_UPDATE_SESSION")) {
                $this->create_session($database, $wms->config);
            }
        }

    }

    function load_user_account($database, $wms, $user_id, $login_key) {
        $this->userid = $user_id;
        $user_info = $database->select("users", array("uid" => $user_id, "login_key" => $login_key), "uid, username, email, avatar, usergroup, invisible_user, lastactive, default_language", NULL, 1);
        if ($user_info) {
		    $user_group = $database->select("usergroups", array("gid" => $user_info["usergroup"], "is_active" => 1), "*", NULL, 1);
            if ($user_group) {
                if ($user_group["is_admin"]) {
                    $this->is_admin = TRUE;
                }
                $wms->usergroup = json_decode(json_encode($user_group));
            } else {
                $wms->errormanager->trigger("Unable to load your usergroup information. Please notify the website owner.", WMS_SQL);
            }
            $wms->user = json_decode(json_encode($user_info));
            $user_language = sys_strtolower(trim($user_info["default_language"]));
            if ($user_language && $wms->lang->language_exists($user_language) && $user_language != $wms->config->general->language) {
    		    $user_info_array["language"] = $user_language;
    		    $wms->lang->set_language($user_language);
    		    $wms->user->language = $user_language;
    		} else {
    		    $wms->user->language = $wms->config->general->language;
    		}
		    $updated_user_information = array();
		    $updated_user_information["lastactive"] = CURRENT_TIME;
		    $updated_user_information["lastip"] = $database->escape_binary($this->packaged_ip);
		    $database->update("users", $updated_user_information, array("uid" => $this->userid));
            $this->login_timeout = 0;
			$this->login_attempts = 0;
			if (!defined("NO_UPDATE_SESSION")) {
                $this->update_session($database, $this->session_id);
			}
        } else {
            unset($wms->user);
            $wms->cookiemanager->unset_cookie($wms->config, "wcid");
            $this->load_guest_account($database, $wms);
        }
    }

    // Generate a new session ID. This will pull the length of the session ID from
    // the configuration or default itself to 50 characters and then be md5'd
    function generate_session_id($configuration) {
        if (isset($configuration->length_of_id)) {
            $id = md5(random_str($configuration->length_of_id));
        } else {
            $id = md5(random_str(50));
        }
        return $id;
    }

    // Create a new user session if they don't have one, or in the case of spiders
    // every time they load a new page, it is basically a new session for them
    function create_session($database, $configuration, $user_id = 0) {

        // Create an array for the user data to be loaded into and sent off
        $user_data = array();

        // If there is a user_id, we will delete the record from sessions since
        // this is a new session and doesn't need that information
        if ($user_id > 0) {
			$database->delete("sessions", array("uid" => $user_id));
			$user_data["uid"] = $user_id;
		} else {

		    // If this is a spider and it somehow has a session cookie and it is
		    // active in the database, we will get rid of that since it's not
		    // needed.
			if ($this->is_spider) {
			    $database->delete("sessions", array("sid" => $this->session_id));
			}
			$user_data["uid"] = 0;
		}

        // If this is a spider, set the session ID to the spiders corresponding ID
        // otherwise, we will generate a new session ID
        if ($this->is_spider) {
            $user_data["sid"] = $this->session_id;
        } else {
            $user_data["sid"] = $this->generate_session_id($configuration);
        }
        $user_data["start_time"] = CURRENT_TIME;
        $user_data["last_visit"] = CURRENT_TIME;
		$user_data["ip"] = $database->escape_binary($this->packaged_ip);
		$user_data["useragent"] = $database->escape_string(sys_substr($this->useragent, 0, 200));
		$user_data["current_script"] = $database->escape_string(sys_substr($this->user_script, 0, 150));
		$user_data["full_path"] = $database->escape_string(sys_substr($this->user_fullpath, 0, 200));
		$user_data["precise_location"] = $database->escape_string(sys_substr($this->user_location, 0, 200));
		if ($this->sessiondata) {
		    $user_data["user_data"] = $this->sessiondata;
		}
		$database->insert("sessions", $user_data);
		$this->session_id = $user_data['sid'];
		$this->userid = $user_data['uid'];

    }

    function update_session($database, $session_id) {

        // Create an array for the user data to be loaded into and sent off
        $session_information = array();

        // If there is a user_id, we will delete the record from sessions since
        // this is a new session and doesn't need that information
        if ($this->userid > 0) {
			$session_information["uid"] = $this->userid;
		} else {
			$session_information["uid"] = 0;
		}
		
		// Check if lockout time is set then add it to the session again
        if ($this->login_timeout > 0) {
			$session_information["lockout_time"] = $this->login_timeout;
		}

		// Add the login attempts to the system
        if ($this->login_attempts > 0) {
			$session_information["login_attempts"] = $this->login_attempts;
		}

        $session_id = $database->escape_string($session_id);
        $session_information["last_visit"] = CURRENT_TIME;
		$session_information["ip"] = $database->escape_binary($this->packaged_ip);
		$session_information["useragent"] = $database->escape_string(sys_substr($this->useragent, 0, 200));
		$session_information["current_script"] = $database->escape_string(sys_substr($this->user_script, 0, 150));
		$session_information["full_path"] = $database->escape_string(sys_substr($this->user_fullpath, 0, 200));
		$session_information["precise_location"] = $database->escape_string(sys_substr($this->user_location, 0, 200));
		if ($this->sessiondata) {
		    $user_data["user_data"] = $this->sessiondata;
		}
		$database->update("sessions", $session_information, array("sid" => $session_id));

	}

    // Check if the user has exceeded their login attempts, and if they are locked
    // out return TRUE, and if the lockout has expired, remove it from the session
    // AND the user account if it is set - Optional return time to unlock
    function check_login_attempts_exceeded($database, $config, $userid = 0, $return_time = FALSE) {
        
        // Set the time of now for checking if they are past the lockout expiry
        $time = CURRENT_TIME;
        $userid = (int) $userid;
        $unauthenticated_user = FALSE;
        
        // Check if this user is logged in - If they are, we can return false
        // since this user is already logged in and doesn't need to have attempts
        // logged for them anymore.
        if ($userid > 0 && $userid == $this->userid) {
            return FALSE;
        } else {
            $unauthenticated_user = TRUE;
        }

        // If the user is a guest, we can process the request since it's not important
        // otherwise. This condition should always be true, but better safe than sorry?
        if ($unauthenticated_user) {

            // If the login_timeout isn't set we can check if the attempts are over
            // and set it otherwise, it must be set so we will see if its expired
            if (!$this->login_timeout || $this->login_timeout == 0) {

                // Check the global lockout first - if it above the max then we will
                // ignore the fact that the user is over since we're not there yet
                if ($this->login_attempts >= $config->general->max_login_trys) {
                    $this->login_timeout = (int) $time + ($config->general->lockout_time * 60);
                    $database->update("sessions", array("lockout_time" => $this->login_timeout), array("sid" => $this->session_id));
                    return TRUE;
                }

                // Now lets check the specific user case, because we know that the
                // above has been handled and returned already
                if ($config->general->account_timelock && $userid > 0) {
                    $user_info = $database->select("users", array("uid" => $userid), "loginattempts, loginlockoutexpiry", NULL, 1);
                    if ($user_info) {
                        $user_specific_loginattempts = $user_info["loginattempts"];
                        $user_specific_loginexpiry = $user_info["loginlockoutexpiry"];

                        // If this specific user is over the max attempts, then we return
                        // true because they're over the max attempt
                        if ($user_specific_loginattempts >= $config->general->max_login_trys) {

                            // We also want to see if their expiry is over the max limit
                            // and it if is, we will return true, if it is expired,
                            // lets remove it and set these back to 0 and return false!
                            if ($user_specific_loginexpiry > $time) {
                                if ($return_time) {
                                    $secsleft = (int) ($user_specific_loginexpiry - $time);
                    				$hoursleft = floor($secsleft / 3600);
                    				$minsleft = floor(($secsleft / 60) % 60);
                    				$secsleft = floor($secsleft % 60);
                                    return array("hours" => $hoursleft, "minutes" => $minsleft, "seconds" => $secsleft);
                                }
                                return TRUE;
                            } else {
                                // This user specific timeout has expired, so lets
                                // remove it from the system and let the user attempt
                                // a login!
                                $database->update("users", array("loginattempts" => 0, "loginlockoutexpiry" => 0), array("uid" => $userid));
                                return FALSE;
                            }
                        }
                    } else {
                        
                        // There was no user information found for this id, so we can't
                        // return a result and we will just return false instead
                        return FALSE;
                    }
                }

                // Must not be over yet then, so lets return false as no user was
                // supplied and the user hasn't hit the limit globally yet either
                return FALSE;

            } else {
                
                // The login timeout is set, let see if it's expired or not?
                if ($this->login_timeout <= CURRENT_TIME) {
                    if ($config->general->account_timelock && $userid > 0) {
                        $user_info = $database->select("users", array("uid" => $userid), "loginattempts, loginlockoutexpiry", NULL, 1);
                        if ($user_info) {
                            if ($user_info["loginattempts"] >= $config->general->max_login_trys) {
                                if ($user_info["loginlockoutexpiry"] > $time) {
                                    return TRUE;
                                } else {
                                    $this->login_timeout = 0;
                                    $this->login_attempts = 0;
                                    $database->update("sessions", array("login_attempts" => $this->login_attempts, "lockout_time" => $this->login_timeout), array("sid" => $this->session_id));
                                    $database->update("users", array("loginattempts" => 0, "loginlockoutexpiry" => 0), array("uid" => $userid));
                                    return FALSE;
                                }
                            } else {
                                // The user they're trying for is not at the max tries so we can return false!
                                return FALSE;
                            }
                        } else {
                            // There was no user information found for this id, so we can't
                            // return a result and we will just return false instead
                            return FALSE;
                        }
                    } else {
                        // The login timeout has expired for this guest account so
                        // we remove it globally!
                        $this->login_timeout = 0;
                        $this->login_attempts = 0;
                        $database->update("sessions", array("login_attempts" => $this->login_attempts, "lockout_time" => $this->login_timeout), array("sid" => $this->session_id));
                        return FALSE;
                    }
                }

                // If they have the return time set to true
                if ($return_time) {
                    $secsleft = (int) ($this->login_timeout - $time);
    				$hoursleft = floor($secsleft / 3600);
    				$minsleft = floor(($secsleft / 60) % 60);
    				$secsleft = floor($secsleft % 60);
                    return array("hours" => $hoursleft, "minutes" => $minsleft, "seconds" => $secsleft);
                }

                // They must still be expired, so lets return true!
                return TRUE;

            }

        }

        return FALSE;

    }

    // Add a login attempt to the user session or to their account if that is set
    function count_login_attempt($database, $config, $userid = 0) {
        if (!$this->login_timeout || $this->login_timeout == 0) {
            if ($config->general->account_timelock && $userid > 0) {
                $user_info = $database->select("users", array("uid" => $userid), "loginattempts", NULL, 1);
                if ($user_info) {
                    if ($this->login_attempts <= $config->general->max_login_trys) {
                        $this->login_attempts++;
                        $database->update("sessions", array("login_attempts" => $this->login_attempts), array("sid" => $this->session_id));
                    }
                    if ($wms->config->general->account_timelock && $user_info["loginattempts"] <= $config->general->max_login_trys) {
                        $attempts = $user_info["loginattempts"] + 1;
                        $database->update("users", array("loginattempts" => $attempts), array("uid" => $userid));
                    }
                    return array("passed" => TRUE, "reason" => "Accepted - The login attempt was added to the user / session.");
                } else {
                    return array("passed" => FALSE, "reason" => "Fatal Error: This user attempted for does not exist.");
                }
            } else {
                if ($this->login_attempts < $config->general->max_login_trys) {
                    $this->login_attempts++;
                    $database->update("sessions", array("login_attempts" => $this->login_attempts), array("sid" => $this->session_id));
                    return array("passed" => TRUE, "reason" => "Accepted - The login attempt was added to the session.");
                } else {
                    return array("passed" => FALSE, "reason" => "Fatal Error: This user has exceeded login attempts.");
                }
            }
        } else {
            return array("passed" => FALSE, "reason" => "Fatal Error: This user has exceeded login attempts.");
        }
    }

    function process_login($database, $wms, $user_id) {
        $this->login_attempts = 0;
        $this->login_timeout = 0;
        $this->userid = $user_id;
        $user_info = $database->select("users", array("uid" => $user_id), "uid, login_key, username, email, avatar, usergroup, invisible_user, lastactive, default_language", NULL, 1);
        if ($user_info) {
            $user_group = $database->select("usergroups", array("gid" => $user_info["usergroup"], "is_active" => 1), "*", NULL, 1);
            $user_cookie = $user_id . "_" . $user_info["login_key"];
            if ($user_group) {
                if ($user_group["is_admin"]) {
                    $this->is_admin = TRUE;
                }
                $wms->usergroup = json_decode(json_encode($user_group));
            } else {
                $wms->errormanager->trigger("Unable to load your usergroup information. Please notify the website owner.", WMS_SQL);
            }
            $wms->user = json_decode(json_encode($user_info));
            $user_language = sys_strtolower(trim($user_info["default_language"]));
            if ($user_language && $wms->lang->language_exists($user_language) && $user_language != $wms->config->general->language) {
    		    $user_info_array["language"] = $user_language;
    		    $wms->lang->set_language($user_language);
    		    $wms->user->language = $user_language;
    		} else {
    		    $wms->user->language = $wms->config->general->language;
    		}
		    $updated_user_information = array();
		    $updated_user_information["lastactive"] = CURRENT_TIME;
		    $updated_user_information["lastip"] = $database->escape_binary($this->packaged_ip);
		    $updated_user_information["loginattempts"] = 0;
		    $updated_user_information["loginlockoutexpiry"] = 0;
		    $database->update("users", $updated_user_information, array("uid" => $this->userid));
		    $database->update("sessions", array("uid" => $this->userid, "login_attempts" => $this->login_attempts, "lockout_time" => $this->login_timeout), array("sid" => $this->session_id));
		    if (!isset($wms->cookiemanager->cookies["wcid"])) {
		        $wms->cookiemanager->set_cookie($wms->config, "wcid", $user_cookie, -1, TRUE);
		    } else {
		        $wms->cookiemanager->unset_cookie($wms->config, "wcid");
		        $wms->cookiemanager->set_cookie($wms->config, "wcid", $user_cookie, -1, TRUE);
		    }
        }
    }

 }