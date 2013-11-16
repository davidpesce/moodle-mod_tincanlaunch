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
 * Prints a particular instance of tincanlaunch
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
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

add_to_log($course->id, 'tincanlaunch', 'view', "view.php?id={$cm->id}", $tincanlaunch->name, $cm->id);

/// Print the page header

$PAGE->set_url('/mod/tincanlaunch/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($tincanlaunch->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// other things you may want to set - remove if not needed
//$PAGE->set_cacheable(false);
//$PAGE->set_focuscontrol('some-html-id');
//$PAGE->add_body_class('tincanlaunch-'.$somevar);
$PAGE->requires->jquery();

// Output starts here
echo $OUTPUT->header();

if ($tincanlaunch->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('tincanlaunch', $tincanlaunch, $cm->id), 'generalbox mod_introbox', 'tincanlaunchintro');
}

//Insert JavaScript functions

?>
	<script>
		function mod_tincanlaunch_launchexperience(registration) {
			//Set the form paramters
			$('#launchform_registration').val(registration);			
			//post it
			$('#launchform').submit();
		}
	</script>
<?php

//generate a registration id for any new attempt
$registrationid = tincanlaunch_gen_uuid();
//On clicking new attempt, save the registration details to the LRS State and launch a new attempt 
echo "<a onclick=\"mod_tincanlaunch_launchexperience('".$registrationid."')\" style=\"cursor: pointer;\">New Attempt</a>";

$getregistrationdatafromlrsstate = tincanlaunch_get_global_parameters_and_get_state("http://tincanapi.co.uk/stateapikeys/registrations");
$registrationdatafromlrs = $getregistrationdatafromlrsstate["contents"];

//if $registrationdatafromlrs is NULL  
if (is_null($registrationdatafromlrs)){
	//do nothing
} else{
	echo "<table>";
	echo "<th>".get_string('tincanlaunchviewfirstlaunched', 'tincanlaunch')."</th>";
	echo "<th>".get_string('tincanlaunchviewlastlaunched', 'tincanlaunch')."</th>";
	echo "<th>".get_string('tincanlaunchviewlaunchlinkheader', 'tincanlaunch')."</th></tr>";
	
	$index = 0;
	foreach ($registrationdatafromlrs as $thisregistrationid => $thisregistrationdates) {
		$index++;
	    echo "<tr>";
		echo "<td>".date(DateTime::RSS, strtotime($thisregistrationdates['lastlaunched']))."</td>";
		echo "<td>".date(DateTime::RSS, strtotime($thisregistrationdates['created']))."</td>";
		echo "<td><a onclick=\"mod_tincanlaunch_launchexperience('".$thisregistrationid."')\" style=\"cursor: pointer;\">".get_string('tincanlaunchviewlaunchlink', 'tincanlaunch')."</a></td></tr>";
	}
	
	echo "</table>";
}

//Add a form to to posted based on the attempt selected TODO: tidy up the querystring building code (post these too?)
?>
<form id="launchform" action="launch.php?id=<?php echo $id ?>&n=<?php echo $n ?>" method="post" target="_blank">
	<input id="launchform_registration" name="launchform_registration" type="hidden" value="default">
</form>
<?php

// Update completion state
//TODO: put this somewhere where it's likely to be called after the learner finishes the activity. 
$completion=new completion_info($course);
if($completion->is_enabled($cm) && $tincanlaunch->tincanverbid) {
    $completion->update_state($cm,COMPLETION_UNKNOWN);
}

// Finish the page
echo $OUTPUT->footer();
