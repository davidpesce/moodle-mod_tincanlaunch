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
 * Check tincanlaunch activity completion task.
 *
 * @package    mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_tincanlaunch\task;

defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(__FILE__))) . '/lib.php');

/**
 * Check tincanlaunch activity completion task.
 *
 * @package    mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class check_completion extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('checkcompletion', 'tincanlaunch');
    }

    /**
     * Perform the scheduled task.
     */
    public function execute() {
        global $DB;

        $module = $DB->get_record('modules', ['name' => 'tincanlaunch'], '*', MUST_EXIST);
        $modules = $DB->get_records('tincanlaunch');
        $courses = []; // Cache course data in case multiple modules exist in a course.

        foreach ($modules as $tincanlaunch) {
            mtrace('Checking module id ' . $tincanlaunch->id . '.');
            $cm = $DB->get_record(
                'course_modules',
                ['module' => $module->id, 'instance' => $tincanlaunch->id],
                '*',
                IGNORE_MISSING
            );
            if (!$cm) {
                mtrace('  Course module not found, skipping.');
                continue;
            }
            if (!isset($courses[$cm->course])) {
                $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
                // Get enrolled user IDs for this specific course only.
                $coursecontext = \context_course::instance($course->id);
                $enrolledusers = get_enrolled_users($coursecontext, '', 0, 'u.id');
                $course->enrolleduserids = array_keys($enrolledusers);
                $courses[$cm->course] = $course;
            }
            $course = $courses[$cm->course];
            $completion = new \completion_info($course);

            // Determine if the activity has a completion expiration set.
            if ($tincanlaunch->tincanexpiry > 0) {
                $possibleresult = COMPLETION_UNKNOWN;
            } else {
                $possibleresult = COMPLETION_COMPLETE;
            }

            if ($completion->is_enabled($cm) && $tincanlaunch->tincanverbid) {
                foreach ($course->enrolleduserids as $userid) {
                    mtrace('  Checking user id ' . $userid . '.');

                    // Query the Moodle DB to determine current completion state.
                    $oldstate = $completion->get_data($cm, false, $userid)->completionstate;
                    if ($oldstate != COMPLETION_COMPLETE) {
                        mtrace('    Old completion state is ' . $oldstate . '.');

                        // Update completion state based on LRS data.
                        $completion->update_state($cm, $possibleresult, $userid);

                        // Query the Moodle DB again to determine a change in completion state.
                        $newstate = $completion->get_data($cm, false, $userid)->completionstate;
                        mtrace('    New completion state is ' . $newstate . '.');

                        if ($oldstate !== $newstate) {
                            // Trigger Activity completed event.
                            $event = \mod_tincanlaunch\event\activity_completed::create([
                                'objectid' => $tincanlaunch->id,
                                'context' => \context_module::instance($cm->id),
                                'userid' => $userid,
                            ]);
                            $event->add_record_snapshot('course_modules', $cm);
                            $event->add_record_snapshot('tincanlaunch', $tincanlaunch);
                            $event->trigger();
                        }
                    } else {
                        mtrace('    Skipping - activity is already complete.');
                    }
                }
            }
        }
    }
}
