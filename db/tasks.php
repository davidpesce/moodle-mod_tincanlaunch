<?php
defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'mod_tincanlaunch\task\expire_credentials',
        'blocking' => 0,
        'minute' => '*'
    )
);