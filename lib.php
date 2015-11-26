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
require_once("$CFG->dirroot/mod/tincanlaunch/TinCanPHP/autoload.php");

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

     //process uploaded file
    if (!empty($tincanlaunch->packagefile)) {
        // Reload TinCan instance.
        $record = $DB->get_record('tincanlaunch', array('id' => $tincanlaunch->id));

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_tincanlaunch', 'package');
        file_save_draft_area_files($tincanlaunch->packagefile, $context->id, 'mod_tincanlaunch', 'package',
            0, array('subdirs' => 0, 'maxfiles' => 1));
        // Get filename of zip that was uploaded.
        $files = $fs->get_area_files($context->id, 'mod_tincanlaunch', 'package', 0, '', false);
        $file = reset($files);
        $filename = $file->get_filename();
        if ($filename !== false) {
            $record->tincanlaunchurl = $filename;
        }

        // Save reference.
        $DB->update_record('scorm', $record);
    }

    tincanlaunch_package_parse($tincanlaunch);

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

    $cmid = $tincanlaunch->coursemodule;
    $context = context_module::instance($cmid);

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

    //process uploaded file
    if (!empty($tincanlaunch->packagefile)) {
        // Reload TinCan instance.
        $record = $DB->get_record('tincanlaunch', array('id' => $tincanlaunch->id));

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_tincanlaunch', 'package');
        file_save_draft_area_files($tincanlaunch->packagefile, $context->id, 'mod_tincanlaunch', 'package',
            0, array('subdirs' => 0, 'maxfiles' => 1));
        // Get filename of zip that was uploaded.
        $files = $fs->get_area_files($context->id, 'mod_tincanlaunch', 'package', 0, '', false);
        $zipFile = reset($files);
        $zipFilename = $zipFile->get_filename();

        $packagefile = false;

        if ($packagefile = $fs->get_file($context->id, 'mod_tincanlaunch', 'package', 0, '/', $zipFilename)) {
            if ($packagefile->is_external_file()) { // Get zip file so we can check it is correct.
                $packagefile->import_external_file_contents();
            }
            $newhash = $packagefile->get_contenthash();
        } else {
            $newhash = null;
        }

        $fs->delete_area_files($context->id, 'mod_tincanlaunch', 'content');

        $packer = get_file_packer('application/zip');
        $packagefile->extract_to_storage($packer, $context->id, 'mod_tincanlaunch', 'content', 0, '/');

        $manifestFile = $fs->get_file($context->id, 'mod_tincanlaunch', 'content', 0, '/', 'tincan.xml');

        $xmltext = $manifestFile->get_content();

        $defaultorgid = 0;
        $firstinorg = 0;

        $pattern = '/&(?!\w{2,6};)/';
        $replacement = '&amp;';
        $xmltext = preg_replace($pattern, $replacement, $xmltext);

        $objxml = new xml2Array();
        $manifest = $objxml->parse($xmltext);

        //Update activity id from XML file. 
        $record->tincanactivityid = $manifest[0]["children"][0]["children"][0]["attrs"]["ID"];

        foreach ($manifest[0]["children"][0]["children"][0]["children"] as $property) {
            if ($property["name"] === "LAUNCH"){
                $record->tincanlaunchurl = $CFG->wwwroot."/pluginfile.php/".$context->id."/mod_tincanlaunch/".$manifestFile->get_filearea()."/".$property["tagData"];
            }
        }

        // Save reference.
        $DB->update_record('tincanlaunch', $record);

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

// Called by Moodle core
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
        if (!empty($statementquery->content) && $statementquery->success){
            $result = TRUE;
        }else{
            $result = FALSE;
        }
    }

    return $result;
}

