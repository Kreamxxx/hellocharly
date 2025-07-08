<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(

    // 1. Récupérer les données utilisateur (progression Hello Charly)
    'block_hellocharly_get_user_data' => array(
        'classname'   => 'block_hellocharly\\external\\api',
        'methodname'  => 'get_user_data',
        'description' => 'Retrieve user progress and favorite jobs from Hello Charly',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'block/hellocharly:view',
        'services' => ['hellocharly_service'],
    ),

    // 2. Générer un token SSO
    'block_hellocharly_generate_sso_token' => array(
        'classname'   => 'block_hellocharly\\external\\api',
        'methodname'  => 'generate_sso_token',
        'description' => 'Generate SSO token for Hello Charly',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'block/hellocharly:view',
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
);