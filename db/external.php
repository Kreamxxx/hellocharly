<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
        'block_hellocharly_get_user_progress' => [
        'classname'   => 'block_hellocharly\\external\\api',
        'methodname'  => 'get_user_progress',
        'description' => 'Get Hello Charly user progress',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'block/hellocharly:view',
    ],
];