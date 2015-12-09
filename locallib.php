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

/**
 * Send a statement that the activity was launched.
 * This is useful for debugging - if the 'launched' statement is present in the LRS, you know the activity was at least launched.
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @param string/UUID $registration_id The Tin Can Registration UUID associated with the launch.
 * @return TinCan LRS Response
 */
function tincan_launched_statement($registration_id)
{
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
            'actor' => tincanlaunch_getactor($tincanlaunch->id),
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
                    "parent"  => array(
                        array(
                            "id" => $CFG->wwwroot.'/course/view.php?id='. $course->id,
                            "objectType" => "Activity",
                            "definition" => $parentDefinition
                        )
                    ),
                    "grouping"  => array(
                        array(
                            "id" => $CFG->wwwroot,
                            "objectType" => "Activity"
                        )
                    ),
                    "category"  => array(
                        array(
                            "id" => "https://moodle.org",
                            "objectType" => "Activity",
                            "definition" => array (
                                "type" => "http://id.tincanapi.com/activitytype/source"
                            )
                        )
                    )
                ),
                "language" => tincanlaunch_get_moodle_langauge()
            ),
            "timestamp" => date(DATE_ATOM)
        )
    );

    $response = $lrs->saveStatement($statement);
    return $response;
}

/**
 * Builds a Tin Can launch link for the current module and a given registration
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @param string/UUID $registration_id The Tin Can Registration UUID associated with the launch.
 * @return string launch link including querystring.
 */
function tincanlaunch_get_launch_url($registrationuuid)
{
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
    } else {
        $creds = tincanlaunch_get_creds($tincanlaunchsettings['tincanlaunchlrslogin'], $tincanlaunchsettings['tincanlaunchlrspass'], $data, $url);
        $basicauth = base64_encode($creds["contents"]["key"].":".$creds["contents"]["secret"]);
    }

    //build the URL to be returned
    $rtnString = $tincanlaunch->tincanlaunchurl."?".http_build_query(
        array(
            "endpoint" => $url,
            "auth" => "Basic ".$basicauth,
            "actor" => tincanlaunch_myJson_encode(
                tincanlaunch_getactor($tincanlaunch->id)->asVersion(
                    $tincanlaunchsettings['tincanlaunchlrsversion']
                )
            ),
            "registration" => $registrationuuid
        ),
        '',
        '&',
        PHP_QUERY_RFC3986
    );
    
    return $rtnString;
}

/**
 * Used with LRS integrated basic authentication to fetch credentials from the LRS.
 * This process is not part of the xAPI specification or the Tin Can launch spec.
 * It is not supported by all Learning Record Stores.
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @param string $basicLogin login/key for the LRS
 * @param string $basicPass pass/secret for the LRS
 * @param string $url LRS endpoint URL
 * @return array the response of the LRS (Note: not a TinCan LRS Response object)
 */
function tincanlaunch_get_creds($basicLogin, $basicPass, $url)
{
    global $tincanlaunch;
    $actor = tincanlaunch_getactor($tincanlaunch->id);
    $data = array(
        'scope' => array ('all'),
        'expiry' => $current_time->format(DATE_ATOM),
        'historical' => false,
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
    } elseif (null !== $actor->getMbox_sha1sum()) {
        $data['actors']['mbox_sha1sum'] = array($actor->getMbox_sha1sum());
    } elseif (null !== $actor->getOpenid()) {
        $data['actors']['openid'] = array($actor->getOpenid());
    } elseif (null !== $actor->getAccount()) {
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
                'Authorization: Basic ' . base64_encode(trim($basicLogin) . ':' .trim($basicPass)),
                'Content-Type: application/json',
                'Accept: application/json, */*; q=0.01',
            ),
            'content' => tincanlaunch_myJson_encode($data),
        ),
    );

    $streamparams = array();

    $context = stream_context_create($streamopt);

    $stream = fopen(trim($url) . 'Basic/request'.'?'.http_build_query($streamparams, '', '&'), 'rb', false, $context);

    $return_code = @explode(' ', $http_response_header[0]);
    $return_code = (int)$return_code[1];

    switch($return_code){
        case 200:
            $ret = stream_get_contents($stream);
            $meta = stream_get_meta_data($stream);

            if ($ret) {
                $ret = json_decode($ret, true);
            }
            break;
        default: //error
            $ret = null;
            $meta = $return_code;
            break;
    }


    return array(
        'contents'=> $ret,
        'metadata'=> $meta
    );
}

/**
 * By default, PHP escapes slashes when encoding into JSON. This cause problems for Tin Can, so this fucntion unescapes the slashes after encoding.
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @param object or array $obj object or array encode to JSON
 * @return string/JSON JSON encoded object or array
 */
function tincanlaunch_myJson_encode($obj)
{
    return str_replace('\\/', '/', json_encode($obj));
}

/**
 * Save data to the state. Note: registration is not used as this is a general bucket of data against the activity/learner.
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @param string $data data to store as document
 * @param string $key id to store the document against
 * @param string $etag etag associated with the document last time it was fetched (may be Null if document is new)
 * @return TinCan LRS Response
 */
function tincanlaunch_get_global_parameters_and_save_state($data, $key, $etag)
{
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
        tincanlaunch_getactor($tincanlaunch->id),
        $key,
        tincanlaunch_myJson_encode($data),
        array(
            'etag' => $etag,
            'contentType' => 'application/json'
        )
    );
}

/**
 * Save data to the agent profile.
 * Note: registration is not used as this is a general bucket of data against the activity/learner.
 * Note: fetches a new etag before storing. Will ALWAYS overwrite existing contents of the document.
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @param string $data data to store as document
 * @param string $key id to store the document against
 * @return TinCan LRS Response
 */
function tincanlaunch_get_global_parameters_and_save_agentprofile($data, $key)
{
    global $tincanlaunch;
    $tincanlaunchsettings = tincanlaunch_settings($tincanlaunch->id);

    $lrs = new \TinCan\RemoteLRS(
        $tincanlaunchsettings['tincanlaunchlrsendpoint'],
        $tincanlaunchsettings['tincanlaunchlrsversion'],
        $tincanlaunchsettings['tincanlaunchlrslogin'],
        $tincanlaunchsettings['tincanlaunchlrspass']
    );

    $getResponse = $lrs->retrieveAgentProfile(tincanlaunch_getactor($tincanlaunch->id), $key);

    $Opts = array(
        'contentType' => 'application/json'
    );
    if ($getResponse->success) {
        $Opts['etag'] = $getResponse->content->getEtag();
    }

    return $lrs->saveAgentProfile(
        tincanlaunch_getactor($tincanlaunch->id),
        $key,
        tincanlaunch_myJson_encode($data),
        $Opts
    );
}

/**
 * Get data from the state. Note: registration is not used as this is a general bucket of data against the activity/learner.
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @param string $key id to store the document against
 * @return TinCan LRS Response containing the response code and data or error message
 */
function tincanlaunch_get_global_parameters_and_get_state($key)
{
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
        tincanlaunch_getactor($tincanlaunch->id),
        $key
    );
}


/**
 * Get the current lanaguage of the current user and return it as an RFC 5646 language tag
 *
 * @package  mod_tincanlaunch
 * @category tincan
 * @return string RFC 5646 language tag
 */

function tincanlaunch_get_moodle_langauge()
{
    $lang = current_language();
    $langArr = explode('_', $lang);
    if (count($langArr) == 2) {
        return $langArr[0].'-'.strtoupper($langArr[1]);
    } else {
        return $lang;
    }
}
