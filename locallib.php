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

/*
 * tincanlaunch_get_launch_url
 *
 * Returns a launch link based on various data from Moodle
 *
 * @param none
 * @return string - the launch link to be used. 
 */

function tincanlaunch_get_creds($basicLogin,$basicPass, $data, $url) {


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
			// 'content' => myJson_encode($data),
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

function tincan_launched_statement($registration_id){
	global $tincanlaunch, $course, $CFG;
	$tincanlaunchsettings = tincanlaunch_settings($tincanlaunch->id);
	
	$version = $tincanlaunchsettings['tincanlaunchlrsversion'];
	$url = $tincanlaunchsettings['tincanlaunchlrsendpoint'];
	$basicLogin = $tincanlaunchsettings['tincanlaunchlrslogin'];
	$basicPass = $tincanlaunchsettings['tincanlaunchlrspass'];

	$statementid = tincanlaunch_gen_uuid(); 
	
	$statement = array( 
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
					"definition" => array(
						"name" => array(
							"en-US" => $course->fullname
						),
						"description" => array(
							"en-US" => $course->summary
						),
						"type" => "http://adlnet.gov/expapi/activities/course"
					)
				))
			),
			"language" => tincanlaunch_get_moodle_langauge()
		),
		"timestamp" => date(DATE_ATOM)
	);

	try {
		$response = tincanlaunch_save_statement($statement, $url, $basicLogin, $basicPass, $version, $statementid);
		return $response;
	}
	catch (Exception $e) {
		//TODO: handle error
	}
}

function tincanlaunch_gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}
 
function tincanlaunch_get_launch_url($registrationuuid) {
	global $tincanlaunch;
	$tincanlaunchsettings = tincanlaunch_settings($tincanlaunch->id);
	$current_time = new DateTime('NOW');
	$tincan_duration = $tincanlaunchsettings['tincanlaunchlrsduration'];
	$current_time->add(new DateInterval('PT'.$tincan_duration.'M'));

	$actorDetails = tincanlaunch_getactor();
	$url = $tincanlaunchsettings['tincanlaunchlrsendpoint'];
	$data = array(
		'scope' => array ('all'),
		'expiry' => $current_time->format(DATE_ATOM),
		'historical' => FALSE,
		'actors' => array(
			"objectType"=> 'Person',
       	 	"name"=> array($actorDetails['name'])
		),
		'auth' => $actorDetails,
		'activity' => array(
			$tincanlaunch->tincanactivityid,
		),
		'registration' => $registrationuuid
	);
	
	if (isset($actorDetails['mbox'])) {
		$data['actors']['mbox'] = array($actorDetails['mbox']);
	} elseif (isset($actorDetails['mbox_sha1sum'])) {
		$data['actors']['mbox_sha1sum'] = array($actorDetails['mbox_sha1sum']);
	} elseif (isset($actorDetails['openid'])) {
		$data['actors']['openid'] = array($actorDetails['openid']);
	} elseif (isset($actorDetails['account'])) {
		$data['actors']['account'] = array($actorDetails['account']);
	}

	//Call the function to get the credentials from the LRS
	$basicLogin = trim($tincanlaunchsettings['tincanlaunchlrslogin']);
	$basicPass = trim($tincanlaunchsettings['tincanlaunchlrspass']);
	if ($tincanlaunchsettings['tincanlaunchlrsauthentication'] != "0") { //LRS integrated basic authentication is 0
		$basicauth = base64_encode($basicLogin.":".$basicPass);
	}else{
		$creds = tincanlaunch_get_creds($tincanlaunchsettings['tincanlaunchlrslogin'],$tincanlaunchsettings['tincanlaunchlrspass'], $data, $url);
		$basicauth = base64_encode($creds["contents"]["key"].":".$creds["contents"]["secret"]);
	}

	//build the URL to be returned
	//Note: when Moodle moves to PHP 5.4 as a minimum this can be done more smoothly using http_build_query.
	//See 'PHP_QUERY_RFC3986' at http://php.net/manual/en/function.http-build-query.php
	$rtnString = $tincanlaunch->tincanlaunchurl."?".tincanlaunch_http_build_query(array(
	        "endpoint" => trim($tincanlaunchsettings['tincanlaunchlrsendpoint']),
	        "auth" => "Basic ".$basicauth,
	        "actor" => json_encode(tincanlaunch_getactor()),
	        "registration" => $registrationuuid
	    ), 
	    '', 
	    '&'
	);
	
	return $rtnString;
}

function tincanlaunch_myJson_encode($str){
	return str_replace('\\/', '/',json_encode($str));
}

