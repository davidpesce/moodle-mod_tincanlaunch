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
 * Displays an instance of tincanlaunch.
 *
 * @package mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require('header.php');
require_login();

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
echo $OUTPUT->header();

if ($tincanlaunch->intro) { // Conditions to show the intro can change to look for own settings or whatever.
    echo $OUTPUT->box(
        format_module_intro('tincanlaunch', $tincanlaunch, $cm->id),
        'generalbox mod_introbox',
        'tincanlaunchintro'
    );
}

$getregistrationdatafromlrsstate = tincanlaunch_get_global_parameters_and_get_state(
    "http://tincanapi.co.uk/stateapikeys/registrations"
);

$statuscode = $getregistrationdatafromlrsstate->httpResponse['status'];

// Some sort of failure occured; 404 means no registration data.
if ($statuscode != 200 && $statuscode != 404) {
    echo $OUTPUT->notification(get_string('tincanlaunch_notavailable', 'tincanlaunch'), 'error');
    debugging("<p>Error attempting to get registration data from State API.</p><pre>" .
        var_dump($getregistrationdatafromlrsstate) . "</pre>", DEBUG_DEVELOPER);
    die();
}

$lrshasregistrationdata = ($statuscode == 200);

// Success from LRS request for registration data.
if ($lrshasregistrationdata == true) {
    $registrationdatafromlrs = json_decode($getregistrationdatafromlrsstate->content->getContent(), true);

    foreach ($registrationdatafromlrs as $key => $item) {

        if (!is_array($registrationdatafromlrs[$key])) {
            $reason = "Excepted array, found " . $registrationdatafromlrs[$key];
            throw new moodle_exception($reason, 'tincanlaunch', '', $warnings[$reason]);
        }

        // Generate simple or classic launch navigation.
        if ($tincanlaunch->tincansimplelaunchnav == 1) {
            echo "<div id=tincanlaunch_newattempt> <a id=tincanlaunch_newattemptlink-". $key . ">".
            "<b>" . get_string('tincanlaunchviewlaunchlink', 'tincanlaunch') . "</b></a></div>";

        } else {
            array_push(
                $registrationdatafromlrs[$key],
                "<a id='tincanrelaunch_attempt-".$key."'>"
                . get_string('tincanlaunchviewlaunchlink', 'tincanlaunch') . "</a>"
            );

            $registrationdatafromlrs[$key]['created'] = date_format(
                date_create($registrationdatafromlrs[$key]['created']),
                'D, d M Y H:i:s'
            );
            $registrationdatafromlrs[$key]['lastlaunched'] = date_format(
                date_create($registrationdatafromlrs[$key]['lastlaunched']),
                'D, d M Y H:i:s'
            );
        }

        // For single registration, select the first one (the most recent).
        if ($tincanlaunch->tincanmultipleregs == 0) {
            break;
        }
    }

    // Classic launch navigation.
    if ($tincanlaunch->tincansimplelaunchnav == 0) {
        $table = new html_table();
        $table->id = 'tincanlaunch_attempttable';

        $table->caption = get_string('modulenameplural', 'tincanlaunch');
        $table->head = array(
            get_string('tincanlaunchviewfirstlaunched', 'tincanlaunch'),
            get_string('tincanlaunchviewlastlaunched', 'tincanlaunch'),
            get_string('tincanlaunchviewlaunchlinkheader', 'tincanlaunch')
        );

        $table->data = $registrationdatafromlrs;
        echo html_writer::table($table);
    }
}

// Generate a registration id for any new attempt.
$tincanphputil = new \TinCan\Util();
$registrationid = $tincanphputil->getUUID();

if ($tincanlaunch->tincansimplelaunchnav == 1) {
    // Initial registration for simple launch navigation.
    if ($lrshasregistrationdata == false) {
        echo "<div id=tincanlaunch_newattempt><a id=tincanlaunch_newattemptlink-". $registrationid .">".
            "<b>" . get_string('tincanlaunchviewlaunchlink', 'tincanlaunch') . "</b></a></div>";
    }
} else {
    // Multiple registrations for standard launch navigation - Display new registration attempt link.
    if ($tincanlaunch->tincanmultipleregs == 1) {
        echo "<div id=tincanlaunch_newattempt><a id=tincanlaunch_newattemptlink-". $registrationid .">".
            get_string('tincanlaunch_attempt', 'tincanlaunch') ."</a></div>";
    }
}

// Add status placeholder.
echo "<div id='tincanlaunch_status'></div>";

// New AMD module.
$PAGE->requires->js_call_amd('mod_tincanlaunch/launch', 'init');

// Add a form to be posted based on the attempt selected.
?>
    <form id="launchform" action="launch.php" method="get" target="_blank">
        <input id="launchform_registration" name="launchform_registration" type="hidden" value="default">
        <input id="id" name="id" type="hidden" value="<?php echo $id ?>">
        <input id="n" name="n" type="hidden" value="<?php echo $n ?>">
    </form>
<?php

echo $OUTPUT->footer();
