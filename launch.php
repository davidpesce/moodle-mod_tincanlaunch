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
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Trigger Activity launched event.
$event = \mod_tincanlaunch\event\activity_launched::create(array(
    'objectid' => $tincanlaunch->id,
    'context' => $context,
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('tincanlaunch', $tincanlaunch);
$event->trigger();

//get the registration id
$registrationid = $_GET["launchform_registration"];
if (empty($registrationid)) {
	echo "<div class='alert alert-error'>".get_string('tincanlaunch_regidempty','tincanlaunch')."</div>";
	//Failed to connect to LRS
	if ($CFG->debug == 32767) {
		echo "<p>Error attempting to get registration id querystring parameter.</p>";
	}
	die();
}

//Save a record of this registration to the LRS state API

$getregistrationdatafromlrsstate = tincanlaunch_get_global_parameters_and_get_state("http://tincanapi.co.uk/stateapikeys/registrations");
$registrationdata = $getregistrationdatafromlrsstate["contents"];
$registrationdataetag = tincanlaunch_extract_etag($getregistrationdatafromlrsstate["metadata"]["wrapper_data"]);

$errorhtml = "<div class='alert alert-error'>".get_string('tincanlaunch_notavailable','tincanlaunch')."</div>";

$lrsrespond = tincanlaunch_get_lrsresponse($getregistrationdatafromlrsstate["metadata"]);
if ($lrsrespond[1] != 200 && $lrsrespond != 404) {
	//Failed to connect to LRS
	echo $errorhtml;
	if ($CFG->debug == 32767) {
		echo "<p>Error attempting to get registration data from State API.</p>";
		echo "<pre>";
		var_dump($getregistrationdatafromlrsstate);
		echo "</pre>";
	}
	die();
}

$datenow = date("c");

$registrationdataforthisattempt = array(
    $registrationid => array(
	    "created" => $datenow,
	    "lastlaunched" => $datenow
	   )
);

if (is_null($registrationdata)){
	//if the error is 404 create a new registration data array
	if ($registrationdata["metadata"] = 404){
		$registrationdata = $registrationdataforthisattempt;
	}else { 
		//TODO: Some other error - possibly network connection. Consider re-trying.
	}
} elseif (array_key_exists($registrationid,$registrationdata)) { 
//elseif the regsitration exists update the lastlaunched date
	$registrationdata[$registrationid]["lastlaunched"] = $datenow;
} else { //else push the new data on the end
	$registrationdata[$registrationid] = $registrationdataforthisattempt[$registrationid];
}

//sort the registration data by last launched (most recent first)
uasort($registrationdata, function($a, $b) {
    return strtotime($b['lastlaunched']) - strtotime($a['lastlaunched']);
});

//TODO:currently this is re-PUTting all of the data - it may be better just to POST the new data. This will prevent us sorting, but sorting could be done on output. 
$saveresgistrationdata = tincanlaunch_get_global_parameters_and_save_state($registrationdata,"http://tincanapi.co.uk/stateapikeys/registrations",$registrationdataetag);

$lrsrespond = tincanlaunch_get_lrsresponse($saveresgistrationdata["metadata"]);
if ($lrsrespond[1] != 204) {
	//Failed to connect to LRS
	echo $errorhtml;
	if ($CFG->debug == 32767) {
		echo "<p>Error attempting to set registration data to State API.</p>";
		echo "<pre>";
		var_dump($saveresgistrationdata);
		echo "</pre>";
	}
	die();
}



$langpreference = array(
	"languagePreference" =>  tincanlaunch_get_moodle_langauge()
);

$saveagentprofile = tincanlaunch_get_global_parameters_and_save_agentprofile($langpreference,"CMI5LearnerPreferences");

$lrsrespond = tincanlaunch_get_lrsresponse($saveagentprofile["metadata"]);
if ($lrsrespond[1] != 204) {
	//Failed to connect to LRS
	echo $errorhtml;
	if ($CFG->debug == 32767) {
		echo "<p>Error attempting to set learner preferences to Agent Profile API.</p>";
		echo "<pre>";
		var_dump($saveagentprofile);
		echo "</pre>";
	}
	die();
}

$savelaunchedstatement = tincan_launched_statement($registrationid);

$lrsrespond = tincanlaunch_get_lrsresponse($savelaunchedstatement ["metadata"]);
if ($lrsrespond[1] != 204) {
	//Failed to connect to LRS
	echo $errorhtml;
	if ($CFG->debug == 32767) {
		echo "<p>Error attempting to send 'launched' statement.</p>";
		echo "<pre>";
		var_dump($savelaunchedstatement);
		echo "</pre>";
	}
	die();
}

//launch the experience
header("Location: ". tincanlaunch_get_launch_url($registrationid));

exit;

?>