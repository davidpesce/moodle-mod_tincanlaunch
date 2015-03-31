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

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    // And upgrade begins here. For each one, you'll need one
    // block of code similar to the next one. Please, delete
    // this comment lines once this file start handling proper
    // upgrade code.

    if ($oldversion < 2013083100) { //New version in version.php
    	// Define field tincanactivityid to be added to newmodule
        $table = new xmldb_table('tincanlaunch');
        $field = new xmldb_field('tincanactivityid', XMLDB_TYPE_TEXT, '255', null, XMLDB_NOTNULL, null, null, 'tincanlaunchurl');
		
        // Add field tincanactivityid
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        // Once we reach this point, we can store the new version and consider the module
        // upgraded to the version 2013083101 so the next time this block is skipped
        upgrade_mod_savepoint(true, 2013083100, 'tincanlaunch');
    }
	
	if ($oldversion < 2013111600) { //New version in version.php
    	// Define field tincanactivityid to be added to newmodule
        $table = new xmldb_table('tincanlaunch');
        $field = new xmldb_field('tincanverbid', XMLDB_TYPE_TEXT, '255', null, XMLDB_NOTNULL, null, null, 'tincanlaunchurl');
		
        // Add field tincanactivityid
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        // Once we reach this point, we can store the new version and consider the module
        // upgraded to the version 2013083101 so the next time this block is skipped
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

    if($oldversion < 2015033100){

        unset_config('tincanlaunchlrsversion', 'tincanlaunch');
        unset_config('tincanlaunchlrauthentication', 'tincanlaunch');

        upgrade_mod_savepoint(true, 2015033100, 'tincanlaunch');
    }

    // Final return of upgrade result (true, all went good) to Moodle.
    return true;
}
