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
 * Library of interface functions and constants for module tincanlaunch
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the tincanlaunch specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/** example constant */
//define('tincanlaunch_ULTIMATE_ANSWER', 42);

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function tincanlaunch_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:         return true;
		case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES: return true;
        default:                        return null;
    }
}

/**
 * Saves a new instance of the tincanlaunch into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $tincanlaunch An object from the form in mod_form.php
 * @param mod_tincanlaunch_mod_form $mform
 * @return int The id of the newly inserted tincanlaunch record
 */
function tincanlaunch_add_instance(stdClass $tincanlaunch, mod_tincanlaunch_mod_form $mform = null) {
    global $DB;

    $tincanlaunch->timecreated = time();

    //Data for tincanlaunch_lrs table
    $tincanlaunch_lrs = new stdClass();
    $tincanlaunch_lrs->lrsendpoint = $tincanlaunch->tincanlaunchlrsendpoint;
    $tincanlaunch_lrs->lrsauthentication = $tincanlaunch->tincanlaunchlrsauthentication;
    $tincanlaunch_lrs->lrslogin = $tincanlaunch->tincanlaunchlrslogin;
    $tincanlaunch_lrs->lrspass = $tincanlaunch->tincanlaunchlrspass;
    $tincanlaunch_lrs->lrsduration = $tincanlaunch->tincanlaunchlrsduration;

    //need the id of the newly created instance to return (and use if override defaults checkbox is checked)
    $tincanlaunch->id = $DB->insert_record('tincanlaunch', $tincanlaunch);

    //determine if override defaults checkbox is checked
    if($tincanlaunch->overridedefaults=='1'){
        $tincanlaunch_lrs->tincanlaunchid = $tincanlaunch->id;

        //insert data into tincanlaunch_lrs table
        if(!$DB->insert_record('tincanlaunch_lrs', $tincanlaunch_lrs)){
            return false;
        }
    }

    return $tincanlaunch->id;
}

/**
 * Updates an instance of the tincanlaunch in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $tincanlaunch An object from the form in mod_form.php
 * @param mod_tincanlaunch_mod_form $mform
 * @return boolean Success/Fail
 */
function tincanlaunch_update_instance(stdClass $tincanlaunch, mod_tincanlaunch_mod_form $mform = null) {
    global $DB;

    $tincanlaunch->timemodified = time();
    $tincanlaunch->id = $tincanlaunch->instance;

    //Data for tincanlaunch_lrs table
    $tincanlaunch_lrs = new stdClass();
    $tincanlaunch_lrs->tincanlaunchid = $tincanlaunch->instance;
    $tincanlaunch_lrs->lrsendpoint = $tincanlaunch->tincanlaunchlrsendpoint;
    $tincanlaunch_lrs->lrsauthentication = $tincanlaunch->tincanlaunchlrsauthentication;
    $tincanlaunch_lrs->lrslogin = $tincanlaunch->tincanlaunchlrslogin;
    $tincanlaunch_lrs->lrspass = $tincanlaunch->tincanlaunchlrspass;
    $tincanlaunch_lrs->lrsduration = $tincanlaunch->tincanlaunchlrsduration;

    //determine if override defaults checkbox is checked
    if($tincanlaunch->overridedefaults=='1'){
        //check to see if there is a record of this instance in the table
        $tincanlaunch_lrs_id = $DB->get_field('tincanlaunch_lrs', 'id', array('tincanlaunchid'=>$tincanlaunch->instance), $strictness=IGNORE_MISSING);
        //if not, will need to insert_record
        if(!$tincanlaunch_lrs_id){
            if(!$DB->insert_record('tincanlaunch_lrs', $tincanlaunch_lrs)){
                return false;
            }
        }else{//if it does exist, update it
            $tincanlaunch_lrs->id = $tincanlaunch_lrs_id;
            if(!$DB->update_record('tincanlaunch_lrs', $tincanlaunch_lrs)){
                return false;
            }
        }
    }else{//if the user previously overrode defaults, there will be a record in tincanlaunch_lrs
        $tincanlaunch_lrs_id = $DB->get_field('tincanlaunch_lrs', 'id', array('tincanlaunchid'=>$tincanlaunch->instance), $strictness=IGNORE_MISSING);
        if($tincanlaunch_lrs_id){
            //delete it if so
            $DB->delete_records('tincanlaunch_lrs', array('id' => $tincanlaunch_lrs_id));
        }
    }

    if(!$DB->update_record('tincanlaunch', $tincanlaunch)){
        return false;
    }

    return true;
}

