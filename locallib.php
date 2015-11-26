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
 * Internal library of functions for module tincanlaunch
 *
 * All the tincanlaunch specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package mod_tincanlaunch
 * @copyright  2013 Andrew Downes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->dirroot/mod/tincanlaunch/lib.php");

function tincan_launched_statement($registration_id){
    global $tincanlaunch, $course, $CFG;
    $tincanlaunchsettings = tincanlaunch_settings($tincanlaunch->id);
    
    $version = $tincanlaunchsettings['tincanlaunchlrsversion'];
    $url = $tincanlaunchsettings['tincanlaunchlrsendpoint'];
    $basicLogin = $tincanlaunchsettings['tincanlaunchlrslogin'];
    $basicPass = $tincanlaunchsettings['tincanlaunchlrspass'];

    $tinCanPHPUtil = new \TinCan\Util();
    $statementid = $tinCanPHPUtil->getUUID();

    $lrs = new \TinCan\RemoteLRS($url, $version, $basicLogin, $basicPass);

    $parentDefinition = array();
    if (isset($course->summary) && $course->summary !== "") {
        $parentDefinition["description"] = array(
            "en-US" => $course->summary
        );
    }

    if (isset($course->fullname) && $course->fullname !== "") {
        $parentDefinition["name"] = array(
            "en-US" => $course->fullname
        );
    }

    $statement = new \TinCan\statement(
        array( 
            'id' => $statementid,
            'actor' => tincanlaunch_getactor(), 
            'verb' => array(
                'id' => 'http://adlnet.gov/expapi/verbs/launched',
                'display' => array(
                    'en-US' => 'launched'
                )
            ),

            'object' => array(
                'id' =>  $tincanlaunch->tincanactivityid,
                'objectType' => "Activity"
            ),

            "context" => array(
                "registration" => $registration_id,
                "contextActivities" => array(
                    "parent"  => array(array(
                        "id" => $CFG->wwwroot.'/course/view.php?id='. $course->id,
                        "objectType" => "Activity",
                        "definition" => $parentDefinition
                    ))
                ),
                "language" => tincanlaunch_get_moodle_langauge()
            ),
            "timestamp" => date(DATE_ATOM)
        )
    );

    $response = $lrs->saveStatement($statement);
    return $response;
}


/*
 * tincanlaunch_get_launch_url
 *
 * Returns a launch link based on various data from Moodle
 *
 */
 
function tincanlaunch_get_launch_url($registrationuuid) {
    global $tincanlaunch;
    $tincanlaunchsettings = tincanlaunch_settings($tincanlaunch->id);
    $current_time = new DateTime('NOW');
    $tincan_duration = $tincanlaunchsettings['tincanlaunchlrsduration'];
    $current_time->add(new DateInterval('PT'.$tincan_duration.'M'));

    $url = trim($tincanlaunchsettings['tincanlaunchlrsendpoint']);

    //Call the function to get the credentials from the LRS
    $basicLogin = trim($tincanlaunchsettings['tincanlaunchlrslogin']);
    $basicPass = trim($tincanlaunchsettings['tincanlaunchlrspass']);


    if ($tincanlaunchsettings['tincanlaunchlrsauthentication'] != "0") { //LRS integrated basic authentication is 0
        $basicauth = base64_encode($basicLogin.":".$basicPass);
    }
    else {
        $creds = tincanlaunch_get_creds($tincanlaunchsettings['tincanlaunchlrslogin'],$tincanlaunchsettings['tincanlaunchlrspass'], $data, $url);
        $basicauth = base64_encode($creds["contents"]["key"].":".$creds["contents"]["secret"]);
    }

    //build the URL to be returned
    $rtnString = $tincanlaunch->tincanlaunchurl."?".http_build_query(array(
            "endpoint" => $url,
            "auth" => "Basic ".$basicauth,
            "actor" => json_encode(tincanlaunch_getactor()),
            "registration" => $registrationuuid
        ), 
        '', 
        '&'
    );
    
    return $rtnString;
}

/*
 * tincanlaunch_get_creds
 *
 * Used with LRS integrated basic authentication to fetch credentials from the LRS. 
 *
 */
