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
 * launches the experience with the requested registration
 *
 * @package mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('header.php');

$completion = new completion_info($course);

$possibleresult = COMPLETION_COMPLETE;

if ($tincanlaunch->tincanexpiry > 0) {
    $possibleresult = COMPLETION_UNKNOWN;
}

if ($completion->is_enabled($cm) && $tincanlaunch->tincanverbid) {
    $oldstate = $completion->get_data($cm, false, 0);
    $completion->update_state($cm, $possibleresult);
    $newstate = $completion->get_data($cm, false, 0);

    if ($oldstate->completionstate !== $newstate->completionstate) {
        // Trigger Activity completed event.
        $event = \mod_tincanlaunch\event\activity_completed::create(array(
            'objectid' => $tincanlaunch->id,
            'context' => $context,
        ));
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('tincanlaunch', $tincanlaunch);
        $event->trigger();
    }
}
