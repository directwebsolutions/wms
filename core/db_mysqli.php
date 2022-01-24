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
 * MySQLiInstance
 *
 *   Connect to your database type using MySqli connection type. This allows you to
 *   support Mysql database types.
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

    public $type;
    public $title = "MySQLi";
	public $engine = "mysqli";
	public $db_encoding = "utf8";
	public $read_link;
	public $write_link;
	public $current_link;
	public $connections = array();
	public $table_prefix;
	public $error_reporting = 1;
	public $query_count = 0;
	public $rows_affected_on_last_query = 0;
	public $query_time = 0;
	protected $last_query_type = 0;
	
    /**
     *              PRIVATE FUNCTIONS
    **/
	private function quote_val($value, $quote = "'") {
		if (is_int($value)) {
			$quoted = $value;
		} else {
			$quoted = $quote . $value . $quote;
		}
		return $quoted;
	}

    /**
     *              PUBLIC FUNCTIONS
    **/
    function connect($configuration) {
        if ($configuration->database_host) {
			$connections["read"]["database"] = $configuration->database_name;
            $connections["read"]["hostname"] = $configuration->database_host;
            $connections["read"]["username"] = $configuration->database_user;
            $connections["read"]["password"] = $configuration->database_pw;
            $connections["read"]["encoding"] = $configuration->encoding;
		} else {
			die("Error loading connection information into the database handler - Config unavailable.");
		}
		$this->db_encoding = $configuration->encoding;
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
				$connect_function = "mysqli_connect";
				$persist = "";
				if (!empty($single_connection["pconnect"]) && version_compare(PHP_VERSION, "5.3.0", ">=")) {
					$persist = "p:";
				}
				$link = "{$type}_link";
				$this->get_execution_time();
				$port = 0;
				if (strstr($single_connection["hostname"],":")) {
					list($hostname, $port) = explode(":", $single_connection["hostname"], 2);
				}
				if ($port) {
					$this->$link = @$connect_function($persist.$hostname, $single_connection["username"], $single_connection["password"], "", $port);
				} else {
					$this->$link = @$connect_function($persist.$single_connection["hostname"], $single_connection["username"], $single_connection["password"]);
				}
				$time_spent = $this->get_execution_time();
				$this->query_time += $time_spent;
				if ($this->$link) {
					$this->connections[] = "[".sys_strtoupper($type)."] {$single_connection["username"]}@{$single_connection["hostname"]} (Connected in ".format_time_duration($time_spent).")";
					break;
				} else {
					$this->connections[] = "<span style=\"color: red\">[FAILED] [".strtoupper($type)."] {$single_connection["username"]}@{$single_connection["hostname"]}</span>";
				}
			}
		}
		if (!array_key_exists("write", $connections)) {
			$this->write_link = &$this->read_link;
		}
		if (!$this->read_link) {
			$this->error("[READ] Unable to connect to MySQL server");
			return FALSE;
		} elseif (!$this->write_link) {
			$this->error("[WRITE] Unable to connect to MySQL server");
			return FALSE;
		}
		if (!$this->select_db($configuration->database_name)) {
			return -1;
		}
		$this->current_link = &$this->read_link;
		return $this->read_link;
    }

    function close() {
		@mysqli_close($this->read_link);
		if ($this->write_link) {
			@mysqli_close($this->write_link);
		}
	}

    function get_execution_time() {
		return get_execution_time();
	}

    function set_table_prefix($prefix) {
		$this->table_prefix = $prefix;
	}

    function select_db($database) {
		$this->database = $database;
		$master_success = @mysqli_select_db($this->read_link, $database) or $this->error("[READ] Unable to select database", $this->read_link);
		if ($this->write_link) {
			$slave_success = @mysqli_select_db($this->write_link, $database) or $this->error("[WRITE] Unable to select slave database", $this->write_link);
			$success = ($master_success && $slave_success ? TRUE : FALSE);
		} else {
			$success = $master_success;
		}
		if ($success && $this->db_encoding) {
			@mysqli_set_charset($this->read_link, $this->db_encoding);
			if ($slave_success && count($this->connections) > 1) {
				@mysqli_set_charset($this->write_link, $this->db_encoding);
			}
		}
		return $success;
	}

	function error_number()	{
		if ($this->current_link) {
			return mysqli_errno($this->current_link);
		} else {
			return mysqli_connect_errno();
		}
	}

	function error_string() {
		if ($this->current_link) {
			return mysqli_error($this->current_link);
		} else {
			return mysqli_connect_error();
		}
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
			    $message = "[" . date(DATE_RFC2822, CURRENT_TIME) . "] [SQL] [" . $this->error_number() . "] ". $this->error_string();
                $log = file_put_contents(__DIR__ . "/../logs/sql_errors.log", $message.PHP_EOL , FILE_APPEND | LOCK_EX);
				$error_handler->error(WMS_SQL, $error);
			} else {
			    $message = "[" . date(DATE_RFC2822, CURRENT_TIME) . "] [SQL] [" . $this->error_number() . "] ". $this->error_string();
                $log = file_put_contents(__DIR__ . "/../logs/sql_errors.log", $message.PHP_EOL , FILE_APPEND | LOCK_EX);
				trigger_error("<strong>[SQL] [".$this->error_number()."] ".$this->error_string()."</strong><br />{$string}", E_USER_ERROR);
			}
			return TRUE;
		} else {
		    // With error reporting off we will still create a soft log of the error
		    // to help with debugging anyways
		    $message = "[" . date(DATE_RFC2822, CURRENT_TIME) . "] [SQL] [" . $this->error_number() . "] ". $this->error_string();
            $log = file_put_contents(__DIR__ . "/../logs/sql_errors.log", $message.PHP_EOL , FILE_APPEND | LOCK_EX);
			return FALSE;
		}
	}

	function escape_string($string) {
		if ($this->db_encoding == "utf8") {
			$string = validate_utf8_string($string, FALSE);
		} else if ($this->db_encoding == "utf8mb4") {
			$string = validate_utf8_string($string);
		}
		if (function_exists("mysqli_real_escape_string") && $this->read_link) {
			$string = mysqli_real_escape_string($this->read_link, $string);
		} else {
			$string = addslashes($string);
		}
		return $string;
	}

	function escape_binary($string) {
		return "X" . $this->escape_string(bin2hex($string));
	}

	function unescape_binary($string) {
		return $string;
	}

	function affected_rows() {
		return mysqli_affected_rows($this->current_link);
	}

    function select_all_from($table, $use_prefix = TRUE) {
        $this->get_execution_time();
	    if ($use_prefix) {
            $query = "SELECT * FROM " . $this->table_prefix . $table;
        } else {
            $query = "SELECT * FROM " . $table;
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
            $query_time = $this->get_execution_time();
    		$this->query_time += $query_time;
    		$this->query_count++;
            return $result_set;
        } else {
            return FALSE;
        }
	}

	function query($query_string, $bind_components = NULL) {
	    if ((isset($bind_components) && !is_array($bind_components)) || empty($query_string) || is_null($query_string)) {
	        return FALSE;
	    }
	    $this->get_execution_time();
        $bind_statement = "";
	    $count = 0;
	    foreach ($bind_components as $variable_to_bind) {
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
		    for ($i = 0; $i < count($bind_components); $i++) {
                $bind_item = "bind" . $i;
                $$bind_item = $bind_components[$i];
                $bind_itemset[] = &$$bind_item;
            }
            call_user_func_array(array($stmt, "bind_param"), $bind_itemset);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $count = @mysqli_num_rows($result);
                $result_set = $count;
                $query_time = $this->get_execution_time();
        		$this->query_time += $query_time;
        		$this->query_count++;
                return $result_set;
            }
        }
        return FALSE;
	}

	function select($table, $bind_components, $select = "*", $orderby = NULL, $limit_results = NULL, $use_prefix = TRUE) {
	    if (!is_array($bind_components) || empty($select) || is_null($select)) {
	        return FALSE;
	    }
	    $this->get_execution_time();
	    if ($use_prefix) {
	        $table = $this->table_prefix . $table;
	    }
	    $query_string = "SELECT {$select} FROM `" . $table . "` WHERE ";
	    $components_count = count($bind_components);
	    $_argument_counter = 1;
	    $bind_Variables = array();
        foreach ($bind_components as $key => $value) {
            if (is_array($value) && isset($value["field_name"]) && (isset($value["value"]) || is_null($value["value"]))) {
                $field_name = $value["field_name"];
                if (isset($value["operator"])) {
                    $pre_operator = trim(sys_strtoupper($value["operator"]));
                    $component = "";
                    switch ($pre_operator) {
                        case ">":
                            $operator = " > ";
                            $component = "?";
                            $bind_Variables[] = $value["value"];
                            break;
                        case "<":
                            $operator = " < ";
                            $component = "?";
                            $bind_Variables[] = $value["value"];
                            break;
                        case ">=":
                            $operator = " >= ";
                            $component = "?";
                            $bind_Variables[] = $value["value"];
                            break;
                        case "<=":
                            $operator = " <= ";
                            $component = "?";
                            $bind_Variables[] = $value["value"];
                            break;
                        case "<>":
                            $operator = " <> ";
                            $component = "?";
                            $bind_Variables[] = $value["value"];
                            break;
                        case "!=":
                            $operator = " != ";
                            $component = "?";
                            $bind_Variables[] = $value["value"];
                            break;
                        case "BETWEEN":
                            if (is_array($value["value"]) && array_key_exists(1, $value["value"])) {
                                $operator = " BETWEEN";
                                $component = " ? AND ?";
                                $bind_Variables[] = $value["value"][0];
                                $bind_Variables[] = $value["value"][1];
                            } else {
                                $operator = " = ";
                                $component = "?";
                                $bind_Variables[] = $value["value"];
                            }
                            break;
                        case "LIKE":
                            $operator = " LIKE";
                            $component = " ?";
                            $bind_Variables[] = $value["value"];
                            break;
                        case "IS_NULL":
                            $operator = " IS NULL";
                            $component = "";
                            break;
                        case "IN":
                            if (is_array($value["value"]) && array_key_exists(1, $value["value"])) {
                                $operator = " IN(";
                                $component = "";
                                $_components_count = count($value["value"]);
	                            $__argument_counter = 1;
                                foreach($value["value"] as $value_item) {
                                    if ($__argument_counter == $_components_count) {
                                        $sep = "";
                                    } else {
                                        $sep = ",";
                                    }
                                    $component .= "?{$sep}";
                                    $bind_Variables[] = $value_item;
                                    $__argument_counter++;
                                }
                                $component .= ")";
                            } else {
                                $operator = " = ";
                                $component = "?";
                                $bind_Variables[] = $value["value"];
                            }
                            break;
                        default:
                            $operator = " = ";
                            $component = "?";
                            $bind_Variables[] = $value["value"];
                            break;
                    }
                } else {
                    $operator = " = ";
                    $component = "?";
                    $bind_Variables[] = $value["value"];
                }
                if (isset($value["separator"]) && (strcasecmp(trim($value["separator"]), "AND") || strcasecmp(trim($value["separator"]), "OR") && $_argument_counter > 1)) {
                    $separator = " " . trim(sys_strtoupper($value["separator"])) . " ";
                } else {
                    $separator = "";
                }
                $query_string .= $separator . "`{$field_name}`" . $operator . $component;
                $_argument_counter++;
            } else {
                $bind_Variables[] = $value;
                if ($_argument_counter > 1) {
                    $before = " AND ";
                } else {
                    $before = "";
                }
                $query_string .= $before . "`{$key}` = ?";
                $_argument_counter++;
            }
        }
        if (!is_null($orderby)) {
            if (!is_array($orderby)) {
                $query_string .= " ORDER BY {$orderby}";
            } else {
                $query_string .= " ORDER BY";
                $order_components_count = count($orderby);
	            $_order_argument_counter = 1;
                foreach ($orderby as $order_component) {
                    if ($_order_argument_counter == $order_components_count) {
                        $_spacer = "";
                    } else {
                        $_spacer = ",";
                    }
                    if (isset($order_component["direction"])) {
                        $direction = " " . $order_component["direction"];
                    } else {
                        $direction = "";
                    }
                    if (isset($order_component["fields"])) {
                        $fields = $order_component["fields"];
                    } else {
                        $fields = $order_component;
                    }
                    $query_string .= " " . $fields . $direction . $_spacer;
                    $_order_argument_counter++;
                }
            }
        }
        if (!is_null($limit_results)) {
            if (is_int($limit_results)) {
                $query_string .= " LIMIT {$limit_results}";
            }
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
                $bind_item = "bind" . $i;
                $$bind_item = $bind_Variables[$i];
                $bind_itemset[] = &$$bind_item;
            }
            call_user_func_array(array($stmt, "bind_param"), $bind_itemset);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $count = @mysqli_num_rows($result);
                $result_set = NULL;
                if ($count > 1) {
                    $result_set = $result->fetch_all(MYSQLI_ASSOC);
                } else {
                    $result_set = $result->fetch_assoc();
                }
                $result->free();
                $query_time = $this->get_execution_time();
        		$this->query_time += $query_time;
        		$this->query_count++;
                return $result_set;
            }
        }
        return FALSE;
	}

	function insert($table, $bind_components, $ignore = FALSE, $use_prefix = TRUE) {
	    if (!is_array($bind_components)) {
	        return FALSE;
	    }
	    $this->get_execution_time();
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
        if ($ignore) {
            $query_string = "INSERT IGNORE INTO `" . $table . "` ({$conditions}) VALUES ({$questions})";
        } else {
            $query_string = "INSERT INTO `" . $table . "` ({$conditions}) VALUES ({$questions})";
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
                $bind_item = "bind" . $i;
                $$bind_item = $bind_Variables[$i];
                $bind_itemset[] = &$$bind_item;
            }
            call_user_func_array(array($stmt, "bind_param"), $bind_itemset);
            if ($stmt->execute()) {
                $query_time = $this->get_execution_time();
        		$this->query_time += $query_time;
        		$this->query_count++;
                return TRUE;
            }
        }
        return FALSE;
	}
	
	function delete($table, $where = "", $limit = "", $use_prefix = TRUE, $wipe_table = FALSE) {
        if (!isset($where)) {
            return FALSE;
        }
        $this->get_execution_time();
        if ($use_prefix) {
            $table = $this->table_prefix . $table;
        }
        if ($wipe_table) {
            $query_string = "TRUNCATE `{$table}`";
        } else {
            $query_string = "DELETE FROM `{$table}` WHERE ";
            if (is_array($where)) {
                $bind_Variables = array();
                $_where_argument_counter = 1;
                $_where_components_count = count($where);
                foreach ($where as $w_key => $_value) {
                    if (is_array($_value) && isset($_value["field_name"]) && (isset($_value["value"]) || is_null($_value["value"]))) {
                        $field_name = $_value["field_name"];
                        if (isset($_value["operator"])) {
                            $pre_operator = trim(sys_strtoupper($_value["operator"]));
                            $component = "";
                            switch ($pre_operator) {
                                case ">":
                                    $operator = " > ";
                                    $component = "?";
                                    $bind_Variables[] = $_value["value"];
                                    break;
                                case "<":
                                    $operator = " < ";
                                    $component = "?";
                                    $bind_Variables[] = $_value["value"];
                                    break;
                                case ">=":
                                    $operator = " >= ";
                                    $component = "?";
                                    $bind_Variables[] = $_value["value"];
                                    break;
                                case "<=":
                                    $operator = " <= ";
                                    $component = "?";
                                    $bind_Variables[] = $_value["value"];
                                    break;
                                case "<>":
                                    $operator = " <> ";
                                    $component = "?";
                                    $bind_Variables[] = $_value["value"];
                                    break;
                                case "!=":
                                    $operator = " != ";
                                    $component = "?";
                                    $bind_Variables[] = $_value["value"];
                                    break;
                                case "BETWEEN":
                                    if (is_array($_value["value"]) && array_key_exists(1, $_value["value"])) {
                                        $operator = " BETWEEN";
                                        $component = " ? AND ?";
                                        $bind_Variables[] = $_value["value"][0];
                                        $bind_Variables[] = $_value["value"][1];
                                    } else {
                                        $operator = " = ";
                                        $component = "?";
                                        $bind_Variables[] = $_value["value"];
                                    }
                                    break;
                                case "LIKE":
                                    $operator = " LIKE";
                                    $component = " ?";
                                    $bind_Variables[] = $_value["value"];
                                    break;
                                case "IS_NULL":
                                    $operator = " IS NULL";
                                    $component = "";
                                    break;
                                case "IN":
                                    if (is_array($_value["value"]) && array_key_exists(1, $_value["value"])) {
                                        $operator = " IN(";
                                        $component = "";
                                        $_components_count = count($_value["value"]);
        	                            $__argument_counter = 1;
                                        foreach($_value["value"] as $_value_item) {
                                            if ($__argument_counter == $_components_count) {
                                                $sep = "";
                                            } else {
                                                $sep = ",";
                                            }
                                            $component .= "?{$sep}";
                                            $bind_Variables[] = $_value_item;
                                            $__argument_counter++;
                                        }
                                        $component .= ")";
                                    } else {
                                        $operator = " = ";
                                        $component = "?";
                                        $bind_Variables[] = $_value["value"];
                                    }
                                    break;
                                default:
                                    $operator = " = ";
                                    $component = "?";
                                    $bind_Variables[] = $_value["value"];
                                    break;
                            }
                        } else {
                            $operator = " = ";
                            $component = "?";
                            $bind_Variables[] = $_value["value"];
                        }
                        if (isset($_value["separator"]) && (strcasecmp(trim($_value["separator"]), "AND") || strcasecmp(trim($_value["separator"]), "OR") && $_argument_counter > 1)) {
                            $separator = " " . trim(sys_strtoupper($_value["separator"])) . " ";
                        } else {
                            $separator = "";
                        }
                        $query_string .= $separator . "{$field_name}" . $operator . $component;
                        $_where_argument_counter++;
                    } else {
                        if ($_where_argument_counter > 1) {
                            $before = " AND ";
                        } else {
                            $before = "";
                        }
                        $query_string .= $before . "`{$w_key}` = ?";
                        $_where_argument_counter++;
                        $bind_Variables[] = $_value;
                    }
                }
            } else {
                // This allows you to specify the where directly, but it is not
                // handled as a prepared statement like the above which makes it
                // potentially less safe.
                $query_string .= " {$where}";
            }
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
                $bind_item = "bind" . $i;
                $$bind_item = $bind_Variables[$i];
                $bind_itemset[] = &$$bind_item;
            }
            call_user_func_array(array($stmt, "bind_param"), $bind_itemset);
            if ($stmt->execute()) {
                $query_time = $this->get_execution_time();
                $this->rows_affected_on_last_query = $this->affected_rows();
        		$this->query_time += $query_time;
        		$this->query_count++;
                return TRUE;
            }
        }
        return FALSE;
	}
	
	function update($table, $bind_components, $where = "", $limit = "", $use_prefix = TRUE) {
	    if (!is_array($bind_components)) {
	        return FALSE;
	    }
        if (!isset($where)) {
            return FALSE;
        }
        $this->get_execution_time();
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
        if (is_array($where)) {
            $_where_argument_counter = 1;
            $_where_components_count = count($where);
            $query_string .= " WHERE ";
            foreach ($where as $w_key => $_value) {
                if (is_array($_value) && isset($_value["field_name"]) && (isset($_value["value"]) || is_null($_value["value"]))) {
                    $field_name = $_value["field_name"];
                    if (isset($_value["operator"])) {
                        $pre_operator = trim(sys_strtoupper($_value["operator"]));
                        $component = "";
                        switch ($pre_operator) {
                            case ">":
                                $operator = " > ";
                                $component = "?";
                                $bind_Variables[] = $_value["value"];
                                break;
                            case "<":
                                $operator = " < ";
                                $component = "?";
                                $bind_Variables[] = $_value["value"];
                                break;
                            case ">=":
                                $operator = " >= ";
                                $component = "?";
                                $bind_Variables[] = $_value["value"];
                                break;
                            case "<=":
                                $operator = " <= ";
                                $component = "?";
                                $bind_Variables[] = $_value["value"];
                                break;
                            case "<>":
                                $operator = " <> ";
                                $component = "?";
                                $bind_Variables[] = $_value["value"];
                                break;
                            case "!=":
                                $operator = " != ";
                                $component = "?";
                                $bind_Variables[] = $_value["value"];
                                break;
                            case "BETWEEN":
                                if (is_array($_value["value"]) && array_key_exists(1, $_value["value"])) {
                                    $operator = " BETWEEN";
                                    $component = " ? AND ?";
                                    $bind_Variables[] = $_value["value"][0];
                                    $bind_Variables[] = $_value["value"][1];
                                } else {
                                    $operator = " = ";
                                    $component = "?";
                                    $bind_Variables[] = $_value["value"];
                                }
                                break;
                            case "LIKE":
                                $operator = " LIKE";
                                $component = " ?";
                                $bind_Variables[] = $_value["value"];
                                break;
                            case "IS_NULL":
                                $operator = " IS NULL";
                                $component = "";
                                break;
                            case "IN":
                                if (is_array($_value["value"]) && array_key_exists(1, $_value["value"])) {
                                    $operator = " IN(";
                                    $component = "";
                                    $_components_count = count($_value["value"]);
    	                            $__argument_counter = 1;
                                    foreach($_value["value"] as $_value_item) {
                                        if ($__argument_counter == $_components_count) {
                                            $sep = "";
                                        } else {
                                            $sep = ",";
                                        }
                                        $component .= "?{$sep}";
                                        $bind_Variables[] = $_value_item;
                                        $__argument_counter++;
                                    }
                                    $component .= ")";
                                } else {
                                    $operator = " = ";
                                    $component = "?";
                                    $bind_Variables[] = $_value["value"];
                                }
                                break;
                            default:
                                $operator = " = ";
                                $component = "?";
                                $bind_Variables[] = $_value["value"];
                                break;
                        }
                    } else {
                        $operator = " = ";
                        $component = "?";
                        $bind_Variables[] = $_value["value"];
                    }
                    if (isset($_value["separator"]) && (strcasecmp(trim($_value["separator"]), "AND") || strcasecmp(trim($_value["separator"]), "OR") && $_argument_counter > 1)) {
                        $separator = " " . trim(sys_strtoupper($_value["separator"])) . " ";
                    } else {
                        $separator = "";
                    }
                    $query_string .= $separator . "`{$field_name}`" . $operator . $component;
                    $_where_argument_counter++;
                } else {
                    if ($_where_argument_counter > 1) {
                        $before = " AND ";
                    } else {
                        $before = "";
                    }
                    $query_string .= $before . "`{$w_key}` = ?";
                    $_where_argument_counter++;
                    $bind_Variables[] = $_value;
                }
            }
        } else {
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
                $bind_item = "bind" . $i;
                $$bind_item = $bind_Variables[$i];
                $bind_itemset[] = &$$bind_item;
            }
            call_user_func_array(array($stmt, "bind_param"), $bind_itemset);
            if ($stmt->execute()) {
                $query_time = $this->get_execution_time();
                $this->rows_affected_on_last_query = $this->affected_rows();
        		$this->query_time += $query_time;
        		$this->query_count++;
                return TRUE;
            }
        }
        return FALSE;
	}

}