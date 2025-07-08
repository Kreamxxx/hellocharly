<?php
namespace block_hellocharly\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/filelib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_system;
use moodle_exception;
use curl;
global $CFG;

class api extends external_api {

    public static function get_user_data_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID')
        ]);
    }

    public static function get_user_data($userid) {

    global $CFG;
    
    self::validate_parameters(self::get_user_data_parameters(), ['userid' => $userid]);
        
    try {
        error_log('🔥🔥 Appel de get_user_progress_for_block');
        $progress = self::get_user_progress_for_block($userid);
    } catch (\Throwable $e) {
        error_log('[HelloCharly] Erreur get_user_progress_for_block : ' . $e->getMessage());
        throw new \moodle_exception('progression_error', 'block_hellocharly', '', null, $e->getMessage());
    }

    error_log('[HelloCharly] Données de progression JSON : ' . json_encode($progress));

    $html = '<p>🎯 Objectifs terminés : ' . ($progress['user']['completedGoals'] ?? 0) .
            ' — Missions complétées : ' . ($progress['user']['completedMissions'] ?? 0) . '</p>';

    if (!empty($progress['favoriteJobs'])) {
        $html .= '<ul>';
        foreach ($progress['favoriteJobs'] as $job) {
            $html .= '<li>💼 ' . htmlspecialchars($job['name']) . '</li>';
        }
        $html .= '</ul>';
    }

    return ['html' => $html];
}

    public static function get_user_data_returns() {
        return new external_single_structure([
            'html' => new external_value(PARAM_RAW, 'HTML content')
        ]);
    }

    public static function generate_sso_token_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'sesskey' => new external_value(PARAM_RAW, 'Session key')
        ]);
    }

    public static function generate_sso_token($userid, $sesskey) {
        global $CFG, $USER, $DB;

        error_log('[HelloCharly] Début de generate_sso_token');
        error_log('[HelloCharly] CFG API Key : ' . ($CFG->hellocharly_api_key ?? 'NON DÉFINIE'));

        $params = self::validate_parameters(self::generate_sso_token_parameters(), [
            'userid' => $userid,
            'sesskey' => $sesskey
        ]);

        require_login();
        require_sesskey();
        $context = context_system::instance();
        self::validate_context($context);

        if (!has_capability('block/hellocharly:view', $context)) {
            error_log('[HelloCharly] Permission refusée');
            throw new moodle_exception('nopermissions', 'error');
        }

        if ($USER->id != $params['userid']) {
            error_log('[HelloCharly] Utilisateur invalide');
            throw new moodle_exception('invaliduser', 'error');
        }

        if (empty($CFG->hellocharly_api_key)) {
            error_log('[HelloCharly] Clé API absente');
            return [
                'success' => false,
                'message' => get_string('error_api_key', 'block_hellocharly'),
                'redirect_url' => ''
            ];
        }

        try {
            $user = $DB->get_record('user', ['id' => $params['userid']], '*', MUST_EXIST);
            $hellocharly_data = self::prepare_user_data($user);

            error_log('[HelloCharly] Données utilisateur préparées : ' . json_encode($hellocharly_data));
            $response = self::call_hellocharly_api($hellocharly_data, $CFG->hellocharly_api_key);

            if ($response['success']) {
                error_log('[HelloCharly] Token généré avec succès');
                return [
                    'success' => true,
                    'message' => 'Token généré avec succès',
                    'redirect_url' => $response['redirectUrl']
                ];
            } else {
                error_log('[HelloCharly] Échec retour API : ' . $response['message']);
                return [
                    'success' => false,
                    'message' => $response['message'],
                    'redirect_url' => ''
                ];
            }
        } catch (\Exception $e) {
            error_log('[HelloCharly] Exception : ' . $e->getMessage());
            return [
                'success' => false,
                'message' => get_string('error_api_call', 'block_hellocharly'),
                'redirect_url' => ''
            ];
        }
    }

    public static function generate_sso_token_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
            'redirect_url' => new external_value(PARAM_URL, 'Redirect URL for Hello Charly')
        ]);
    }

    private static function prepare_user_data($user) {
        global $DB;

        $context = context_system::instance();
        $roles = get_user_roles($context, $user->id);

        $role = 'eleve';
        foreach ($roles as $userrole) {
            if (in_array($userrole->shortname, ['teacher', 'editingteacher', 'manager'])) {
                $role = 'accompagnant';
                break;
            }
        }

        $data = [
            'id' => (string)$user->id,
            'name' => $user->firstname ?: 'Prénom',
            'lastName' => $user->lastname ?: 'Nom',
            'role' => $role
        ];

        if ($role === 'eleve') {
            $data['group'] = self::get_user_group($user->id);
            $data['profile'] = self::get_user_profile($user->id);
        }

        return $data;
    }

    private static function get_user_group($userid) {
        global $DB;
        $customfield = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => 1]);
        return $customfield ? $customfield->data : 'Groupe par défaut';
    }

    private static function get_user_profile($userid) {
        global $DB;
        $customfield = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => 2]);
        return $customfield ? $customfield->data : '1ePro';
    }

    private static function call_hellocharly_api($data, $api_key) {
        $curl = new curl();

        $headers = [
            'Content-Type: application/json',
            'Authorization: ' . $api_key
        ];

        $curl->setopt([
            'CURLOPT_HTTPHEADER' => $headers,
            'CURLOPT_TIMEOUT' => 30,
            'CURLOPT_CONNECTTIMEOUT' => 10
        ]);

        $response = $curl->post(
            'https://staging.api.hello-charly.com/v2/users/generate-sso-token',
            json_encode($data)
        );

        $http_code = $curl->get_info()['http_code'];

        error_log('[HelloCharly] Requête API envoyée');
        error_log('[HelloCharly] Payload : ' . json_encode($data));
        error_log('[HelloCharly] Réponse brute : ' . $response);
        error_log('[HelloCharly] Code HTTP : ' . $http_code);

        if ($curl->get_errno() || !in_array($http_code, [200, 201])) {
            return [
                'success' => false,
                'message' => 'Erreur API: ' . $curl->error . ' HTTP: ' . $http_code
            ];
        }

        $response_data = json_decode($response, true);
        if (!isset($response_data['redirectUrl'])) {
            error_log('[HelloCharly] Pas d\'URL de redirection dans la réponse');
            return [
                'success' => false,
                'message' => 'URL de redirection manquante dans la réponse'
            ];
        }

        return [
            'success' => true,
            'redirectUrl' => $response_data['redirectUrl']
        ];
    }

    public static function get_user_progress_for_block($userid) {
        global $CFG, $DB;

        $context = context_system::instance();
        self::validate_context($context);

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        $curl = new curl();
        $headers = [
            'Content-Type: application/json',
            'Authorization: ' . $CFG->hellocharly_api_key
        ];

        $url = 'https://staging.api.hello-charly.com/v2/open-api/user-data/' . $user->id;
        $response = $curl->get($url, [], $headers);

        $http_code = $curl->get_info()['http_code'];
        error_log('[HelloCharly] Récupération progression HTTP ' . $http_code);
        error_log('[HelloCharly] Réponse : ' . $response);

        if ($curl->get_errno() || $http_code !== 200) {
            return [
                'user' => ['completedGoals' => 0, 'completedMissions' => 0],
                'favoriteJobs' => []
            ];
        }

        return json_decode($response, true);
    }
}