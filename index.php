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
 * Default Index
 *
 *   Basic index.php file to load the WMS system. You can build your system out
 *   from this point using this page as a template on which to structure your
 *   new website from.
 *
 * @category    User Defined
 * @package     WMS User Website
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 */

// You must define if direct access to this file is allowed, and then the current
// script location (this will be used for user tracking on the session handler).
// We also have to check if it's already been defined as this can be called by
// another script and we don't want to throw any errors for redefining a constant
if (!defined("ALLOW_ACCESS")) {
    define("ALLOW_ACCESS", TRUE);
}

if (!defined("CURRENT_SCRIPT")) {
    define("CURRENT_SCRIPT", "index.php");
}

// Include the global script to enable the WMS engine in the background
require_once("global.php");
$wms->templates->create_headers();

// We will be taking control of our system by implementing a page object. This
// is a database level object consisting of all of the templates as a set that
// will be rendered on this page. If the object doesn't exist, well throw an
// error that it doesn't exist in the system
if ($wms->templates->create_page_object("default_index")) {

    // We have loaded into our object, now we can inject variables into the
    // templates before they are rendered. You can inject everything from
    // here and then render all of it below
    $wms->templates->inject_variables("head", array("Index Page", "Description of this page"));
    $wms->templates->inject_variables("foot", NULL);

    // Now we will render the header template that we just injected into
    $wms->templates->render("head");

    // You can use the following to see if a user is logged in to show members
    // only content to the user, or restricted content by user groups.
    if ($wms->user->uid > 0) {
        // A simple logout button
        echo "Logout: <a href='process.php?action=logout'>CLICK HERE TO LOGOUT</a>.";

        // Check if the user is an admin, and show them the ACP link if so
        if ($wms->session->is_admin) {
            echo "<br>\nAdmin Panel: <a href='panel/'>ADMIN PANEL</a>.";
        }
    } else {
        // A simple way to check login attempts and display remaining attempts
        if ($wms->session->check_login_attempts_exceeded($db, $wms->config, $wms->user->uid)) {
            echo "You have 0 tries left to login. ({$wms->session->login_attempts}/{$wms->config->general->max_login_trys})<br><br>\n";
        } else {
            $attempts = $wms->config->general->max_login_trys - $wms->session->login_attempts;
            echo "You have {$attempts} attempts left to login. ({$wms->session->login_attempts}/{$wms->config->general->max_login_trys})<br><br>\n";
        }
        // Print the system login_box template - This is not a full page login like
        // you see on the admin panel, rather a small form you can insert into your
        // templates to enable logins. To enable javascript login on this box, you
        // will need to include the javascript_login_module.js in the footer of your
        // site and have jquery enabled, otherwise it will use html and the processing
        // script to run
        $wms->templates->login_box();
        echo "\n<br><br>Or you can <a href='login'>click here to go to the login page</a> instead if you'd like.";
    }

    // We are done implementing the page, lets close with the footer template
    // and add the true variable so that it clarifies its the end of the page
    // and doesn't print a \n (new line) character.
    $templates->render("foot", TRUE);

    // Finally, lets run the shutdown on WMS command and make sure everything
    // is free and closed, this is the last thing we will do on any page.
    $wms->close($db, $templates);
} else {
    $wms->templates->generate_error(412);
}
