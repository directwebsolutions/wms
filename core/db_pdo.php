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
 * PDOInstance
 *
 *   Connect to your database type using PDO connection type. This allows you to
 *   support more database types than MySql.
 *
 * @category    Core
 * @package     DatabaseManager
 * @author      Direct Web Solutions <darryn@directweb.solutions>
 * @copyright   2017-2022 Direct Web Solutions
 * @license     https://www.directwebsolutions.ca/wms/v3/license 3.0+ License
 * @version     Release: 3.0.0
 * @link        https://www.directwebsolutions.ca/wms/latest
 * @since       File available since Release 3.0.0
 * @deprecated  File deprecated in Release 4.0.0
 */

// Implement the database type - in this case it is a MySqli instance but you
// can also have PDO as an option and eventually we plan to build NoSQL options
class DatabaseInstance implements DatabaseInterface {

    public $title = "PDO";
	public $query_time = 0;
	public $query_count = 0;
	public $querylist = array();
	public $error_reporting = 1;
	public $read_link;
	public $write_link;
	public $current_link;
	public $database;
	public $version;
	public $table_type = "myisam";
	public $table_prefix;
	public $engine = "pdo";
	public $can_search = TRUE;
	public $db_encoding = "utf8";
	public $rows_affected_on_last_query = 0;
	public $connections = array();
	protected $last_query_type = 0;
	private $lastPdoException;
	private $resultSeekPositions = array();
	private $lastResult = null;

    function connect($db_conf) {
        $connections = array(
			"read" => array(),
			"write" => array()
		);
        if ($db_conf->database_host) {
			$connections["read"]["database"] = $db_conf->database_name;
            $connections["read"]["hostname"] = $db_conf->database_host;
            $connections["read"]["username"] = $db_conf->database_user;
            $connections["read"]["password"] = $db_conf->database_pw;
            $connections["read"]["encoding"] = $db_conf->encoding;
		} else {
			if (!isset($db_conf["read"])) {
			    foreach ($db_conf as $key => $settings) {
			        if (is_int($key)) {
						$connections["read"][] = $settings;
					}
			    }
			} else {
			    $connections = $db_conf;
			}
		}
		$this->db_encoding = $db_conf->encoding;
		foreach (array("read", "write") as $type) {
			if (!isset($connections[$type]) || !is_array($connections[$type])) {
				break;
			}
			if (array_key_exists("hostname", $connections[$type])) {
				$details = $connections[$type];
				unset($connections[$type]);
				$connections[$type][] = $details;
			}
			shuffle($connections[$type]);
			foreach($connections[$type] as $single_connection) {
				$flags = array(
					PDO::ATTR_PERSISTENT => FALSE,
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_EMULATE_PREPARES => FALSE,
				);
				if (!empty($single_connection["pconnect"])) {
					$flags[PDO::ATTR_PERSISTENT] = TRUE;
				}
				$link = "{$type}_link";
				get_execution_time();
				list($hostname, $port) = self::parseHostname($single_connection["hostname"]);
				$dns = array(
					$hostname,
					$db_conf->database_name,
					$port,
					$this->db_encoding
				);
				try {
					$this->$link = new PDO(
						$dsn,
						$single_connection["username"],
						$single_connection["password"],
						$flags
					);
					$this->lastPdoException = null;
				} catch (PDOException $e) {
					$this->$link = null;
					$this->lastPdoException = $e;
				}
				$time_spent = get_execution_time();
				$this->query_time += $time_spent;
				if ($this->$link !== null) {
					$this->connections[] = "[" . sys_strtoupper($type) . "] {$single_connection['username']}@{$single_connection['hostname']} (Connected in " . format_time_duration($time_spent) . ")";
					break;
				} else {
					$this->connections[] = "<span style=\"color: red\">[FAILED] [" . sys_strtoupper($type) . "] {$single_connection['username']}@{$single_connection['hostname']}</span>";
				}
			}
		}
		if (!array_key_exists("write", $connections)) {
			$this->write_link = &$this->read_link;
		}
		if (!$this->read_link) {
			$this->error("[READ] Unable to connect to database server");
			return FALSE;
		} elseif (!$this->write_link) {
			$this->error("[WRITE] Unable to connect to database server");
			return FALSE;
		}
		$this->database = $db_conf->database_name;
		if (version_compare("PHP_VERSION", "5.3.6", "<") === TRUE) {
			$this->setCharacterSet($this->db_encoding);
		}
		$this->current_link = &$this->read_link;
		return $this->read_link;
    }
    
