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
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require('header.php');

// Trigger module viewed event.
$event = \mod_tincanlaunch\event\course_module_viewed::create(array(
    'objectid' => $tincanlaunch->id,
    'context' => $context,
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('tincanlaunch', $tincanlaunch);
$event->add_record_snapshot('course_modules', $cm);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/tincanlaunch/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($tincanlaunch->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$PAGE->requires->jquery();

// Output starts here.
echo $OUTPUT->header();

if ($tincanlaunch->intro) { // Conditions to show the intro can change to look for own settings or whatever.
    echo $OUTPUT->box(
        format_module_intro('tincanlaunch', $tincanlaunch, $cm->id),
        'generalbox mod_introbox',
        'tincanlaunchintro'
    );
}

// TODO: Put all the php inserted data as parameters on the functions and put the functions in a separate JS file.
?>
    <script>

        // Function to test for key press and call launch function if space or enter is hit.
        function key_test(registration) {
            if (event.keyCode === 13 || event.keyCode === 32) {
                mod_tincanlaunch_launchexperience(registration);
            }
        }

        // Function to run when the experience is launched.
        function mod_tincanlaunch_launchexperience(registration, id, n, course_url) {

            var form = document.createElement("form");
            document.body.appendChild(form);
            form.method = "GET";
            form.action = "launch.php";
            form.target = "_blank";

            var element1 = document.createElement("INPUT");
            element1.name="launchform_registration"
            element1.id="launchform_registration"
            element1.value = registration;
            element1.type = 'hidden'
            form.appendChild(element1);

            var element2 = document.createElement("INPUT");
            element2.name="id"
            element2.id="id"
            element2.value = id;
            element2.type = 'hidden'
            form.appendChild(element2);

            var element3 = document.createElement("INPUT");
            element3.name="n"
            element3.id="n"
            element3.value = n;
            element3.type = 'hidden'
            form.appendChild(element3);


            form.submit();
            console.log("got here!");

            location.href = course_url;
            console.log("got here! 2");

        }


    </script>
<?php

// Generate a registration id for any new attempt.
$tincanphputil = new \TinCan\Util();
$registrationid = $tincanphputil->getUUID();
$getregistrationdatafromlrsstate = tincanlaunch_get_global_parameters_and_get_state(
    "http://tincanapi.co.uk/stateapikeys/registrations"
);
$lrsrespond = $getregistrationdatafromlrsstate->httpResponse['status'];
console_log($getregistrationdatafromlrsstate);


if ($lrsrespond != 200 && $lrsrespond != 404) {
    // On clicking new attempt, save the registration details to the LRS State and launch a new attempt.
    echo "<div class='alert alert-error'>" . get_string('tincanlaunch_notavailable', 'tincanlaunch') . "</div>";

    if ($CFG->debug == 32767) {
        echo "<p>Error attempting to get registration data from State API.</p>";
        echo "<pre>";
        var_dump($getregistrationdatafromlrsstate);
        echo "</pre>";
    }
    die();
}

console_log($id);
console_log($PAGE->course->id);
$cid = $PAGE->course->id;



if ($lrsrespond == 200) {
    $registrationdatafromlrs = json_decode($getregistrationdatafromlrsstate->content->getContent(), true);
    //console_log($id);

    console_log($registrationdatafromlrs);
    $keys = array_keys($registrationdatafromlrs);
    $index = count($keys) - 1;
    $key = $keys[$index];
    //$cid = $PAGE->course->id
    //console_log($key);
    //console_log($registrationid);
    $course_url = $CFG->wwwroot .'/course/view.php?id=' . $cid;
    echo '<script type="text/javascript">mod_tincanlaunch_launchexperience(' . json_encode( $key ) . ', ' . json_encode( $id ) . ',  ' . json_encode( $n ) . ', ' . json_encode( $course_url ) . ' )</script>';


} else {
    echo "<p tabindex=\"0\"
        onkeyup=\"key_test('".$registrationid."')\"
        id='tincanlaunch_newattempt'><a onclick=\"mod_tincanlaunch_launchexperience('"
        . $registrationid
        . "')\" style=\"cursor: pointer;\">"
        . get_string('tincanlaunch_attempt', 'tincanlaunch')
        . "</a></p>";
}

function console_log( $data ){
    echo '<script>';
    echo 'console.log('. json_encode( $data ) .')';
    echo '</script>';
}

echo $OUTPUT->footer();

