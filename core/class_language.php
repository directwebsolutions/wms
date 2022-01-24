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
 * Language Engine
 *
 *   Set the language up for your site to make it easier to load multiple local
 *   versions of the site and translate using generic LANG files.
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
 
class Lang {

    public $path;
    public $language;
    public $fallback = "english";
    public $settings = array();
    public $data;

    function set_path($path) {
		$this->path = $path;
	}

	function language_exists($language) {
		$language = preg_replace("#[^a-z0-9\-_]#i", "", $language);
		if (file_exists($this->path . "/{$language}/core_language.php")) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	function compile_installed_lang($database) {
	    if ($installed_langs = $database->select("languages", array("is_active" => 1))) {
	        if (array_key_exists(0, $installed_langs)) {
	            $language_list = "";
	            foreach ($installed_langs as $languages) {
	                if ($this->language_exists(sys_strtolower($languages["lang_directory"]))) {
	                    $add = "";
	                    if ($this->language == sys_strtolower($languages["lang_directory"])) {
	                        $add = " selected";
	                    }
	                    $language_list .= "<option value=\"" . $languages["lid"] . "\"{$add}>" . $languages["lang_title"] . "</option>";
	                }
	            }
	            if (!empty($language_list)) {
	                return $language_list;
	            } else {
	                return "<option>There are no languages installed.</option>";
	            }
	        } else {
	            if ($this->language_exists(sys_strtolower($installed_langs["lang_directory"]))) {
	                return "<option value=\"" . $installed_langs["lid"] . "\">" . $installed_langs["lang_title"] . "</option>";
	            } else {
	                return "<option>There are no languages installed.</option>";
	            }
	        }
	    }
	    return "<option>There are no languages installed.</option>";
	}
	
	function set_language($language = "") {
		$language = sys_strtolower(preg_replace("#[^a-z0-9\-_]#i", "", $language));
		if ($language == "") {
			$language = $this->fallback;
		}
		if (!$this->language_exists($language)) {
			die("Language $language ($this->path/$language) is not installed");
		}
		$this->language = $language;
		require $this->path . "/" . $language . "/core_language.php";
		if (isset($l) && is_array($l)) {
			$this->data = json_decode(json_encode($l));
		}
		if (isset($settings) && is_array($settings)) {
			// Convert this back to an object for OOP useage
			$this->settings = json_decode(json_encode($settings));
		}
	}

	function sprintf($string) {
		$arg_list = func_get_args();
		$num_args = count($arg_list);
		for ($i = 1; $i < $num_args; $i++) {
			$string = str_replace('{'.$i.'}', $arg_list[$i], $string);
		}
		return $string;
	}

	function parse($contents) {
		$contents = preg_replace_callback("#<lang:([a-zA-Z0-9-_>]+)>#", array($this, "parse_replace"), $contents);
		return $contents;
	}

	function parse_replace($matches) {
	    $language_string = $matches[1];
	    $locations = explode("->", $language_string);
	    $data = $this->data;
	    foreach ($locations as $location) {
	        $data = $data->{$location};
	    }
		return $data;
	}

}