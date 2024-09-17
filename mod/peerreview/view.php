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
 * Prints a particular instance of peerreview
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_peerreview
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');

$id         = optional_param('id', 0, PARAM_INT); // course_module ID, or
$w          = optional_param('w', 0, PARAM_INT);  // peerreview instance ID
$editmode   = optional_param('editmode', null, PARAM_BOOL);
$page       = optional_param('page', 0, PARAM_INT);
$perpage    = optional_param('perpage', null, PARAM_INT);
$sortby     = optional_param('sortby', 'lastname', PARAM_ALPHA);
$sorthow    = optional_param('sorthow', 'ASC', PARAM_ALPHA);
$eval       = optional_param('eval', null, PARAM_PLUGIN);

if ($id) {
    $cm             = get_coursemodule_from_id('peerreview', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $peerreviewrecord = $DB->get_record('peerreview', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $peerreviewrecord = $DB->get_record('peerreview', array('id' => $w), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $peerreviewrecord->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('peerreview', $peerreviewrecord->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);
require_capability('mod/peerreview:view', $PAGE->context);

$peerreview = new peerreview($peerreviewrecord, $cm, $course);

$PAGE->set_url($peerreview->view_url());

// Mark viewed.
$peerreview->set_module_viewed();

$peerreview->init_initial_bar();
$userplan = new peerreview_user_plan($peerreview, $USER->id);
$currentphasetitle = '';

$PAGE->set_title($peerreview->name . " (" . $currentphasetitle . ")");
$PAGE->set_heading($course->fullname);

if ($eval) {
    require_sesskey();
    require_capability('mod/peerreview:overridegrades', $peerreview->context);
    $peerreview->set_grading_evaluation_method($eval);
    redirect($PAGE->url);
}

$heading = $OUTPUT->heading_with_help(format_string($peerreview->name), 'userplan', 'peerreview');
$heading = preg_replace('/<h2[^>]*>([.\s\S]*)<\/h2>/', '$1', $heading);
$PAGE->activityheader->set_attrs([
    'title' => $PAGE->activityheader->is_title_allowed() ? $heading : "",
    'description' => ''
]);

$output = $PAGE->get_renderer('mod_peerreview');

// Output starts here.

echo $output->header();

echo $output->view_page($peerreview, $userplan, $currentphasetitle, $page, $sortby, $sorthow);

$PAGE->requires->js_call_amd('mod_peerreview/peerreviewview', 'init');
echo $output->footer();
