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
 * Template Engine
 *
 *   Where all the magic happens! This class is the core component of WMS - the
 *   templating engine. You can load templates directly from file or from the
 *   database using this file and inject variables into them natively. This allows
 *   you to build dynamic web content with very little or no PHP knowledge!
 *
 * @category    Core
 * @package     Templates
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 3.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */
 
class Templates {

    public $template_count = 0;
    public $cache = array();
    public $nonce_id = 0;
    public $lastfile_rendertime = 0;
    private $configuration;
    private $database;
    private $language;
    private $errorhandler;
    private $cookiemanager;
    private $timer;
    private $wms;

    function __construct($wms, $db) {
        $this->configuration = $wms->config;
        $this->database = $db;
        $this->language = $wms->lang;
        $this->errorhandler = $wms->errormanager;
        $this->cookiemanager = $wms->cookiemanager;
        $this->timer = $wms->timer;
        $this->wms = $wms;
    }
    
    function format_content($content) {
        if ($this->configuration->general->path_to && $this->configuration->general->use_cdn) {
            $base_uri = $this->configuration->general->base_url . $this->configuration->general->path_to;
            $asset_uri = $this->configuration->general->cdn_url;
            $add_asset_dir = "";
        } else {
            if ($this->configuration->general->path_to && !$this->configuration->general->use_cdn) {
                $base_uri = $this->configuration->general->base_url . "/" . $this->configuration->general->path_to;
                $base_uri_only = $this->configuration->general->base_url;
                $asset_uri = $base_uri;
                if (isset($this->configuration->general->asset_folder) && !empty($this->configuration->general->asset_folder)) {
                    $add_asset_dir = "/" . $this->configuration->general->asset_folder;
                } else {
                    $add_asset_dir = "/assets";
                }
            } else {
                $base_uri = $this->configuration->general->base_url;
                $base_uri_only = $base_uri;
                $asset_uri = $base_uri;
                if (isset($this->configuration->general->asset_folder) && !empty($this->configuration->general->asset_folder)) {
                    $add_asset_dir = "/" . $this->configuration->general->asset_folder;
                } else {
                    $add_asset_dir = "/assets";
                }
            }
        }
        if (isset($_SERVER["REQUEST_URI"])) {
            $canonical_path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        } else {
            $canonical_path = "/error_loading_path/";
        }
        if ($this->configuration->general->force_ssl_links) {
            $http_type = "https";
        } else {
            $http_type = isset($_SERVER["HTTPS"]) ? "https" : "http";
        }
        if ($this->configuration->general->style_debug) {
            $revision_code = $this->configuration->general->revision_code . "-" . CURRENT_TIME;
        } else {
            $revision_code = $this->configuration->general->revision_code;
        }
        if ($this->configuration->general->panel_fancy_url) {
            $pf_ext = trim(sys_strtolower($this->configuration->general->panel_fancy_ext));
            $panel_extension_type = ".{$pf_ext}";
            $panel_identifier = "";
            if (!empty($this->configuration->general->panel_fancy_path)) {
                $panel_identifier = "{$this->configuration->general->panel_fancy_path}/";
            }
        } else {
            $panel_extension_type = "";
            $panel_identifier = "?mid=";
        }
        if ($this->configuration->general->show_php) {
            if (isset($this->configuration->general->ext_type)) {
                $extension_set = ".{$this->configuration->general->ext_type}";
            } else {
                $extension_set = ".php";
            }
        } else {
            $extension_set = "";
        }
        $replace = array(
            "%%YEAR%%"          =>      date("Y"),
            "%%AUTHOR%%"        =>      $this->configuration->general->meta_author,
            "%%SUPPORT%%"       =>      $this->configuration->general->support_email,
            "%%METANAME%%"      =>      $this->configuration->general->meta_name,
            "%%SITE_TITLE%%"    =>      $this->configuration->general->site_name,
            "%%LANG_HTML%%"     =>      $this->language->settings->htmllang,
            "%%LANG_CHARSET%%"  =>      $this->language->settings->charset,
            "%%APPTITLE%%"      =>      $this->configuration->general->app_title,
            "%%ASSET_DOMAIN%%"  =>      $http_type . "://" . $asset_uri . $add_asset_dir,
            "%%CANONICAL%%"     =>      $http_type . "://" . $this->configuration->general->base_url . $canonical_path,
            "%%BASEURI%%"       =>      $http_type . "://" . $base_uri_only,
            "%%DOMAIN%%"        =>      $http_type . "://" . $base_uri,
            "%%PANEL%%"         =>      $http_type . "://" . $base_uri . "/" . $this->configuration->general->admin_directory . "/",
            "%%CDN%%"           =>      $http_type . "://" . $this->configuration->general->cdn_url,
            "%%POST_KEY%%"      =>      generate_pepper(55),
            "%%SOCIALS%%"       =>      $this->load_social_links(),
            "%%EXTENSION%%"     =>      $extension_set,
            "%%P_EXT%%"         =>      $panel_extension_type,
            "%%P_ID%%"          =>      $panel_identifier,
            "%%THEME%%"         =>      "default",
            "%%TWITTER%%"       =>      $this->configuration->socials->twitter,
            "%%FACEBOOK%%"      =>      $this->configuration->socials->facebook,
            "%%LINKEDIN%%"      =>      $this->configuration->socials->linkedin,
            "%%YOUTUBE%%"       =>      $this->configuration->socials->youtube,
            "%%PINTREST%%"      =>      $this->configuration->socials->pintrest,
            "%%VERSIONCODE%%"   =>      "rev=" . $revision_code,
            "%%TIME_NOW%%"      =>      date("h:i A", CURRENT_TIME),
            "%%TIME%%"          =>      date("h:i A", CURRENT_TIME),
            "%%TIMECODE%%"      =>      CURRENT_TIME,
            "%%RETURN_TO%%"     =>      $this->generate_return_to()
        );
        $content = str_replace(array_keys($replace), array_values($replace), $content);
        return $content;
    }

