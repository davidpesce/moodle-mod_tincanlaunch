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
 * This launches the experience with the requested registration.
 *
 * @package mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_tincanlaunch;

// phpcs:ignore moodle.Files.RequireLogin.Missing -- require_login() is called in header.php.
require_once(__DIR__ . '/../../config.php');
require('header.php'); // Includes lib.php, locallib.php, params, and require_login().

// Trigger Activity launched event.
$event = \mod_tincanlaunch\event\activity_launched::create([
    'objectid' => $tincanlaunch->id,
    'context' => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('tincanlaunch', $tincanlaunch);
$event->trigger();

// Get the registration id.
$registrationid = required_param('launchform_registration', PARAM_TEXT);

if (empty($registrationid)) {
    debugging("Error attempting to get registration id querystring parameter.", DEBUG_DEVELOPER);
    throw new \moodle_exception('tincanlaunch_regidempty', 'tincanlaunch');
}

// Get record(s) of registration(s) from the LRS state API.
$getregistrationdatafromlrsstate = tincanlaunch_get_global_parameters_and_get_state(
    TINCANLAUNCH_STATE_REGISTRATIONS_KEY
);

$lrsrespond = $getregistrationdatafromlrsstate->httpResponse['status'];
// Failed to connect to LRS.
if ($lrsrespond != 200 && $lrsrespond != 204 && $lrsrespond != 404) {
    debugging("Error attempting to get registration data from State API. Status: " . $lrsrespond, DEBUG_DEVELOPER);
    throw new \moodle_exception('tincanlaunch_notavailable', 'tincanlaunch');
}
if ($lrsrespond == 200) {
    $registrationdata = json_decode($getregistrationdatafromlrsstate->content->getContent(), true);
} else {
    $registrationdata = null;
}
$registrationdataetag = $getregistrationdatafromlrsstate->content->getEtag();

$datenow = date("c");

$registrationdataforthisattempt = [
    $registrationid => [
        "created" => $datenow,
        "lastlaunched" => $datenow,
    ],
];

// If registrationdata is null (could be from 204/404 above) create a new registration data array.
if (is_null($registrationdata)) {
    $registrationdata = $registrationdataforthisattempt;
} else if (array_key_exists($registrationid, $registrationdata)) {
    // Else if the registration exists update the lastlaunched date.
    $registrationdata[$registrationid]["lastlaunched"] = $datenow;
} else { // Push the new data on the end.
    $registrationdata[$registrationid] = $registrationdataforthisattempt[$registrationid];
}

// Sort the registration data by last launched (most recent first).
uasort($registrationdata, function ($a, $b) {
    return strtotime($b['lastlaunched']) - strtotime($a['lastlaunched']);
});

// Note: Currently this is re-PUTting all of the data - it may be better just to POST the new data.
// This will prevent us sorting, but sorting could be done on output.
$saveregistrationdata = tincanlaunch_get_global_parameters_and_save_state(
    $registrationdata,
    TINCANLAUNCH_STATE_REGISTRATIONS_KEY,
    $registrationdataetag
);
$lrsrespond = $saveregistrationdata->httpResponse['status'];
// Failed to connect to LRS.
if ($lrsrespond != 204) {
    debugging("Error attempting to set registration data to State API. Status: " . $lrsrespond, DEBUG_DEVELOPER);
    throw new \moodle_exception('tincanlaunch_notavailable', 'tincanlaunch');
}

// Compile user data to send to agent profile.
$agentprofiles['CMI5LearnerPreferences'] = ["languagePreference" => tincanlaunch_get_moodle_language()];

// Check if there are any profile fields needing to be synced.
$profilefields = explode(',', get_config('tincanlaunch', 'profilefields'));
if (count($profilefields) > 0) {
    $agentprofiles['LMSUserFields'] = [];
    foreach ($profilefields as $profilefield) {
        $profilefield = strtolower($profilefield);
        // Lookup profile field value.
        if (array_key_exists($profilefield, $USER->profile)) {
            $agentprofiles['LMSUserFields'] = $agentprofiles['LMSUserFields'] +
                [$profilefield => $USER->profile[$profilefield]];
        }
    }
}

foreach ($agentprofiles as $key => $value) {
    $saveagentprofile = tincanlaunch_get_global_parameters_and_save_agentprofile($key, $value);

    $lrsrespond = $saveagentprofile->httpResponse['status'];
    if ($lrsrespond != 204) {
        // Failed to connect to LRS.
        debugging("Error attempting to set learner preferences (" . $key .
            ") to Agent Profile API. Status: " . $lrsrespond, DEBUG_DEVELOPER);
        throw new \moodle_exception('tincanlaunch_notavailable', 'tincanlaunch');
    }
}

// Send launched statement.
$savelaunchedstatement = tincan_launched_statement($registrationid);

$lrsrespond = $savelaunchedstatement->httpResponse['status'];
if ($lrsrespond != 204) {
    // Failed to connect to LRS.
    debugging("Error attempting to send 'launched' statement. Status: " . $lrsrespond, DEBUG_DEVELOPER);
    throw new \moodle_exception('tincanlaunch_notavailable', 'tincanlaunch');
}

// Set completion for module_viewed.
$completion = new \completion_info($course);
$completion->set_module_viewed($cm);

// Launch the experience.
header("Location: " . tincanlaunch_get_launch_url($registrationid));

exit;