    private static function parseHostname($hostname) {
		$openingSquareBracket = strpos($hostname, "[");
		if ($openingSquareBracket === 0) {
			$closingSquareBracket = strpos($hostname, "]", $openingSquareBracket);
			if ($closingSquareBracket !== FALSE) {
				$portSeparator = strpos($hostname, ':', $closingSquareBracket);
				if ($portSeparator === FALSE) {
					return array($hostname, NULL);
				} else {
					$host = substr($hostname, $openingSquareBracket, $closingSquareBracket + 1);
					$port = (int) substr($hostname, $portSeparator + 1);
					return array($host, $port);
				}
			} else {
				throw new InvalidArgumentException("Hostname is missing a closing square bracket for IPv6 address: {$hostname}");
			}
		}
		$portSeparator = strpos($hostname, ":", 0);
		if ($portSeparator === FALSE) {
			return array($hostname, NULL);
		} else {
			$host = substr($hostname, 0, $portSeparator);
			$port = (int) substr($hostname, $portSeparator + 1);
			return array($host, $port);
		}
	}

	function setCharacterSet($characterSet) {
		$query = "SET NAMES {$characterSet}";
		self::execIgnoreError($this->read_link, $query);
		if ($this->write_link !== $this->read_link) {
			self::execIgnoreError($this->write_link, $query);
		}
	}

	private static function execIgnoreError($connection, $query) {
		try {
			$connection->exec($query);
		} catch (PDOException $e) {
		}
	}

    function set_table_prefix($prefix) {
		$this->table_prefix = $prefix;
	}

	function error_number() {
		if ($this->lastPdoException !== NULL) {
			return $this->lastPdoException->getCode();
		}
		return NULL;
	}

	function error_string() {
		if ($this->lastPdoException !== null && isset($this->lastPdoException->errorInfo[2])) {
			return $this->lastPdoException->errorInfo[2];
		}
		return null;
	}

	function error($string = "") {
		if ($this->error_reporting) {
			if (class_exists("ErrorEngine")) {
				global $error_handler;
				if (!is_object($error_handler))	{
					require_once ROOT_DIR . "core/class_errorhandler.php";
					$error_handler = new ErrorEngine;
				}
				$error = array(
					"error_no" => $this->error_number(),
					"error" => $this->error_string(),
					"query" => $string
				);
				$error_handler->error(WMS_SQL, $error);
			} else {
				trigger_error("<strong>[SQL] [".$this->error_number()."] ".$this->error_string()."</strong><br />{$string}", E_USER_ERROR);
			}
			return TRUE;
		} else {
			return FALSE;
		}
	}