    function load_page_information($page_id = NULL) {
        if (isset($page_id)) {
            $page_information_query = $this->database->select("page_information", array("page_name" => $page_id, "is_active" => 1), "*", NULL, 1);
            if ($page_information_query) {
                return array("title" => unicode_htmlspecialchars($this->language->parse($page_information_query["page_title"])), "description" => unicode_htmlspecialchars($this->language->parse($page_information_query["page_description"])));
            } else {
                return array("title" => $this->language->templates->errors->missing_page_info->title, "description" => $this->language->templates->errors->missing_page_info->content);
            }
        } else {
            return array("title" => $this->language->templates->errors->missing_page_info->title, "description" => $this->language->templates->errors->missing_page_info->content);
        }
    }

    function add_cached_template($title, $mode = "sql", $path = NULL, $escape_slashes = FALSE) {
        $mode = sys_strtolower($mode);
        if (isset($this->cache[$title]) && $mode === $this->cache[$title]["mode"]) {
            return TRUE;
        } else {
            $content = NULL;
            switch ($mode) {
                case "sql":
                    $result = $this->database->select("templates", array("template_name" => $title, "is_active" => 1), "*", NULL, 1);
                    if ($result) {
                        $content = $result;
                    }
                    break;
                case "html":
                    if (isset($path)) {
                        $path = rtrim($path, "/");
                        $path = ltrim($path, "/");
                    }
                    if ($result = file_get_contents(dirname(dirname(__FILE__)) . "/templates/" . $path . "/". $title . ".html", TRUE)) {
                        $path = dirname(dirname(__FILE__)) . "/templates/" . $path . "/". $title . ".html";
                        $content["template"] = $result;
                    }
                    break;
            }
            if ($content) {
                if ($escape_slashes) {
                    $content = str_replace("\\'", "'", addslashes($content));
                }
                $content = $this->format_content($content);
                $content = $this->language->parse($content);
                $this->cache[$title] = array(
                    "title"     =>  $title,
                    "mode"      =>  $mode,
                    "path"      =>  $path,
                    "is_esc"    =>  $escape_slashes,
                    "content"   =>  $content
                );
                $this->template_count++;
                return TRUE;
            } else {
                return FALSE;
            }
        }
    }

