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

/* For global tincan settings  */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/mod/tincanlaunch/locallib.php');
    $yesno = array(0 => get_string('no'),
                   1 => get_string('yes'));

    //default display settings
    $settings->add(new admin_setting_heading('tincanlaunch/tincanlaunchlrsfieldset',  get_string('tincanlaunchlrsfieldset', 'tincanlaunch'), ''));

    $settings->add(new admin_setting_configtext('tincanlaunch/tincanlaunchlrsendpoint',
        get_string('tincanlaunchlrsendpoint', 'tincanlaunch'), get_string('tincanlaunchlrsendpoint', 'tincanlaunch'),''));

    $settings->add(new admin_setting_configtext('tincanlaunch/tincanlaunchlrslogin',
        get_string('tincanlaunchlrslogin', 'tincanlaunch'), get_string('tincanlaunchlrslogin', 'tincanlaunch'),''));

    $settings->add(new admin_setting_configtext('tincanlaunch/tincanlaunchlrspass',
        get_string('tincanlaunchlrspass', 'tincanlaunch'), get_string('tincanlaunchlrspass', 'tincanlaunch'),''));

    $settings->add(new admin_setting_configtext('tincanlaunch/tincanlaunchlrsversion',
        get_string('tincanlaunchlrsversion', 'tincanlaunch'), get_string('tincanlaunchlrsversion', 'tincanlaunch'),''));

    $settings->add(new admin_setting_configtext('tincanlaunch/tincanlaunchlrsduration',
        get_string('tincanlaunchlrsduration', 'tincanlaunch'), '',''));

    $options = array(0=>'LRS integrated basic authentication', 1=>'Insecure basic authentication');
    $settings->add(new admin_setting_configselect('tincanlaunch/tincanlaunchlrauthentication',
                                    get_string('tincanlaunchlrauthentication', 'tincanlaunch'),
                                    ' ', 0, $options));

}
