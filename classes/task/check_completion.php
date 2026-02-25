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

use mod_tincanlaunch\completion\custom_completion;

defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(__FILE__))) . '/lib.php');

/**
 * Check tincanlaunch activity completion task.
 *
 * Makes one batch LRS request per module (instead of per user) and uses the
 * 'since' parameter to only fetch new statements since the last successful run.
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
        global $DB, $tincanlaunchsettings;

        $runstarttime = (new \DateTime('now', new \DateTimeZone('UTC')))->format('c');
        $lastsince = get_config('tincanlaunch', 'cron_last_successful_run');
        $lrserror = false;

        $module = $DB->get_record('modules', ['name' => 'tincanlaunch'], '*', MUST_EXIST);
        $modules = $DB->get_records('tincanlaunch');
        $courses = [];

        foreach ($modules as $tincanlaunch) {
            // Reset the settings cache so each module gets its own LRS settings.
            $tincanlaunchsettings = null;

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
                $coursecontext = \context_course::instance($course->id);
                $enrolledusers = get_enrolled_users($coursecontext, '', 0, 'u.id, u.idnumber, u.email, u.username,
                    u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename');
                $course->enrolledusers = $enrolledusers;
                $course->enrolleduserids = array_keys($enrolledusers);
                $courses[$cm->course] = $course;
            }
            $course = $courses[$cm->course];
            $completion = new \completion_info($course);

            if (!$completion->is_enabled($cm) || empty($tincanlaunch->tincanverbid)) {
                mtrace('  Completion not enabled or no verb set, skipping.');
                continue;
            }

            // Determine the 'since' parameter for this module.
            $since = null;
            $hasexpiry = ($tincanlaunch->tincanexpiry > 0);

            if ($hasexpiry) {
                $expirydatetime = new \DateTime('now', new \DateTimeZone('UTC'));
                $expirydatetime->sub(new \DateInterval('P' . $tincanlaunch->tincanexpiry . 'D'));
                $expirysincedate = $expirydatetime->format('c');

                // Use the older of last_cron_run and (now - expiry_days) so we catch expirations.
                if (!empty($lastsince) && $lastsince < $expirysincedate) {
                    $since = $lastsince;
                } else {
                    $since = $expirysincedate;
                }
            } else if (!empty($lastsince)) {
                $since = $lastsince;
            }

            // Load LRS settings for this module.
            $settings = tincanlaunch_settings($cm->instance);

            mtrace('  Querying LRS for all users (batch)' . ($since ? ' since ' . $since : '') . '.');

            // One LRS request for ALL users for this module.
            $statementsresponse = tincanlaunch_get_statements_batch(
                $settings['tincanlaunchlrsendpoint'],
                $settings['tincanlaunchlrslogin'],
                $settings['tincanlaunchlrspass'],
                $settings['tincanlaunchlrsversion'],
                $tincanlaunch->tincanactivityid,
                $tincanlaunch->tincanverbid,
                $since
            );

            if ($statementsresponse->success == false) {
                mtrace('  ERROR: LRS query failed, skipping module.');
                $lrserror = true;
                continue;
            }

            // Build actor map from enrolled users.
            $actormap = tincanlaunch_build_actor_map(array_values($course->enrolledusers), $settings);

            // Determine the expiry range start date for filtering statement timestamps.
            $expiryrangestartdate = null;
            if ($hasexpiry) {
                $expirydatetime = new \DateTime('now', new \DateTimeZone('UTC'));
                $expirydatetime->sub(new \DateInterval('P' . $tincanlaunch->tincanexpiry . 'D'));
                $expiryrangestartdate = $expirydatetime->format('c');
            }

            // Match statements to users and build batch results.
            $batchresults = [];
            $statements = is_array($statementsresponse->content) ? $statementsresponse->content : [];
            foreach ($statements as $statement) {
                $target = $statement->getTarget();
                if ($target === null) {
                    continue;
                }
                $objectid = $target->getId();
                $objecttype = $target->getObjectType();
                if ($objecttype !== "Activity" || $tincanlaunch->tincanactivityid !== $objectid) {
                    continue;
                }

                // Check expiry on the statement timestamp.
                if ($expiryrangestartdate !== null) {
                    $statementtimestamp = $statement->getTimestamp();
                    if ($statementtimestamp < $expiryrangestartdate) {
                        continue;
                    }
                }

                $userid = tincanlaunch_match_statement_to_user($statement, $actormap);
                if ($userid !== null) {
                    $batchresults[$userid] = true;
                }
            }

            mtrace('  Found ' . count($batchresults) . ' user(s) with matching statements.');

            // Populate the batch cache for custom_completion::get_state().
            custom_completion::set_batch_results($batchresults);

            // Determine the possible result for update_state.
            if ($hasexpiry) {
                $possibleresult = COMPLETION_UNKNOWN;
            } else {
                $possibleresult = COMPLETION_COMPLETE;
            }

            foreach ($course->enrolleduserids as $userid) {
                $oldstate = $completion->get_data($cm, false, $userid)->completionstate;
                $hascompleted = !empty($batchresults[$userid]);

                // Only call update_state when something may change:
                // - User is not complete and has a new matching statement, OR
                // - Module has expiry and user is complete but has no qualifying statement (expired).
                $needsupdate = false;
                if ($oldstate != COMPLETION_COMPLETE && $hascompleted) {
                    $needsupdate = true;
                } else if ($hasexpiry && $oldstate == COMPLETION_COMPLETE && !$hascompleted) {
                    $needsupdate = true;
                }

                if (!$needsupdate) {
                    continue;
                }

                mtrace('    Updating user id ' . $userid . ' (old state: ' . $oldstate . ').');

                $completion->update_state($cm, $possibleresult, $userid);

                $newstate = $completion->get_data($cm, false, $userid)->completionstate;
                mtrace('    New completion state is ' . $newstate . '.');

                if ($oldstate !== $newstate) {
                    $event = \mod_tincanlaunch\event\activity_completed::create([
                        'objectid' => $tincanlaunch->id,
                        'context' => \context_module::instance($cm->id),
                        'userid' => $userid,
                    ]);
                    $event->add_record_snapshot('course_modules', $cm);
                    $event->add_record_snapshot('tincanlaunch', $tincanlaunch);
                    $event->trigger();
                }
            }

            // Grade sync: extract scores from already-fetched statements and push to gradebook.
            if (!empty($tincanlaunch->grade) && $tincanlaunch->grade > 0) {
                $userscores = [];
                foreach ($statements as $statement) {
                    $userid = tincanlaunch_match_statement_to_user($statement, $actormap);
                    if ($userid === null) {
                        continue;
                    }

                    $result = $statement->getResult();
                    if ($result === null) {
                        continue;
                    }
                    $score = $result->getScore();
                    if ($score === null) {
                        continue;
                    }
                    $scaled = $score->getScaled();
                    if ($scaled === null) {
                        continue;
                    }

                    // Clamp negative scores to 0 (xAPI allows -1.0 to 1.0).
                    $scaled = max(0.0, (float) $scaled);

                    // Keep highest score per user.
                    if (!isset($userscores[$userid]) || $scaled > $userscores[$userid]) {
                        $userscores[$userid] = $scaled;
                    }
                }

                if (!empty($userscores)) {
                    $grades = [];
                    foreach ($userscores as $userid => $scaled) {
                        $grade = new \stdClass();
                        $grade->userid = $userid;
                        $grade->rawgrade = $scaled * $tincanlaunch->grade;
                        $grades[$userid] = $grade;
                    }
                    tincanlaunch_grade_item_update($tincanlaunch, $grades);
                    mtrace('  Pushed grades for ' . count($grades) . ' user(s).');
                }
            }

            // Clear batch results after processing this module.
            custom_completion::set_batch_results(null);
        }

        // Persist the run start time if no LRS errors occurred.
        if (!$lrserror) {
            set_config('cron_last_successful_run', $runstarttime, 'tincanlaunch');
            mtrace('Saved last successful run time: ' . $runstarttime);
        } else {
            mtrace('LRS errors occurred; not updating last successful run time.');
        }
    }
}
