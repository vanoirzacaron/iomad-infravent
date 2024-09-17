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
 * Assess a submission or view the single assessment
 *
 * Assessment id parameter must be passed. The script displays the submission and
 * the assessment form. If the current user is the reviewer and the assessing is
 * allowed, new assessment can be saved.
 * If the assessing is not allowed (for example, the assessment period is over
 * or the current user is eg a teacher), the assessment form is opened
 * in a non-editable mode.
 * The capability 'mod/peerreview:peerassess' is intentionally not checked here.
 * The user is considered as a reviewer if the corresponding assessment record
 * has been prepared for him/her (during the allocation). So even a user without the
 * peerassess capability (like a 'teacher', for example) can become a reviewer.
 *
 * @package    mod_peerreview
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use PhpOffice\PhpSpreadsheet\Calculation\Database\DVar;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');

$asid       = required_param('asid', PARAM_INT);  // assessment id
$assessment = $DB->get_record('peerreview_assessments', array('id' => $asid), '*', MUST_EXIST);
$submission = $DB->get_record('peerreview_submissions', array('id' => $assessment->submissionid, 'example' => 0), '*', MUST_EXIST);
global $USER;

$isgroupcoauthor = false;

if($submission->groupcoauthor) {
$groupcoauthor_emails = explode(';', $submission->groupcoauthor);
        // Check if the current user's email is in the array
        if (in_array($USER->email, $groupcoauthor_emails)) {
            $isgroupcoauthor = true;
        } 
    }

    if ($isgroupcoauthor) {
        $PAGE->add_body_class('peerreviewuserissubmissioncoauthor');
    }

//EDIT: First lets be sure the current assessment belongs to the current user
if($assessment->reviewerid != $USER->id) {
    $assessment = $DB->get_record('peerreview_assessments', array('submissionid' => $submission->id, 'reviewerid' => $USER->id), '*');
    redirect(new moodle_url('/mod/peerreview/assessment.php', array('asid' => $assessment->id)));  
}

//EDIT: Ensure an assessment record exists
if (!$assessment && $submission->authorid != $USER->id) {
    $newassessment = new stdClass();
    $newassessment->submissionid = $submission->id;
    $newassessment->reviewerid = $USER->id;
    $newassessment->weight = 1;  // Default weight
    $newassessment->timecreated = time();
    $newassessment->timemodified = time();
    $newassessment->feedbackauthorformat = 0;  // Default format
    $newassessment->feedbackauthorattachment = 0;  // Default attachment status
    $newassessment->feedbackreviewerformat = 0;  // Default format

    $newassessment->id = $DB->insert_record('peerreview_assessments', $newassessment);
    redirect(new moodle_url('/mod/peerreview/assessment.php', array('asid' => $newassessment->id))); 

}

$peerreview   = $DB->get_record('peerreview', array('id' => $submission->peerreviewid), '*', MUST_EXIST);
$course     = $DB->get_record('course', array('id' => $peerreview->course), '*', MUST_EXIST);
$cm         = get_coursemodule_from_instance('peerreview', $peerreview->id, $course->id, false, MUST_EXIST);

require_login($course, false, $cm);
if (isguestuser()) {
    throw new \moodle_exception('guestsarenotallowed');
}
$peerreview = new peerreview($peerreview, $cm, $course);

$PAGE->set_url($peerreview->assess_url($assessment->id));
$PAGE->set_title($peerreview->name);
$PAGE->set_heading($course->fullname);
$PAGE->activityheader->set_attrs([
    "hidecompletion" => true,
    "description" => ""
]);

$PAGE->navbar->add(get_string('assessingsubmission', 'peerreview'));
$PAGE->set_secondary_active_tab('modulepage');

$cansetassessmentweight = has_capability('mod/peerreview:allocate', $peerreview->context);
$canoverridegrades      = has_capability('mod/peerreview:overridegrades', $peerreview->context);
$isreviewer             = ($USER->id == $assessment->reviewerid);
$isreviewer = true;
$peerreview->check_view_assessment($assessment, $submission);

// only the reviewer is allowed to modify the assessment
if ($isreviewer) {
    $assessmenteditable = true;
} else {
    $assessmenteditable = false;
}
// $assessmenteditable = true;
// check that all required examples have been assessed by the user
if ($assessmenteditable) {

    list($assessed, $notice) = $peerreview->check_examples_assessed_before_assessment($assessment->reviewerid);
    if (!$assessed) {
        echo $output->header();
        notice(get_string($notice, 'peerreview'), new moodle_url('/mod/peerreview/view.php', array('id' => $cm->id)));
        echo $output->footer();
        exit;
    }
}

