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

// TinCanPHP - required for interacting with the LRS in tincanlaunch_get_statements.
require_once("$CFG->dirroot/mod/tincanlaunch/TinCanPHP/autoload.php");

// WatershedPHP - required for Watershed integration.
require_once("$CFG->dirroot/mod/tincanlaunch/WatershedPHP/watershed.php");

// SCORM library from the SCORM module. Required for its xml2Array class by tincanlaunch_process_new_package.
require_once("$CFG->dirroot/mod/scorm/datamodels/scormlib.php");

global $tincanlaunchsettings;
$tincanlaunchsettings = null;

// Moodle Core API.

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function tincanlaunch_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
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
    global $DB, $CFG;

    $tincanlaunch->timecreated = time();

    // Need the id of the newly created instance to return (and use if override defaults checkbox is checked).
    $tincanlaunch->id = $DB->insert_record('tincanlaunch', $tincanlaunch);

    $tincanlaunchlrs = tincanlaunch_build_lrs_settings($tincanlaunch);

    // Determine if override defaults checkbox is checked or we need to save watershed creds.
    if ($tincanlaunch->overridedefaults == '1' || $tincanlaunchlrs->lrsauthentication == '2') {
        $tincanlaunchlrs->tincanlaunchid = $tincanlaunch->id;

        // Insert data into tincanlaunch_lrs table.
        if (!$DB->insert_record('tincanlaunch_lrs', $tincanlaunchlrs)) {
            return false;
        }
    }

    // Process uploaded file.
    if (!empty($tincanlaunch->packagefile)) {
        tincanlaunch_process_new_package($tincanlaunch);
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
    global $DB, $CFG;

    $tincanlaunch->timemodified = time();
    $tincanlaunch->id = $tincanlaunch->instance;

    $tincanlaunchlrs = tincanlaunch_build_lrs_settings($tincanlaunch);

    // Determine if override defaults checkbox is checked.
    if ($tincanlaunch->overridedefaults == '1') {
        // Check to see if there is a record of this instance in the table.
        $tincanlaunchlrsid = $DB->get_field(
            'tincanlaunch_lrs',
            'id',
            array('tincanlaunchid' => $tincanlaunch->instance),
            IGNORE_MISSING
        );
        // If not, will need to insert_record.
        if (!$tincanlaunchlrsid) {
            if (!$DB->insert_record('tincanlaunch_lrs', $tincanlaunchlrs)) {
                return false;
            }
        } else { // If it does exist, update it.
            $tincanlaunchlrs->id = $tincanlaunchlrsid;

            if (!$DB->update_record('tincanlaunch_lrs', $tincanlaunchlrs)) {
                return false;
            }
        }
    }

    if (!$DB->update_record('tincanlaunch', $tincanlaunch)) {
        return false;
    }

    // Process uploaded file.
    if (!empty($tincanlaunch->packagefile)) {
        tincanlaunch_process_new_package($tincanlaunch);
    }

    return true;
}