    function generate_return_to($uri = NULL) {
        if (is_null($uri) && isset($_SERVER["REQUEST_URI"])) {
            if (!empty(trim($this->configuration->general->path_to))) {
                $base_path = str_replace("/{$this->configuration->general->path_to}/", "", $_SERVER["REQUEST_URI"]);
            } else {
                $base_path = ltrim($_SERVER["REQUEST_URI"], "/");
            }
            return $base_path;
        } else {
            if ($uri) {
                $base_path = ltrim($uri, "/");
                return $base_path;
            } else {
                // Fallback incase it's not set somehow
                return CURRENT_SCRIPT;
            }
        }
    }

    function load_social_links() {
        $social_links = "";
        if (isset($this->configuration->socials->facebook) && !empty($this->configuration->socials->facebook)) {
            $social_links .= "<a href=\"{$this->configuration->socials->facebook}\" target=\"_blank\"><i class=\"fab fa-facebook-f\"></i></a>";
        }
        if (isset($this->configuration->socials->twitter) && !empty($this->configuration->socials->twitter)) {
            $social_links .= "<a href=\"{$this->configuration->socials->twitter}\" target=\"_blank\"><i class=\"fab fa-twitter\"></i></a>";
        }
        if (isset($this->configuration->socials->pintrest) && !empty($this->configuration->socials->pintrest)) {
            $social_links .= "<a href=\"{$this->configuration->socials->pintrest}\" target=\"_blank\"><i class=\"fab fa-pinterest-p\"></i></a>";
        }
        if (isset($this->configuration->socials->linkedin) && !empty($this->configuration->socials->linkedin)) {
            $social_links .= "<a href=\"{$this->configuration->socials->linkedin}\" target=\"_blank\"><i class=\"fab fa-linkedin-in\"></i></a>";
        }
        if (isset($this->configuration->socials->youtube) && !empty($this->configuration->socials->youtube)) {
            $social_links .= "<a href=\"{$this->configuration->socials->youtube}\" target=\"_blank\"><i class=\"fab fa-youtube\"></i></a>";
        }
        return $social_links;
    }
    
    function build_language_menu($echo_menu = TRUE) {
        $this->add_cached_template("language_selection");
        $this->inject_variables("language_selection", $this->language->compile_installed_lang($this->database));
        if ($echo_menu) {
            $this->render("language_selection", TRUE);
            return TRUE;
        } else {
            return $this->render("language_selection", TRUE, FALSE);
        }
    }

    function remove_cached_template($title, $mode = "sql") {
        $mode = sys_strtolower($mode);
        if (isset($this->cache[$title]) && $mode === $this->cache[$title]["mode"]) {
            unset($this->$cache[$title]);
            $this->template_count--;
            return TRUE;
        }
        return FALSE;
    }

    function inject_variables($template, $variable_set, $reusable = FALSE) {
        $template_contents = "";
        if ($this->cache[$template]) {
            if (is_array($variable_set)) {
                $count = 1;
                if ($reusable) {
                    $template_contents = $this->cache[$template]["content"];
                    foreach ($variable_set as $variable_replace) {
                        $template_contents = sys_str_replace("%%VAR" . $count . "%%", $variable_replace, $template_contents);
                        $count++;
                    }
                    $template_contents = preg_replace("#%%VAR([0-9]+)#", "NULL", $template_contents);
                    return $template_contents["template"];
                } else {
                    foreach ($variable_set as $variable_replace) {
                        $this->cache[$template]["content"] = sys_str_replace("%%VAR" . $count . "%%", $variable_replace, $this->cache[$template]["content"]);
                        $count++;
                    }
                    $this->cache[$template]["content"] = preg_replace("#%%VAR([0-9]+)%%#", "", $this->cache[$template]["content"]);
                }
            } else {
                if ($reusable) {
                    $template_contents = sys_str_replace("%%VAR1%%", $variable_set, $this->cache[$template]["content"]);
                    $template_contents = preg_replace("#%%VAR([0-9]+)%%#", "", $template_contents);
                    return $template_contents["template"];
                } else {
                    $template_contents = sys_str_replace("%%VAR1%%", $variable_set, $this->cache[$template]["content"]);
                    $template_contents = preg_replace("#%%VAR([0-9]+)%%#", "", $template_contents);
                    $this->cache[$template]["content"] = $template_contents;
                }
            }
        }
    }
    
