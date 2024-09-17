<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_learningagreement', get_string('pluginname', 'local_learningagreement'));

    $ADMIN->add('localplugins', $settings);
}

