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
 * mod_tincanlaunch data generator for testing.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Data generator class for mod_tincanlaunch.
 *
 * @package    mod_tincanlaunch
 * @copyright  2024 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_tincanlaunch_generator extends testing_module_generator {
    /**
     * Creates an instance of tincanlaunch for testing purposes.
     *
     * @param array|stdClass|null $record Data for the module instance.
     * @param array|null $options General options for course module.
     * @return stdClass Record from the tincanlaunch table with the cmid field.
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;

        $defaultsettings = [
            'tincanlaunchurl' => 'https://example.com/xapi-activity/index.html',
            'tincanactivityid' => 'https://example.com/xapi-activity',
            'tincanverbid' => 'http://adlnet.gov/expapi/verbs/completed',
            'tincanexpiry' => 365,
            'overridedefaults' => 0,
            'tincanmultipleregs' => 1,
            'tincansimplelaunchnav' => 0,
            'tincanlaunchlrsendpoint' => 'https://lrs.example.com/endpoint/',
            'tincanlaunchlrsauthentication' => 1,
            'tincanlaunchlrslogin' => 'testkey',
            'tincanlaunchlrspass' => 'testsecret',
            'tincanlaunchlrsduration' => 9000,
            'tincanlaunchcustomacchp' => '',
            'tincanlaunchuseactoremail' => 1,
        ];

        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array) $options);
    }
}
