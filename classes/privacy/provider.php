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
 * Privacy provider for mod_tincanlaunch.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_tincanlaunch\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for mod_tincanlaunch.
 *
 * This plugin sends user data to an external Learning Record Store (LRS)
 * and does not store per-user data in Moodle's database. It implements
 * the metadata provider to declare what data is sent externally.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Returns metadata about the user data stored and/or transmitted by this plugin.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored or transmitted through this system.
     */
    public static function get_metadata(collection $collection): collection {
        // Data sent to the external LRS.
        $collection->add_external_location_link(
            'lrs',
            [
                'actor_name' => 'privacy:metadata:lrs:actor_name',
                'actor_email' => 'privacy:metadata:lrs:actor_email',
                'actor_account_name' => 'privacy:metadata:lrs:actor_account_name',
                'registration' => 'privacy:metadata:lrs:registration',
                'statements' => 'privacy:metadata:lrs:statements',
                'agent_profile' => 'privacy:metadata:lrs:agent_profile',
            ],
            'privacy:metadata:lrs'
        );

        // The tincanlaunch_lrs table stores per-instance LRS configuration (not per-user data).

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * This plugin does not store user data in Moodle — it sends data to an external LRS.
     * However, we return contexts where the user has launched activities so that
     * the user is aware data was sent externally.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Find contexts where the user has viewed/launched a tincanlaunch activity.
        // We use the standard log table to find this since we don't store per-user data.
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {logstore_standard_log} log ON log.contextid = ctx.id
                       AND log.userid = :userid
                       AND log.component = :component
                       AND log.action = :action";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'tincanlaunch',
            'userid' => $userid,
            'component' => 'mod_tincanlaunch',
            'action' => 'launched',
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT log.userid
                  FROM {logstore_standard_log} log
                 WHERE log.contextid = :contextid
                   AND log.component = :component
                   AND log.action = :action";

        $params = [
            'contextid' => $context->id,
            'component' => 'mod_tincanlaunch',
            'action' => 'launched',
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id('tincanlaunch', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }

            $tincanlaunch = $DB->get_record('tincanlaunch', ['id' => $cm->instance], '*', IGNORE_MISSING);
            if (!$tincanlaunch) {
                continue;
            }

            $data = (object) [
                'activity_name' => format_string($tincanlaunch->name),
                'activity_id' => $tincanlaunch->tincanactivityid,
                'launch_url' => $tincanlaunch->tincanlaunchurl,
                'data_sent_to_lrs' => (object) [
                    'actor_name' => fullname($user),
                    'actor_email' => $user->email,
                    'actor_idnumber' => $user->idnumber,
                ],
            ];

            writer::with_context($context)->export_data([], $data);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * As this plugin does not store per-user data in Moodle (data is in the external LRS),
     * there is nothing to delete from Moodle's database.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // No per-user data stored in Moodle. Data resides in external LRS.
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * As this plugin does not store per-user data in Moodle (data is in the external LRS),
     * there is nothing to delete from Moodle's database.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // No per-user data stored in Moodle. Data resides in external LRS.
    }

    /**
     * Delete multiple users within a single context.
     *
     * As this plugin does not store per-user data in Moodle (data is in the external LRS),
     * there is nothing to delete from Moodle's database.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        // No per-user data stored in Moodle. Data resides in external LRS.
    }
}
