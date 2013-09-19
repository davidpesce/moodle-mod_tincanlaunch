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
 * The main tincanlaunch configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 */
class mod_tincanlaunch_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = $this->_form;

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('tincanlaunchname', 'tincanlaunch'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'tincanlaunchname', 'tincanlaunch');

        // Adding the standard "intro" and "introformat" fields
        $this->add_intro_editor();

        //-------------------------------------------------------------------------------
        //Add the launch URL field
		$mform->addElement('text', 'tincanlaunchurl', get_string('tincanlaunchurl', 'tincanlaunch'), array('size'=>'64'));
		$mform->setType('tincanlaunchurl', PARAM_TEXT);
		$mform->addRule('tincanlaunchurl', null, 'required', null, 'client');
        $mform->addRule('tincanlaunchurl', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanlaunchurl', 'tincanlaunchurl', 'tincanlaunch');
		
		//Add the activity id field
		$mform->addElement('text', 'tincanactivityid', get_string('tincanactivityid', 'tincanlaunch'), array('size'=>'64'));
		$mform->setType('tincanactivityid', PARAM_TEXT);
		$mform->addRule('tincanactivityid', null, 'required', null, 'client');
        $mform->addRule('tincanactivityid', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanactivityid', 'tincanactivityid', 'tincanlaunch');
		
		//Add the LRS fieldset. TODO: move these fields as global settings in a separate plugin 
        $mform->addElement('header', 'tincanlaunchlrsfieldset', get_string('tincanlaunchlrsfieldset', 'tincanlaunch'));
		
		//Add LRS endpoint
		$mform->addElement('text', 'tincanlaunchlrsendpoint', get_string('tincanlaunchlrsendpoint', 'tincanlaunch'), array('size'=>'64'));
		$mform->setType('tincanlaunchlrsendpoint', PARAM_TEXT);
		$mform->addRule('tincanlaunchlrsendpoint', null, 'required', null, 'client');
        $mform->addRule('tincanlaunchlrsendpoint', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanlaunchlrsendpoint', 'tincanlaunchlrsendpoint', 'tincanlaunch');
		$mform->setDefault('tincanlaunchlrsendpoint','http://example.com/endpoint/'); //TODO: amend to use this format: $this->_customdata['email']
		
		//Add basic authorisation login. TODO: OAuth
		$mform->addElement('text', 'tincanlaunchlrslogin', get_string('tincanlaunchlrslogin', 'tincanlaunch'), array('size'=>'64'));
		$mform->setType('tincanlaunchlrslogin', PARAM_TEXT);
		$mform->addRule('tincanlaunchlrslogin', null, 'required', null, 'client');
        $mform->addRule('tincanlaunchlrslogin', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanlaunchlrslogin', 'tincanlaunchlrslogin', 'tincanlaunch');
		
		//Add basic authorisation pass. TODO: OAuth
		$mform->addElement('passwordunmask', 'tincanlaunchlrspass', get_string('tincanlaunchlrspass', 'tincanlaunch'), array('size'=>'64'));
		$mform->setType('tincanlaunchlrspass', PARAM_TEXT);
		$mform->addRule('tincanlaunchlrspass', null, 'required', null, 'client');
        $mform->addRule('tincanlaunchlrspass', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanlaunchlrspass', 'tincanlaunchlrspass', 'tincanlaunch');
		
		$mform->addElement('text', 'tincanlaunchlrsversion', get_string('tincanlaunchlrsversion', 'tincanlaunch'), array('size'=>'64'));
		$mform->setType('tincanlaunchlrsversion', PARAM_TEXT);
		$mform->addRule('tincanlaunchlrsversion', null, 'required', null, 'client');
        $mform->addRule('tincanlaunchlrsversion', get_string('maximumchars', '', 5), 'maxlength', 5, 'client');
        $mform->addHelpButton('tincanlaunchlrsversion', 'tincanlaunchlrsversion', 'tincanlaunch');
		$mform->setDefault('tincanlaunchlrsversion','1.0.0'); //TODO: amend to use this format: $this->_customdata['email']

        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }
}