/**
 * Serves scorm content, introduction images and packages. Implements needed access control ;-)
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
function tincanlaunch_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);
    $canmanageactivity = has_capability('moodle/course:manageactivities', $context);

    if ($filearea === 'content') {
        //$relativepath = implode('/', $args);
       //$fullpath = "/$context->id/tincanlaunch/content/0/$relativepath";
        $filename = array_pop($args);
        $filepath = implode('/', $args);
        $lifetime = null;
    }
    else if ($filearea === 'package') {
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/tincanlaunch/package/0/$relativepath";
        $lifetime = 0; // No caching here.

    } 
    else {
        return false;
    }

    $fs = get_file_storage();

    if (!$file = $fs->get_file($context->id, 'mod_tincanlaunch', 'content', 0, '/'.$filepath.'/', $filename) or $file->is_directory()) {
        if ($filearea === 'content') { // Return file not found straight away to improve performance.
            send_header_404();
            die;
        }
        return false;
    }

    // Finally send the file.
    send_stored_file($file, $lifetime, 0, false, $options);
}


//The functions below should really be in locallib, however they are required for the completion check so need to be here. 
//It looks like the standard Quiz module does that same thing, so I don't feel so bad. 

function tincanlaunch_get_statements($url, $basicLogin, $basicPass, $version, $activityid, $agent, $verb) {


    $lrs = new \TinCan\RemoteLRS($url, $version, $basicLogin, $basicPass);

    $statementsQuery = array(
        "agent" => $agent,
        "verb" => new \TinCan\Verb(array("id"=> trim($verb))),
        "activity" => new \TinCan\Activity(array("id"=> trim($activityid))),
        "related_activities" => "false",
        //"limit" => 1, //Use this to test the "more" statements feature
        "format"=>"ids"
    );

    //Get all the statements from the LRS
    $statementsResponse = $lrs->queryStatements($statementsQuery);


    if($statementsResponse->success == false){
        return $statementsResponse;
    }

    $allTheStatements = $statementsResponse->content->getStatements();
    $moreStatementsURL = $statementsResponse->content->getMore();
    while (!is_null($moreStatementsURL)) {
        $moreStmtsResponse = $lrs->moreStatements($moreStatementsURL);
        if($moreStmtsResponse->success == false){
            return $moreStmtsResponse;
        }
        $moreStatements = $moreStmtsResponse->content->getStatements();
        $moreStatementsURL = $moreStmtsResponse->content->getMore();
        //Note: due to the structure of the arrays, array_merge does not work as expected.
        foreach ($moreStatements as $moreStatement) {
            array_push($allTheStatements, $moreStatement);
        }
    }

    return new \TinCan\LRSResponse (
        $statementsResponse->success,
        $allTheStatements,
        $statementsResponse->httpResponse
    );
}

function tincanlaunch_getactor(){
    global $USER, $CFG; 
    if ($USER->email){
        $agent = array(
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

/**
 * Check that a Zip file contains a valid TinCan package
 *
 * @param $file stored_file a Zip file.
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


/* Usage
 Grab some XML data, either from a file, URL, etc. however you want. Assume storage in $strYourXML;

 $objXML = new xml2Array();
 $arroutput = $objXML->parse($strYourXML);
 print_r($arroutput); //print it out, or do whatever!

*/
class xml2Array {

    public $arroutput = array();
    public $resparser;
    public $strxmldata;

    /**
     * Convert a utf-8 string to html entities
     *
     * @param string $str The UTF-8 string
     * @return string
     */
    public function utf8_to_entities($str) {
        global $CFG;

        $entities = '';
        $values = array();
        $lookingfor = 1;

        return $str;
    }

    /**
     * Parse an XML text string and create an array tree that rapresent the XML structure
     *
     * @param string $strinputxml The XML string
     * @return array
     */
    public function parse($strinputxml) {
        $this->resparser = xml_parser_create ('UTF-8');
        xml_set_object($this->resparser, $this);
        xml_set_element_handler($this->resparser, "tagopen", "tagclosed");

        xml_set_character_data_handler($this->resparser, "tagdata");

        $this->strxmldata = xml_parse($this->resparser, $strinputxml );
        if (!$this->strxmldata) {
            die(sprintf("XML error: %s at line %d",
            xml_error_string(xml_get_error_code($this->resparser)),
            xml_get_current_line_number($this->resparser)));
        }

        xml_parser_free($this->resparser);

        return $this->arroutput;
    }

    public function tagopen($parser, $name, $attrs) {
        $tag = array("name" => $name, "attrs" => $attrs);
        array_push($this->arroutput, $tag);
    }

    public function tagdata($parser, $tagdata) {
        if (trim($tagdata)) {
            if (isset($this->arroutput[count($this->arroutput) - 1]['tagData'])) {
                $this->arroutput[count($this->arroutput) - 1]['tagData'] .= $this->utf8_to_entities($tagdata);
            } else {
                $this->arroutput[count($this->arroutput) - 1]['tagData'] = $this->utf8_to_entities($tagdata);
            }
        }
    }

    public function tagclosed($parser, $name) {
        $this->arroutput[count($this->arroutput) - 2]['children'][] = $this->arroutput[count($this->arroutput) - 1];
        array_pop($this->arroutput);
    }

}
