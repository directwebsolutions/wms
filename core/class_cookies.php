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
 * Cookie Manager
 *
 *   Handle the getting and setting of cookies through this class. These will be
 *   specific to the WMS system as the prefix is defined by WMS specific config.
 *
 * @category    Core
 * @package     CookieManager
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 3.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */
 
class CookieManager {

    // Set the cookies as an empty array by default, we will populate this later
    public $cookies = array();
    public $cookie_prefix = "";

    // Parse the cookies that may be set so we can load them into our own array
    public function parse_cookies($config) {
        
        // There isn't any cookies set, so we can return
        if (!is_array($_COOKIE)) {
			return;
		}
		
		// Get the cookie_prefix from the system configuration
		$cookie_prefix = sys_strlen($config->cookie_prefix);
        $this->cookie_prefix = $config->cookie_prefix;

		// Loop through any cookies that are set
		foreach ($_COOKIE as $key => $val) {

		    if ($cookie_prefix && sys_substr($key, 0, $cookie_prefix) == $config->cookie_prefix) {
		        $key = substr($key, $cookie_prefix);
		        if (isset($this->cookies[$key])) {
		            unset($this->cookies[$key]);
		        }
		    }

		    if (empty($this->cookies[$key])) {
				$this->cookies[$key] = $val;
			}

		}

    }

    function set_cookie($config, $name, $value = "", $expires = "", $httponly = FALSE, $samesite = "") {

        // Set the path to / if the system doesnt have a path set
    	if (!$config->cookies->path) {
    		$config->cookies->path = "/";
    	}

        // Handle the expiration of the cookie
    	if ($expires == -1) {
    		$expires = 0;
    	} else if ($expires == "" || $expires == NULL) {
    		$expires = CURRENT_TIME + $config->session->timeout; // Make the cookie expire in a years time by default
    	} 	else {
    		$expires = CURRENT_TIME + (int) $expires;
    	}

        $config->cookies->path = str_replace(array("\n","\r"), "", $config->cookies->path);
        $config->cookies->domain = str_replace(array("\n","\r"), "", $config->cookies->domain);
        $config->cookies->cookie_prefix = str_replace(array("\n","\r", " "), "", $config->cookies->cookie_prefix);

        // Build the set-cookie request
        $cookie = "Set-Cookie: {$config->cookies->cookie_prefix}{$name}=" . urlencode($value);

        // If there is a time to expire, lets add that to the cookie string
    	if ($expires > 0) {
    		$cookie .= "; expires=".@gmdate('D, d-M-Y H:i:s \\G\\M\\T', $expires);
    	}

        // Set the cookie path
    	if (!empty($config->cookies->path)) {
    		$cookie .= "; path={$config->cookies->path}";
    	}

        // Set the cookie domain
    	if (!empty($config->cookies->domain)) {
    		$cookie .= "; domain={$config->cookies->domain}";
    	}

        // If this is a http only cookie, set the flag
    	if ($httponly == TRUE) {
    		$cookie .= "; HttpOnly";
    	}

        // Add the samesite flag if the configuration is on and set
    	if ($samesite != "" && $config->cookies->enable_samesite) {
    	    if ($config->cookies->force_samesite) {
    	        $samesite = sys_strtolower($config->cookies->samesite);
    	    } else {
    		    $samesite = sys_strtolower($samesite);
    	    }
    		if ($samesite == "lax" || $samesite == "strict") {
    			$cookie .= "; SameSite=" . $samesite;
    		}
    	}

        // Add the secure flag, if its set to be enabled global
    	if ($config->cookies->secure) {
    		$cookie .= "; Secure";
    	}

        // Set the cookie and add it to the browser via header
	    $this->cookies[$name] = $value;
        header($cookie, false);
    }

    function unset_cookie($config, $name) {
    	$expires = -3600;
    	$this->set_cookie($config, $name, "", $expires);
    	unset($this->cookies[$name]);
    }

    function sys_get_array_cookie($session, $name, $id) {
    	if (!isset($this->cookies[$session->session_name][$name])) {
    		return false;
    	}
    	$cookie = sys_unserialize($this->cookies[$session->session_name][$name]);
    	if (is_array($cookie) && isset($cookie[$id])) {
    		return $cookie[$id];
    	} else {
    		return 0;
    	}
    }

    function sys_set_array_cookie($session, $name, $id, $value, $expires = "") {
    	if (isset($this->cookies[$session->session_name][$name])) {
    		$newcookie = sys_unserialize($this->cookies[$session->session_name][$name]);
    	} else {
    		$newcookie = array();
    	}
    	$newcookie[$id] = $value;
    	$newcookie = sys_serialize($newcookie);
    	$this->set_cookie($session->session_name . "[$name]", addslashes($newcookie), $expires);
    	$this->cookies[$session->session_name][$name] = $newcookie;
    }

}