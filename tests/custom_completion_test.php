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
 * Unit tests for mod_tincanlaunch custom completion.
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
 * Unit tests for mod_tincanlaunch custom completion rules.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_tincanlaunch\completion\custom_completion
 */
final class custom_completion_test extends \advanced_testcase {
    /**
     * Test get_defined_custom_rules returns the expected rules.
     */
    public function test_get_defined_custom_rules(): void {
        $rules = custom_completion::get_defined_custom_rules();

        $this->assertIsArray($rules);
        $this->assertCount(2, $rules);
        $this->assertContains('tincancompletionverb', $rules);
        $this->assertContains('tincancompletioexpiry', $rules);
    }

    /**
     * Test get_sort_order returns completionview and custom rules.
     */
    public function test_get_sort_order(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'tincanverbid' => 'http://adlnet.gov/expapi/verbs/completed',
            'tincanexpiry' => 30,
        ]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id);
        $cminfo = \cm_info::create($cm);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $customcompletion = new custom_completion($cminfo, $student->id);
        $sortorder = $customcompletion->get_sort_order();

        $this->assertIsArray($sortorder);
        $this->assertContains('completionview', $sortorder);
        $this->assertContains('tincancompletionverb', $sortorder);
        $this->assertContains('tincancompletioexpiry', $sortorder);
    }

    /**
     * Test get_custom_rule_descriptions returns descriptions for both rules.
     */
    public function test_get_custom_rule_descriptions(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'tincanverbid' => 'http://adlnet.gov/expapi/verbs/completed',
            'tincanexpiry' => 30,
        ]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id);
        $cminfo = \cm_info::create($cm);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $customcompletion = new custom_completion($cminfo, $student->id);
        $descriptions = $customcompletion->get_custom_rule_descriptions();

        $this->assertIsArray($descriptions);
        $this->assertArrayHasKey('tincancompletionverb', $descriptions);
        $this->assertArrayHasKey('tincancompletioexpiry', $descriptions);
        // The verb description should contain the verb name extracted from the URI.
        $this->assertStringContainsString('Completed', $descriptions['tincancompletionverb']);
        // The expiry description should contain the number of days.
        $this->assertStringContainsString('30', $descriptions['tincancompletioexpiry']);
    }

    /**
     * Test manual_completion_always_shown returns true.
     */
    public function test_manual_completion_always_shown(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'tincanverbid' => 'http://adlnet.gov/expapi/verbs/completed',
        ]);

        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id);
        $cminfo = \cm_info::create($cm);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $customcompletion = new custom_completion($cminfo, $student->id);
        $this->assertTrue($customcompletion->manual_completion_always_shown());
    }
}
