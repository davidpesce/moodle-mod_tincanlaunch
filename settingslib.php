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
 * Extend admin_setting_configtext to validate form data in tincanlaunch global settings
 *
 * @package    mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class admin_setting_configtext_mod_tincanlaunch extends admin_setting_configtext {
    /**
     * Saves the setting(s) provided in $data
     *
     * @param array $data An array of data, if not array returns empty str
     * @return mixed empty string on useless data or success, error string if failed
     */
    public function write_setting($data)
    {
        if ($this->paramtype === PARAM_INT and $data === '') {
            // do not complain if '' used instead of 0
            $data = 0;
        }
        // $data is a string
        $validated = $this->validate($data);
        if ($validated !== true) {
            return $validated;
        }
        //make sure there is always a trailing slash on endpoint URLs
        if($this->name=='tincanlaunchlrsendpoint'){
            $data = rtrim($data, '/') . '/';
        }
        return ($this->config_write($this->name, $data) ? '' : get_string('errorsetting', 'admin'));
    }
}

/**
 * Update instances on the basis of a change in default settings
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @return success
 */

function tincanlaunch_update_instances()
{
    global $DB, $CFG;
    static $is_processed;
    if ($is_processed) {
        // Run only once per request
        return;
    }

    $defaults = ($DB->get_records('config_plugins', array('plugin' =>'tincanlaunch')));
    $default_settings = new stdClass();
    foreach ($defaults as $index => $default) {
        $name = $default->name;
        $default_settings->$name = $default->value;
    }

    $instances = $DB->get_records('tincanlaunch', array('overridedefaults' => '0'));
    foreach ($instances as $index => $instance) {
        $tincanlaunch_lrs = new stdClass();
        $tincanlaunch_lrs->lrsendpoint = $default_settings->tincanlaunchlrsendpoint;
        $tincanlaunch_lrs->lrsauthentication = $default_settings->tincanlaunchlrsauthentication;
        $tincanlaunch_lrs->customacchp = $default_settings->tincanlaunchcustomacchp;
        $tincanlaunch_lrs->useactoremail = $default_settings->tincanlaunchuseactoremail;
        $tincanlaunch_lrs->lrsduration = $default_settings->tincanlaunchlrsduration;
        $tincanlaunch_lrs->tincanlaunchid = $instance->id;

        //check to see if there is a record of this instance in the lrs settings table
        $tincanlaunch_lrs_old = $DB->get_record('tincanlaunch_lrs', array('tincanlaunchid'=>$instance->id));

        //if watershed integration
        if ($tincanlaunch_lrs->lrsauthentication == '2') {
            $tincanlaunch_lrs->watershedlogin = $default_settings->tincanlaunchlrslogin;
            $tincanlaunch_lrs->watershedpass = $default_settings->tincanlaunchlrspass;

            // If Watershed creds have changed
            if (
                $tincanlaunch_lrs_old == false
                || $tincanlaunch_lrs_old->watershedlogin !== $tincanlaunch_lrs->watershedlogin
                || $tincanlaunch_lrs_old->watershedpass !== $tincanlaunch_lrs->watershedpass
                || $tincanlaunch_lrs_old->lrsauthentication !== '2'
            ) {
                // Create a new Watershed activity provider
                $creds = tincanlaunch_get_creds_watershed(
                    $tincanlaunch_lrs->watershedlogin, 
                    $tincanlaunch_lrs->watershedpass, 
                    $tincanlaunch_lrs->lrsendpoint,
                    $instance->id,
                    $CFG->wwwroot.'/mod/tincanlaunch/view.php?id='. $instance->id,
                    null
                );

                $tincanlaunch_lrs->lrslogin = $creds["key"];
                $tincanlaunch_lrs->lrspass = $creds["secret"];
            }
        } 
        else { 
            $tincanlaunch_lrs->lrslogin = $default_settings->tincanlaunchlrslogin;
            $tincanlaunch_lrs->lrspass = $default_settings->tincanlaunchlrspass;
        }

        //if record does not exist, will need to insert_record
        if ($tincanlaunch_lrs_old == false) {
            if (!$DB->insert_record('tincanlaunch_lrs', $tincanlaunch_lrs)) {
                return false;
            }
        } else {//if it does exist, update it
            $tincanlaunch_lrs->id = $tincanlaunch_lrs_old->id;

            if (!$DB->update_record('tincanlaunch_lrs', $tincanlaunch_lrs)) {
                return false;
            }
        }

    }

    $is_processed = true;
}