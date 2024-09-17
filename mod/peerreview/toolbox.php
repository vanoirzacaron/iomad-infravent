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
 * Various peerreview maintainance utilities
 *
 * @package    mod_peerreview
 * @copyright  2010 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');

$id         = required_param('id', PARAM_INT); // course_module ID
$tool       = required_param('tool', PARAM_ALPHA);

$cm         = get_coursemodule_from_id('peerreview', $id, 0, false, MUST_EXIST);
$course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$peerreview   = $DB->get_record('peerreview', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);
$peerreview = new peerreview($peerreview, $cm, $course);
require_sesskey();

$params = array(
    'context' => $peerreview->context,
    'courseid' => $course->id,
    'other' => array('peerreviewid' => $peerreview->id)
);

switch ($tool) {
case 'clearaggregatedgrades':
    require_capability('mod/peerreview:overridegrades', $peerreview->context);
    $peerreview->clear_submission_grades();
    $peerreview->clear_grading_grades();
    $event = \mod_peerreview\event\assessment_evaluations_reset::create($params);
    $event->trigger();
    break;

case 'clearassessments':
    require_capability('mod/peerreview:overridegrades', $peerreview->context);
    $peerreview->clear_assessments();
    $event = \mod_peerreview\event\assessments_reset::create($params);
    $event->trigger();
    break;
}

redirect($peerreview->view_url());
