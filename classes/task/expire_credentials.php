<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_tincanlaunch\task;
defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(__FILE__))).'/lib.php');

class expire_credentials extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('expirecredentials', 'mod_tincanlaunch');
    }

    public function execute() {
        global $DB;
        $records = $DB->get_records_select('tincanlaunch_credentials', "expiry > 0 AND expiry < ".time());

        if ($records != false) {
            foreach ($records as $record) {
                if (tincanlaunch_delete_creds_watershed($record->tincanlaunchid, $record->credentialid)) {
                    $DB->delete_records('tincanlaunch_credentials', ['credentialid' => $record->credentialid]);
                };
            }
        }
    }
}