//Note: $numeric_prefix is ignored but kept so that this function has the same number of paramters as http_build_query
function tincanlaunch_http_build_query($query_data, $numeric_prefix, $arg_separator){
	$rtnArray = array();
	foreach ($query_data as $key => $value) {
		$encodedValue = rawurlencode($value);
		array_push($rtnArray, "{$key}={$encodedValue}");
	}
	return implode("&", $rtnArray);
}

//TODO: use TinCanPHP for PHP 5.4
function tincanlaunch_get_global_parameters_and_save_state($data, $key, $etag){
	global $tincanlaunch;
	$tincanlaunchsettings = tincanlaunch_settings($tincanlaunch->id);
	return tincanlaunch_save_state($data, $tincanlaunchsettings['tincanlaunchlrsendpoint'], $tincanlaunchsettings['tincanlaunchlrslogin'], $tincanlaunchsettings['tincanlaunchlrspass'], $tincanlaunchsettings['tincanlaunchlrsversion'], $tincanlaunch->tincanactivityid, tincanlaunch_getactor(), $key, $etag);
}


//TODO: use TinCanPHP for PHP 5.4
function tincanlaunch_save_state($data, $url, $basicLogin, $basicPass, $version, $activityid, $agent, $key, $etag) {
$return_code = "";

	if ($etag == "")
	{
		$EtagHeader = "If-None-Match : *";
	}
	else
	{	
		$EtagHeader = "If-Match : ".$etag;
	}

	$streamopt = array(
		'ssl' => array(
			'verify-peer' => false, 
			), 
		'http' => array(
			'method' => 'PUT', 
			'ignore_errors' => false, 
			'header' => array(
				'Authorization: Basic ' . base64_encode( trim($basicLogin) . ':' .trim($basicPass)), 
				'Content-Type: application/json', 
				'Accept: application/json, */*; q=0.01',
				'X-Experience-API-Version: '.$version,
				$EtagHeader
			), 
			'content' => tincanlaunch_myJson_encode($data), 
		), 
	);
	
	$streamparams = array(
		'activityId' => trim($activityid),
		'agent' => json_encode($agent),
		'stateId' => $key
	);

	
	$context = stream_context_create($streamopt);
	
	$stream = fopen(trim($url) . 'activities/state'.'?'.http_build_query($streamparams,'','&'), 'rb', false, $context);
	
	$return_code =  $http_response_header;
    //$return_code = (int)$return_code[1];

	$ret = stream_get_contents($stream);
	$meta = stream_get_meta_data($stream);

	if ($ret) {
		$ret = json_decode($ret, TRUE);
	}
	
	return array(
		'contents'=> $ret, 
		'metadata'=> $meta,
		'code'=>$return_code,
		'EtagHeader'=>$EtagHeader
	);
}

function tincanlaunch_save_statement($data, $url, $basicLogin, $basicPass, $version, $statementid) {
$return_code = "";

	$streamopt = array(
		'ssl' => array(
			'verify-peer' => false, 
			), 
		'http' => array(
			'method' => 'PUT', 
			'ignore_errors' => false, 
			'header' => array(
				'Authorization: Basic ' . base64_encode( trim($basicLogin) . ':' .trim($basicPass)), 
				'Content-Type: application/json', 
				'Accept: application/json, */*; q=0.01',
				'X-Experience-API-Version: '.$version
			), 
			'content' => tincanlaunch_myJson_encode($data), 
		), 
	);
	
	$streamparams = array(
		'statementId' => $statementid,
	);

	
	$context = stream_context_create($streamopt);
	
	$stream = fopen(trim($url) . 'statements'.'?'.http_build_query($streamparams,'','&'), 'rb', false, $context);
	

	$ret = stream_get_contents($stream);
	$meta = stream_get_meta_data($stream);

	if ($ret) {
		$ret = json_decode($ret, TRUE);
	}
	
	return array(
		'contents'=> $ret, 
		'metadata'=> $meta
	);
}

function tincanlaunch_get_global_parameters_and_save_agentprofile($data, $key){
	global $tincanlaunch;
	$tincanlaunchsettings = tincanlaunch_settings($tincanlaunch->id);
	
	$GetRequestReturnObj = tincanlaunch_get_agentprofile($tincanlaunchsettings['tincanlaunchlrsendpoint'], $tincanlaunchsettings['tincanlaunchlrslogin'], $tincanlaunchsettings['tincanlaunchlrspass'], $tincanlaunchsettings['tincanlaunchlrsversion'], tincanlaunch_getactor(), $key);
	
	$EtagHeader = "";
	if (strlen(tincanlaunch_extract_etag($GetRequestReturnObj["metadata"]["wrapper_data"]))<1)
	{
		$EtagHeader = "If-None-Match : *";
	}
	else
	{	
		$EtagHeader = "If-Match : ".tincanlaunch_extract_etag($GetRequestReturnObj["metadata"]["wrapper_data"]);
	}
	
	return tincanlaunch_save_agentprofile($data, $tincanlaunchsettings['tincanlaunchlrsendpoint'], $tincanlaunchsettings['tincanlaunchlrslogin'], $tincanlaunchsettings['tincanlaunchlrspass'], $tincanlaunchsettings['tincanlaunchlrsversion'], tincanlaunch_getactor(), $key, $EtagHeader);
}


