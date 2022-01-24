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
 * Control Panel
 *
 *   This is your control panel. Some functions here are defined for Admins only
 *   while others are available for editors or usrs above a defined level of user
 *   group ID. You can modify their permissions by group in the admin portion and
 *   define allowed access groups on scope of this script
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
    define("CURRENT_SCRIPT", "panel/index.php");
}

if (!defined("ADMIN_PANEL")) {
    define("ADMIN_PANEL", TRUE);
}

// Set the usergroups required to be able to access this panel without being
// a member of the admin groups. This has no effect on users in the admin group as
// they are admins and bypass this setting. Comma seperated usergroup numbers.
if (!defined("GROUP_REQUIRED")) {
    define("GROUP_REQUIRED", array(
        4
    ));
}

// Include the global script to enable the WMS engine in the background. Since we
// are in a subfolder, we need to change directory UP one to get the global scope.
require_once dirname(dirname(__FILE__)) . "/global.php";

// Check we are logged in, then we can pass the get_data without having to process
// anything else. This allows us to use jQuery Ajax calls to load data that
// we can display in modals without having to render everything else.
if (isset($_GET["get_data"])) {
    if (isset($wms->user) && $wms->user->uid > 0) {
        die();
    }
}

// Check if they are logged in. If not, lets show the login page instead with a
// return_to the control panel.
if (isset($wms->user) && $wms->user->uid > 0) {

    // Check if they are admin and if so, check if the system needs to be updated
    // as there is no point giving this information to non admin users.
    if ($wms->session->is_admin) {
        require_once dirname(dirname(__FILE__)) . "/core/class_update.php";
        $updateTaskManager = new UpdateTaskManager;
        $updateTaskManager->read($db);

        // If the version on cache and the version on core are not the same we
        // have an issue and need to deal with it. This should not happen as it 
        // means an upgrade failed and you have conflicting cores and db types
        if (!$wms->version == $updateTaskManager->version_information->currentversion) {
            die("<b>Core error</b>:: <br>There is a major conflict with the current system version and the database version on file.<br>You should never see this error unless things have gone horribly sideways!");
        }

        // If it's been more than 5 days since the last check of the system to
        // see if it's up to date, lets go ahead and run the check for a new
        // edition of the WMS script
        if ((CURRENT_TIME - $updateTaskManager->version_information->lastchecked) >= 432000) {
            $updateTaskManager->check_for_new_version($wms, $db);
        }
    }

    // Check if the user has access to see the panel - This doesn't mean an individual
    // script, but rather the panel as a whole. If not, we will show them a generic
    // error message that they don't have permissions to see this page. We moved
    // to this approach in WMS v3.0.0 as a way to combine the editor window and
    // the admin panel into a single mod cp and allow developers the ability to 
    // make their plugins targeted to both groups easier as well.
    if (in_array($wms->user->usergroup, GROUP_REQUIRED) || $wms->session->is_admin) {

        // Since we are in admin area we override nocache and set no-reffer headers
        // - Side note - 
        // #  Any headers or cookies you wish to set need to be done before this
        // #  line as once the headers are sent you can no longer modify them
        $wms->templates->create_headers(TRUE, TRUE);

        // Default the PID to 0, incase there in no page set or no matching page
        // at least. Also lets create the default panel object as an error so we
        // can show an error from database should they visit a page not yet
        // implemented or that has been removed and cached by browser.
        $panel_current_pid = 0;
        $panel_page_object = "panel_error";
        $panel_content_templates = array(
            array(
                "template_name" =>  "panel_error",
                "template_type" =>  "SQL"
            )
        );

        // DO YOUR CONTENT GENERATION HERE TO GET THE PAGE READY TO OUTPUT
        if (isset($_GET["mid"])) {

            // Get the page id being passed from the GET variable
            $page_id = trim(sys_strtolower($_GET["mid"]));

            if ($page_id == "stats" || $page_id == 1) {
                $panel_content_templates = array(
                    array(
                        "template_name" =>  "main",
                        "template_type" =>  "HTML",
                        "template_path" =>  "static/admin/general/statistics_module",
                        "inject"        =>  NULL,
                        "render_now"    =>  FALSE
                    )
                );
                $panel_current_pid = 2;
                $panel_page_object = "panel_stats";
            }

        } else {
            $panel_content_templates = array(
                array(
                    "template_name" =>  "main",
                    "template_type" =>  "HTML",
                    "template_path" =>  "static/admin/dashboard",
                    "inject"        =>  NULL,
                    "render_now"    =>  FALSE
                )
            );
            $panel_current_pid = 1;
            $panel_page_object = "panel_dashboard";
        }


        // OUTPUT THE PAGE - Using the page objects we dynamically create a title,
        // description, head includes, and foot includes while also defaulting the
        // head and foot templates into the scope so they can be rendered.
        if ($wms->templates->create_and_inject_object($panel_page_object, NULL, date("Y-m-d, g:i A", CURRENT_TIME))) {

            // Lets render the head portion of the HTMl right away
            $wms->templates->render("head");

            // Create an empty template array for deferring templates
            $deferred_templates = array();

            // Load the panel content templates from above. We will by default
            // render them in the order they were added as a deferred template
            // below, but you can set render_now to true to render it NOW before
            // the page content, but below the header. Obviously if you want to you
            // can move this or just not use it if you want.
            if (isset($panel_content_templates)) {
                foreach ($panel_content_templates as $panel_template) {
                    (isset($panel_template["template_path"])) ? $template_path = $panel_template["template_path"] : $template_path = NULL;
                    (isset($panel_template["inject"])) ? $inject = $panel_template["inject"] : $inject = NULL;
                    $wms->templates->add_cached_template($panel_template["template_name"], $panel_template["template_type"], $template_path);
                    $wms->templates->inject_variables($panel_template["template_name"], $inject);
                    if (isset($panel_template["render_now"]) && $panel_template["render_now"]) {
                        $wms->templates->render($panel_template["template_name"]);
                    } else{
                        $deferred_templates[] = $panel_template["template_name"];
                    }
                }
            }

            // Lets print the update essage for the admins so they know the system
            // needs to be updated if there is an update available.
            if ($wms->session->is_admin && isset($updateTaskManager) && $updateTaskManager->needs_update) {
                $wms->templates->add_cached_template("update_banner");
                $wms->templates->inject_variables("update_banner", array($wms->version, $wms->version_code, $updateTaskManager->version_information->new_version, $updateTaskManager->version_information->new_versioncode));
                $wms->templates->render("update_banner");
            }

            // Add the header and navigatiom sections now.
            $wms->templates->add_cached_template("header", "HTML", "static/admin");
            $wms->templates->add_cached_template("navigation", "HTML", "static/admin");

            // Inject the navigation component with the panel menu based on the
            // users current permissions and viewing allowance.
            $wms->templates->inject_variables("navigation", array($wms->templates->build_panel_menu($panel_current_pid, $wms->user->usergroup, $wms->session->is_admin), $wms->templates->build_language_menu(FALSE)));

            // Render the page output and foreach deferred template we will load
            // them in the middle between the header, navigation, and footer.
            $wms->templates->render("header");
            $wms->templates->render("navigation");
            foreach ($deferred_templates as $template) {
                $wms->templates->render($template);
            }
            $wms->templates->render("foot", TRUE);
        } else {
            echo "Unable to load the admin page object - '{$panel_page_object}' - Please check the database to ensure it exists.";
        }
    } else {
        // No permission, so lets show them a 403 forbidden error instead
        $wms->templates->generate_error(403);
    }

} else {
    $wms->templates->login($wms->lang->data->templates->login_title, "information", $wms->config->general->admin_directory);
}