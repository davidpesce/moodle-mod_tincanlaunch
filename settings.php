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
    require_once($CFG->dirroot . '/mod/tincanlaunch/settingslib.php');

    //default display settings
    $settings->add(new admin_setting_heading('tincanlaunch/tincanlaunchlrsfieldset',
        get_string('tincanlaunchlrsfieldset', 'tincanlaunch'),
        get_string('tincanlaunchlrsfieldset_help', 'tincanlaunch')));

    $settings->add(new admin_setting_configtext_mod_tincanlaunch('tincanlaunch/tincanlaunchlrsendpoint',
        get_string('tincanlaunchlrsendpoint', 'tincanlaunch'),
        get_string('tincanlaunchlrsendpoint_help', 'tincanlaunch'),
        get_string('tincanlaunchlrsendpoint_default', 'tincanlaunch'), PARAM_URL));

    $options = array(0=>get_string('tincanlaunchlrsauthentication_option_0', 'tincanlaunch'), 1=>get_string('tincanlaunchlrsauthentication_option_1', 'tincanlaunch'));
    $settings->add(new admin_setting_configselect('tincanlaunch/tincanlaunchlrsauthentication',
        get_string('tincanlaunchlrsauthentication', 'tincanlaunch'),
        get_string('tincanlaunchlrsauthentication_help', 'tincanlaunch'), 1, $options));

    $settings->add(new admin_setting_configtext('tincanlaunch/tincanlaunchlrslogin',
        get_string('tincanlaunchlrslogin', 'tincanlaunch'),
        get_string('tincanlaunchlrslogin_help', 'tincanlaunch'),
        get_string('tincanlaunchlrslogin_default', 'tincanlaunch')));

    $settings->add(new admin_setting_configtext('tincanlaunch/tincanlaunchlrspass',
        get_string('tincanlaunchlrspass', 'tincanlaunch'),
        get_string('tincanlaunchlrspass_help', 'tincanlaunch'),
        get_string('tincanlaunchlrspass_default', 'tincanlaunch')));

    $settings->add(new admin_setting_configtext('tincanlaunch/tincanlaunchlrsduration',
        get_string('tincanlaunchlrsduration', 'tincanlaunch'),
        get_string('tincanlaunchlrsduration_help', 'tincanlaunch'),
        get_string('tincanlaunchlrsduration_default', 'tincanlaunch')));

}
