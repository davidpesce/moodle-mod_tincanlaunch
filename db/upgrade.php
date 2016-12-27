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
 * This file keeps track of upgrades to the tincanlaunch module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute tincanlaunch upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_tincanlaunch_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016121200) {
        $table = new xmldb_table('tincanlaunch');
        $field = new xmldb_field('tincanexpiry', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 365);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2016021508) {

        // Define table tincanlaunch_credentials to be created.
        $table = new xmldb_table('tincanlaunch_credentials');

        // Adding fields to table tincanlaunch_credentials.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('tincanlaunchid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('credentialid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('expiry', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tincanlaunch_lrs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table tincanlaunch_lrs.
        $table->add_index('tincanlaunch_credentialid', XMLDB_INDEX_NOTUNIQUE, array('credentialid'));
        $table->add_index('tincanlaunch_tincanlaunchid', XMLDB_INDEX_NOTUNIQUE, array('tincanlaunchid'));

        // Conditionally launch create table for tincanlaunch_lrs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2016021508, 'tincanlaunch');
    }

    if ($oldversion < 2016021502) {
        // Define field  to be added to table.
        $table = new xmldb_table('tincanlaunch_lrs');
        $field = new xmldb_field('watershedlogin', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('watershedpass', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2016021502, 'tincanlaunch');
    }

    if ($oldversion < 2015112702) {
        // Define field tincanactivityid to be added to tincanlaunch.
        $table = new xmldb_table('tincanlaunch');
        $field = new xmldb_field('tincanmultipleregs', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'tincanverbid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('tincanlaunch_lrs');
        $field = new xmldb_field('useactoremail', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table->add_field('customacchp', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2015112702, 'tincanlaunch');
    }

    if ($oldversion < 2013083100) {
        // Define field tincanactivityid to be added to tincanlaunch.
        $table = new xmldb_table('tincanlaunch');
        $field = new xmldb_field('tincanactivityid', XMLDB_TYPE_TEXT, '255', null, XMLDB_NOTNULL, null, null, 'tincanlaunchurl');

        // Add field tincanactivityid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2013083100, 'tincanlaunch');
    }

    if ($oldversion < 2013111600) {
        // Define field tincanverbid to be added to tincanlaunch.
        $table = new xmldb_table('tincanlaunch');
        $field = new xmldb_field('tincanverbid', XMLDB_TYPE_TEXT, '255', null, XMLDB_NOTNULL, null, null, 'tincanlaunchurl');

        // Add field tincanactivityid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2013111600, 'tincanlaunch');
    }

    if ($oldversion < 2015032500) {

        // Define field overridedefaults to be added to tincanlaunch.
        $table = new xmldb_table('tincanlaunch');
        $field = new xmldb_field('overridedefaults', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'tincanverbid');

        // Conditionally launch add field overridedefaults.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define table tincanlaunch_lrs to be created.
        $table = new xmldb_table('tincanlaunch_lrs');

        // Adding fields to table tincanlaunch_lrs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('tincanlaunchid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lrsendpoint', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lrsauthentication', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lrslogin', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lrspass', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lrsduration', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tincanlaunch_lrs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table tincanlaunch_lrs.
        $table->add_index('tincanlaunchid', XMLDB_INDEX_NOTUNIQUE, array('tincanlaunchid'));

        // Conditionally launch create table for tincanlaunch_lrs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Tincanlaunch savepoint reached.
        upgrade_mod_savepoint(true, 2015032500, 'tincanlaunch');
    }

    if ($oldversion < 2015033100) {

        unset_config('tincanlaunchlrsversion', 'tincanlaunch');
        unset_config('tincanlaunchlrauthentication', 'tincanlaunch');

        upgrade_mod_savepoint(true, 2015033100, 'tincanlaunch');
    }

    // Final return of upgrade result (true, all went good) to Moodle.
    return true;
}
