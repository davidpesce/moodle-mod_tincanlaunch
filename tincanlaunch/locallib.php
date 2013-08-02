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
require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/tincanlaunch/lib.php");
*/

/**
 * Does something really useful with the passed things
 *
 * @param array $things
 * @return object
 */
 // 
 
function tincanlaunch_get_launch_url() {
	global $tincanlaunch, $USER, $CFG;
	
	//calculate basic authentication
	$basicauth = base64_encode($tincanlaunch->tincanlaunchlrslogin.":".$tincanlaunch->tincanlaunchlrspass);
	
	$rtnString = $tincanlaunch->tincanlaunchurl."?endpoint=".$tincanlaunch->tincanlaunchlrsendpoint."&auth=Basic%20$".$basicauth."=&actor={%22name%22:%22".fullname($USER)."%22,%22account%22:{%22homePage%22:%22".$CFG->wwwroot."%22,%22name%22:%22".$USER->id."%22},%22objectType%22:%22Agent%22}";
	
	//QUESTION: should I be using $USER->id, $USER->idnumber or even $USER->username ?
	
	return $rtnString;
}
