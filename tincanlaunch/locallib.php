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

 
function tincanlaunch_get_launch_url() {
	global $tincanlaunch, $USER, $CFG;
	
	//calculate basic authentication 
	$basicauth = base64_encode($tincanlaunch->tincanlaunchlrslogin.":".$tincanlaunch->tincanlaunchlrspass);
	
	//build the actor object
	$launchActor = array(
		"name" => fullname($USER),
		"account" => array(
			"homePage" => $CFG->wwwroot,
			"name" => $USER->id
		),
		"objectType" => "Agent"
	);
	
	//build the URL to be returned
	$rtnString = $tincanlaunch->tincanlaunchurl."?".http_build_query(array(
	        "endpoint" => $tincanlaunch->tincanlaunchlrsendpoint,
	        "auth" => "Basic ".$basicauth,
	        "actor" => json_encode($launchActor)
	    ), 
	    '', 
	    '&'
	);
	
	//TODO: QUESTION: should we be using $USER->id, $USER->idnumber or even $USER->username ?
	
	return $rtnString;
}
