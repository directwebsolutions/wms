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
 * Spiders
 *
 *   Read the spiders information from the datacache in the database into
 *   a readable object for session comparison
 *
 * @category    utilities
 * @package     Crawlers
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 1.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */
 
class Spiders {

    public $crawlers = array();

    public function read($database, $spider_id = NULL) {
        // If this spider is in the cache already, we can return that
        if (isset($this->crawlers[$spider_id])) {
            return $this->crawlers[$spider_id];
        }
		$crawler_data = $database->select_all_from("spiders");
		// Store the crawler data in the spider cache
		if ($crawler_data !== FALSE) {
		    if (array_key_exists(0, $crawler_data)) {
		        $this->crawlers = $crawler_data;
		    } else {
		        $this->crawlers[] = $crawler_data;
		    }
			return $crawler_data;
		} else {
			return FALSE;
		}
    }

}