function tincanlaunch_build_lrs_settings(stdClass $tincanlaunch) {
    global $DB, $CFG;

    // Data for tincanlaunch_lrs table.
    $tincanlaunchlrs = new stdClass();
    $tincanlaunchlrs->lrsendpoint = $tincanlaunch->tincanlaunchlrsendpoint;
    $tincanlaunchlrs->lrsauthentication = $tincanlaunch->tincanlaunchlrsauthentication;
    $tincanlaunchlrs->customacchp = $tincanlaunch->tincanlaunchcustomacchp;
    $tincanlaunchlrs->useactoremail = $tincanlaunch->tincanlaunchuseactoremail;
    $tincanlaunchlrs->lrsduration = $tincanlaunch->tincanlaunchlrsduration;

    // If Watershed integration.
    if ($tincanlaunchlrs->lrsauthentication == '2') {
        $tincanlaunchlrs->watershedlogin = $tincanlaunch->tincanlaunchlrslogin;
        $tincanlaunchlrs->watershedpass = $tincanlaunch->tincanlaunchlrspass;

        // If Watershed creds have changed.
        $tincanlaunchlrsold = $DB->get_record('tincanlaunch_lrs', array('tincanlaunchid' => $tincanlaunch->id));
        if (
            $tincanlaunchlrsold == false
            || $tincanlaunchlrsold->watershedlogin !== $tincanlaunchlrs->watershedlogin
            || $tincanlaunchlrsold->watershedpass !== $tincanlaunchlrs->watershedpass
            || $tincanlaunchlrsold->lrsauthentication !== '2'
        ) {
            // Create a new Watershed activity provider.
            $creds = tincanlaunch_get_creds_watershed(
                $tincanlaunchlrs->watershedlogin,
                $tincanlaunchlrs->watershedpass,
                $tincanlaunchlrs->lrsendpoint,
                $tincanlaunch->id,
                $CFG->wwwroot.'/mod/tincanlaunch/view.php?id='. $tincanlaunch->id,
                null
            );

            $tincanlaunchlrs->lrslogin = $creds["key"];
            $tincanlaunchlrs->lrspass = $creds["secret"];
        }
    } else {
        $tincanlaunchlrs->lrslogin = $tincanlaunch->tincanlaunchlrslogin;
        $tincanlaunchlrs->lrspass = $tincanlaunch->tincanlaunchlrspass;
    }

    return $tincanlaunchlrs;
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

    // Delete master LRS credentials for this instance.
    if ($credentialid = $DB->get_field('tincanlaunch_credentials', 'credentialid', array('tincanlaunchid' => $id))) {
        if (tincanlaunch_delete_creds_watershed($id, $credentialid) == true) {
            $DB->delete_records('tincanlaunch_credentials', ['credentialid' => $credentialid]);
        }
    }

    // Determine if there is a record of this (ever) in the tincanlaunch_lrs table.
    $tincanlaunchlrsid = $DB->get_field('tincanlaunch_lrs', 'id', array('tincanlaunchid' => $id), $strictness = IGNORE_MISSING);
    if ($tincanlaunchlrsid) {
        // If there is, delete it.
        $DB->delete_records('tincanlaunch_lrs', array('id' => $tincanlaunchlrsid));
    }

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
    return false;  // True if anything was printed, otherwise false.
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
function tincanlaunch_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0) {
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
function tincanlaunch_cron() {
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

// File API.

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
    $areas = array();
    $areas['content'] = get_string('areacontent', 'scorm');
    $areas['package'] = get_string('areapackage', 'scorm');
    return $areas;
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
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'package') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_tincanlaunch', 'package', 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_tincanlaunch', 'package', 0);
            } else {
                // Not found.
                return null;
            }
        }
        return new file_info_stored($browser, $context, $storedfile, $urlbase, $areas[$filearea], false, true, false, false);
    }

    return false;
}

/**
 * Serves Tin Can content, introduction images and packages. Implements needed access control ;-)
 *
 * @package  mod_tincanlaunch
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function tincanlaunch_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);
    $canmanageactivity = has_capability('moodle/course:manageactivities', $context);

    $filename = array_pop($args);
    $filepath = implode('/', $args);
    if ($filearea === 'content') {
        $lifetime = null;
    } else if ($filearea === 'package') {
        $lifetime = 0; // No caching here.
    } else {
        return false;
    }

    $fs = get_file_storage();

    if (
        !$file = $fs->get_file($context->id, 'mod_tincanlaunch', $filearea, 0, '/'.$filepath.'/', $filename)
        or $file->is_directory()
    ) {
        if ($filearea === 'content') { // Return file not found straight away to improve performance.
            send_header_404();
            die;
        }
        return false;
    }

    // Finally send the file.
    send_stored_file($file, $lifetime, 0, false, $options);
}

/**
 * Export file resource contents for web service access.
 *
 * @param cm_info $cm Course module object.
 * @param string $baseurl Base URL for Moodle.
 * @return array array of file content
 */
