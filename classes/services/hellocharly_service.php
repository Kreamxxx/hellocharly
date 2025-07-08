<?php
// Fichier : blocks/hellocharly/classes/services/hellocharly_service.php

namespace block_hellocharly\services;

use moodle_exception;

class hellocharly_service {

    /**
     * Récupère la progression et les métiers favoris d’un utilisateur via l’API Hello Charly.
     *
     * @param int $userid
     * @return array
     * @throws moodle_exception
     */
        public static function get_user_progress_for_block(int $userid): array {
        global $CFG, $USER;

        // Vérifications classiques
        if ($USER->id !== $userid && !has_capability('moodle/user:viewdetails', \context_system::instance())) {
            error_log('[HelloCharly] Permission refusée pour user '.$USER->id);
            throw new moodle_exception('nopermission', 'error');
        }

        $apikey = $CFG->hellocharly_api_key ?? null;
        if (!$apikey) {
            error_log('[HelloCharly] Clé API manquante');
            throw new moodle_exception('missingapikey', 'block_hellocharly');
        }

        $url = 'https://staging.api.hello-charly.com/v2/open-api/user-data/' . urlencode($userid);
        error_log('[HelloCharly] Appel API à : '.$url);

        $curl = new \curl();
        $headers = [
            'Authorization: ' . $apikey,  // PAS de Bearer en staging, OK
            'Accept: application/json'
        ];

        $response = $curl->get($url, null, ['CURLOPT_HTTPHEADER' => $headers]);
        $http_code = $curl->get_info()['http_code'];

        error_log("[HelloCharly] HTTP code : $http_code");
        error_log("[HelloCharly] Réponse brute : $response");

        $info = json_decode($response, true);

        if ($http_code !== 200) {
            error_log('[HelloCharly] Erreur HTTP non 200');
            throw new moodle_exception('apiresponseinvalid', 'block_hellocharly');
        }
        if (empty($info)) {
            error_log('[HelloCharly] Réponse vide ou invalide JSON');
            throw new moodle_exception('apiresponseinvalid', 'block_hellocharly');
        }

        error_log('[HelloCharly] Données décodées OK');

        return $info;
    }
}