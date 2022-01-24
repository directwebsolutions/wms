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
 * English Language Pack
 *
 *   English version of your website. This is the default website language.
 *
 * @category    Language
 * @package     English
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 2.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */
 
// Settings for this language
$settings = array(
    "name"      =>  "English (American)",
    "htmllang"  =>  "en",
    "charset"   =>  "UTF-8"
);

// Language Global Variables
$l = array(
    "generic" => array(
        "na"                =>  "N/A",
        "unknown"           =>  "Unknown",
        "create_new"        =>  "Create New",
        "error"             =>  "Error",
        "logout"            =>  "Logout",
        "logout_title"      =>  "Logout of your account",
        "no_perms"          =>  "You do not have permission to view this page.",
        "error_loading"     =>  "There was an error loading the requested page.",
        "edit_template"     =>  "Edit Template",
        "delete_template"   =>  "Delete Template",
        "rename_folder"     =>  "Rename Folder",
        "delete_folder"     =>  "Delete Folder",
        "edit_object"       =>  "Edit Object",
        "delete_object"     =>  "Delete Object",
        "error_submitting"  =>  "Error submitting data.",
        "preview"           =>  "Preview",
        "preview_website"   =>  "Opens a new tab to your website",
        "current_time"      =>  "Current Time",
        "powered_by"        =>  "Powered By",
        "return_home"       =>  "Return to the homepage",
        "email_address"     =>  "E-mail Address",
        "login_return"      =>  "Return to login",
        "home_return"       =>  "Return to home",
        "reset_sent"        =>  "Reset code sent - Check your e-mail",
        "login"             =>  "Login",
        "login_title"       =>  "Login to access your account.",
        "forgot_pw"         =>  "Forgot Password",
        "forgot_pw_title"   =>  "Send a reset code to recover your account.",
        "success"           =>  "Success"
    ),
    "email" => array(
        "pw_changed_email"  =>  "The password for your {1} account, {2}, has been successfully reset. \n\r <br><br>If you did not make this change or you believe an unauthorised person has accessed your account, you should go our website and reset your password immediately. Then sign into your account page at <a href='{3}'>{3}</a> to review and update your security settings. \n\r <br><br>If you need additional help, please contact Support.  \n\r <br><br>Sincerely, \n\r <br>{1} Support",
        "email_reset_done"  =>  "Your {1} password has been reset",
        "reset_success"     =>  "The password for your account has been reset successfully. You can click the link below to return to the website and login.",
        "reset_message"     =>  "Fill out the fields below to reset your password on your account:",
        "repeat_pw"         =>  "Repeat Password",
        "new_pw"            =>  "New Password",
        "cancel"            =>  "Cancel",
        "change_pw"         =>  "Change Password",
        "reset"             =>  "Reset",
        "reset_title"       =>  "Reset your {1} password",
        "hello"             =>  "Hello",
        "email_reason"      =>  "You are receiving this email because we have received a password reset request for your account. This link will be valid for 24 hours.",
        "reset_password"    =>  "Reset Password",
        "no_further_action" =>  "If you did not make this request, no further action is required.",
        "regards"           =>  "Regards",
        "trouble_message"   =>  "If you are having trouble clicking the \"Reset Password\" button, copy and paste the URL below into your browser:",
        "rights_reserved"   =>  "All rights reserved.",
        "reset_expired"     =>  "The reset link you have entered is invalid or has expired. Please submit a new reset request. Requests are only valid for 24 hours from the time of request."
    ),
    "admin_menu" => array(
        "dashboard"         =>  "Dashboard",
        "dashboard_title"   =>  "View the dashboard",
        "dashboard_desc"    =>  "Manage the content of your website from this Dashboard Panel.",
        "assets"            =>  "Assets",
        "asset_title"       =>  "Manage your website assets",
        "users"             =>  "Users",
        "users_title"       =>  "Manage User Accounts",
        "users_manage"      =>  "Manage Users",
        "userset_title"     =>  "Manage User Settings",
        "extensions"        =>  "Extensions",
        "extensions_title"  =>  "Manage your add-ons and extensions",
        "general"           =>  "General",
        "general_title"     =>  "View general settings",
        "stats"             =>  "Website Statistics",
        "stats_title"       =>  "View website statistics",
        "spiders"           =>  "Manage Spiders",
        "spiders_title"     =>  "View and manage Spiders &amp; Search Engines",
        "sessions"          =>  "Manage Sessions",
        "sessions_title"    =>  "View and manage session data",
        "wms_info"          =>  "Version Information",
        "wms_info_title"    =>  "View your current WMS Version and update options",
        "lang"              =>  "System Languages",
        "lang_title"        =>  "Manage system languages",
        "cron"              =>  "Scheduled Tasks",
        "cron_title"        =>  "View scheduled cron tasks",
        "errors"            =>  "View Error Logs",
        "errors_title"      =>  "View stored system errors",
        "templates"         =>  "Templates &amp; Objects",
        "templates_title"   =>  "View the system templates",
        "css"               =>  "CSS Stylesheets",
        "css_title"         =>  "View the system CSS sheets",
        "fonts"             =>  "System Fonts",
        "fonts_title"       =>  "View the system fonts",
        "image_assets"      =>  "Images &amp; Videos",
        "page_info"         =>  "Page Information",
        "addons"            =>  "Manage Extensions",
        "addons_title"      =>  "Manage extensions &amp; Add-ons",
        "usergroups"        =>  "Manage User Groups",
        "usergroups_title"  =>  "Manage user groups"
    ),
    "templates" => array(
        "errors" => array(
            "missing_template_from_cache" => "Unable to load this template from the cache.",
            "missing_page_info" => array(
                "title"     =>  "Error",
                "content"   =>  "The page information was unable to be loaded from the database."
            ),
            "forbidden_access"  =>  array(
                "page_title"    =>  "Access Denied",
                "error_line"    =>  "Well this is awkward..",
                "error_msg"     =>  "The page or resource you are attempting to view is Forbidden and cannot be viewed. Sorry about that!"
            ),
            "missing_page"  =>  array(
                "page_title"    =>  "File not found",
                "error_line"    =>  "Oops! Nothing was found",
                "error_msg"     =>  "The page or resource you are looking for is missing or may have been had its name changed. Please check the URL and try again."
            ),
            "server_error"  =>  array(
                "page_title"    =>  "Internal Error",
                "error_line"    =>  "It's not you, It's us.",
                "error_msg"     =>  "The server is having a tough day and is unable to process anything right now due to an error. Please try again later."
            ),
            "object_creation_failed"  =>  array(
                "page_title"    =>  "Template Error",
                "error_line"    =>  "Don't be alarmed!",
                "error_msg"     =>  "The page object you were attempting to create is missing some information - This is usually because of missing templates."
            ),
            "already_logged_in"  =>  array(
                "page_title"    =>  "Fatal Error",
                "error_line"    =>  "What are you doing?",
                "error_msg"     =>  "You are already logged in but appear to be trying to log in again for some reason. This is weird behavior and has been logged. Now, "
            ),
            "already_loggedin"  =>  array(
                "page_title"    =>  "Already logged in",
                "error_line"    =>  "You already did it!",
                "error_msg"     =>  "You are already logged in but appear to be trying to log in again for some reason? You can click the following link to "
            ),
            "generic_error" =>  array(
                "page_title"    =>  "Unknown Error",
                "error_line"    =>  "What did you do?",
                "error_msg"     =>  "We're not really sure what's happened here but you've triggered some kind of a system error. Please,"
            )
        ),
        "login_title"       =>  "Enter your details to login",
        "login_user"        =>  "Username / Email",
        "login_pass"        =>  "Password",
        "login_remember"    =>  "Remember my username",
        "login_h1"          =>  "Sign In",
        "login_forgot_link" =>  "Forgot Password?",
        "incorrect_username"    =>  "Invalid Username or Password",
        "attempts_exceeded" =>  "Login attempts have been exceeded",
        "forgot_title"      =>  "Reset",
        "forgot_message"    =>  "If you have forgotten your password, enter the e-mail address linked to your account below. If there is a matching account in our system, we will send you a code to reset your password."
    ),
    "debug" => array(
        "generated_in"      =>  "Generated in {1}",
        "dweight"           =>  "({1}% PHP / {2}% {3})",
        "sql_queries"       =>  "SQL Queries: {1}",
        "server_load"       =>  "Server Load: {1}",
        "memory_usage"      =>  "Memory Usage: {1}"
    ),
    "admin" => array(
        "templates" => array(
            "title"         =>  "Templates",
            "obj_title"     =>  "Template Page Objects",
            "available_tpl_html"    =>  "Available HTML Templates",
            "available_tpl_sql"     =>  "Available SQL Templates"
        ),
        "errors" => array(
            "no_tpl_obj"    =>  "There are no templates objects at this time.",
            "no_templates"  =>  "There are no template items.",
            "no_templates_spf"      =>  "There are no {1} template items.",
            "tpl_select_tpls"       =>  "Select templates to add to the object.",
            "no_login_tpl"          =>  "The login templates have been removed from your template set.<br>You will need to manually rebuild them from file.",
            "invalid_tpl_obj_title" =>  "Invalid page object title.",
            "tpl_obj_exists"        =>  "Page object already exists with this name.",
            "page_doesnt_exist"     =>  "This page or script does not exist",
            "doesnt_exist_message"  =>  "The script you are attempting to load does not exist or the templates are missing.",
            "return_to_dash"        =>  "Return to Dashboard"
        )
    )
);