    function create_headers($in_adminarea = FALSE, $ovideride_nocache = FALSE) {
	    header("X-Frame-Options: SAMEORIGIN");
	    if ($in_adminarea) {
            header("Referrer-Policy: no-referrer");
	    } else {
            header("Referrer-Policy: strict-origin-when-cross-origin");
	    }
    	if ($this->configuration->general->use_nocache || $ovideride_nocache) {
    		header("Cache-Control: no-cache, private");
    	}
        if (function_exists("mb_internal_encoding") && !empty($this->language->settings->charset)) {
    	    @mb_internal_encoding($this->language->settings->charset);
        }
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        header("X-Content-Type-Options: nosniff");
        header("Permissions-Policy: fullscreen=('self'), geolocation=('self')");
        header("Content-type: text/html; charset={$this->language->settings->charset}");
    }

    function list_templates($filter_by_type = FALSE, $type = "SQL") {
        $templates = array();
        if (is_array($this->cache) && !empty($this->cache)) {
            if ($filter_by_type && isset($type)) {
                $type = sys_strtolower($type);
                switch ($type) {
                    case "html":
                        foreach ($this->cache as $template) {
                            if ("html" === $template["mode"]) {
                                $templates[] = $template["title"];
                            }
                        }
                        break;
                    case "sql":
                        foreach ($this->cache as $template) {
                            if ("sql" === $template["mode"]) {
                                $templates[] = $template["title"];
                            }
                        }
                        break;
                    default:
                        $templates = $this->cache;
                        break;
                }
            } else {
                $templates = $this->cache;
            }
        }
        if (!empty($templates)) {
            return $templates;
        }
        return "There are no templates currently loaded.";
    }
    
    function create_and_inject_object($page_id, $additional_head_vars = NULL, $additional_foot_vars = NULL) {
        $set_data = $this->database->select("template_sets", array("set_name" => $page_id, "set_active" => 1));
        $page_data = $this->database->select("page_information", array("page_name" => $page_id, "is_active" => 1));
        if ($set_data && $page_data) {
            $dataObj = json_decode($set_data["set_object"], TRUE);
            if (is_array($dataObj)) {
                $contentarray = array();
                foreach ($dataObj as $template) {
                    if (!($this->add_cached_template($template["template_name"], $template["template_type"], $template["template_path"], $template["escape_slashes"]))) {
                        return FALSE;
                        break;
                    }
                }
                if (isset($this->cache["head"])) {
                    if (!is_null($additional_head_vars)) {
                        if (is_array($additional_head_vars)) {
                            $content_array = array(unicode_htmlspecialchars($this->language->parse($page_data["page_title"])), unicode_htmlspecialchars($this->language->parse($page_data["page_description"])), $page_data["head_includes"]);
                            $variables = array_merge($content_array, $additional_head_vars);
                            $this->inject_variables("head", $variables);
                        } else {
                            $this->inject_variables("head", array(unicode_htmlspecialchars($this->language->parse($page_data["page_title"])), unicode_htmlspecialchars($this->language->parse($page_data["page_description"])), $page_data["head_includes"], $additional_head_vars));
                        }
                    } else {
                        $this->inject_variables("head", array(unicode_htmlspecialchars($this->language->parse($page_data["page_title"])), unicode_htmlspecialchars($this->language->parse($page_data["page_description"])), $page_data["head_includes"]));
                    }
                }
                if (isset($this->cache["foot"])) {
                    if (!is_null($additional_foot_vars)) {
                        if (is_array($additional_foot_vars)) {
                            $_content_array = array($this->format_content($page_data["foot_includes"]));
                            $_variables = array_merge($_content_array, $additional_foot_vars);
                            $this->inject_variables("foot", $_variables);
                        } else {
                            $this->inject_variables("foot", array($this->format_content($page_data["foot_includes"]), $additional_foot_vars));
                        }
                    } else {
                        $this->inject_variables("foot", array($this->format_content($page_data["foot_includes"])));
                    }
                }
                return TRUE;
            }
        }
		return FALSE;
    }

