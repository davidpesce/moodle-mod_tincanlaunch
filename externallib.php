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
 * xAPI Launch Link module external API
 *
 * @package    mod_tincanlaunch
 * @category   external
 * @copyright  2016 Float, LLC <info@gowithfloat.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * mod_tincanlaunch module external functions
 */
class mod_tincanlaunch_external extends external_api {
        
    /**
     * Describes the parameters for update_completion.
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function update_completion_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'course module id'),
                'userid' => new external_value(PARAM_INT, 'user id')
            )
        );
    }
    
    /**
     * Reaches out to the LRS and checks if the specified module is completed 
     * for the specified user.
     *
     * @return array of warnings and the updated completion status
     * @since Moodle 3.0
     */
    public static function update_completion($cmid, $userid) {
        global $DB;

        $cm = get_coursemodule_from_id('tincanlaunch', $cmid, 0, false, MUST_EXIST);
        $tincanlaunch = $DB->get_record('tincanlaunch', array('id' => $cm->instance), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

        $completion = new completion_info($course);

        $possibleresult = COMPLETION_COMPLETE;

        if ($tincanlaunch->tincanexpiry > 0) {
            $possibleresult = COMPLETION_UNKNOWN;
        }

        $oldstate = $completion->get_data($cm, false, $userid);
        $completion->update_state($cm, $possibleresult, $userid);
        $newstate = $completion->get_data($cm, false, $userid);

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

        $result = array();
        $result['completionstatus'] = $newstate->completionstate;
        $result['warnings'] = array();
        return $result; 
    }

    /**
     * Describes the return value for update_completion.
     *
     * @return external_single_structure
     * @since Moodle 3.0
     */
    public static function update_completion_returns() {
        return new external_single_structure(
            array(
                'completionstatus' => new external_value(PARAM_INT, 'Course status'),
                'warnings' => new external_warnings(),
            )
        );
    }
}