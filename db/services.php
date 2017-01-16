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

defined('MOODLE_INTERNAL') || die();

/**
 * xAPI Launch Link external functions and definitions.
 *
 * @package    mod_tincanlaunch
 * @category   external
 * @copyright  2016 Float, LLC <info@gowithfloat.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'mod_tincanlaunch_update_completion' => array(
        'classname'     => 'mod_tincanlaunch_external',
        'methodname'    => 'update_completion',
        'description'   => 'Triggers a check to the LRS to see if the activity is completed for the specified user',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => '',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
);