    function create_page_object($page_id) {
        $set_data = $this->database->select("template_sets", array("set_name" => $page_id, "set_active" => 1), "*", NULL, 1);
        if ($set_data) {
            $dataObj = json_decode($set_data["set_object"], true);
            if (is_array($dataObj)) {
                foreach ($dataObj as $template) {
                    if (!($passed = $this->add_cached_template($template["template_name"], $template["template_type"], $template["template_path"], $template["escape_slashes"]))) {
                        return FALSE;
                        break;
                    }
                }
                return TRUE;
            }
        }
		return FALSE;
    }

    function render($template, $end_of_file = FALSE, $echo_file = TRUE) {
        if ($this->cache[$template]) {
            if ($this->cache[$template]["mode"] == "sql") {
                $template = $this->cache[$template]["content"]["template_contents"];
            } else {
                $template = $this->cache[$template]["content"]["template"];
            }
            if (!$end_of_file) {
                $template = $template . "\n";
            }
            if ($echo_file) {
                echo $template;
            } else {
                return $template;
            }
        } else {
            if ($echo_file) {
                echo $this->language->data->templates->errors->missing_template_from_cache;
            } else {
                return $this->language->data->templates->errors->missing_template_from_cache;
            }
        }
    }

    function build_panel_menu($slug_id = 0, $usergroup = 0, $is_admin = FALSE) {
        $menu_items = $this->database->select("panel_menu", array("is_active" => 1), "*", array(array("fields" => "sort_key", "direction" => "ASC")));
        if ($menu_items) {
            $menu_array = sort_menu_array($menu_items);
        } else {
            return "<li>Unable to load menu items from the database.</li>";
        }
        $admin_menu = construct_panel_menu($menu_array, $usergroup, $is_admin, $slug_id, FALSE);
        $admin_menu = $this->format_content($admin_menu);
        return $this->language->parse($admin_menu);
    }

    function login_box($echo = TRUE) {
        $this->add_cached_template("login_box");
        $saved_username = "";
        $remember_check = "";
        if (isset($this->cookiemanager->cookies["remember_me"])) {
            $saved_username = trim(sys_strtolower(unicode_htmlspecialchars(undo_htmlentities($this->cookiemanager->cookies["remember_me"]))));
            $remember_check = " checked";
        }
        $this->inject_variables("login_box", array($saved_username, $remember_check));
        if ($echo) {
            $this->render("login_box");
        } else {
            return $this->render("login_box", FALSE, FALSE);
        }
    }

    function login($error_message = "", $message_class = "", $return = NULL, $echo = TRUE) {
        $this->add_cached_template("login");
        $saved_username = "";
        $remember_check = "";
        if (is_null($return)) {
            $return_to = "";
        } else {
            $return_to = "{$return}/";
        }
        if (isset($_GET["r"])) {
            $return_to = trim(sys_strtolower(unicode_htmlspecialchars(undo_htmlentities($_GET["r"]))));
        }
        if (isset($_GET["e"])) {
            $error_code = (int) $_GET["e"];
            switch ($error_code) {
                case 1:
                    $error_message = $this->language->data->templates->attempts_exceeded;
                    $message_class = "failed";
                    break;
                case 2:
                    $error_message = $this->language->data->templates->incorrect_username;
                    $message_class = "failed";
                    break;
            }
        }
        if (isset($this->cookiemanager->cookies["remember_me"])) {
            $saved_username = trim(sys_strtolower(unicode_htmlspecialchars(undo_htmlentities($this->cookiemanager->cookies["remember_me"]))));
            $remember_check = " checked";
        }
        $this->inject_variables("login", array($error_message, $message_class, $saved_username, $remember_check, $return_to, $this->build_language_menu(FALSE)));
        if ($echo) {
            $this->render("login", TRUE);
        } else {
            return $this->render("login", TRUE, FALSE);
        }
    }

