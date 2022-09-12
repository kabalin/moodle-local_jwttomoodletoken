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
 * Upgrade routines.
 *
 * @package    local_jwttomoodletoken
 * @copyright  2022 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Upgrade task
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_local_jwttomoodletoken_upgrade($oldversion) {
    global $CFG, $DB;

    if ($oldversion < 2022091200) {
        // Fix SAML2 user auth type.
        $DB->execute("UPDATE {user} SET auth = 'saml2' WHERE auth = 'SAML2'");

        upgrade_plugin_savepoint(true, 2022091200, 'local', 'jwttomoodletoken');
    }

    return true;
}
