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
 * English strings for tincanlaunch
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Tin Can Launch Link';
$string['modulenameplural'] = 'Tin Can Launch Links';
$string['modulename_help'] = 'A plug in for Moodle that allows the launch of Tin Can content which is then tracked to a separate LRS.';

$string['tincanlaunchname'] = 'Launch link name';
$string['tincanlaunchname_help'] = 'The name of the launch link as it will appear to the user.';

$string['tincanlaunchurl'] = 'Launch URL';
$string['tincanlaunchurl_help'] = 'The base URL of the Tin Can activity you want to launch (including scheme).';

$string['tincanactivityid'] = 'Activity id';
$string['tincanactivityid_help'] = 'The identifying IRI for the primary activity being launched.';

//Start LRS settings
$string['tincanlaunchlrsfieldset'] = 'LRS settings';

$string['tincanlaunchlrsendpoint'] = 'Endpoint';
$string['tincanlaunchlrsendpoint_help'] = 'The LRS endpoint e.g. http://example.com/endpoint/';

$string['tincanlaunchlrslogin'] = 'Basic Login';
$string['tincanlaunchlrslogin_help'] = 'Your LRS login key.';

$string['tincanlaunchlrspass'] = 'Basic Password';
$string['tincanlaunchlrspass_help'] = 'Your LRS password key.';

$string['tincanlaunchlrsversion'] = 'Version';
$string['tincanlaunchlrsversion_help'] = 'The version of Tin Can to use e.g. 1.0.0.';

// LRS durationn
$string['tincanlaunchlrsduration'] = 'Duration (min)';
$string['tincanlaunchlrsduration_help'] = 'Duration should be in minute';
$string['tincanlaunchlrauthentication'] = 'Module settings';
//End LRS settings

$string['tincanlaunch'] = 'Tin Can Launch Link';
$string['pluginadministration'] = 'Tin Can Launch Link administration';
$string['pluginname'] = 'Tin Can Launch Link';

//verb completion settings
$string['completionverb'] = 'Verb';
$string['completionverbgroup'] = 'Track completion by verb';
$string['completionverbgroup_help'] = 'Moodle will look for statements where the actor is the current user, the object is the specified activity id and the verb is the one set here. If it finds a matching statement, the activity will be marked complete.';


//View settings
$string['tincanlaunchviewfirstlaunched'] = 'First launched';
$string['tincanlaunchviewlastlaunched'] = 'Last launched';
$string['tincanlaunchviewlaunchlinkheader'] = 'Launch link';
$string['tincanlaunchviewlaunchlink'] = 'launch';

$string['tincanlaunch_completed'] = 'Experience complete!';
$string['tincanlaunch_progress'] = 'Attempt in progress.';
$string['tincanlaunch_attempt'] = 'New Attempt';
$string['tincanlaunch_notavailable'] = 'The Learning Record Store is not available. Please contact a system administrator.';
$string['tincanlaunch_regidempty'] = 'Registration id not found. Please close this window.';

$string['idmissing'] = 'You must specify a course_module ID or an instance ID';
