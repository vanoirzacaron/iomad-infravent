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
 * Edit grading form in for a particular instance of peerreview
 *
 * @package    mod_peerreview
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');

$cmid       = required_param('cmid', PARAM_INT);

$cm         = get_coursemodule_from_id('peerreview', $cmid, 0, false, MUST_EXIST);
$course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, false, $cm);
require_capability('mod/peerreview:editdimensions', $PAGE->context);

$peerreview   = $DB->get_record('peerreview', array('id' => $cm->instance), '*', MUST_EXIST);
$peerreview   = new peerreview($peerreview, $cm, $course);

// todo: check if there already is some assessment done and do not allowed the change of the form
// once somebody already used it to assess

$PAGE->set_url($peerreview->editform_url());
$PAGE->set_title($peerreview->name);
$PAGE->set_heading($course->fullname);
$PAGE->activityheader->set_attrs([
    'hidecompletion' => true,
    'description' => ''
]);
$PAGE->navbar->add(get_string('editingassessmentform', 'peerreview'));

// load the grading strategy logic
$strategy = $peerreview->grading_strategy_instance();

// load the form to edit the grading strategy dimensions
$mform = $strategy->get_edit_strategy_form($PAGE->url);

if ($mform->is_cancelled()) {
    redirect($peerreview->view_url());
} elseif ($data = $mform->get_data()) {
    if (($data->peerreviewid != $peerreview->id) or ($data->strategy != $peerreview->strategy)) {
        // this may happen if someone changes the peerreview setting while the user had the
        // editing form opened
        throw new invalid_parameter_exception('Invalid peerreview ID or the grading strategy has changed.');
    }
    $strategy->save_edit_strategy_form($data);
    if (isset($data->saveandclose)) {
        redirect($peerreview->view_url());
    } elseif (isset($data->saveandpreview)) {
        redirect($peerreview->previewform_url());
    } else {
        // save and continue - redirect to self to prevent data being re-posted by pressing "Reload"
        redirect($PAGE->url);
    }
}

// Output starts here

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'peerreviewform_' . $peerreview->strategy), 3);

$mform->display();

echo $OUTPUT->footer();
