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
 * Process Requests
 *
 *   This is our predefined process core that allows you to implement logins and
 *   logouts to your website using direct post and ajax requests as well as handle
 *   all requests to and from the dataset for your scripts. By keeping them all
 *   in one easy to access location, you create a content router for handling the
 *   incoming data to the server and returning easy to read data.
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

// You must define if direct access to this file is allowed, and then the current
// script location (this will be used for user tracking on the session handler).
// We also have to check if it's already been defined as this can be called by
// another script and we don't want to throw any errors for redefining a constant
if (!defined("ALLOW_ACCESS")) {
    define("ALLOW_ACCESS", TRUE);
}

if (!defined("CURRENT_SCRIPT")) {
    define("CURRENT_SCRIPT", "process.php");
}

if (isset($_GET["action"]) && !empty($_GET["action"]) && $_GET["action"] == "datalog") {
    if (!defined("NO_UPDATE_SESSION")) {
        define("NO_UPDATE_SESSION", 1);
    }
}

// Include the global script to enable the WMS engine in the background
require_once("global.php");

// Since this is a processing script, lets make sure the user has the ?action=VARIABLE
// set, else we will kill the script because it's not being utilized properly and
// someone is most likely doing some fuckery.
if (isset($_GET["action"]) && !empty($_GET["action"])) {

    // Lets trim the action requests and make them all small case, this makes it
    // easier to handle the requests in case we made a typo somehow..
    $_request_action = trim(sys_strtolower($_GET["action"]));

    // If the data logger is enabled, track the data for this user for statistics building later on
    if ($_request_action == "datalog") {
        if ($wms->config->general->enable_datalogger) {
            if (isset($_POST)) {
                $post_data = json_decode(json_encode($_POST));
                (isset($post_data->screen_width)) ? $screen_width = $post_data->screen_width : $screen_width = 0;
                (isset($post_data->screen_height)) ? $screen_height = $post_data->screen_height : $screen_height = 0;
                (isset($post_data->window_width)) ? $window_width = $post_data->window_width : $window_width = 0;
                (isset($post_data->window_height)) ? $window_height = $post_data->window_height : $window_height = 0;
                (isset($post_data->agent)) ? $agent = sys_strtolower($post_data->agent) : $agent = NULL;
                if ($post_data->path) {
                    $script = $post_data->path;
                } else {
                    $script = "No script detected";
                }
                if (CURRENT_TIME) {
                    $timestamp = CURRENT_TIME;
                } else {
                    $timestamp = time();
                }
                if (!is_null($agent)) {
                    $device = "Unknown";
                    $os = "Unknown";
                    $browser = "Unknown";
                    if (function_exists(str_contains)) {
                        if (str_contains($agent, "firefox")) {
                            $browser = "Firefox";
                        }
                        if (str_contains($agent, "safari")) {
                            $browser = "Safari";
                        }
                        if (str_contains($agent, "chrome")) {
                            $browser = "Chrome";
                        }
                        if (str_contains($agent, "windows")) {
                            $os = "Windows";
                            $device = "PC";
                        }
                        if (str_contains($agent, "macintosh")) {
                            $os = "OS X";
                            $device = "Mac";
                        }
                        if (str_contains($agent, "iphone")) {
                            $os = "iOS";
                            $device = "iPhone";
                        }
                        if (str_contains($agent, "ipad")) {
                            $os = "iOS";
                            $device = "iPad";
                        }
                        if (str_contains($agent, "linux") || str_contains($agent, "unix")) {
                            $os = "Unix/Linux";
                            $device = "PC";
                        }
                        if (str_contains($agent, "android")) {
                            $os = "Android";
                            $device = "Android";
                        }
                        if (str_contains($agent, "curl")) {
                            $browser = "cURL Request";
                        }
                        if (str_contains($agent, "edge")) {
                            $browser = "Edge";
                        }
                        if (str_contains($agent, "opera")) {
                            $browser = "Opera";
                        }
                        if (str_contains($agent, "oprgx")) {
                            $browser = "Opera GX";
                        }
                    } else {
                        if (!(sys_strpos($agent, "firefox") === FALSE)) {
                            $browser = "Firefox";
                        }
                        if (!(sys_strpos($agent, "safari") === FALSE)) {
                            $browser = "Safari";
                        }
                        if (!(sys_strpos($agent, "chrome") === FALSE)) {
                            $browser = "Chrome";
                        }
                        if (!(sys_strpos($agent, "windows") === FALSE)) {
                            $os = "Windows";
                            $device = "PC";
                        }
                        if (!(sys_strpos($agent, "macintosh") === FALSE)) {
                            $os = "OS X";
                            $device = "Mac";
                        }
                        if (!(sys_strpos($agent, "iphone") === FALSE)) {
                            $os = "iOS";
                            $device = "iPhone";
                        }
                        if (!(sys_strpos($agent, "ipad") === FALSE)) {
                            $os = "iOS";
                            $device = "iPad";
                        }
                        if (!(sys_strpos($agent, "linux") === FALSE) || !(sys_strpos($agent, "unix") === FALSE)) {
                            $os = "Unix/Linux";
                            $device = "PC";
                        }
                        if (!(sys_strpos($agent, "android") === FALSE)) {
                            $os = "Android";
                            $device = "Android";
                        }
                        if (!(sys_strpos($agent, "curl") === FALSE)) {
                            $browser = "cURL Request";
                        }
                        if (!(sys_strpos($agent, "edge") === FALSE)) {
                            $browser = "Edge";
                        }
                        if (!(sys_strpos($agent, "opera") === FALSE)) {
                            $browser = "Opera";
                        }
                        if (!(sys_strpos($agent, "oprgx") === FALSE)) {
                            $browser = "Opera GX";
                        }
                    }
                } else {
                    $device = "Unknown";
                    $os = "Unknown";
                    $browser = "Unknown";
                }
                (isset($post_data->user_timecode)) ? $user_x_code = $post_data->user_timecode : $user_x_code = "Other";
                $window = "{$window_width}x{$window_height}";
                $screen = "{$screen_width}x{$screen_height}";
                // Lets not log spiders information the same way, instead we will log spiders on their own table
                if (isset($wms->session) && !$wms->session->is_spider) {
                    $user_id = $wms->user->uid;
                    $db->insert("analytics_data", array(
                        "user_id"       =>  $user_id,
                        "x_day"         =>  date("d", $timestamp),
                        "x_month"       =>  date("m", $timestamp),
                        "x_year"        =>  date("Y", $timestamp),
                        "x_os"          =>  $os,
                        "x_device"      =>  $device,
                        "x_screen"      =>  $screen,
                        "x_window"      =>  $window,
                        "x_timecode"    =>  $timestamp,
                        "x_browser"     =>  $browser,
                        "x_language"    =>  $wms->lang->language,
                        "x_country"     =>  $user_x_code,
                        "session_id"    =>  $wms->session->session_id,
                        "x_script"      =>  $script
                    ), TRUE);
                } else {
                    // If this IS a spider, lets log their data seperately
                    if (isset($wms->session) && $wms->session->is_spider) {
                        $db->insert("analytics_data", array(
                            "user_id"       =>  0,
                            "x_day"         =>  date("d", $timestamp),
                            "x_month"       =>  date("m", $timestamp),
                            "x_year"        =>  date("Y", $timestamp),
                            "x_os"          =>  "Spider",
                            "x_device"      =>  "Spider",
                            "x_screen"      =>  "Spider",
                            "x_window"      =>  "Spider",
                            "x_timecode"    =>  $timestamp,
                            "x_browser"     =>  "Spider",
                            "x_language"    =>  $wms->lang->language,
                            "x_country"     =>  "Spider",
                            "session_id"    =>  $wms->session->session_id,
                            "x_script"      =>  $script
                        ), TRUE);
                    }
                }
            }
        }
        die();
    }

    // Set the language for the user
    if ($_request_action == "setlang") {
        if (isset($_POST["language_selection"])) {
            $select_language = (int) $_POST["language_selection"];
            if ($language_info = $db->select("languages", array("lid" => $select_language))) {
                if ($wms->lang->language_exists($language_info["lang_directory"])) {
                    if ($wms->user->uid > 0) {
                        $db->update("users", array("default_language" => $language_info["lang_directory"]), array("uid" => $wms->user->uid));
                    }
                    $db->update("sessions", array("default_language" => $language_info["lang_directory"]), array("sid" => $wms->session->session_id));
                }
            }
            (isset($_POST["return_to"]) && !empty($_POST["return_to"])) ? $return_to = trim(sys_strtolower($_POST["return_to"])) : $return_to = "";
            if (isset($wms->config->general->path_to) && !empty($wms->config->general->path_to)) {
                $home_location = "/{$wms->config->general->path_to}/" . $return_to;
            } else {
                $home_location = "/" . $return_to;
            }
            redirect($home_location);
            die();
        } else {
            $return_to = "";
            if (isset($wms->config->general->path_to) && !empty($wms->config->general->path_to)) {
                $home_location = "/{$wms->config->general->path_to}/" . $return_to;
            } else {
                $home_location = "/" . $return_to;
            }
            redirect($home_location);
            die();
        }
    }

    // The user is trying to reset their password
    if ($_request_action == "forgot") {
        if (isset($_POST["post_key"]) && isset($_POST["forgotten_username"])) {
            (isset($_POST["via_js"]) && $_POST["via_js"] == TRUE) ? $is_js = TRUE : $is_js = FALSE;
            $user_id = trim(sys_strtolower($_POST["forgotten_username"]));

            // Check the users to see if there is a matching email on any account
            $query = $db->select("users", array("email" => $user_id));

            // There is a matching user found in the system
            if ($query) {

                // Check if the user already has a reset_key set
                $check_reset_key = $db->select("reset_keys", array("email" => $query["email"]), "*");

                // If there are any existing keys, lets delete them as the new reset
                // should expire those old keys by default
                if ($check_reset_key) {
                    $db->delete("reset_keys", array("email" => $query["email"]));
                }

                // Lets create a new reset key now
                $reset_key = md5(CURRENT_TIME * 2 . $query["email"]);
                $key = CURRENT_TIME * rand() . $reset_key;
                $reset_key = hash("sha256", $key);
                $expiry = CURRENT_TIME + (60 * 60 * 24); // Set the expiry for 24 hours from now.
                $db->insert("reset_keys", array("email" => $query["email"], "user_id" => $query["uid"], "r_key" => $reset_key, "expiry" => $expiry));
                $wms->templates->add_cached_template("reset_email");
                $wms->templates->inject_variables("reset_email", array($wms->config->general->site_name, $reset_key));

                // Now send the email with the reset password link to the user
                sys_sendmail($wms, $query["email"], $wms->lang->sprintf($wms->lang->data->email->reset_title, $wms->config->general->site_name), $wms->templates->render("reset_email", TRUE, FALSE));

            }

            if ($is_js) {
                http_response_code(200);
                $wms->templates->add_cached_template("forgot_submitted");
                $wms->templates->render("forgot_submitted");
                die();
            } else {
                if (isset($wms->config->general->path_to) && !empty($wms->config->general->path_to)) {
                    $home_location = "/{$wms->config->general->path_to}/login.php?do=forgot&s=1";
                } else {
                    $home_location = "/login.php?do=forgot&s=1";
                }
                redirect($home_location);
                die();
            }
        }
    }

    // This checks if the user is attempting to pass a login attempt on the
    // processing core. This is a secure component and has a lot more little checks
    // on it because it's the most important part of the processing unit.
    if ($_request_action == "login") {

        // Ensure they are posting data - we will not handle any get requests on
        // the login function. We optionally check if it's AJAX, which is the
        // prefered login method via Javascript, but we have added a fallback for
        // http direct post on v3 of WMS
        if (isset($_POST["login_key"]) && isset($_POST["username"]) && isset($_POST["password"])) {

            // Check if ajax was used and if remember_me was checked
            (isset($_POST["via_js"]) && $_POST["via_js"] == TRUE) ? $is_js = TRUE : $is_js = FALSE;
            (isset($_POST["remember_me"]) && filter_var($_POST["remember_me"], FILTER_VALIDATE_BOOLEAN) == TRUE) ? $remember_me = TRUE : $remember_me = FALSE;

            // Check if this user is already logged in and display an error page
            // if they are.
            if ($wms->user->uid > 0) {
                $db->insert("errorlogging", array("etime" => CURRENT_TIME, "elocation" => $wms->session->user_script, "uid" => $wms->user->uid, "emessage" => "User tried to login while already logged in."));
                if ($is_js) {
                    http_response_code(412);
                    die("You are already logged in");
                }
                $templates->generate_error(428);
            }

            // We store username and emails as lowercase items and don't have spaces
            // before or after, so make whatever they send us match that.
            $posted_username = trim(sys_strtolower($_POST["username"]));
            $posted_password = $_POST["password"];

            if ($remember_me) {
                $wms->cookiemanager->set_cookie($wms->config, "remember_me", unicode_htmlspecialchars($posted_username));
            } else {
                if (isset($wms->cookiemanager->cookies["remember_me"])) {
                    $wms->cookiemanager->unset_cookie($wms->config, "remember_me");
                }
            }

            // Check if this user has exceeded their global login attempts on the
            // system. This is the session specific check for guests. If they are
            // over send them back to login with an error or if ajax, return the
            // limit exceeded message
            if ($wms->session->check_login_attempts_exceeded($db, $wms->config, 0)) {
                if ($is_js) {
                    http_response_code(428);
                    die($wms->lang->data->templates->attempts_exceeded);
                } else {
                    (isset($_POST["return_to"]) && !empty($_POST["return_to"])) ? $return_to = "&r=" . trim(sys_strtolower($_POST["return_to"])) : $return_to = "";
                    if (isset($wms->config->general->path_to) && !empty($wms->config->general->path_to)) {
                        $home_location = "/{$wms->config->general->path_to}/login.php?e=1" . $return_to;
                    } else {
                        $home_location = "/login.php?e=1" . $return_to;
                    }
                    redirect($home_location);
                    die();
                }
            }

            // Send the username off to see if it's even a valid account - This
            // function will check emails and usernames and return an account ID
            // if there are any matching in the system and a boolean flag
            $login_user = get_user_by_username($db, $wms->config, $posted_username, TRUE);

            // If the user exists, we can process the login attempt
            if ($login_user["found_user"]) {

                // Set the user_id
                $user_id = $login_user["uid"];

                // This is a real user, lets quickly check if the USER account has
                // reached a login limit, even if the users themselves hasn't. This
                // is a second level of protection on brute forcing the account as
                // it is account specific.
                if ($wms->config->general->account_timelock) {
                    if (check_login_attempt_exceeded($db, $wms->config, $user_id)) {
                        if ($is_js) {
                            http_response_code(428);
                            die($wms->lang->data->templates->attempts_exceeded);
                        } else {
                            (isset($_POST["return_to"]) && !empty($_POST["return_to"])) ? $return_to = "&r=" . trim(sys_strtolower($_POST["return_to"])) : $return_to = "";
                            if (isset($wms->config->general->path_to) && !empty($wms->config->general->path_to)) {
                                $home_location = "/{$wms->config->general->path_to}/login.php?e=1" . $return_to;
                            } else {
                                $home_location = "/login.php?e=1" . $return_to;
                            }
                            redirect($home_location);
                            die();
                        }
                    }
                }

                // If the password posted matched with the one for the account, then
                // we can let them into the account. We will also update the user
                // information on the account on a successful login as this was
                // not happening on v2 of WMS properly
                if (validate_password($wms->config, $login_user["hashed_content"], $posted_password)) {

                    (isset($_POST["return_to"])) ? $return_to = trim(sys_strtolower($_POST["return_to"])) : $return_to = "";
                    if ($return_to == "login.php" || $return_to == "login" || $return_to == "login.{$wms->config->general->ext_type}" || empty($return_to)) {
                        if (isset($wms->config->general->path_to) && !empty($wms->config->general->path_to)) {
                            $js_return = "/{$wms->config->general->path_to}/";
                        } else {
                            $js_return = "/";
                        }
                        $return_to = "";
                    } else {
                        $js_return = "reload";
                    }
                    if (isset($wms->config->general->path_to) && !empty($wms->config->general->path_to)) {
                        $home_location = "/{$wms->config->general->path_to}/" . $return_to;
                    } else {
                        $home_location = "/" . $return_to;
                    }

                    // Send the login function to the system
                    $wms->session->process_login($db, $wms, $user_id);

                    // Now send an OKAY for JS to refresh or return them to their
                    // initial login place.
                    if ($is_js) {
                        http_response_code(200);
                        die($js_return);
                    } else {
                        redirect($home_location);
                        die();
                    }
                } else {
                    $wms->session->count_login_attempt($db, $wms->config, $user_id);
                    if ($is_js) {
                        http_response_code(412);
                        die($wms->lang->data->templates->incorrect_username);
                    } else {
                        (isset($_POST["return_to"]) && !empty($_POST["return_to"])) ? $return_to = "&r=" . trim(sys_strtolower($_POST["return_to"])) : $return_to = "";
                        if (isset($wms->config->general->path_to) && !empty($wms->config->general->path_to)) {
                            $home_location = "/{$wms->config->general->path_to}/login.php?e=2" . $return_to;
                        } else {
                            $home_location = "/login.php?e=2" . $return_to;
                        }
                        redirect($home_location);
                        die();
                    }
                }
            } else {
                $wms->session->count_login_attempt($db, $wms->config, 0);
                if ($is_js) {
                    http_response_code(412);
                    die($wms->lang->data->templates->incorrect_username);
                } else {
                    (isset($_POST["return_to"]) && !empty($_POST["return_to"])) ? $return_to = "&r=" . trim(sys_strtolower($_POST["return_to"])) : $return_to = "";
                    if (isset($wms->config->general->path_to) && !empty($wms->config->general->path_to)) {
                        $home_location = "/{$wms->config->general->path_to}/login.php?e=2" . $return_to;
                    } else {
                        $home_location = "/login.php?e=2" . $return_to;
                    }
                    redirect($home_location);
                    die();
                }
            }

        }

    }

    // The user is trying to logout. We will first check that they even ARE logged
    // in, and if so, we can do the logout logic. We won't show an error on the user
    // end for this one either way - we will just redirect them back to the / index
    // of the script 100% of the time.
    if ($_request_action == "logout") {
        // Make sure a user id is set (IT SHOULD BE EITHER WAY!) and that it's
        // not 0 (guest account) and then we can handle the logout stuffs
        if (isset($wms->user->uid) && $wms->user->uid > 0) {

            // Set the session userid back to 0 (guest) so we can reuse the session
            // id for this user without having to delete it. We also remove any
            // user_data on the session variable just incase someone is able to
            // hijack this session id - we don't want them getting that information
            // assigned to them by accident.
            $db->update("sessions", array("uid" => 0, "user_data" => NULL), array("sid" => $wms->session->session_id));
            if (isset($wms->cookiemanager->cookies["wcid"])) {
                $wms->cookiemanager->unset_cookie($wms->config, "wcid");
            }
            unset($wms->user);
            unset($wms->usergroup);
        }

        // Redirect the user to the script /home/ directory
        if (isset($wms->config->general->path_to) && !empty($wms->config->general->path_to)) {
            $home_location = "/{$wms->config->general->path_to}/";
        } else {
            $home_location = "/";
        }
        redirect($home_location);
        die();

    }

    // They didn't match any script conditions on the request so lets generate a
    // Forbidden 403 Error Page instead
    $templates->generate_error(403);

} else {

    // They didn't include the ACTION=VARIABLE on the request so lets generate a
    // Forbidden 403 Error Page instead
    $templates->generate_error(403);

}
