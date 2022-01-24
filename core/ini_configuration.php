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
 * Configuration
 *
 *   WMS specific config.
 *
 * @category    Core
 * @package     Configuration
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 3.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */

// Block direct access to this file - we don't want people in here
if (!defined("ALLOW_ACCESS")) {
    die("Direct access of this file is not allowed.");
}

class Configuration {

    /**
     * Database Settings
     *   Setup your database connection type and details here
    */
    private $_db = array(
        "type"      =>      "mysqli",   // The type of db engine you're using (Default Options: mysqli/pdo)
        "table_prefix"  =>  "wms_",     // If you want to prefix your WMS instance in the db set a prefix here
        "settings"      =>  array(
            "database_name" =>  "",
            "database_host" =>  "localhost",
            "database_user" =>  "",
            "database_pw"   =>  "",
            "encoding"      =>  "utf8mb4"
        )
    );

    /**
     * General Settings
     *   These are the basic system settings
    */
    private $_general = array(
        "support_email"     =>  "",                                 // The default email to show for support on error pages
        "base_url"          =>  "www.websitenamehere.com",          // The base URL for the website, without path or trailing slash
        "path_to"           =>  "",                                 // The path to your installation of WMS, can be left blank normally. No trailing slash!
        "use_cdn"           =>  FALSE,                              // Should we load assets from a CDN? If false, it will load from /assets/ folder
        "cdn_url"           =>  "",                                 // The CDN url to load files from if it's active
        "asset_folder"      =>  "",                                 // If you're using a folder other than `assets` set the name here (no slashes)
        "force_ssl_links"   =>  TRUE,                               // Add a https:// to all internal links and includes? (Default: TRUE)
        "site_name"         =>  "WMS Website Name",                 // The title to appear on each page
        "meta_name"         =>  "WMS Website Name",                 // The META name, search results use this on Google
        "meta_author"       =>  "WMS Website Name",                 // The META author, usually company name goes here
        "app_title"         =>  "App Name",                         // The title set by default if page is saved as a webapp
        "revision_code"     =>  "",                                 // The current system revision build code, used for debug (d.m.y.v)
        "style_debug"       =>  FALSE,                              // Add a timestamp behind revision codes for debugging styles. Turn off for caching
        "default_timezone"  =>  "America/Swift_Current",            // The PHP timeslot code - https://www.php.net/manual/en/timezones.php
        "admin_directory"   =>  "panel",                            // The name of the folder the ACP is in, no beginning or trailing slashes
        "gzip"              =>  array(
            "enabled"       =>  TRUE,                               // Enable or disable gzip compression at the PHP level
            "level"         =>  4                                   // Typically between 2-4 for optimal compression vs load time
        ),
        "use_nocache"       =>  TRUE,                               // Should the system send nocache headers? Helpful for browsers that cache user info
        "display_errors"    =>  TRUE,                               // Enable force display errors on the custom error_handler
        "language"          =>  "english",                          // Set the default system language for your system
        "max_login_trys"    =>  5,                                  // Set the maximum tries a user has to login before being locked out
        "lockout_time"      =>  5,                                  // Time in minutes to lock them out before they can try again
        "account_timelock"  =>  FALSE,                              // Should account specific user lockout be enabled as well or just session based
        "show_php"          =>  FALSE,                              // Should we show the .php filetype? Enable this to use the override ext_type below
        "ext_type"          =>  "php",                              // Disguise your links as .html or other file types and use this to set that system wide
        "panel_fancy_url"   =>  TRUE,                               // Hide the ?mid= on the panel and add the extension bellow to make your admin panel links readable. Default FALSE, needs htaccess to work
        "panel_fancy_path"  =>  "",                                 // The path to show in front of the fancy url id - default is blank. Htaccess needs to match. No trailing slash.
        "panel_fancy_ext"   =>  "html",                             // The extension to show behind the fancy url if enabled.
        "enable_datalogger" =>  FALSE                               // If you want, you can enable the data logger and include the JS file in your footer after jquery to track user data like browser, screen size, etc. No private information is saved
    );

    /**
     * Email server settings
     *   Control how the system sends emails
    */
    private $_email_settings = array(
        "send_from"         =>  "",                                 // The email address you want to send from, should match the SMTP details below if you're using smtp
        "from_name"         =>  "Support",                          // The name to attach to your emails with the above address
        "admin_email"       =>  "",                                 // The email adress for the admin - this is the defaulted return too if not set
        "return_email"      =>  "",                                 // The default return to address, defaults to send_from above unless you want replies to go somewhere else.
        "mail_message_id"   =>  FALSE,                              // Use additional headers - Values TRUE, FALSE - Default FALSE
        "email_type"        =>  "smtp",                             // How to send the emails - Values mail, smpt
        "mail_parameters"   =>  "text",                             // Additional mail parameters to send with the 'mail' (PHP) type
        "ssl_type"          =>  2,                                  // Use the following: 0 - No ssl, 1 - Regular SSL, 2 - TLS
        "smtp_port"         =>  25,                                 // Port for sending unsecured SMTP requests
        "smtp_secure"       =>  465,                                // Port used by the SMTP server for secure connections - 465 is cPanel default, 587 for Gmail
        "smtp_host"         =>  "mail.yourdomain.com",              // localhost OR your mail domain if using an SMTP server (like cPanel is mail.domain.com)
        "smtp_username"     =>  "",                                 // SMTP Username
        "smtp_password"     =>  ''                                  // Password for SMTP account
    );