function tincanlaunch_export_contents($cm, $baseurl) {
    global $CFG;
    $contents = array();
    $context = context_module::instance($cm->id);

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_tincanlaunch', 'package', 0, 'sortorder DESC, id ASC', false);

    foreach ($files as $fileinfo) {
        $file = array();
        $file['type'] = 'file';
        $file['filename']     = $fileinfo->get_filename();
        $file['filepath']     = $fileinfo->get_filepath();
        $file['filesize']     = $fileinfo->get_filesize();
        $file['fileurl']      = file_encode_url("$CFG->wwwroot/" . $baseurl, '/'.$context->id.'/mod_tincanlaunch/package'.
            $fileinfo->get_filepath().$fileinfo->get_filename(), true);
        $file['timecreated']  = $fileinfo->get_timecreated();
        $file['timemodified'] = $fileinfo->get_timemodified();
        $file['sortorder']    = $fileinfo->get_sortorder();
        $file['userid']       = $fileinfo->get_userid();
        $file['author']       = $fileinfo->get_author();
        $file['license']      = $fileinfo->get_license();
        $contents[] = $file;
    }

    return $contents;
}

// Navigation API.

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
function tincanlaunch_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $tincanlaunchnode = null) {
}

// Called by Moodle core.
function tincanlaunch_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;
    $result = $type; // Default return value.

     // Get tincanlaunch.
    if (!$tincanlaunch = $DB->get_record('tincanlaunch', array('id' => $cm->instance))) {
        throw new Exception("Can't find activity {$cm->instance}"); // TODO: localise this.
    }

    $tincanlaunchsettings = tincanlaunch_settings($cm->instance);

    $expirydate = null;
    $expirydays = $tincanlaunch->tincanexpiry;
    if ($expirydays > 0) {
        $expirydatetime = new DateTime();
        $expirydatetime->sub(new DateInterval('P'.$expirydays.'D'));
        $expirydate = $expirydatetime->format('c');
    }

    if (!empty($tincanlaunch->tincanverbid)) {
        // Try to get a statement matching actor, verb and object specified in module settings.
        $statementquery = tincanlaunch_get_statements(
            $tincanlaunchsettings['tincanlaunchlrsendpoint'],
            $tincanlaunchsettings['tincanlaunchlrslogin'],
            $tincanlaunchsettings['tincanlaunchlrspass'],
            $tincanlaunchsettings['tincanlaunchlrsversion'],
            $tincanlaunch->tincanactivityid,
            tincanlaunch_getactor($cm->instance),
            $tincanlaunch->tincanverbid,
            $expirydate
        );

        // If the statement exists, return true else return false.
        if (!empty($statementquery->content) && $statementquery->success) {
            $result = true;
        } else {
            $result = false;
        }
    }

    return $result;
}

// TinCanLaunch specific functions.

/*
The functions below should really be in locallib, however they are required for one
or more of the functions above so need to be here.
It looks like the standard Quiz module does that same thing, so I don't feel so bad.
*/

/**
 * Handles uploaded zip packages when a module is added or updated. Unpacks the zip contents
 * and extracts the launch url and activity id from the tincan.xml file.
 * Note: This takes the *first* activity from the tincan.xml file to be the activity intended
 * to be launched. It will not go hunting for launch URLs any activities listed below.
 * Based closely on code from the SCORM and (to a lesser extent) Resource modules.
 * @package  mod_tincanlaunch
 * @category tincan
 * @param object $tincanlaunch An object from the form in mod_form.php
 * @return array empty if no issue is found. Array of error message otherwise
 */