    function forgot($echo = TRUE) {
        $this->add_cached_template("forgot_pw");
        if (isset($_GET["s"]) && $_GET["s"] == 1) {
            $this->add_cached_template("forgot_submitted");
            $content_message = $this->render("forgot_submitted", FALSE, FALSE);
        } else {
            $this->add_cached_template("forgot_form");
            $content_message = $this->render("forgot_form", FALSE, FALSE);
        }
        $this->inject_variables("forgot_pw", array($this->build_language_menu(FALSE), $content_message));
        if ($echo) {
            $this->render("forgot_pw", TRUE);
        } else {
            return $this->render("forgot_pw", TRUE, FALSE);
        }
    }

    function generate_error($error_code, $template_name = "errors", $template_type = "sql", $path = "static/errors") {

        // Set the header response code
        http_response_code($error_code);

        // Allow the user to specify html templates or use the SQL versions
        $template_type = sys_strtolower($template_type);

        switch ($template_type) {
            case "html":
                $this->add_cached_template($template_name, "html", $path);
                break;
            default:
                $template_name = "error_template";
                $this->add_cached_template($template_name);
                break;
        }
        switch ($error_code) {
            case 403:
                $error_type = $this->language->data->templates->errors->forbidden_access->page_title;
                $error_line = $this->language->data->templates->errors->forbidden_access->error_line;
                $error_message = $this->language->data->templates->errors->forbidden_access->error_msg;
                break;
            case 404:
                $error_type = $this->language->data->templates->errors->missing_page->page_title;
                $error_line = $this->language->data->templates->errors->missing_page->error_line;
                $error_message = $this->language->data->templates->errors->missing_page->error_msg;
                break;
            case 409:
                $error_code = "002";
                $error_type = $this->language->data->templates->errors->already_loggedin->page_title;
                $error_line = $this->language->data->templates->errors->already_loggedin->error_line;
                $error_message = $this->language->data->templates->errors->already_loggedin->error_msg;
                break;
            case 412:
                $error_code = "003";
                $error_type = $this->language->data->templates->errors->object_creation_failed->page_title;
                $error_line = $this->language->data->templates->errors->object_creation_failed->error_line;
                $error_message = $this->language->data->templates->errors->object_creation_failed->error_msg;
                break;
            case 428:
                $error_code = "003";
                $error_type = $this->language->data->templates->errors->already_logged_in->page_title;
                $error_line = $this->language->data->templates->errors->already_logged_in->error_line;
                $error_message = $this->language->data->templates->errors->already_logged_in->error_msg;
                break;
            case 500:
                $error_type = $this->language->data->templates->errors->server_error->page_title;
                $error_line = $this->language->data->templates->errors->server_error->error_line;
                $error_message = $this->language->data->templates->errors->server_error->error_msg;
                break;
            default:
                $error_code = "001";
                $error_type = $this->language->data->templates->errors->generic_error->page_title;
                $error_line = $this->language->data->templates->errors->generic_error->error_line;
                $error_message = $this->language->data->templates->errors->generic_error->error_msg;
                break;
        }
        $this->inject_variables($template_name, array($error_type, $error_message, $error_code, $error_line));
        $this->render($template_name, TRUE);

        // Shutdown the database connection if were done to free resources
        $this->wms->close($this->database, $this);
    }

}