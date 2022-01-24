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
 * Update Engine
 *
 *   Check to see if the system is running the latest and greatest version of WMS
 *
 * @category    Core
 * @package     Updates
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 3.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */

class UpdateTaskManager {
    
    public $version_information;
    public $needs_update = FALSE;
    
    function read($database) {
        $cache = $database->select("datacache", array("title" => "update"), "title,cache", NULL, 1);
        if ($cache) {
            $data = array_values(unserialize($cache["cache"]));
            $this->version_information = (object) $data[0];
            return $data;
        } else {
            return FALSE;
        }
    }
    
    // default the version code to 2000 since this method was not created
    // until version code 2000 was released.
    function check_for_new_version($wms, $database) {
        $ch = curl_init();
        $options = [
            CURLOPT_SSL_VERIFYPEER  =>  TRUE,
            CURLOPT_RETURNTRANSFER  =>  TRUE,
            CURLOPT_TIMEOUT         =>  30,
            CURLOPT_CONNECTTIMEOUT  =>  30,
            CURLOPT_URL             =>  "https://www.directwebsolutions.ca/wms/latest-version.json"
        ];
        curl_setopt_array($ch, $options);
        $data = json_decode(curl_exec($ch));
        curl_close($ch);
        if ($data) {
            $this->version_information->new_version = $data->currentversion;
            $this->version_information->new_versioncode = $data->revisioncode;
            $this->version_information->new_version_released_on = $data->releasedon;
            $this->version_information->new_version_minimum_dependency_code = $data->minrevision;
        } else {
            return FALSE;
        }
        if ($wms->version_code != $this->version_information->new_versioncode) {
            $this->needs_update = TRUE;
        }
        if ($this->version_information->currentversion != $this->version_information->new_version) {
            $this->needs_update = TRUE;
        }
        $un_compiled = array("sid" => 1, "currentversion" => $this->version_information->currentversion, "lastchecked" => time());
        $compiled = serialize(array($un_compiled));
        $database->update("datacache", array("cache" => $compiled), array("title" => "update"));
        return NULL;
    }

}