function tincanlaunch_get_creds($basicLogin, $basicPass, $url) {

    $actor = tincanlaunch_getactor();
    $data = array(
        'scope' => array ('all'),
        'expiry' => $current_time->format(DATE_ATOM),
        'historical' => FALSE,
        'actors' => array(
            "objectType"=> 'Person',
            "name"=> array($actor->getName())
        ),
        'auth' => $actor,
        'activity' => array(
            $tincanlaunch->tincanactivityid,
        ),
        'registration' => $registrationuuid
    );
    
    if (null !== $actor->getMbox()) {
        $data['actors']['mbox'] = array($actor->getMbox());
    } 
    elseif (null !== $actor->getMbox_sha1sum()) {
        $data['actors']['mbox_sha1sum'] = array($actor->getMbox_sha1sum());
    } 
    elseif (null !== $actor->getOpenid()) {
        $data['actors']['openid'] = array($actor->getOpenid());
    } 
    elseif (null !== $actor->getAccount()) {
        $data['actors']['account'] = array($actor->getAccount());
    }

    $streamopt = array(
        'ssl' => array(
            'verify-peer' => false, 
            ), 
        'http' => array(
            'method' => 'POST', 
            'ignore_errors' => false, 
            'header' => array(
                'Authorization: Basic ' . base64_encode( trim($basicLogin) . ':' .trim($basicPass)), 
                'Content-Type: application/json', 
                'Accept: application/json, */*; q=0.01',
            ), 
            'content' => tincanlaunch_myJson_encode($data),
        ), 
    );

    $streamparams = array();

    $context = stream_context_create($streamopt);

    $stream = fopen(trim($url) . 'Basic/request'.'?'.http_build_query($streamparams,'','&'), 'rb', false, $context);

    $return_code = @explode(' ', $http_response_header[0]);
    $return_code = (int)$return_code[1];

    switch($return_code){
        case 200:
            $ret = stream_get_contents($stream);
            $meta = stream_get_meta_data($stream);

            if ($ret) {
                $ret = json_decode($ret, TRUE);
            }
            break;
            default: //error
            $ret = NULL;
            $meta = $return_code;
            break;
    }


    return array(
        'contents'=> $ret, 
        'metadata'=> $meta
    );
}

function tincanlaunch_myJson_encode($str){
    return str_replace('\\/', '/',json_encode($str));
}

function tincanlaunch_get_global_parameters_and_save_state($data, $key, $etag){
    global $tincanlaunch;
    $tincanlaunchsettings = tincanlaunch_settings($tincanlaunch->id);
    $lrs = new \TinCan\RemoteLRS(
        $tincanlaunchsettings['tincanlaunchlrsendpoint'], 
        $tincanlaunchsettings['tincanlaunchlrsversion'], 
        $tincanlaunchsettings['tincanlaunchlrslogin'], 
        $tincanlaunchsettings['tincanlaunchlrspass']
    );

    return $lrs->saveState(
        new \TinCan\Activity(array("id"=> trim($tincanlaunch->tincanactivityid))), 
        tincanlaunch_getactor(), 
        $key, 
        tincanlaunch_myJson_encode($data), 
        array(
            'etag' => $etag,
            'contentType' => 'application/json'
        )
    );
}

function tincanlaunch_get_global_parameters_and_save_agentprofile($data, $key){
    global $tincanlaunch;
    $tincanlaunchsettings = tincanlaunch_settings($tincanlaunch->id);

    $lrs = new \TinCan\RemoteLRS(
        $tincanlaunchsettings['tincanlaunchlrsendpoint'], 
        $tincanlaunchsettings['tincanlaunchlrsversion'], 
        $tincanlaunchsettings['tincanlaunchlrslogin'], 
        $tincanlaunchsettings['tincanlaunchlrspass']
    );

    $getResponse = $lrs->retrieveAgentProfile(tincanlaunch_getactor(), $key);

    $Opts = array(
        'contentType' => 'application/json'
    );
    if ($getResponse->success)
    {
        $Opts['etag'] = $getResponse->content->getEtag();
    }

    return $lrs->saveAgentProfile(
        tincanlaunch_getactor(), 
        $key, 
        tincanlaunch_myJson_encode($data), 
        $Opts
    );
}

function tincanlaunch_get_global_parameters_and_get_state($key){
    global $tincanlaunch;
    $tincanlaunchsettings = tincanlaunch_settings($tincanlaunch->id);

    $lrs = new \TinCan\RemoteLRS(
        $tincanlaunchsettings['tincanlaunchlrsendpoint'], 
        $tincanlaunchsettings['tincanlaunchlrsversion'], 
        $tincanlaunchsettings['tincanlaunchlrslogin'], 
        $tincanlaunchsettings['tincanlaunchlrspass']
    );

    return $lrs->retrieveState(
        new \TinCan\Activity(array("id"=> trim($tincanlaunch->tincanactivityid))), 
        tincanlaunch_getactor(), 
        $key
    );
}

function tincanlaunch_get_moodle_langauge(){
    $lang = current_language();
    $langArr = explode('_',$lang);
    if (count($langArr) == 2){
        return $langArr[0].'-'.strtoupper($langArr[1]);
    }
    else {
        return $lang;
    }
}