function tincanlaunch_process_new_package($tincanlaunch) {
    global $DB, $CFG;

    $cmid = $tincanlaunch->coursemodule;
    $context = context_module::instance($cmid);

    // Reload TinCan instance.
    $record = $DB->get_record('tincanlaunch', array('id' => $tincanlaunch->id));

    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_tincanlaunch', 'package');
    file_save_draft_area_files(
        $tincanlaunch->packagefile,
        $context->id,
        'mod_tincanlaunch',
        'package',
        0,
        array('subdirs' => 0, 'maxfiles' => 1)
    );

    // Get filename of zip that was uploaded.
    $files = $fs->get_area_files($context->id, 'mod_tincanlaunch', 'package', 0, '', false);
    if (count($files) < 1) {
        return false;
    }

    $zipfile = reset($files);
    $zipfilename = $zipfile->get_filename();

    $packagefile = false;

    $packagefile = $fs->get_file($context->id, 'mod_tincanlaunch', 'package', 0, '/', $zipfilename);

    $fs->delete_area_files($context->id, 'mod_tincanlaunch', 'content');

    $packer = get_file_packer('application/zip');
    $packagefile->extract_to_storage($packer, $context->id, 'mod_tincanlaunch', 'content', 0, '/');

    // If the tincan.xml file isn't there, don't do try to use it.
    // This is unlikely as it should have been checked when the file was validated.
    if ($manifestfile = $fs->get_file($context->id, 'mod_tincanlaunch', 'content', 0, '/', 'tincan.xml')) {
        $xmltext = $manifestfile->get_content();

        $defaultorgid = 0;
        $firstinorg = 0;

        $pattern = '/&(?!\w{2,6};)/';
        $replacement = '&amp;';
        $xmltext = preg_replace($pattern, $replacement, $xmltext);

        $objxml = new xml2Array();
        $manifest = $objxml->parse($xmltext);

        // Update activity id from the first activity in tincan.xml, if it is found.
        // Skip without error if not. (The Moodle admin will need to enter the id manually).
        if (isset($manifest[0]["children"][0]["children"][0]["attrs"]["ID"])) {
            $record->tincanactivityid = $manifest[0]["children"][0]["children"][0]["attrs"]["ID"];
        }

        // Update launch from the first activity in tincan.xml, if it is found.
        // Skip if not. (The Moodle admin will need to enter the url manually).
        foreach ($manifest[0]["children"][0]["children"][0]["children"] as $property) {
            if ($property["name"] === "LAUNCH") {
                $record->tincanlaunchurl = $CFG->wwwroot."/pluginfile.php/".$context->id."/mod_tincanlaunch/"
                .$manifestfile->get_filearea()."/".$property["tagData"];
            }
        }
    }
    // Save reference.
    return $DB->update_record('tincanlaunch', $record);
}

/**
 * Check that a Zip file contains a tincan.xml file in the right place. Used in mod_form.php.
 * Heavily based on scorm_validate_package in /mod/scorm/lib.php
 * @package  mod_tincanlaunch
 * @category tincan
 * @param stored_file $file a Zip file.
 * @return array empty if no issue is found. Array of error message otherwise
 */
function tincanlaunch_validate_package($file) {
    $packer = get_file_packer('application/zip');
    $errors = array();
    $filelist = $file->list_files($packer);
    if (!is_array($filelist)) {
        $errors['packagefile'] = get_string('badarchive', 'tincanlaunch');
    } else {
        $badmanifestpresent = false;
        foreach ($filelist as $info) {
            if ($info->pathname == 'tincan.xml') {
                return array();
            } else if (strpos($info->pathname, 'tincan.xml') !== false) {
                // This package has tincan xml file inside a folder of the package.
                $badmanifestpresent = true;
            }
            if (preg_match('/\.cst$/', $info->pathname)) {
                return array();
            }
        }
        if ($badmanifestpresent) {
            $errors['packagefile'] = get_string('badimsmanifestlocation', 'tincanlaunch');
        } else {
            $errors['packagefile'] = get_string('nomanifest', 'tincanlaunch');
        }
    }
    return $errors;
}

