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
require_once($CFG->dirroot.'/lib/completionlib.php');

class check_completion extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('checkcompletion', 'mod_tincanlaunch');
    }

    public function execute() {
        global $DB;

        $module = $DB->get_record('modules', array('name' => 'tincanlaunch'), '*', MUST_EXIST);
        $modules = $DB->get_records('tincanlaunch');
        $courses = array(); // Cache course data incase the multiple modules exist in a course.

        foreach ($modules as $tincanlaunch) {
            echo ('Checking module id '.$tincanlaunch->id.'. '.PHP_EOL);
            $cm = $DB->get_record(
                'course_modules',
                array('module' => $module->id,
                    'instance' => $tincanlaunch->id),
                '*',
                MUST_EXIST
            );
            if (!isset($courses[$cm->course])) {
                $courses[$cm->course] = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
                $courses[$cm->course]->enrolments = $DB->get_records('user_enrolments', array('status' => 0));
            }
            $course = $courses[$cm->course];
            $completion = new \completion_info($course);

            $possibleresult = COMPLETION_COMPLETE;

            if ($tincanlaunch->tincanexpiry > 0) {
                $possibleresult = COMPLETION_UNKNOWN;
            }

            if ($completion->is_enabled($cm) && $tincanlaunch->tincanverbid) {
                foreach ($course->enrolments as $enrolment) {
                    echo ('Checking user id '.$enrolment->userid.'. ');
                    $oldstate = $completion->get_data($cm, false, $enrolment->userid)->completionstate;
                    echo ('Old completion state was '.$oldstate.'. ');
                    $completion->update_state($cm, $possibleresult, $enrolment->userid);
                    $newstate = $completion->get_data($cm, false, $enrolment->userid)->completionstate;
                    echo ('New completion state is '.$newstate.'. '.PHP_EOL);
                    if ($oldstate !== $newstate) {
                        // Trigger Activity completed event.
                        $event = \mod_tincanlaunch\event\activity_completed::create(array(
                            'objectid' => $tincanlaunch->id,
                            'context' => \context_module::instance($cm->id),
                            'userid' => $enrolment->userid
                        ));
                        $event->add_record_snapshot('course_modules', $cm);
                        $event->add_record_snapshot('tincanlaunch', $tincanlaunch);
                        $event->trigger();
                    }
                }
            }
        }
    }
}