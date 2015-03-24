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
 * launches the experience with the requested registration 
 *
 * @package mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
include 'locallib.php';

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // tincanlaunch instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('tincanlaunch', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $tincanlaunch  = $DB->get_record('tincanlaunch', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $tincanlaunch  = $DB->get_record('tincanlaunch', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $tincanlaunch->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('tincanlaunch', $tincanlaunch->id, $course->id, false, MUST_EXIST);
} else {
    error( get_string('idmissing', 'report_tincan') );
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

global $USER;
//check for completion
if (tincanlaunch_get_completion_state_test($course,$cm,$USER->id, TRUE, $tincanlaunch->id)){
	//Update the completion status
	$completion = new completion_info($course);
	if($completion->is_enabled($cm) && $tincanlaunch->tincanverbid) {
	    echo get_string('tincanlaunch_completed', 'tincanlaunch');
	}else{
		//return a status string
	    echo get_string('tincanlaunch_progress', 'tincanlaunch');
	}
}else{
	//return a status string
    echo get_string('tincanlaunch_progress', 'tincanlaunch');
}

function tincanlaunch_get_completion_state_test($course,$cm,$userid,$type, $tincanactivityid) {
    global $CFG,$DB;
    $tincanlaunchsettings = tincanlaunch_settings($tincanactivityid);
    $result=$type; // Default return value

	 // Get tincanlaunch
    if (!$tincanlaunch= $DB->get_record('tincanlaunch', array('id' => $cm->instance))) {
        throw new Exception("Can't find activity {$cm->instance}"); //TODO: localise this
    }
	
    if (!empty($tincanlaunch->tincanverbid)) {
    	//Try to get a statement matching actor, verb and object specified in module settings
    	$areAnyStatementsReturned = tincanlaunch_check_statements($tincanlaunchsettings['tincanlaunchlrsendpoint'], $tincanlaunchsettings['tincanlaunchlrslogin'], $tincanlaunchsettings['tincanlaunchlrspass'], $tincanlaunchsettings['tincanlaunchlrsversion'], $tincanlaunch->tincanactivityid, tincanlaunch_getactor(), $tincanlaunch->tincanverbid);

		//if the statement exists, return true else return false
		if ($areAnyStatementsReturned){
			//At this point we would normally set the Moodle completion to true by running $completion->update_state($cm,COMPLETION_COMPLETE);
			$result = TRUE;
			echo ('<p>completion is true</p>');
		}else{
			$result = FALSE;
			echo ('<p>completion is false</p>');
		}
    }
	else
	{
		echo ('<p>no verb specified</p>');
	}
	
    return $result;
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
 