/**
 * Fetches Statements from the LRS. This is used for completion tracking -
 * we check for a statement matching certain criteria for each learner.
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @param string $url LRS endpoint URL
 * @param string $basiclogin login/key for the LRS
 * @param string $basicpass pass/secret for the LRS
 * @param string $version version of xAPI to use
 * @param string $activityid Activity Id to filter by
 * @param TinCan Agent $agent Agent to filter by
 * @param string $verb Verb Id to filter by
 * @param string $since Since date to filter by
 * @return TinCan LRS Response
 */
function tincanlaunch_get_statements($url, $basiclogin, $basicpass, $version, $activityid, $agent, $verb, $since = null) {

    $lrs = new \TinCan\RemoteLRS($url, $version, $basiclogin, $basicpass);

    $statementsquery = array(
        "agent" => $agent,
        "verb" => new \TinCan\Verb(array("id" => trim($verb))),
        "activity" => new \TinCan\Activity(array("id" => trim($activityid))),
        "related_activities" => "false",
        "format" => "ids"
    );

    if (!is_null($since)) {
        $statementsquery["since"] = $since;
    }

    // Get all the statements from the LRS.
    $statementsresponse = $lrs->queryStatements($statementsquery);

    if ($statementsresponse->success == false) {
        return $statementsresponse;
    }

    $allthestatements = $statementsresponse->content->getStatements();
    $morestatementsurl = $statementsresponse->content->getMore();
    while (!empty($morestatementsurl)) {
        $morestmtsresponse = $lrs->moreStatements($morestatementsurl);
        if ($morestmtsresponse->success == false) {
            return $morestmtsresponse;
        }
        $morestatements = $morestmtsresponse->content->getStatements();
        $morestatementsurl = $morestmtsresponse->content->getMore();
        // Note: due to the structure of the arrays, array_merge does not work as expected.
        foreach ($morestatements as $morestatement) {
            array_push($allthestatements, $morestatement);
        }
    }

    return new \TinCan\LRSResponse(
        $statementsresponse->success,
        $allthestatements,
        $statementsresponse->httpResponse
    );
}

/**
 * Build a TinCan Agent based on the current user
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @return TinCan Agent $agent Agent
 */
function tincanlaunch_getactor($instance) {
    global $USER, $CFG;

    $settings = tincanlaunch_settings($instance);

    if ($USER->idnumber && $settings['tincanlaunchcustomacchp']) {
        $agent = array(
            "name" => fullname($USER),
            "account" => array(
                "homePage" => $settings['tincanlaunchcustomacchp'],
                "name" => $USER->idnumber
            ),
            "objectType" => "Agent"
        );
    } else if ($USER->email && $settings['tincanlaunchuseactoremail']) {
        $agent = array(
            "name" => fullname($USER),
            "mbox" => "mailto:".$USER->email,
            "objectType" => "Agent"
        );
    } else {
        $agent = array(
            "name" => fullname($USER),
            "account" => array(
                "homePage" => $CFG->wwwroot,
                "name" => $USER->username
            ),
            "objectType" => "Agent"
        );
    }

    return new \TinCan\Agent($agent);
}

/**
 * Used with Watershed integration to fetch credentials from the LRS.
 * This process is not part of the xAPI specification or the Tin Can launch spec.
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @param string $login login for Watershed
 * @param string $pass pass for Watershed
 * @param string $endpoint LRS endpoint URL
 * @param int $expiry Unix timestamp for credentials to expire null = never.
 * @return array the response of the LRS (Note: not a TinCan LRS Response object)
 */
