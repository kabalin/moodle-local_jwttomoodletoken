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

namespace local_jwttomoodletoken\local;

require_once($CFG->dirroot . '/local/jwttomoodletoken/phpjwt/JWT.php');
require_once($CFG->dirroot . '/local/jwttomoodletoken/phpjwt/JWK.php');
require_once($CFG->libdir . '/filelib.php');

use curl;
use moodle_exception;
use stdClass;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

/**
 * Collection of helper functions.
 *
 * @package    local_jwttomoodletoken
 * @copyright  2022 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /** The default curl timeout. */
    const CURL_TIMEOUT = 10;

    /** The max number of keys retrieval attempts if kid is not found in the set. */
    const MAX_KEY_CALLS = 1;

    /**
     * Fetches JSON Web Key Sets from public uri.
     *
     * @param string $token
     * @param string $jwksuri
     * @param string $algo
     * @return stdClass
     */
    public static function decode_token(string $token, string $jwksuri, string $algo): stdClass {
        static $callscount = 0;

        // Retrieve cached keys.
        $cache = \cache::make('local_jwttomoodletoken', 'jwks');
        if (!$keys = $cache->get('keys')) {
            $keys = self::request_jwks_data_from_uri($jwksuri);
            $cache->set('keys', $keys);
        }

        try {
            // Decode token.
            $token_contents = JWT::decode($token, JWK::parseKeySet($keys, $algo));
        } catch (\Exception $e) {
            if ($e instanceof \UnexpectedValueException && $e->getMessage() == '"kid" invalid, unable to lookup correct key') {
                // The 'kid' value in not found in the set of keys.
                // Purge cache and recursively call itself unless we exceeded the maximum number of key calls (throw otherwise).
                $callscount++;
                if ($callscount <= self::MAX_KEY_CALLS) {
                    $cache->purge();
                    return self::decode_token($token, $jwksuri, $algo);
                }
            }
            http_response_code(400);
            throw $e;
        }
        return $token_contents;
    }

    /**
     * Fetches JSON Web Key Sets from public uri.
     *
     * @param string $jwksuri
     * @return array
     */
    private static function request_jwks_data_from_uri(string $jwksuri): array {
        $curl = new curl();
        $curl->setopt(['CURLOPT_TIMEOUT' => self::CURL_TIMEOUT, 'CURLOPT_CONNECTTIMEOUT' => self::CURL_TIMEOUT]);
        $curl->setHeader(['Cache-Control: no-cache', 'Content-Type: application/json']);
        $response = @json_decode($curl->get($jwksuri), true);

        // Check errors.
        if ($curlerrno = $curl->get_errno() || empty($response)) {
            // Curl error.
            $failurereason = "Unexpected response, CURL error number: $curlerrno Error: {$curl->error}";
            $debuginfo = '';
        } else if (!empty($response['error'])) {
            $failurereason = "Unexpected response ({$response['error']}) " . $response['error_description'];
            $debuginfo = $response['error_uri'];
        }
        if (!empty($failurereason)) {
            http_response_code(400);
            throw new moodle_exception('invalidextresponse', 'webservice', '', $failurereason, $debuginfo);
        }
        return $response;
    }
}
