<?php

// Remove sessions that are over 24 hours old (60 * '1440'). You can edit how long
// to keep sessions for by changing the min_between_task field on the tasklist db
// entry for this daily task.
function clear_inactive_sessions($data_array) {
    global $db;
    (isset($data_array["logging"]) && ($data_array["logging"] > 0)) ? $log = TRUE : $log = FALSE;
    $db->delete("sessions", array(array("field_name" => "last_visit + " . (60 * $data_array["minute"]), "operator" => "<", "value" => CURRENT_TIME)));
    if ($db->rows_affected_on_last_query > 0) {
        $timecode = date(DATE_RFC2822, CURRENT_TIME);
        $taskname = $data_array["taskname"];
        $message = "[{$timecode}] [CRON] [SUCCESS] The task {$taskname} was ran and {$db->rows_affected_on_last_query} sessions were removed successfully.";
        $db->update("tasklist", array("last_executed" => CURRENT_TIME), array("tid" => $data_array["task_id"]));
        if ($log) {
            file_put_contents(ROOT_DIR . "logs/cron.log", $message.PHP_EOL , FILE_APPEND | LOCK_EX);
        }
        return TRUE;
    }
    return FALSE;
}

function clear_inactive_reset_keys($data_array) {
    global $db;
    (isset($data_array["logging"]) && ($data_array["logging"] > 0)) ? $log = TRUE : $log = FALSE;
    $db->delete("reset_keys", array(array("field_name" => "expiry", "operator" => "<=", "value" => CURRENT_TIME)));
    if ($db->rows_affected_on_last_query > 0) {
        $timecode = date(DATE_RFC2822, CURRENT_TIME);
        $taskname = $data_array["taskname"];
        $message = "[{$timecode}] [CRON] [SUCCESS] The task {$taskname} was ran and {$db->rows_affected_on_last_query} reset keys were removed successfully.";
        $db->update("tasklist", array("last_executed" => CURRENT_TIME), array("tid" => $data_array["task_id"]));
        if ($log) {
            file_put_contents(ROOT_DIR . "logs/cron.log", $message.PHP_EOL , FILE_APPEND | LOCK_EX);
        }
        return TRUE;
    }
    return FALSE;
}

function condense_and_clear_datalogger($data_array) {
    global $wms, $db;
    (isset($data_array["logging"]) && ($data_array["logging"] > 0)) ? $log = TRUE : $log = FALSE;
    if ($wms->config->general->enable_datalogger) {
        $Date = build_compiled_date_func();
        $data = $db->select("analytics_data", array("x_day" => $Date->day->previous_nlz, "x_month" => $Date->day->previous_day_month_nlz, "x_year" => $Date->day->previous_day_year));
        // If there are any entrys from yesterday in the system, we can process them
        // otherwise it is still today so we dont need to export the data yet.
        if ($data) {
            $res = count($data);
            $data_string = "[";
            $count = 1;
            foreach ($data as $result) {
                if ($count == $res) {
                    $data_string .= json_encode($result) . "]";
                } else {
                    $data_string .= json_encode($result) . ",\n";
                    $count++;
                }
            }
            $daycode = $Date->day->previous . $Date->day->previous_day_month . $Date->day->previous_day_year_nlz;
            file_put_contents(ROOT_DIR . "logs/statistics/{$daycode}.json", $data_string , FILE_APPEND | LOCK_EX);
            $db->delete("analytics_data", array("x_day" => $Date->day->previous_nlz, "x_month" => $Date->day->previous_day_month_nlz, "x_year" => $Date->day->previous_day_year));
            $db->update("tasklist", array("last_executed" => CURRENT_TIME), array("tid" => $data_array["task_id"]));
            $db->query("SET @count = 0;");
            $db->query("UPDATE `{$db->table_prefix}analytics_data` SET `{$db->table_prefix}analytics_data`.`entryid` = @count:= @count + 1");
            $db->query("ALTER TABLE `{$db->table_prefix}analytics_data` AUTO_INCREMENT = 1");
            if ($log) {
                $taskname = $data_array["taskname"];
                $timecode = date(DATE_RFC2822, CURRENT_TIME);
                $message = "[{$timecode}] [CRON] [SUCCESS] The task {$taskname} was ran and {$res} entrys were moved to filesystem.";
                file_put_contents(ROOT_DIR . "logs/cron.log", $message.PHP_EOL , FILE_APPEND | LOCK_EX);
            }
            return TRUE;
        } else {
            return FALSE;
        }
    }
    return FALSE;
}