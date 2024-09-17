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
 * The peerreview module configuration variables
 *
 * The values defined here are often used as defaults for all module instances.
 *
 * @package    mod_peerreview
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/peerreview/locallib.php');

    $grades = peerreview::available_maxgrades_list();

    $settings->add(new admin_setting_configselect('peerreview/grade', get_string('submissiongrade', 'peerreview'),
                        get_string('configgrade', 'peerreview'), 80, $grades));

    $settings->add(new admin_setting_configselect('peerreview/gradinggrade', get_string('gradinggrade', 'peerreview'),
                        get_string('configgradinggrade', 'peerreview'), 20, $grades));

    $options = array();
    for ($i = 5; $i >= 0; $i--) {
        $options[$i] = $i;
    }
    $settings->add(new admin_setting_configselect('peerreview/gradedecimals', get_string('gradedecimals', 'peerreview'),
                        get_string('configgradedecimals', 'peerreview'), 0, $options));

    if (isset($CFG->maxbytes)) {
        $maxbytes = get_config('peerreview', 'maxbytes');
        $options = get_max_upload_sizes($CFG->maxbytes, 0, 0, $maxbytes);
        $settings->add(new admin_setting_configselect('peerreview/maxbytes', get_string('maxbytes', 'peerreview'),
                            get_string('configmaxbytes', 'peerreview'), 0, $options));
    }

    $settings->add(new admin_setting_configselect('peerreview/strategy', get_string('strategy', 'peerreview'),
                        get_string('configstrategy', 'peerreview'), 'accumulative', peerreview::available_strategies_list()));

    $options = peerreview::available_example_modes_list();
    $settings->add(new admin_setting_configselect('peerreview/examplesmode', get_string('examplesmode', 'peerreview'),
                        get_string('configexamplesmode', 'peerreview'), peerreview::EXAMPLES_VOLUNTARY, $options));

    // include the settings of allocation subplugins
    $allocators = core_component::get_plugin_list('peerreviewallocation');
    foreach ($allocators as $allocator => $path) {
        if (file_exists($settingsfile = $path . '/settings.php')) {
            $settings->add(new admin_setting_heading('peerreviewallocationsetting'.$allocator,
                    get_string('allocation', 'peerreview') . ' - ' . get_string('pluginname', 'peerreviewallocation_' . $allocator), ''));
            include($settingsfile);
        }
    }

    // include the settings of grading strategy subplugins
    $strategies = core_component::get_plugin_list('peerreviewform');
    foreach ($strategies as $strategy => $path) {
        if (file_exists($settingsfile = $path . '/settings.php')) {
            $settings->add(new admin_setting_heading('peerreviewformsetting'.$strategy,
                    get_string('strategy', 'peerreview') . ' - ' . get_string('pluginname', 'peerreviewform_' . $strategy), ''));
            include($settingsfile);
        }
    }

    // include the settings of grading evaluation subplugins
    $evaluations = core_component::get_plugin_list('peerrevieweval');
    foreach ($evaluations as $evaluation => $path) {
        if (file_exists($settingsfile = $path . '/settings.php')) {
            $settings->add(new admin_setting_heading('peerreviewevalsetting'.$evaluation,
                    get_string('evaluation', 'peerreview') . ' - ' . get_string('pluginname', 'peerrevieweval_' . $evaluation), ''));
            include($settingsfile);
        }
    }

}