function tincanlaunch_get_creds_watershed($login, $pass, $endpoint, $tincanlaunchid, $apname, $expiry) {
    global $CFG, $DB;
    // Create a new Watershed activity provider.
    $auth = array(
        "method" => "BASIC",
        "username" => $login,
        "password" => $pass
    );

    $explodedendpoint = explode ('/', $endpoint);
    $wsserver = $explodedendpoint[0].'//'.$explodedendpoint[2];
    $orgid = $explodedendpoint[5];

    $wsclient = new \WatershedClient\Watershed($wsserver, $auth, $orgid, null);

    if (is_null($expiry)) {
        $expiryunix = 0;
    } else {
        $expiryunix = $expiry->getTimestamp();
    }

    $response = $wsclient->createActivityProvider($apname, $orgid);
    if ($response["success"]) {
        $credentialid = json_decode($response["content"])->id;
        $DB->insert_record('tincanlaunch_credentials', (object)[
            "tincanlaunchid" => $tincanlaunchid,
            "credentialid" => $credentialid,
            "expiry" => $expiryunix
        ], false);
        return $response;
    } else {
        $reason = get_string('apCreationFailed', 'tincanlaunch')
        ." Status: ". $response["status"].". Response: ".$response["content"]."<br/>";
        throw new moodle_exception($reason, 'tincanlaunch', '');
    }
}

/**
 * Used with Watershed integration to fetch credentials from the LRS.
 * This process is not part of the xAPI specification or the Tin Can launch spec.
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @param int $tincanlaunchid instance id for LRS settings
 * @param int $credentialid credential id to delete
 * @return Bool success
 */
function tincanlaunch_delete_creds_watershed($tincanlaunchid, $credentialid) {
    global $CFG;

    $tincanlaunchsettings = tincanlaunch_settings($tincanlaunchid);

    // Create a new Watershed activity provider.
    $auth = array(
        "method" => "BASIC",
        "username" => $tincanlaunchsettings['tincanlaunchwatershedlogin'],
        "password" => $tincanlaunchsettings['tincanlaunchwatershedpass']
    );

    $explodedendpoint = explode ('/', $tincanlaunchsettings['tincanlaunchlrsendpoint']);
    $wsserver = $explodedendpoint[0].'//'.$explodedendpoint[2];
    $orgid = $explodedendpoint[5];

    $wsclient = new \WatershedClient\Watershed($wsserver, $auth, $orgid, 'Moodle');

    $response = $wsclient->deleteActivityProvider($credentialid, $orgid);
    if ($response["success"]) {
        echo("Deleted credential id {$credentialid} on organization id {$orgid}");
        return true;
    } else {
        echo("Failed to delete credential id {$credentialid} on organization id {$orgid}");
        echo ('<pre>');
        var_dump($response);
        echo ('</pre>');
        return false;
    }
}

/**
 * Returns the LRS settings relating to a Tin Can Launch module instance
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @param string $instance The Moodle id for the Tin Can module instance.
 * @return array LRS settings to use
 */