/**
 * Removes an instance of the tincanlaunch from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function tincanlaunch_delete_instance($id) {
    global $DB;

    if (! $tincanlaunch = $DB->get_record('tincanlaunch', array('id' => $id))) {
        return false;
    }

    //determine if there is a record of this (ever) in the tincanlaunch_lrs table
    $tincanlaunch_lrs_id = $DB->get_field('tincanlaunch_lrs', 'id', array('tincanlaunchid'=>$id), $strictness=IGNORE_MISSING);
    if($tincanlaunch_lrs_id){
        //if there is, delete it
        $DB->delete_records('tincanlaunch_lrs', array('id' => $tincanlaunch_lrs_id));
    }

    # Delete any dependent records here #

    $DB->delete_records('tincanlaunch', array('id' => $tincanlaunch->id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function tincanlaunch_user_outline($course, $user, $mod, $tincanlaunch) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $tincanlaunch the module instance record
 * @return void, is supposed to echp directly
 */
function tincanlaunch_user_complete($course, $user, $mod, $tincanlaunch) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in tincanlaunch activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function tincanlaunch_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link tincanlaunch_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function tincanlaunch_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see tincanlaunch_get_recent_mod_activity()}

 * @return void
 */
function tincanlaunch_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function tincanlaunch_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function tincanlaunch_get_extra_capabilities() {
    return array();
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Is a given scale used by the instance of tincanlaunch?
 *
 * This function returns if a scale is being used by one tincanlaunch
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $tincanlaunchid ID of an instance of this module
 * @return bool true if the scale is used by the given tincanlaunch instance
 */
function tincanlaunch_scale_used($tincanlaunchid, $scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists('tincanlaunch', array('id' => $tincanlaunchid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of tincanlaunch.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any tincanlaunch instance
 */
function tincanlaunch_scale_used_anywhere($scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists('tincanlaunch', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the give tincanlaunch instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $tincanlaunch instance object with extra cmidnumber and modname property
 * @return void
 */
function tincanlaunch_grade_item_update(stdClass $tincanlaunch) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    /** @example */
    $item = array();
    $item['itemname'] = clean_param($tincanlaunch->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = $tincanlaunch->grade;
    $item['grademin']  = 0;

    grade_update('mod/tincanlaunch', $tincanlaunch->course, 'mod', 'tincanlaunch', $tincanlaunch->id, 0, null, $item);
}

/**
 * Update tincanlaunch grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $tincanlaunch instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function tincanlaunch_update_grades(stdClass $tincanlaunch, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    /** @example */
    $grades = array(); // populate array of grade objects indexed by userid

    grade_update('mod/tincanlaunch', $tincanlaunch->course, 'mod', 'tincanlaunch', $tincanlaunch->id, 0, $grades);
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function tincanlaunch_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for tincanlaunch file areas
 *
  * @package mod_tincanlaunch
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function tincanlaunch_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the tincanlaunch file areas
 *
  * @package mod_tincanlaunch
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the tincanlaunch's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function tincanlaunch_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding tincanlaunch nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the tincanlaunch module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function tincanlaunch_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the tincanlaunch settings
 *
 * This function is called when the context for the page is a tincanlaunch module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $tincanlaunchnode {@link navigation_node}
 */
function tincanlaunch_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $tincanlaunchnode=null) {
}

//TODO: this function is never used. determine if it can be removed.
function tincanlaunch_get_completion_state($course,$cm,$userid,$type) {
    global $CFG,$DB;
    //temporarily hard coding a value here - for 'semi-graceful' failure
    $tincanlaunchsettings = tincanlaunch_settings('1');
    $result=$type; // Default return value

	 // Get tincanlaunch
    if (!$tincanlaunch= $DB->get_record('tincanlaunch', array('id' => $cm->instance))) {
        throw new Exception("Can't find activity {$cm->instance}"); //TODO: localise this
    }
	
    if (!empty($tincanlaunch->tincanverbid)) {
    	//Try to get a statement matching actor, verb and object specified in module settings
    	$statementquery = tincanlaunch_get_statements($tincanlaunchsettings['tincanlaunchlrsendpoint'], $tincanlaunchsettings['tincanlaunchlrslogin'], $tincanlaunchsettings['tincanlaunchlrspass'], $tincanlaunchsettings['tincanlaunchlrsversion'], $tincanlaunch->tincanactivityid, tincanlaunch_getactor(), $tincanlaunch->tincanverbid);

		//if the statement exists, return true else return false
		if (current($statementquery["contents"]["statements"])){
			$result = TRUE;
		}else{
			$result = FALSE;
		}
    }

    return $result;
}

function tincanlaunch_get_statements($url, $basicLogin, $basicPass, $version, $activityid, $agent, $verb) {

	$streamopt = array(
		'ssl' => array(
			'verify-peer' => false, 
			), 
		'http' => array(
			'method' => 'GET', 
			'ignore_errors' => false, 
			'header' => array(
				'Authorization: Basic ' . base64_encode( $basicLogin . ':' . $basicPass), 
				'Content-Type: application/json', 
				'Accept: application/json, */*; q=0.01',
				'X-Experience-API-Version: '.$version
			)
		), 
	);

	$streamparams = array(
		'activity' => trim($activityid),
		'agent' => json_encode($agent),
		'verb' => trim($verb)
	);

	
	$context = stream_context_create($streamopt);
	
	$stream = fopen(trim($url) . 'statements'.'?'.http_build_query($streamparams,'','&'), 'rb', false, $context);
	
	//Handle possible error codes
	$return_code = @explode(' ', $http_response_header[0]);
    $return_code = (int)$return_code[1];

    switch($return_code){
        case 200:
            $ret = stream_get_contents($stream);
            $meta = stream_get_meta_data($stream);
            if ($ret) {
                $ret = json_decode($ret, TRUE);
            }
            break;
        default: //error
            $ret = NULL;
            $meta = $return_code;
            break;
    }
    
    return array(
        'contents'=> $ret, 
        'metadata'=> $meta
    );
}

function tincanlaunch_check_statements($url, $basicLogin, $basicPass, $version, $activityid, $agent, $verb) {

	$streamopt = array(
		'ssl' => array(
			'verify-peer' => false, 
			), 
		'http' => array(
			'method' => 'GET', 
			'ignore_errors' => false, 
			'header' => array(
				'Authorization: Basic ' . base64_encode( $basicLogin . ':' . $basicPass), 
				'Content-Type: application/json', 
				'Accept: application/json, */*; q=0.01',
				'X-Experience-API-Version: '.$version
			)
		), 
	);

	$streamparams = array(
		'activity' => trim($activityid),
		'agent' => json_encode($agent),
		'verb' => trim($verb)
	);

	
	$context = stream_context_create($streamopt);
	
	$stream = fopen(trim($url) . 'statements'.'?'.http_build_query($streamparams,'','&'), 'rb', false, $context);
	
	//Handle possible error codes
	$return_code = @explode(' ', $http_response_header[0]);
    $return_code = (int)$return_code[1];

    switch($return_code){
        case 200:
            return FALSE;
        default: //error
            return TRUE;
    }
}

function tincanlaunch_getactor(){
	//TODO: make order of priority for user id a config setting
    global $USER, $CFG; 
    if ($USER->email){
        return array(
            "name" => fullname($USER),
            "mbox" => "mailto:".$USER->email,
            "objectType" => "Agent"
        );
    }
    /* elseif ($USER->idnumber){ 
        return array(
			"name" => fullname($USER),
			"account" => array(
				"homePage" => 'https://example.com', //TODO: make this a config setting
				"name" => $USER->idnumber
			),
			"objectType" => "Agent"
		);
    } */
    else{
		return array(
			"name" => fullname($USER),
			"account" => array(
				"homePage" => $CFG->wwwroot,
				"name" => $USER->username
			),
			"objectType" => "Agent"
		);
	}
}


//  tincan launch settings
function tincanlaunch_settings($tincanactivityid){
    global $DB;

    $expresult = array();

    //if global settings are not used, retrieve activity settings
    if(!use_global_lrs_settings($tincanactivityid)){
        $activitysettings = $DB->get_record('tincanlaunch_lrs', array('tincanlaunchid'=>$tincanactivityid), $fields='*', $strictness=IGNORE_MISSING);
        $expresult['tincanlaunchlrsendpoint'] = $activitysettings->lrsendpoint;
        $expresult['tincanlaunchlrsauthentication'] = $activitysettings->lrsauthentication;
        $expresult['tincanlaunchlrslogin'] = $activitysettings->lrslogin;
        $expresult['tincanlaunchlrspass'] = $activitysettings->lrspass;
        $expresult['tincanlaunchlrsduration'] = $activitysettings->lrsduration;
    }else{//use global lrs settings
        $result = $DB->get_records('config_plugins', array('plugin' =>'tincanlaunch'));
        foreach($result as $value){
            $expresult[$value->name] = $value->value;
        }
    }
    $expresult['tincanlaunchlrsversion'] = '1.0.0';

    return $expresult;
}

function use_global_lrs_settings($tincanactivityid){
    global $DB;
    //determine if there is a row in tincanlaunch_lrs matching the current activity id
    $activitysettings = $DB->record_exists('tincanlaunch_lrs', array('tincanlaunchid'=>$tincanactivityid));
    if($activitysettings){
        return false;
    }
    return true;
}