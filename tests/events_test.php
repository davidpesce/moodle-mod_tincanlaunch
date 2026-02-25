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
 * Unit tests for mod_tincanlaunch events.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_tincanlaunch;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/tincanlaunch/lib.php');

/**
 * Unit tests for mod_tincanlaunch event classes.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_tincanlaunch\event\course_module_viewed
 * @covers \mod_tincanlaunch\event\activity_launched
 * @covers \mod_tincanlaunch\event\activity_completed
 * @covers \mod_tincanlaunch\event\course_module_instance_list_viewed
 */
final class events_test extends \advanced_testcase {
    /** @var \stdClass Test course. */
    protected \stdClass $course;

    /** @var \stdClass Test tincanlaunch instance. */
    protected \stdClass $instance;

    /** @var \stdClass Test course module. */
    protected \stdClass $cm;

    /** @var \context_module Test context. */
    protected \context_module $context;

    /**
     * Set up test fixtures.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->course = $this->getDataGenerator()->create_course();
        $this->instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $this->course->id,
        ]);
        $this->cm = get_coursemodule_from_instance('tincanlaunch', $this->instance->id);
        $this->context = \context_module::instance($this->cm->id);
    }

    /**
     * Test course_module_viewed event.
     */
    public function test_course_module_viewed(): void {
        $sink = $this->redirectEvents();

        $event = \mod_tincanlaunch\event\course_module_viewed::create([
            'objectid' => $this->instance->id,
            'context' => $this->context,
        ]);
        $event->add_record_snapshot('course', $this->course);
        $event->add_record_snapshot('tincanlaunch', $this->instance);
        $event->add_record_snapshot('course_modules', $this->cm);
        $event->trigger();

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = array_shift($events);

        $this->assertInstanceOf('\mod_tincanlaunch\event\course_module_viewed', $event);
        $this->assertEquals($this->context, $event->get_context());
        $this->assertEquals($this->instance->id, $event->objectid);
    }

    /**
     * Test activity_launched event.
     */
    public function test_activity_launched(): void {
        $sink = $this->redirectEvents();

        $event = \mod_tincanlaunch\event\activity_launched::create([
            'objectid' => $this->instance->id,
            'context' => $this->context,
        ]);
        $event->add_record_snapshot('course_modules', $this->cm);
        $event->add_record_snapshot('tincanlaunch', $this->instance);
        $event->trigger();

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = array_shift($events);

        $this->assertInstanceOf('\mod_tincanlaunch\event\activity_launched', $event);
        $this->assertEquals($this->context, $event->get_context());
        $this->assertEquals($this->instance->id, $event->objectid);
        $this->assertStringContainsString('launched the activity', $event->get_description());
    }

    /**
     * Test activity_completed event.
     */
    public function test_activity_completed(): void {
        $student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');

        $sink = $this->redirectEvents();

        $event = \mod_tincanlaunch\event\activity_completed::create([
            'objectid' => $this->instance->id,
            'context' => $this->context,
            'userid' => $student->id,
        ]);
        $event->add_record_snapshot('course_modules', $this->cm);
        $event->add_record_snapshot('tincanlaunch', $this->instance);
        $event->trigger();

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = array_shift($events);

        $this->assertInstanceOf('\mod_tincanlaunch\event\activity_completed', $event);
        $this->assertEquals($this->context, $event->get_context());
        $this->assertEquals($student->id, $event->userid);
    }

    /**
     * Test course_module_instance_list_viewed event.
     */
    public function test_course_module_instance_list_viewed(): void {
        $coursecontext = \context_course::instance($this->course->id);

        $sink = $this->redirectEvents();

        $event = \mod_tincanlaunch\event\course_module_instance_list_viewed::create([
            'context' => $coursecontext,
        ]);
        $event->add_record_snapshot('course', $this->course);
        $event->trigger();

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = array_shift($events);

        $this->assertInstanceOf('\mod_tincanlaunch\event\course_module_instance_list_viewed', $event);
        $this->assertEquals($coursecontext, $event->get_context());
    }
}
