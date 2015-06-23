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

    	global $CFG;
    	$cfg_tincanlaunch = get_config('tincanlaunch');

        $mform = $this->_form;

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('tincanlaunchname', 'tincanlaunch'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'tincanlaunchname', 'tincanlaunch');

        // Adding the standard "intro" and "introformat" fields
        $this->add_intro_editor();

        //-------------------------------------------------------------------------------
        //Start required Fields for Activity
		$mform->addElement('text', 'tincanlaunchurl', get_string('tincanlaunchurl', 'tincanlaunch'), array('size'=>'64'));
		$mform->setType('tincanlaunchurl', PARAM_TEXT);
		$mform->addRule('tincanlaunchurl', null, 'required', null, 'client');
        $mform->addRule('tincanlaunchurl', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanlaunchurl', 'tincanlaunchurl', 'tincanlaunch');
		
		$mform->addElement('text', 'tincanactivityid', get_string('tincanactivityid', 'tincanlaunch'), array('size'=>'64'));
		$mform->setType('tincanactivityid', PARAM_TEXT);
		$mform->addRule('tincanactivityid', null, 'required', null, 'client');
        $mform->addRule('tincanactivityid', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanactivityid', 'tincanactivityid', 'tincanlaunch');
        //End required Fields for Activity

        //Start advanced settings
        $mform->addElement('header', 'lrsheading', get_string('lrsheading', 'tincanlaunch'));
        $mform->setExpanded('lrsheading');

        $mform->addElement('static', 'description', get_string('lrsdefaults', 'tincanlaunch'), get_string('lrssettingdescription', 'tincanlaunch'));

        //Override default LRS settings
        $mform->addElement('advcheckbox', 'overridedefaults', get_string('overridedefaults', 'tincanlaunch'));
        $mform->addHelpButton('overridedefaults', 'overridedefaults', 'tincanlaunch');

        //Add LRS endpoint
        $mform->addElement('text', 'tincanlaunchlrsendpoint', get_string('tincanlaunchlrsendpoint', 'tincanlaunch'), array('size'=>'64'));
        $mform->setType('tincanlaunchlrsendpoint', PARAM_TEXT);
/*        $mform->addRule('tincanlaunchlrsendpoint', null, 'required', null, 'client');*/
        $mform->addRule('tincanlaunchlrsendpoint', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanlaunchlrsendpoint', 'tincanlaunchlrsendpoint', 'tincanlaunch');
        $mform->setDefault('tincanlaunchlrsendpoint', $cfg_tincanlaunch->tincanlaunchlrsendpoint);
        $mform->disabledIf('tincanlaunchlrsendpoint', 'overridedefaults');

        //Add LRS Authentication
        $authoptions = array(0=>get_string('tincanlaunchlrsauthentication_option_0', 'tincanlaunch'), 1=>get_string('tincanlaunchlrsauthentication_option_1', 'tincanlaunch'));
        $mform->addElement('select', 'tincanlaunchlrsauthentication', get_string('tincanlaunchlrsauthentication','tincanlaunch'), $authoptions);
/*        $mform->addRule('tincanlaunchlrsauthentication', null, 'required', null, 'client');*/
        $mform->disabledIf('tincanlaunchlrsauthentication', 'overridedefaults');
        $mform->addHelpButton('tincanlaunchlrsauthentication', 'tincanlaunchlrsauthentication', 'tincanlaunch');
        $mform->getElement('tincanlaunchlrsauthentication')->setSelected($cfg_tincanlaunch->tincanlaunchlrsauthentication);

        //Add basic authorisation login. TODO: OAuth
        $mform->addElement('text', 'tincanlaunchlrslogin', get_string('tincanlaunchlrslogin', 'tincanlaunch'), array('size'=>'64'));
        $mform->setType('tincanlaunchlrslogin', PARAM_TEXT);
/*        $mform->addRule('tincanlaunchlrslogin', null, 'required', null, 'client');*/
        $mform->addRule('tincanlaunchlrslogin', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanlaunchlrslogin', 'tincanlaunchlrslogin', 'tincanlaunch');
        $mform->setDefault('tincanlaunchlrslogin', $cfg_tincanlaunch->tincanlaunchlrslogin);
        $mform->disabledIf('tincanlaunchlrslogin', 'overridedefaults');

        //Add basic authorisation pass. TODO: OAuth
        $mform->addElement('text', 'tincanlaunchlrspass', get_string('tincanlaunchlrspass', 'tincanlaunch'), array('size'=>'64'));
        $mform->setType('tincanlaunchlrspass', PARAM_TEXT);
/*        $mform->addRule('tincanlaunchlrspass', null, 'required', null, 'client');*/
        $mform->addRule('tincanlaunchlrspass', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('tincanlaunchlrspass', 'tincanlaunchlrspass', 'tincanlaunch');
        $mform->setDefault('tincanlaunchlrspass', $cfg_tincanlaunch->tincanlaunchlrspass);
        $mform->disabledIf('tincanlaunchlrspass', 'overridedefaults');

        //Duration
        $mform->addElement('text', 'tincanlaunchlrsduration', get_string('tincanlaunchlrsduration', 'tincanlaunch'), array('size'=>'64'));
        $mform->setType('tincanlaunchlrsduration', PARAM_TEXT);
/*        $mform->addRule('tincanlaunchlrsduration', null, 'required', null, 'client');*/
        $mform->addRule('tincanlaunchlrsduration', get_string('maximumchars', '', 5), 'maxlength', 5, 'client');
        $mform->addHelpButton('tincanlaunchlrsduration', 'tincanlaunchlrsduration', 'tincanlaunch');
        $mform->setDefault('tincanlaunchlrsduration', $cfg_tincanlaunch->tincanlaunchlrsduration);
        $mform->disabledIf('tincanlaunchlrsduration', 'overridedefaults');
        //End advanced settings

        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }

	function add_completion_rules() {
	    $mform =& $this->_form;
	
	    $group=array();
	    $group[] =& $mform->createElement('checkbox', 'completionverbenabled', ' ', get_string('completionverb','tincanlaunch'));
	    $group[] =& $mform->createElement('text', 'tincanverbid', ' ',array('size'=>'64'));
	    $mform->setType('tincanverbid', PARAM_TEXT);
		
	    $mform->addGroup($group, 'completionverbgroup', get_string('completionverbgroup','tincanlaunch'), array(' '), false);
		$mform->addGroupRule('completionverbgroup', array(
			'tincanverbid' => array( 
				array(get_string('maximumchars', '', 255), 'maxlength', 255, 'client')
				)
			)
		);
		
	    $mform->addHelpButton('completionverbgroup', 'completionverbgroup', 'tincanlaunch');
	    $mform->disabledIf('tincanverbid','completionverbenabled','notchecked');
		$mform->setDefault('tincanverbid','http://adlnet.gov/expapi/verbs/completed'); 
		
	
	    return array('completionverbgroup');
	}
	
	function completion_rule_enabled($data) {
	    return (!empty($data['completionverbenabled']) && !empty($data['tincanverbid']));
	}
	
	function get_data() {
	    $data = parent::get_data();
	    if (!$data) {
	        return $data;
	    }
	    if (!empty($data->completionunlocked)) {
	        // Turn off completion settings if the checkboxes aren't ticked
	        $autocompletion = !empty($data->completion) && $data->completion==COMPLETION_TRACKING_AUTOMATIC;
	        if (empty($data->completionverbenabled) || !$autocompletion) {
	           $data->tincanverbid = '';
	        }
	    }
	    return $data;
	}
	
	function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);

        global $DB;

        //determine if default lrs settings were overriden
        if(!empty($default_values['overridedefaults'])){
            if($default_values['overridedefaults']=='1'){
                //retrieve activity lrs settings from DB
                $tincanlaunch_lrs = $DB->get_record('tincanlaunch_lrs', array('tincanlaunchid'=>$default_values['instance']), $fields='*', $strictness=IGNORE_MISSING);
                $default_values['tincanlaunchlrsendpoint'] = $tincanlaunch_lrs->lrsendpoint;
                $default_values['tincanlaunchlrsauthentication'] = $tincanlaunch_lrs->lrsauthentication;
                $default_values['tincanlaunchlrslogin'] = $tincanlaunch_lrs->lrslogin;
                $default_values['tincanlaunchlrspass'] = $tincanlaunch_lrs->lrspass;
                $default_values['tincanlaunchlrsduration'] = $tincanlaunch_lrs->lrsduration;
            }
        }

        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $default_values['completionverbenabled']=
            !empty($default_values['tincanverbid']) ? 1 : 0;
        if (empty($default_values['tincanverbid'])) {
            $default_values['completionverbenabled']=1;
        }
    }
    //Validate the form elements after submitting (server-side)
    function validation($data, $files){
        $errors = parent::validation($data, $files);

        //TODO: Validate IRI

        return $errors;
    }
}
