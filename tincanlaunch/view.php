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

/// (Replace tincanlaunch with the name of your module and remove this line)

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

echo "<script src='js/viewfunctions.js'></script>
<script>var myViewFunctions = new mod_tincanlaunch_view();</script>";

//TODO: localisation of launch table

//Get a list of registrations from the LRS State

//Create a table of registrations, each with a launch link. 
//On clicking a launch link, launch the experience with the correct registration

//generate a registration id for any new attempt

$registrationid = 'foo';

//Add a new attempt link below the table
//On clicking new attempt, save the registration details to the LRS State and launch a new attempt

?>

<a onclick="myViewFunctions.saveNewRegistration('<?php echo $registrationid ?>')">New Attempt</a>;
<?php
echo tincanlaunch_get_launch_url();
echo "The activity has opened in a new window. 
<script>window.open('".tincanlaunch_get_launch_url()."');</script>";

// Finish the page
echo $OUTPUT->footer();
