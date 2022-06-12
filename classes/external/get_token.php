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

require_once($CFG->dirroot . '/local/jwttomoodletoken/phpjwt/JWT.php');

namespace local_jwttomoodletoken\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use context_system;
use moodle_exception;
use Firebase\JWT;

/**
 * Web service function to retrieve access token.
 *
 * @package    local_jwttomoodletoken
 * @author     Nicolas Dunand <nicolas.dunand@unil.ch>
 * @copyright  2020 Copyright Université de Lausanne, RISET {@link http://www.unil.ch/riset}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_token extends external_api {

    /**
     * Parameters for retrieving access token.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
                'accesstoken' => new external_value(PARAM_RAW_TRIMMED, 'the JWT access token as yielded by keycloak')
        ]);
    }

    /**
     * Retrieve access token.
     *
     * @param string $accesstoken
     * @return array
     */
    public static function execute(string $accesstoken): array {
        global $CFG, $DB, $PAGE, $SITE, $USER;
        $PAGE->set_url('/webservice/rest/server.php', []);
        $params = self::validate_parameters(self::gettoken_parameters(), [
                'accesstoken' => $accesstoken
        ]);

        $pubkey = get_config('local_jwttomoodletoken', 'pubkey');
        $pubalgo = get_config('local_jwttomoodletoken', 'pubalgo');

        $token_contents = JWT\JWT::decode($params['accesstoken'], $pubkey, [$pubalgo]);

        // TODO si ok validate signature, expiration etc. => sinon HTTP unauthorized 401

        $email = strtolower($token_contents->preferred_username);

        $user = $DB->get_record('user', [
                'username'  => $email,
                'suspended' => 0,
                'deleted'   => 0
        ], '*', IGNORE_MISSING);

        if (!$user) {
            // We have to create this user as it does not yet exist.
            $newuser = (object)[
                    'auth'         => 'SAML2',
                    'confirmed'    => 1,
                    'policyagreed' => 0,
                    'deleted'      => 0,
                    'suspended'    => 0,
                    'description'  => 'Autocreated from Azure AD',
                    'username'     => $email,
                    'email'        => $email,
                    'password'     => 'not cached',
                    'firstname'    => $token_contents->name,
                    'lastname'     => $token_contents->name,
                    'timecreated'  => time(),
                    'mnethostid'   => $SITE->id,
            ];
            $newuserid = $DB->insert_record('user', $newuser);
            $user = $DB->get_record('user', ['id' => $newuserid], '*', MUST_EXIST);
        }

        // Check if the service exists and is enabled.
        $service = $DB->get_record('external_services', [
                'shortname' => 'moodle_mobile_app',
                'enabled'   => 1
        ]);
        if (empty($service)) {
            throw new moodle_exception('servicenotavailable', 'webservice');
            http_response_code(503);
            die();
        }

        // Get an existing token or create a new one.
        //        require_once($CFG->dirroot . '/lib/externallib.php');
        //        $validuntil = time() + $CFG->tokenduration; // Défaut : 12 semaines
        //        $token = external_generate_token(EXTERNAL_TOKEN_PERMANENT, $service, $user->id, \context_system::instance(),
        //                $validuntil);


//        core\session\manager::login_user($user);
        set_moodle_cookie($email);

        // Ugly hack on global vars
        $realuser = $USER;
        $USER = $user;
        $token = external_generate_token_for_current_user($service);
        $USER = $realuser;

        external_log_token_request($token);

        return [
                'moodletoken' => $token->token
        ];
    }

    /**
     * Return for retrieving access token.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'moodletoken' => new external_value(PARAM_ALPHANUM, 'valid Moodle mobile token')
        ]);
    }
}
