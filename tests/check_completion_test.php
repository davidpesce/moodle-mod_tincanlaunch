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
 * Unit tests for mod_tincanlaunch check_completion task and batch cache.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_tincanlaunch;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/tincanlaunch/lib.php');

use mod_tincanlaunch\completion\custom_completion;

/**
 * Unit tests for batch completion cache in custom_completion.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_tincanlaunch\completion\custom_completion
 */
final class check_completion_test extends \advanced_testcase {
    /**
     * Test that get_state uses batch results when set.
     */
    public function test_batch_results_cache(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'tincanverbid' => 'http://adlnet.gov/expapi/verbs/completed',
            'tincanexpiry' => 0,
        ]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id);
        $cminfo = \cm_info::create($cm);

        // Set batch results: student1 completed, student2 not found.
        custom_completion::set_batch_results([
            $student->id => true,
        ]);

        $cc1 = new custom_completion($cminfo, $student->id);
        $this->assertEquals(COMPLETION_COMPLETE, $cc1->get_state('tincancompletionverb'));

        $cc2 = new custom_completion($cminfo, $student2->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $cc2->get_state('tincancompletionverb'));

        // Clean up.
        custom_completion::set_batch_results(null);
    }

    /**
     * Test that get_state falls through to LRS query when batch results are cleared.
     *
     * When batch results are null, custom_completion should attempt a per-user
     * LRS query. We verify this by checking that clearing the cache causes
     * different code to execute (the TinCanPHP library is invoked).
     */
    public function test_batch_results_cleared(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        set_config('tincanlaunchlrsendpoint', 'https://lrs.example.com/endpoint/', 'tincanlaunch');
        set_config('tincanlaunchlrsauthentication', 1, 'tincanlaunch');
        set_config('tincanlaunchlrslogin', 'key', 'tincanlaunch');
        set_config('tincanlaunchlrspass', 'secret', 'tincanlaunch');
        set_config('tincanlaunchlrsduration', 9000, 'tincanlaunch');
        set_config('tincanlaunchcustomacchp', '', 'tincanlaunch');
        set_config('tincanlaunchuseactoremail', 1, 'tincanlaunch');

        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'tincanverbid' => 'http://adlnet.gov/expapi/verbs/completed',
            'tincanexpiry' => 0,
        ]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id);
        $cminfo = \cm_info::create($cm);

        // First, verify that batch results WORK (cache returns COMPLETE).
        custom_completion::set_batch_results([$student->id => true]);
        $cc = new custom_completion($cminfo, $student->id);
        $this->assertEquals(COMPLETION_COMPLETE, $cc->get_state('tincancompletionverb'));

        // Now clear batch results — the same student should NOT get COMPLETE
        // because the code will fall through to the per-user LRS path.
        // The fake LRS will fail, so the result will be INCOMPLETE.
        custom_completion::set_batch_results(null);

        // Reset the settings cache so tincanlaunch_settings re-reads config.
        global $tincanlaunchsettings;
        $tincanlaunchsettings = null;

        // The TinCanPHP library will throw an error when trying to contact
        // the fake LRS. We catch this to confirm the fallback path ran.
        // Suppress TinCanPHP deprecation notices (third-party library).
        $olderror = error_reporting(E_ALL & ~E_DEPRECATED);
        $cc2 = new custom_completion($cminfo, $student->id);
        try {
            $state = $cc2->get_state('tincancompletionverb');
            // If no exception, the LRS query ran but returned no results.
            $this->assertEquals(COMPLETION_INCOMPLETE, $state);
        } catch (\Throwable $e) {
            // The TinCanPHP library threw an error contacting the fake LRS.
            // This confirms that clearing batch results causes the fallback
            // per-user LRS query path to execute.
            $this->assertStringContainsString('RemoteLRS', $e->getFile());
        } finally {
            error_reporting($olderror);
        }
    }

    /**
     * Test that set_batch_results with explicit false marks user incomplete.
     */
    public function test_batch_results_explicit_false(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'tincanverbid' => 'http://adlnet.gov/expapi/verbs/completed',
            'tincanexpiry' => 30,
        ]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id);
        $cminfo = \cm_info::create($cm);

        // Set batch results with explicit false (expired).
        custom_completion::set_batch_results([
            $student->id => false,
        ]);

        $cc = new custom_completion($cminfo, $student->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $cc->get_state('tincancompletionverb'));

        // Clean up.
        custom_completion::set_batch_results(null);
    }
}