	function query($string, $hideErrors = false, $writeQuery = false) {
		get_execution_time();
		if (($writeQuery || $this->last_query_type) && $this->write_link) {
			$this->current_link = &$this->write_link;
		} else {
			$this->current_link = &$this->read_link;
		}
		$query = null;
		try {
			if (preg_match("/^\\s*SELECT\\b/i", $string) === 1) {
				$query = $this->current_link->prepare($string, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
				$query->execute();
				$this->lastPdoException = NULL;
			} else {
				$query = $this->current_link->query($string);
				$this->lastPdoException = NULL;
			}
		} catch (PDOException $e) {
			$this->lastPdoException = $e;
			$query = NULL;
			if (!$hideErrors) {
				$this->error($string);
				exit;
			}
		}
		if ($writeQuery) {
			$this->last_query_type = 1;
		} else {
			$this->last_query_type = 0;
		}
		$query_time = get_execution_time();
		$this->query_time += $query_time;
		$this->query_count++;
		$this->lastResult = $query;
		return $query;
	}

    function write_query($query, $hideErrors = FALSE) {
		return $this->query($query, $hideErrors, TRUE);
	}

    function fetch_array($query, $resultType = PDO::FETCH_ASSOC) {
		if (is_null($query) || !($query instanceof PDOStatement)) {
			return FALSE;
		}
		switch ($resultType)	{
			case PDO::FETCH_NUM:
			case PDO::FETCH_BOTH:
				break;
			default:
				$resultType = PDO::FETCH_ASSOC;
				break;
		}
		$hash = spl_object_hash($query);
		if (isset($this->resultSeekPositions[$hash])) {
			return $query->fetch($resultType, PDO::FETCH_ORI_ABS, $this->resultSeekPositions[$hash]);
		}
		return $query->fetch($resultType);
	}

    function fetch_field($query, $field, $row = FALSE) {
		if (is_null($query) || !($query instanceof PDOStatement)) {
			return FALSE;
		}
		if ($row !== FALSE) {
			$this->data_seek($query, (int) $row);
		}
		$array = $this->fetch_array($query, PDO::FETCH_ASSOC);
		if ($array === FALSE) {
			return FALSE;
		}
		return $array[$field];
	}

    function data_seek($query, $row) {
		if (is_null($query) || !($query instanceof PDOStatement)) {
			return FALSE;
		}
		$hash = spl_object_hash($query);
		$this->resultSeekPositions[$hash] = ((int) $row) + 1;
		return TRUE;
	}

    function num_rows($query) {
		if (is_null($query) || !($query instanceof PDOStatement)) {
			return FALSE;
		}
		if (preg_match("/^\\s*SELECT\\b/i", $query->queryString) === 1) {
			$countQuery = $this->read_link->query($query->queryString);
			$result = $countQuery->fetchAll(PDO::FETCH_COLUMN, 0);
			return count($result);
		} else {
			return $query->rowCount();
		}
	}

	function insert_id()	{
		return $this->current_link->lastInsertId();
	}

	function close()	{
		$this->read_link = $this->write_link = $this->current_link = NULL;
	}

	function affected_rows()	{
		if ($this->lastResult === NULL) {
			return 0;
		}
		return $this->lastResult->rowCount();
	}

	function num_fields($query) {
		if (is_null($query) || !($query instanceof PDOStatement)) {
			return FALSE;
		}
		return $query->columnCount();
	}

    function list_tables($database, $prefix = "") {
        $sql = "SHOW TABLES";
        if ($this->is_connected) {
            $query = $this->pdo->query($sql);
            return $query->fetchAll(PDO::FETCH_COLUMN);
        }
        return FALSE;
    }

	 function escape_string($string) {
		 $string = $this->read_link->quote($string);
		 $string = substr($string, 1);
		 $string = substr($string, 0, -1);
		 return $string;
	 }

	 function free_result($query) {
	 	 if (is_object($query) && $query instanceof PDOStatement) {
		     return $query->closeCursor();
	     }
	 	 return FALSE;
	 }

	 function escape_string_like($string) {
		 return $this->escape_string(str_replace(array("\\", "%", "_") , array("\\\\", "\\%" , "\\_") , $string));
	 }

	 function get_version() {
		 if ($this->version) {
			 return $this->version;
		 }
		 $this->version = $this->read_link->getAttribute(PDO::ATTR_SERVER_VERSION);
		 return $this->version;
	 }


	 function get_execution_time() {
		 return get_execution_time();
	 }
	
	function insert_prepared_query($table, $bind_components, $use_prefix = TRUE) {
	    if (!is_array($bind_components)) {
	        return FALSE;
	    }
	    if ($use_prefix) {
	        $table = $this->table_prefix . $table;
	    }
	    $components_count = count($bind_components);
	    $_argument_counter = 1;
	    $bind_Variables = array();
	    $conditions = "";
	    $questions = "";
        foreach ($bind_components as $key => $value) {
            $bind_Variables[] = $value;
            if ($_argument_counter == $components_count) {
                $spacer = "";
            } else {
                $spacer = ", ";
            }
            $conditions .= "`{$key}`{$spacer}";
            $questions .= "?{$spacer}";
            $_argument_counter++;
        }
        $query_string = "INSERT INTO `" . $table . "` ({$conditions}) VALUES ({$questions})";
        $bind_statement = "";
	    $count = 0;
	    foreach ($bind_Variables as $variable_to_bind) {
            if (is_float($variable_to_bind)) {
                $bind_statement .= "d";
            } else if (is_int($variable_to_bind)) {
                $bind_statement .= "i";
            } else if (is_object($variable_to_bind)) {
                $bind_statement .= "b";
            } else {
                $bind_statement .= "s";
            }
            $count++;
        }
	    $bind_itemset[] = $bind_statement;
        $this->current_link = &$this->read_link;
        if (($query_string || $this->last_query_type) && $this->write_link) {
			$this->current_link = &$this->write_link;
			$link_class = $this->write_link;
		} else {
			$this->current_link = &$this->read_link;
			$link_class = $this->read_link;
		}
		if ($this->error_number()) {
			return $this->error($stmt);
		}
		if ($stmt = @mysqli_prepare($link_class, $query_string)) {
		for ($i = 0; $i < count($bind_Variables); $i++) {
                $bind_item = 'bind' . $i;
                $$bind_item = $bind_Variables[$i];
                $bind_itemset[] = &$$bind_item;
            }
            call_user_func_array(array($stmt, 'bind_param'), $bind_itemset);
            if ($stmt->execute()) {
                return true;
            }
        }
        return false;
	}
	
	function update_prepared_query($table, $bind_components, $where = "", $limit = "", $use_prefix = TRUE) {
	    if (!is_array($bind_components)) {
	        return FALSE;
	    }
        if (isset($where) && !is_array($where)) {
            return FALSE;
        } 
	    if ($use_prefix) {
	        $table = $this->table_prefix . $table;
	    }
	    $query_string = "UPDATE `" . $table . "` SET ";
	    $components_count = count($bind_components);
	    $_argument_counter = 1;
	    $bind_Variables = array();
        foreach ($bind_components as $key => $value) {
            $bind_Variables[] = $value;
            if ($_argument_counter == $components_count) {
                $spacer = "";
            } else {
                $spacer = ", ";
            }
            $query_string .= "`{$key}`=?{$spacer}";
            $_argument_counter++;
        }
        if (isset($where) && is_array($where)) {
            // Currently only written for one variable, we will want to handle
            // multiple WHERE requests such as OR and AND or IN()
            foreach ($where as $w_key => $_value) {
                $bind_Variables[] = $_value;
                $query_string .= " WHERE `{$w_key}`=?";
            }
        } else if (isset($where) && !is_array($where)) {
            // We don't recommend using this way as you lose the prepared side
            // of the query but if you want to really narrow your results more
            // than a dynamically built function can do, then use it at your
            // own risk - ideally with the escape function used properly.
            $query_string .= " WHERE {$where}";
        } 
        $bind_statement = "";
	    $count = 0;
	    foreach ($bind_Variables as $variable_to_bind) {
            if (is_float($variable_to_bind)) {
                $bind_statement .= "d";
            } else if (is_int($variable_to_bind)) {
                $bind_statement .= "i";
            } else if (is_object($variable_to_bind)) {
                $bind_statement .= "b";
            } else {
                $bind_statement .= "s";
            }
            $count++;
        }
	    $bind_itemset[] = $bind_statement;
        $this->current_link = &$this->read_link;
        if (($query_string || $this->last_query_type) && $this->write_link) {
			$this->current_link = &$this->write_link;
			$link_class = $this->write_link;
		} else {
			$this->current_link = &$this->read_link;
			$link_class = $this->read_link;
		}
		if ($this->error_number()) {
			return $this->error($stmt);
		}
		if ($stmt = @mysqli_prepare($link_class, $query_string)) {
		for ($i = 0; $i < count($bind_Variables); $i++) {
                $bind_item = 'bind' . $i;
                $$bind_item = $bind_Variables[$i];
                $bind_itemset[] = &$$bind_item;
            }
            call_user_func_array(array($stmt, 'bind_param'), $bind_itemset);
            if ($stmt->execute()) {
                return true;
            }
        }
        return false;
	}
	
	function select_all_from($fromTable, $use_prefix = TRUE) {
	    if ($use_prefix) {
            $query = "SELECT * FROM " . $this->table_prefix . $fromTable;
        } else {
            $query = "SELECT * FROM " . $fromTable;
        }
        $this->current_link = &$this->read_link;
        if (($query || $this->last_query_type) && $this->write_link) {
			$this->current_link = &$this->write_link;
			$link_class = $this->write_link;
		} else {
			$this->current_link = &$this->read_link;
			$link_class = $this->read_link;
		}
		if ($this->error_number()) {
			return $this->error($stmt);
		}
		if ($stmt = @mysqli_prepare($link_class, $query)) {
		    $stmt->execute();
            $result = $stmt->get_result();
            $count = @mysqli_num_rows($result);
            $result_set = NULL;
            if ($count > 1) {
                $result_set = $result->fetch_all(MYSQLI_ASSOC);
            } else {
                $result_set = $result->fetch_assoc();
            }
            $result->free();
            return $result_set;
        } else {
            return FALSE;
        }
	}
	
	function prepared_select($fromTable, $conditions = NULL, $bindVariable = NULL, $toSelect = "*", $use_prefix = TRUE) {
	    if (!is_array($bindVariable)) {
	        return FALSE;
	    }
	    if (empty($conditions)) {
	        return FALSE;
	    }
	    if (!empty($conditions)) {
	        $conditions = " " . $conditions;
	    }
	    $bind_stmt = "";
	    $count = 0;
	    foreach ($bindVariable as $variable_to_bind) {
            if (is_float($variable_to_bind)) {
                $bind_stmt .= "d";
            } else if (is_int($variable_to_bind)) {
                $bind_stmt .= "i";
            } else if (is_object($variable_to_bind)) {
                $bind_stmt .= "b";
            } else {
                $bind_stmt .= "s";
            }
            $count++;
        }
        $bind_itemset[] = $bind_stmt;
        if ($use_prefix) {
            $query = "SELECT " . $toSelect . " FROM " . $this->table_prefix . $fromTable . $conditions;
        } else {
            $query = "SELECT " . $toSelect . " FROM " . $fromTable . $conditions;
        }
        $this->current_link = &$this->read_link;
        if (($query || $this->last_query_type) && $this->write_link) {
			$this->current_link = &$this->write_link;
			$link_class = $this->write_link;
		} else {
			$this->current_link = &$this->read_link;
			$link_class = $this->read_link;
		}
		if ($this->error_number()) {
			return $this->error($stmt);
		}
        if ($stmt = @mysqli_prepare($link_class, $query)) {
            for ($i = 0; $i < count($bindVariable); $i++) {
                $bind_item = "bind" . $i;
                $$bind_item = $bindVariable[$i];
                $bind_itemset[] = &$$bind_item;
            }
            call_user_func_array(array($stmt, "bind_param"), $bind_itemset);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = @mysqli_num_rows($result);
            $result_set = NULL;
            if ($count > 1) {
                $result_set = $result->fetch_all(MYSQLI_ASSOC);
            } else {
                $result_set = $result->fetch_assoc();
            }
            $result->free();
            return $result_set;
        } else {
            return FALSE;
        }
	}

}