    /**
     * Social Settings
     *   Links to your social media that you may want to load into a template
    */
    private $_socials = array(
        "facebook"          =>  "https://www.facebook.com/yourlinkhere",
        "twitter"           =>  "https://twitter.com/yourlinkhere",
        "youtube"           =>  "",
        "linkedin"          =>  "",
        "pintrest"          =>  ""
    );

    /**
     * Cookie Settings
     *   Set the variables for storing cookies on the system
    */
    private $_cookies = array(
        "cookie_prefix"     =>      "wms_",                         // Set a prefix for your cookies
        "path"              =>      "/",                            // The default path for the cookies
        "enable_samesite"   =>      TRUE,                           // Enables the samesite attribute (Default: TRUE)
        "force_samesite"    =>      TRUE,                           // If enabled, forces the samesite on cookies (Default: TRUE)
        "samesite"          =>      "STRICT",                       // Samesite attribute: strict/lax (Default: STRICT)
        "secure"            =>      TRUE,                           // Use the secure cookie attribute (Default: TRUE)
        "domain"            =>      ".yourdomaingoeshere.com"       // Set the domain the cookies apply to
    );

    /**
     * Session
     *   Change your session name for the WMS system here. This will be reflected
     *   throughout all included files. This is important for third party integration
    */
    private $_session = array(
        "session_name"      =>      "sid",                          // Name of the session cookie (Default: sid)
        "timeout"           =>      "31536000",                     // Life of the session extended on page reload in seconds (Default: 31536000 - 1 year)
        "length_of_id"      =>      70                              // How long should the Session ID be (Default 70, fallback 50)
    );

    /**
     * Security
     *   Set the password encryption type for the system as well as some cost parameters
     *   WARNING: this system will implement bcrypt or argon2 encryption over the md5
     *            hashing algo some scripts may try and run. Ensure your system supports
     *            the encryption type you are planning to use
     *   NOTICE:  higher cost methods are tougher to crack but will show performance
     *            impacts on your website. Test with your server to find middle ground
     *            for speed and security.
     *   CAUTION: if you turn use_pepper off or on you will need to reset the passwords
     *            for accounts using the previous implementation - the same applies if you
     *            you change your pepper as the encryption on the passwords will also change
     */
    private $_security = array(
        "encryption_mode"   =>  "argon2id",                         // Options: argon2i, argon2id, bcrypt (default: argon2id)
        "bcrypt_cost"       =>  "12",                               // Options: Min 4 / Max 31 - Default 12 (and up recommended)
        "argon_cost"        =>  "16",                               // Amount in KB of argon memory - Min is 3, 16 is default
        "argon_iterations"  =>  "3",                                // Min 1, the more rounds the more secure but slower, default 3
        "argon_threads"     =>  "1",                                // Min 1, how many threads to run
        "use_pepper"        =>  TRUE,                               // Should the system use a pepper on the password to secure it more?
        "pepper"            =>  "GENERATEYOUROWNPEPPERSTRING"       // Generate a pepper string to encrypt your passwords: generate_pepper($length);
    );

    // Additional modules can be added here and have their settings loaded in that way
    private $_modules = array(
        /**
         * Shipment Settings
         *   If you have the store module enabled on your WMS, you may choose to ship items with
         *   Canada Post. To do so, enter your API credentials here
        */
        "shipping"  =>  array(
            "enabled"           =>  FALSE,                          // Is the shipping module enabled? (Default FALSE)
            "local_pickup"      =>  FALSE,                          // Does your store allow local pickup?
            "canadapost_userid" =>  "",                             // User ID supplied by Canada Post API
            "canadapost_passkey" => "",                             // Passkey for the API system
            "canadapost_apikey" =>  "",                             // Private API key from Canada Post
            "canadapost_apiurl" =>  "",                             // The API URL you want to call (Ex: https://ct.soa-gw.canadapost.ca/rs/ship/price)
            "your_postal_code"  =>  ""                              // Your Postal Code, no spaces
        ),

        /**
         * Career API Settings
         *   If you have the career module enabled on your WMS, you can configure it to talk
         *   to the Direct Web Solutions API system using this function
         */
        "career_plugin" =>  array(
            "enabled"       =>      FALSE,
            "api_token"     =>      "",
            "allow_online_apps" =>  FALSE
        )
    );

    public $database;
    public $general;
    public $socials;
    public $cookies;
    public $session;
    public $security;
    public $modules;
    public $email;

    // Construct the configuration for the system - do not change anything here
    // unless you absolutely know what you are doing as it will wreck your system
    public function __construct() {
        $this->database = json_decode(json_encode($this->_db));
        $this->general = json_decode(json_encode($this->_general));
        $this->socials = json_decode(json_encode($this->_socials));
        $this->cookies = json_decode(json_encode($this->_cookies));
        $this->session = json_decode(json_encode($this->_session));
        $this->security = json_decode(json_encode($this->_security));
        $this->modules = json_decode(json_encode($this->_modules));
        $this->email = json_decode(json_encode($this->_email_settings));
    }

}
