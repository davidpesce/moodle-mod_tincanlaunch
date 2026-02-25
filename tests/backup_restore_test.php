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
 * Unit tests for mod_tincanlaunch backup and restore.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_tincanlaunch;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/mod/tincanlaunch/lib.php');

/**
 * Unit tests for mod_tincanlaunch backup and restore functionality.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \backup_tincanlaunch_activity_task
 * @covers \restore_tincanlaunch_activity_task
 */
final class backup_restore_test extends \advanced_testcase {
    /**
     * Test backup and restore of a basic tincanlaunch instance.
     */
    public function test_backup_restore_basic(): void {
        global $DB, $USER, $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        $this->expectOutputRegex('/.*/');  // Suppress output from backup/restore.

        // Create a course with a tincanlaunch instance.
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $course->id,
            'name' => 'Test xAPI Activity',
            'intro' => 'This is a test xAPI activity',
            'introformat' => FORMAT_HTML,
            'tincanlaunchurl' => 'https://example.com/activity/index.html',
            'tincanactivityid' => 'https://example.com/activity',
            'tincanverbid' => 'http://adlnet.gov/expapi/verbs/completed',
            'tincanexpiry' => 30,
            'tincanmultipleregs' => 1,
            'tincansimplelaunchnav' => 0,
        ]);

        // Backup the course.
        $CFG->backup_file_logger_level = \backup::LOG_NONE;
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];

        // Extract backup.
        $fp = get_file_packer('application/vnd.moodle.backup');
        $tempdir = make_backup_temp_directory('test-restore-tincanlaunch');
        $file->extract_to_pathname($fp, $tempdir);
        $bc->destroy();

        // Restore to a new course.
        $newcourseid = \restore_dbops::create_new_course(
            $course->fullname . ' (restored)',
            $course->shortname . '_restored',
            $course->category
        );
        $rc = new \restore_controller(
            'test-restore-tincanlaunch',
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        // Verify restored instance.
        $restoredinstances = $DB->get_records('tincanlaunch', ['course' => $newcourseid]);
        $this->assertCount(1, $restoredinstances);

        $restored = reset($restoredinstances);
        $this->assertEquals('Test xAPI Activity', $restored->name);
        $this->assertEquals('This is a test xAPI activity', $restored->intro);
        $this->assertEquals('https://example.com/activity/index.html', $restored->tincanlaunchurl);
        $this->assertEquals('https://example.com/activity', $restored->tincanactivityid);
        $this->assertEquals('http://adlnet.gov/expapi/verbs/completed', $restored->tincanverbid);
        $this->assertEquals(30, $restored->tincanexpiry);
        $this->assertEquals(1, $restored->tincanmultipleregs);
        $this->assertEquals(0, $restored->tincansimplelaunchnav);
    }

    /**
     * Test backup and restore of a tincanlaunch instance with LRS overrides.
     */
    public function test_backup_restore_with_lrs_override(): void {
        global $DB, $USER, $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        $this->expectOutputRegex('/.*/');  // Suppress output from backup/restore.

        // Create a course with a tincanlaunch instance that overrides LRS settings.
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $course->id,
            'name' => 'Custom LRS Activity',
            'overridedefaults' => 1,
            'tincanlaunchlrsendpoint' => 'https://custom-lrs.example.com/endpoint/',
            'tincanlaunchlrsauthentication' => 1,
            'tincanlaunchlrslogin' => 'customkey',
            'tincanlaunchlrspass' => 'customsecret',
            'tincanlaunchlrsduration' => 5000,
            'tincanlaunchcustomacchp' => 'https://myschool.example.com',
            'tincanlaunchuseactoremail' => 0,
        ]);

        // Verify the LRS record was created.
        $originallrs = $DB->get_record('tincanlaunch_lrs', ['tincanlaunchid' => $instance->id]);
        $this->assertNotFalse($originallrs);

        // Backup the course.
        $CFG->backup_file_logger_level = \backup::LOG_NONE;
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];

        // Extract and restore.
        $fp = get_file_packer('application/vnd.moodle.backup');
        $tempdir = make_backup_temp_directory('test-restore-tincanlaunch-lrs');
        $file->extract_to_pathname($fp, $tempdir);
        $bc->destroy();

        $newcourseid = \restore_dbops::create_new_course(
            $course->fullname . ' (restored)',
            $course->shortname . '_restored2',
            $course->category
        );
        $rc = new \restore_controller(
            'test-restore-tincanlaunch-lrs',
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        // Verify restored instance and LRS settings.
        $restoredinstances = $DB->get_records('tincanlaunch', ['course' => $newcourseid]);
        $this->assertCount(1, $restoredinstances);

        $restored = reset($restoredinstances);
        $this->assertEquals('Custom LRS Activity', $restored->name);
        $this->assertEquals(1, $restored->overridedefaults);

        // Verify LRS record was restored.
        $restoredlrs = $DB->get_record('tincanlaunch_lrs', ['tincanlaunchid' => $restored->id]);
        $this->assertNotFalse($restoredlrs);
        $this->assertEquals('https://custom-lrs.example.com/endpoint/', $restoredlrs->lrsendpoint);
        $this->assertEquals(1, $restoredlrs->lrsauthentication);
        $this->assertEquals('customkey', $restoredlrs->lrslogin);
        $this->assertEquals('customsecret', $restoredlrs->lrspass);
        $this->assertEquals(5000, $restoredlrs->lrsduration);
        $this->assertEquals('https://myschool.example.com', $restoredlrs->customacchp);
        $this->assertEquals(0, $restoredlrs->useactoremail);
    }
}
