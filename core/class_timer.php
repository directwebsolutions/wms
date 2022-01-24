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
 * Timer
 *
 *   Track how long a specific call or function takes to run or pages take to
 *   load using this built in timer class. Usefull for debugging what resource
 *   takes more time to load than others
 *
 * @category    utilities
 * @package     Time
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 2.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */

class Timer {

	public $name;
	public $start;
	public $end;
	public $totaltime;
	public $formatted;

	function __construct() {
		$this->add();
		return 0;
	}

	function add() {
		if (!$this->start) {
			$this->start = microtime(TRUE);
		}
	}

	function getTime() {
		if ($this->end) {
			return $this->totaltime;
		} else if ($this->start && !$this->end) {
			$currenttime = microtime(TRUE);
			$totaltime = $currenttime - $this->start;
			return $this->format($totaltime);
		} else {
			return FALSE;
		}
	}

	function stop()	{
		if ($this->start) {
			$this->end = microtime(TRUE);
			$totaltime = $this->end - $this->start;
			$this->totaltime = $totaltime;
			$this->formatted = $this->format($totaltime);
			return $this->formatted;
		}
		return "";
	}

	function remove() {
		$this->name = "";
		$this->start = "";
		$this->end = "";
		$this->totaltime = "";
		$this->formatted = "";
	}

	function format($string) {
		return number_format($string, 7);
	}

}