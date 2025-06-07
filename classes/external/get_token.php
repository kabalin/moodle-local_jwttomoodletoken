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

namespace local_jwttomoodletoken\external;

require_once($CFG->dirroot . '/local/jwttomoodletoken/phpjwt/JWT.php');
require_once($CFG->dirroot . '/local/jwttomoodletoken/phpjwt/Key.php');

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use context_system;
use moodle_exception;
use local_jwttomoodletoken\local\helper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
        global $CFG, $DB;

        // Parameter and permission validation.
        $params = self::validate_parameters(self::execute_parameters(), [
            'accesstoken' => $accesstoken,
        ]);
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/jwttomoodletoken:usews', $context);

        // Check if the service exists and is enabled.
        $service = $DB->get_record('external_services', ['shortname' => MOODLE_OFFICIAL_MOBILE_SERVICE, 'enabled' => 1]);
        if (empty($service)) {
            http_response_code(503);
            throw new moodle_exception('servicenotavailable', 'webservice');
        }

        // Check settings.
        $jwksuri = get_config('local_jwttomoodletoken', 'jwksuri');
        $pubkey = get_config('local_jwttomoodletoken', 'pubkey');
        $pubalgo = get_config('local_jwttomoodletoken', 'pubalgo');
        
        if ($jwksuri) {
            // Decode token using keys set url.
            $token_contents = helper::decode_token($params['accesstoken'], $jwksuri, $pubalgo);
        } else if ($pubkey) {
            // Decode token using public key.
            $token_contents = JWT::decode($params['accesstoken'], new Key($pubkey, $pubalgo));            
        } else {
            // We need JWKS URL or public key to proceed.
            http_response_code(503);
            throw new moodle_exception('servicenotavailable', 'webservice');
        }

        $email = strtolower($token_contents->preferred_username);
        if ($user = \core_user::get_user_by_username($email)) {
            // User has to be active.
            \core_user::require_active_user($user);
        } else {
            // We have to create this user as it does not yet exist.
            require_once($CFG->dirroot.'/user/lib.php');
            $newuser = (object)[
                'auth'         => get_config('local_jwttomoodletoken', 'auth'),
                'confirmed'    => 1,
                'policyagreed' => 0,
                'deleted'      => 0,
                'suspended'    => 0,
                'description'  => 'Autocreated from Azure AD',
                'username'     => $email,
                'email'        => $email,
                'firstname'    => $token_contents->given_name,
                'lastname'     => $token_contents->family_name,
                'mnethostid'   => $CFG->mnet_localhost_id,
            ];
            // Create user silently (no events triggered).
            $newuserid = user_create_user($newuser, false, false);
            $user = \core_user::get_user($newuserid, '*', MUST_EXIST);
        }

        // Set current user, generate token and log token request.
        \core\session\manager::set_user($user);
        $token = external_generate_token_for_current_user($service);
        external_log_token_request($token);

        return [
            'moodletoken' => $token->token,
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
