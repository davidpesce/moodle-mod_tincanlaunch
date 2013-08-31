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



/*
 * tincanlaunch_get_launch_url
 *
 * Returns a launch link based on various data from Moodle
 *
 * @param none
 * @return string - the launch link to be used. 
 */
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

function tincanlaunch_getactor()
{
	global $USER, $CFG;
	return array(
		"name" => fullname($USER),
		"account" => array(
			"homePage" => $CFG->wwwroot,
			"name" => $USER->id
		),
		"objectType" => "Agent"
	);
}
 
function tincanlaunch_get_launch_url($registrationuuid) {
	global $tincanlaunch;
	
	//calculate basic authentication 
	$basicauth = base64_encode($tincanlaunch->tincanlaunchlrslogin.":".$tincanlaunch->tincanlaunchlrspass);
	
	//build the URL to be returned
	$rtnString = $tincanlaunch->tincanlaunchurl."?".http_build_query(array(
	        "endpoint" => $tincanlaunch->tincanlaunchlrsendpoint,
	        "auth" => "Basic ".$basicauth,
	        "actor" => json_encode(tincanlaunch_getactor()),
	        "registration" => $registrationuuid,
	        "version" => $tincanlaunch->tincanlaunchlrsversion
	    ), 
	    '', 
	    '&'
	);
	
	//TODO: QUESTION: should we be using $USER->id, $USER->idnumber or even $USER->username ?
	
	return $rtnString;
}

function tincanlaunch_myJson_encode($str)
{
	return str_replace('\\/', '/',json_encode($str));
}

//I've split these two functions up so that tincanlaunch_save_state can be potentially re-used outside of Moodle.
function tincanlaunch_get_global_parameters_and_save_state($data, $key)
{
	global $tincanlaunch;
	return tincanlaunch_save_state($data, $tincanlaunch->tincanlaunchlrsendpoint, $tincanlaunch->tincanlaunchlrslogin, $tincanlaunch->tincanlaunchlrspass, $tincanlaunch->tincanlaunchlrsversion, $tincanlaunch->tincanactivityid, tincanlaunch_getactor(), $key);
}

//TODO: Put this function in a PHP Tin Can library. 
//TODO: Handle failure nicely. E.g. retry sending. 
//TODO: if this is going in a library, it needs to be able to handle registration too
function tincanlaunch_save_state($data, $url, $basicLogin, $basicPass, $version, $activityid, $agent, $key) {


	$streamopt = array(
		'ssl' => array(
			'verify-peer' => false, 
			), 
		'http' => array(
			'method' => 'POST', 
			'ignore_errors' => false, 
			'header' => array(
				'Authorization: Basic ' . base64_encode( $basicLogin . ':' . $basicPass), 
				'Content-Type: application/json', 
				'Accept: application/json, */*; q=0.01',
				'X-Experience-API-Version: '.$version
			), 
			'content' => tincanlaunch_myJson_encode($data), 
		), 
	);
	
	$streamparams = array(
		'activityId' => $activityid,
		'agent' => json_encode($agent),
		'stateId' => $key
	);

	
	$context = stream_context_create($streamopt);

	$stream = fopen($url . 'activities/state'.'?'.http_build_query($streamparams,'','&'), 'rb', false, $context);
	$ret = stream_get_contents($stream);
	$meta = stream_get_meta_data($stream);
	if ($ret) {
		$ret = json_decode($ret);
	}
	
	
	return array($ret, $meta);
}

//Query to code reviewer: should getting and setting the state be  a single function with a "method" parameter, or be two separate but very similar functions as I've done here? 

//I've split these two functions up so that tincanlaunch_save_state can be potentially re-used outside of Moodle.
function tincanlaunch_get_global_parameters_and_get_state($key)
{
	global $tincanlaunch;
	return tincanlaunch_get_state($tincanlaunch->tincanlaunchlrsendpoint, $tincanlaunch->tincanlaunchlrslogin, $tincanlaunch->tincanlaunchlrspass, $tincanlaunch->tincanlaunchlrsversion, $tincanlaunch->tincanactivityid, tincanlaunch_getactor(), $key);
}

//TODO: Put this function in a PHP Tin Can library. 
//TODO: Handle failure nicely. E.g. retry getting. 
//TODO: if this is going in a library, it needs to be able to handle registration too
function tincanlaunch_get_state($url, $basicLogin, $basicPass, $version, $activityid, $agent, $key) {

	$streamopt = array(
		'ssl' => array(
			'verify-peer' => false, 
			), 
		'http' => array(
			'method' => 'GET', 
			'ignore_errors' => false, 
			'header' => array(
				'Authorization: Basic ' . base64_encode( $basicLogin . ':' . $basicPass), 
				'Content-Type: application/json', 
				'Accept: application/json, */*; q=0.01',
				'X-Experience-API-Version: '.$version
			)
		), 
	);

	$streamparams = array(
		'activityId' => $activityid,
		'agent' => json_encode($agent),
		'stateId' => $key
	);
	
	$context = stream_context_create($streamopt);
	
	$stream = fopen($url . 'activities/state'.'?'.http_build_query($streamparams,'','&'), 'rb', false, $context);

	
	$ret = stream_get_contents($stream);
	$meta = stream_get_meta_data($stream);

	if ($ret) {
		$ret = json_decode($ret);
	}
	return array($ret, $meta);
}

