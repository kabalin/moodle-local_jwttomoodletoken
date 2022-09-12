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
 * @package    local_jwttomoodletoken
 * @author     Nicolas Dunand <nicolas.dunand@unil.ch>
 * @copyright  2020 Copyright Université de Lausanne, RISET {@link http://www.unil.ch/riset}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['auth'] = 'User auth';
$string['authdesc'] = 'Auth method to use when plugin creates new user account.';
$string['cachedef_jwks'] = 'This stores the list of JSON Web Key Sets';
$string['pluginname'] = 'Moodle UNIL JWT to mobiletoken web service';
$string['pubkey'] = 'Public Key';
$string['pubkeydesc'] = 'Public Key is used for tokens decoding using specified signature algorithm. If JWKS URI is configured, key sets is used instead of Public key, so this configuration is ignored.';
$string['pubalgo'] = 'Signature algorithm';
$string['jwksuri'] = 'JWKS URI';
$string['jwksuridesc'] = 'JSON Web Key Sets URI to use as source of keys for tokens decoding.';
$string['jwttomoodletoken:usews'] = 'Use web service for jwttomoodletoken';