function tincanlaunch_settings($instance) {
    global $DB, $CFG, $tincanlaunchsettings;

    if (!is_null($tincanlaunchsettings)) {
        return $tincanlaunchsettings;
    }

    $expresult = array();
    $activitysettings = $DB->get_record(
        'tincanlaunch_lrs',
        array('tincanlaunchid' => $instance),
        $fields = '*',
        $strictness = IGNORE_MISSING
    );

    // If global settings are not used, retrieve activity settings.
    if (!use_global_lrs_settings($instance)) {
        $expresult['tincanlaunchlrsendpoint'] = $activitysettings->lrsendpoint;
        $expresult['tincanlaunchlrsauthentication'] = $activitysettings->lrsauthentication;
        $expresult['tincanlaunchlrslogin'] = $activitysettings->lrslogin;
        $expresult['tincanlaunchlrspass'] = $activitysettings->lrspass;
        $expresult['tincanlaunchcustomacchp'] = $activitysettings->customacchp;
        $expresult['tincanlaunchuseactoremail'] = $activitysettings->useactoremail;
        $expresult['tincanlaunchlrsduration'] = $activitysettings->lrsduration;
        $expresult['tincanlaunchwatershedlogin'] = $activitysettings->watershedlogin;
        $expresult['tincanlaunchwatershedpass'] = $activitysettings->watershedpass;
    } else { // Use global lrs settings.
        $result = $DB->get_records('config_plugins', array('plugin' => 'tincanlaunch'));
        foreach ($result as $value) {
            $expresult[$value->name] = $value->value;
        }
    }

    // If Watershed integration, don't use global xAPI creds.
    if ($expresult['tincanlaunchlrsauthentication'] == '2') {

        // The global login and password are always Watershed creds, not xapi creds.
        $expresult['tincanlaunchwatershedlogin'] = $expresult['tincanlaunchlrslogin'];
        $expresult['tincanlaunchwatershedpass'] = $expresult['tincanlaunchlrspass'];

        // Check if we need to update instance record (endpoint, username, password or auth type have changed).
        if (
            $activitysettings == false
            || $activitysettings->watershedlogin !== $expresult['tincanlaunchlrslogin']
            || $activitysettings->watershedpass !== $expresult['tincanlaunchlrspass']
            || $activitysettings->lrsendpoint !== $expresult['tincanlaunchlrsendpoint']
            || $activitysettings->lrsauthentication !== '2'
        ) {
            // Create a new Watershed activity provider.
            $creds = tincanlaunch_get_creds_watershed(
                $expresult['tincanlaunchlrslogin'],
                $expresult['tincanlaunchlrspass'],
                $expresult['tincanlaunchlrsendpoint'],
                $instance,
                $CFG->wwwroot.'/mod/tincanlaunch/view.php?id='. $instance,
                null
            );

            // Update database with newly created xapi creds.
            $tincanlaunchlrs = new stdClass();
            $tincanlaunchlrs->lrsendpoint = $expresult['tincanlaunchlrsendpoint'];
            $tincanlaunchlrs->lrslogin = $creds["key"];
            $tincanlaunchlrs->lrspass = $creds["secret"];
            $tincanlaunchlrs->watershedlogin = $expresult['tincanlaunchlrslogin'];
            $tincanlaunchlrs->watershedpass = $expresult['tincanlaunchlrspass'];
            $tincanlaunchlrs->lrsauthentication = '2';
            $tincanlaunchlrs->customacchp = $expresult['tincanlaunchcustomacchp'];
            $tincanlaunchlrs->useactoremail = $expresult['tincanlaunchuseactoremail'];
            $tincanlaunchlrs->lrsduration = $expresult['tincanlaunchlrsduration'];
            $tincanlaunchlrs->tincanlaunchid = $instance;

            // Populate xapi creds in result.
            $expresult['tincanlaunchlrslogin'] = $creds["key"];
            $expresult['tincanlaunchlrspass'] = $creds["secret"];

            // If record does not exist, will need to insert_record.
            if ($activitysettings == false) {
                if (!$DB->insert_record('tincanlaunch_lrs', $tincanlaunchlrs)) {
                    return false;
                }
            } else {// If it does exist, update it.
                $tincanlaunchlrs->id = $activitysettings->id;
                if (!$DB->update_record('tincanlaunch_lrs', $tincanlaunchlrs)) {
                    return false;
                }
            }
        } else { // Relevant instance settings match global settings; no need to create new creds.
            // Use global settings, plus instance specific xapi creds.
            $expresult['tincanlaunchlrslogin'] = $activitysettings->lrslogin;
            $expresult['tincanlaunchlrspass'] = $activitysettings->lrspass;
        }
    }

    $expresult['tincanlaunchlrsversion'] = '1.0.0';

    $tincanlaunchsettings = $expresult;
    return $expresult;
}

/**
 * Should the global LRS settings be used instead of the instance specific ones?
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @param string $instance The Moodle id for the Tin Can module instance.
 * @return bool
 */
function use_global_lrs_settings($instance) {
    global $DB;
    // Determine if there is a row in tincanlaunch_lrs matching the current activity id.
    $activitysettings = $DB->get_record('tincanlaunch', array('id' => $instance));
    if ($activitysettings->overridedefaults == 1) {
        return false;
    }
    return true;
}