function tincanlaunch_save_agentprofile($data, $url, $basicLogin, $basicPass, $version,  $agent, $key,$EtagHeader) {
$return_code = "";

	$streamopt = array(
		'ssl' => array(
			'verify-peer' => false, 
			), 
		'http' => array(
			'method' => 'PUT', 
			'ignore_errors' => false, 
			'header' => array(
				'Authorization: Basic ' . base64_encode( trim($basicLogin) . ':' .trim($basicPass)), 
				'Content-Type: application/json', 
				'Accept: application/json, */*; q=0.01',
				'X-Experience-API-Version: '.$version,
				$EtagHeader
			), 
			'content' => tincanlaunch_myJson_encode($data), 
		), 
	);

	$streamparams = array(
		'agent' => json_encode($agent),
		'profileId' => $key
	);

	
	$context = stream_context_create($streamopt);
	
	$stream = fopen(trim($url) . 'agents/profile'.'?'.http_build_query($streamparams,'','&'), 'rb', false, $context);
	
	
	$ret = stream_get_contents($stream);
	$meta = stream_get_meta_data($stream);

	if ($ret) {
		$ret = json_decode($ret, TRUE);
	}
            
	
	return array(
		'contents'=> $ret, 
		'metadata'=> $meta
	);
}


function tincanlaunch_get_agentprofile($url, $basicLogin, $basicPass, $version,  $agent, $key) {
$return_code = "";

	$streamopt = array(
		'ssl' => array(
			'verify-peer' => false, 
			), 
		'http' => array(
			'method' => 'GET', 
			'ignore_errors' => false, 
			'header' => array(
				'Authorization: Basic ' . base64_encode( trim($basicLogin) . ':' .trim($basicPass)), 
				'Content-Type: application/json', 
				'Accept: application/json, */*; q=0.01',
				'X-Experience-API-Version: '.$version
			)
		), 
	);
	
	$streamparams = array(
		'agent' => json_encode($agent),
		'profileId' => $key
	);

	
	$context = stream_context_create($streamopt);
	
	$stream = fopen(trim($url) . 'agents/profile'.'?'.http_build_query($streamparams,'','&'), 'rb', false, $context);

	$ret = stream_get_contents($stream);
	$meta = stream_get_meta_data($stream);

	if ($ret) {
		$ret = json_decode($ret, TRUE);
	}
            
	
	return array(
		'contents'=> $ret, 
		'metadata'=> $meta
	);
}

function tincanlaunch_get_global_parameters_and_get_state($key){
	global $tincanlaunch;
	$tincanlaunchsettings = tincanlaunch_settings($tincanlaunch->id);
	return tincanlaunch_get_state($tincanlaunchsettings['tincanlaunchlrsendpoint'], $tincanlaunchsettings['tincanlaunchlrslogin'], $tincanlaunchsettings['tincanlaunchlrspass'],$tincanlaunchsettings['tincanlaunchlrsversion'], $tincanlaunch->tincanactivityid, tincanlaunch_getactor(), $key);
}

function tincanlaunch_get_state($url, $basicLogin, $basicPass, $version, $activityid, $agent, $key) {

	$streamopt = array(
		'ssl' => array(
			'verify-peer' => false, 
			), 
		'http' => array(
			'method' => 'GET', 
			'ignore_errors' => false, 
			'header' => array(
				'Authorization: Basic ' . base64_encode( trim($basicLogin) . ':' .trim($basicPass)), 
				'Content-Type: application/json', 
				'Accept: application/json, */*; q=0.01',
				'X-Experience-API-Version: '.$version
			)
		), 
	);

	$streamparams = array(
		'activityId' => trim($activityid),
		'agent' => json_encode($agent),
		'stateId' => $key
	);
	
	$context = stream_context_create($streamopt);
	
	$stream = fopen(trim($url) . 'activities/state'.'?'.http_build_query($streamparams,'','&'), 'rb', false, $context);
	
	//Handle possible error codes
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

function tincanlaunch_get_lrsresponse($lrsrespond){
	if (is_array($lrsrespond)) {
		$lrsrespond = explode(" ",$lrsrespond['wrapper_data'][0]);
	}
	return $lrsrespond;
}

function tincanlaunch_extract_etag($wrapperdata){
	$etag ='';
	foreach ($wrapperdata as $rtnHeader) {
		if (strpos($rtnHeader, 'ETag') === 0){
			$etag =substr($rtnHeader, 6);
			return $etag;
		}
	}
}