// load the grading strategy logic
$strategy = $peerreview->grading_strategy_instance();
// $assessment->grade = 1;
    // Are there any other pending assessments to do but this one?
    if ($assessmenteditable) {
        $pending = $peerreview->get_pending_assessments_by_reviewer($assessment->reviewerid, $assessment->id);
    } else {
        $pending = array();
    }
    // load the assessment form and process the submitted data eventually
    $mform = $strategy->get_assessment_form($PAGE->url, 'assessment', $assessment, $assessmenteditable,
                                        array('editableweight' => $cansetassessmentweight, 'pending' => !empty($pending)));

    // Set data managed by the peerreview core, subplugins set their own data themselves.
    $currentdata = (object)array(
        'weight' => $assessment->weight,
        'feedbackauthor' => $assessment->feedbackauthor,
        'feedbackauthorformat' => $assessment->feedbackauthorformat,
    );
    if ($assessmenteditable and $peerreview->overallfeedbackmode) {
        $currentdata = file_prepare_standard_editor($currentdata, 'feedbackauthor', $peerreview->overall_feedback_content_options(),
            $peerreview->context, 'mod_peerreview', 'overallfeedback_content', $assessment->id);
        if ($peerreview->overallfeedbackfiles) {
            $currentdata = file_prepare_standard_filemanager($currentdata, 'feedbackauthorattachment',
                $peerreview->overall_feedback_attachment_options(), $peerreview->context, 'mod_peerreview', 'overallfeedback_attachment',
                $assessment->id);
        }
    }
    $mform->set_data($currentdata);

    if ($mform->is_cancelled()) {
        redirect($peerreview->view_url());
    } elseif ($assessmenteditable and ($data = $mform->get_data())) {

        // Add or update assessment.
        $rawgrade = $peerreview->edit_assessment($assessment, $submission, $data, $strategy);

        // And finally redirect the user's browser.
        if (!is_null($rawgrade) and isset($data->saveandclose)) {
            redirect($peerreview->view_url());
        } else if (!is_null($rawgrade) and isset($data->saveandshownext)) {
            $next = reset($pending);
            if (!empty($next)) {
                redirect($peerreview->assess_url($next->id));
            } else {
                redirect($PAGE->url); // This should never happen but just in case...
            }
        } else {
            // either it is not possible to calculate the $rawgrade
            // or the reviewer has chosen "Save and continue"
            redirect($PAGE->url);
        }
    }

// load the form to override gradinggrade and/or set weight and process the submitted data eventually
if ($canoverridegrades or $cansetassessmentweight) {
    $options = array(
        'editable' => true,
        'editableweight' => $cansetassessmentweight,
        'overridablegradinggrade' => $canoverridegrades);
    $feedbackform = $peerreview->get_feedbackreviewer_form($PAGE->url, $assessment, $options);
    if ($data = $feedbackform->get_data()) {
        $peerreview->evaluate_assessment($assessment, $data, $cansetassessmentweight, $canoverridegrades);
        $peerreview->aggregate_grading_grades();
        redirect($peerreview->view_url());
    }
}

// output starts here
$output = $PAGE->get_renderer('mod_peerreview');      // peerreview renderer
echo $output->header();
echo $output->heading(get_string('assessedsubmission', 'peerreview'), 3);

$submission = $peerreview->get_submission_by_id($submission->id);     // reload so can be passed to the renderer

echo $output->render($peerreview->prepare_submission($submission, has_capability('mod/peerreview:viewauthornames', $peerreview->context)));

// show instructions for assessing as they may contain important information
// for evaluating the assessment
if (trim($peerreview->instructreviewers)) {
    $instructions = file_rewrite_pluginfile_urls($peerreview->instructreviewers, 'pluginfile.php', $PAGE->context->id,
        'mod_peerreview', 'instructreviewers', null, peerreview::instruction_editors_options($PAGE->context));
    print_collapsible_region_start('', 'peerreview-viewlet-instructreviewers', get_string('instructreviewers', 'peerreview'),
            'peerreview-viewlet-instructreviewers-collapsed');
    echo $output->box(format_text($instructions, $peerreview->instructreviewersformat, array('overflowdiv'=>true)), array('generalbox', 'instructions'));
    print_collapsible_region_end();
}

// extend the current assessment record with user details
$assessment = $peerreview->get_assessment_by_id($assessment->id);

if ($isreviewer) {
    $options    = array(
        'showreviewer'  => true,
        'showauthor'    => has_capability('mod/peerreview:viewauthornames', $peerreview->context),
        'showform'      => true,
        'showweight'    => true,
    );
    $assessment = $peerreview->prepare_assessment($assessment, $mform, $options);
    $assessment->title = get_string('assessmentbyyourself', 'peerreview');
    echo $output->render($assessment);

} else {
    $options    = array(
        'showreviewer'  => has_capability('mod/peerreview:viewreviewernames', $peerreview->context),
        'showauthor'    => has_capability('mod/peerreview:viewauthornames', $peerreview->context),
        'showform'      => true,
        'showweight'    => true,
    );
    $assessment = $peerreview->prepare_assessment($assessment, $mform, $options);
    echo $output->render($assessment);
}

if (!$assessmenteditable and $canoverridegrades) {
    $feedbackform->display();
}

echo $output->footer();
