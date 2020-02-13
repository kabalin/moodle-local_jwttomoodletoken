<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {


    $settings =
            new admin_settingpage('local_jwttomoodletoken', new lang_string('pluginname', 'local_jwttomoodletoken'));

    $settings->add(new admin_setting_configtextarea('local_jwttomoodletoken/pubkey',
            get_string('pubkey', 'local_jwttomoodletoken'), '', '', PARAM_RAW_TRIMMED));

    $settings->add(new admin_setting_configtext('local_jwttomoodletoken/pubalgo',
            get_string('pubalgo', 'local_jwttomoodletoken'), '', '', PARAM_ALPHANUM));

    $ADMIN->add('localplugins', $settings);
}

