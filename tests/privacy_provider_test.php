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
 * Unit tests for mod_tincanlaunch privacy provider.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_tincanlaunch;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use mod_tincanlaunch\privacy\provider;

/**
 * Unit tests for mod_tincanlaunch privacy provider.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_tincanlaunch\privacy\provider
 */
final class privacy_provider_test extends provider_testcase {
    /**
     * Test get_metadata returns expected external location.
     */
    public function test_get_metadata(): void {
        $collection = new collection('mod_tincanlaunch');
        $collection = provider::get_metadata($collection);

        $items = $collection->get_collection();
        $this->assertNotEmpty($items);

        // Find the external location link.
        $found = false;
        foreach ($items as $item) {
            if ($item instanceof \core_privacy\local\metadata\types\external_location) {
                $found = true;
                $privacyfields = $item->get_privacy_fields();
                $this->assertArrayHasKey('actor_name', $privacyfields);
                $this->assertArrayHasKey('actor_email', $privacyfields);
                $this->assertArrayHasKey('actor_account_name', $privacyfields);
                $this->assertArrayHasKey('registration', $privacyfields);
                $this->assertArrayHasKey('statements', $privacyfields);
                $this->assertArrayHasKey('agent_profile', $privacyfields);
            }
        }
        $this->assertTrue($found, 'External location metadata should be present');
    }

    /**
     * Test that get_contexts_for_userid returns empty for a user with no activity.
     */
    public function test_get_contexts_for_userid_no_data(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $contextlist = provider::get_contexts_for_userid($user->id);

        $this->assertEmpty($contextlist->get_contextids());
    }

    /**
     * Test that get_users_in_context returns empty for a context with no launches.
     */
    public function test_get_users_in_context_no_data(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $course->id,
        ]);
        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id);
        $context = \context_module::instance($cm->id);

        $userlist = new userlist($context, 'mod_tincanlaunch');
        provider::get_users_in_context($userlist);

        $this->assertEmpty($userlist->get_userids());
    }

    /**
     * Test that get_users_in_context returns empty for non-module context.
     */
    public function test_get_users_in_context_wrong_context(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $userlist = new userlist($coursecontext, 'mod_tincanlaunch');
        provider::get_users_in_context($userlist);

        $this->assertEmpty($userlist->get_userids());
    }

    /**
     * Test delete_data_for_all_users_in_context runs without error.
     * Since no per-user data is stored in Moodle, this is a no-op.
     */
    public function test_delete_data_for_all_users_in_context(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $course->id,
        ]);
        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id);
        $context = \context_module::instance($cm->id);

        // Should not throw any exceptions.
        provider::delete_data_for_all_users_in_context($context);
        $this->assertTrue(true);
    }

    /**
     * Test delete_data_for_user runs without error.
     */
    public function test_delete_data_for_user(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $course->id,
        ]);
        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id);
        $context = \context_module::instance($cm->id);

        $contextlist = new approved_contextlist($user, 'mod_tincanlaunch', [$context->id]);

        // Should not throw any exceptions.
        provider::delete_data_for_user($contextlist);
        $this->assertTrue(true);
    }

    /**
     * Test delete_data_for_users runs without error.
     */
    public function test_delete_data_for_users(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('tincanlaunch', [
            'course' => $course->id,
        ]);
        $cm = get_coursemodule_from_instance('tincanlaunch', $instance->id);
        $context = \context_module::instance($cm->id);

        $userlist = new approved_userlist($context, 'mod_tincanlaunch', [$user->id]);

        // Should not throw any exceptions.
        provider::delete_data_for_users($userlist);
        $this->assertTrue(true);
    }
}
