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
 * Reset Passwords
 *
 *   This script allows users to reset their password when forgotten
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

if (!defined("ALLOW_ACCESS")) {
    define("ALLOW_ACCESS", TRUE);
}

if (!defined("CURRENT_SCRIPT")) {
    define("CURRENT_SCRIPT", "reset.php");
}

// Include the global script to enable the WMS engine in the background
require_once("global.php");

// Check if the user is already logged in first
if (isset($wms->user) && $wms->user->uid > 0) {
    $wms->templates->generate_error(403);
} else {
    // Now we check if they have a key set and see if it exists or else we will
    // give them a generic error page saying the reset key is invalid.
    if (isset($_GET["r_key"])) {
        $query = $db->select("reset_keys", array("r_key" => $_GET["r_key"], array("separator" => "AND", "field_name" => "expiry", "operator" => ">=", "value" => CURRENT_TIME)));
        if ($query) {

            $error_message = "";
            $error_css = "";

            // The reset key is valid and exists! Now we can display the reset page.
            if (isset($_POST["new_password"]) && isset($_POST["new_password_repeated"])) {

                $new_pw = trim($_POST["new_password"]);
                $repeat = trim($_POST["new_password_repeated"]);

                // Check that the new password matches our requirements for this website
                $new_password_is_valid = check_password_conditions($new_pw, $repeat);
                if ($new_password_is_valid["status"]) {
                    // Update the login key for the user
                    update_loginkey($db, $query["user_id"]);
                    
                    // Generate a new password hash
                    $_pass = create_password($wms->config, $new_pw);
                    
                    // Set the new password and delete the key
                    $db->update("users", array("password" => $_pass), array("uid" => $query["user_id"], "email" => $query["email"]));
                    $db->delete("reset_keys", array("r_key" => $_GET["r_key"]));
                    $wms->templates->add_cached_template("reset_email_done");
                    if ($wms->config->general->force_ssl_links) {
                        $http_type = "https";
                    } else {
                        $http_type = isset($_SERVER["HTTPS"]) ? "https" : "http";
                    }
                    if ($wms->config->general->path_to) {
                        $domain = $http_type . "://" . $wms->config->general->base_url . $wms->config->general->path_to;
                    } else {
                        $domain =  $http_type . "://" . $wms->config->general->base_url;
                    }
                    $wms->templates->inject_variables("reset_email_done", array($wms->config->general->site_name, $wms->lang->sprintf($wms->lang->data->email->pw_changed_email, $wms->config->general->site_name, $query["email"], $domain)));
                    sys_sendmail($wms, $query["email"], $wms->lang->sprintf($wms->lang->data->email->email_reset_done, $wms->config->general->site_name), $wms->templates->render("reset_email_done", TRUE, FALSE));
                    if ($wms->config->general->show_php) {
                        $extension = ".php";
                    } else {
                        $extension = "";
                    }
                    $return_to = "reset{$extension}?changed";
                    if (isset($wms->config->general->path_to) && !empty($wms->config->general->path_to)) {
                        $home_location = "/{$wms->config->general->path_to}/" . $return_to;
                    } else {
                        $home_location = "/" . $return_to;
                    }
                    redirect($home_location);
                    die();
                } else {
                    $error_message = $new_password_is_valid["e_message"];
                    $error_css = $new_password_is_valid["css"];
                }

            }
            $wms->templates->add_cached_template("reset_form");
            $wms->templates->inject_variables("reset_form", array($wms->templates->build_language_menu(FALSE), $error_css, $error_message));
            $wms->templates->render("reset_form");
            die();
        }
    }
    if (isset($_GET["changed"])) {
        // Show a message that password was changed
        $wms->templates->add_cached_template("reset_success");
        $wms->templates->inject_variables("reset_success", array($wms->templates->build_language_menu(FALSE)));
        $wms->templates->render("reset_success");
        die();
    }
    // Show an error here saying its expired
    $wms->templates->add_cached_template("reset_invalid");
    $wms->templates->inject_variables("reset_invalid", array($wms->templates->build_language_menu(FALSE)));
    $wms->templates->render("reset_invalid");
    die();
}