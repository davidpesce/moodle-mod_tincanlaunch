<?php

namespace mod_tincanlaunch\task;
require_once(dirname(dirname(dirname(__FILE__))).'/lib.php');
defined('MOODLE_INTERNAL') || die();

class expire_credentials extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens
        return get_string('expirecredentials', 'mod_tincanlaunch');
    }

    public function execute() {
        global $DB;
        $records = $DB->get_records_select('tincanlaunch_credentials',"expiry > 0 AND expiry < ".time());

        if ($records != false){
            foreach ($records as $record) {
                if (tincanlaunch_delete_creds_watershed($record->tincanlaunchid, $record->credentialid)) {
                    $DB->delete_records('tincanlaunch_credentials', ['credentialid' => $record->credentialid]);
                };
            }
        }
    }
} 