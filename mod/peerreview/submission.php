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
 * View a single (usually the own) submission, submit own work.
 *
 * @package    mod_peerreview
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/locallib.php');

$cmid = required_param('cmid', PARAM_INT); // Course module id.
$id = optional_param('id', 0, PARAM_INT); // Submission id.
$edit = optional_param('edit', false, PARAM_BOOL); // Open the page for editing?
$assess = optional_param('assess', false, PARAM_BOOL); // Instant assessment required.
$delete = optional_param('delete', false, PARAM_BOOL); // Submission removal requested.
$confirm = optional_param('confirm', false, PARAM_BOOL); // Submission removal request confirmed.

$cm = get_coursemodule_from_id('peerreview', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

global $USER;

require_login($course, false, $cm);
if (isguestuser()) {
    throw new \moodle_exception('guestsarenotallowed');
}

$peerreviewrecord = $DB->get_record('peerreview', array('id' => $cm->instance), '*', MUST_EXIST);
$peerreview = new peerreview($peerreviewrecord, $cm, $course);

$PAGE->set_url($peerreview->submission_url(), array('cmid' => $cmid, 'id' => $id));

$PAGE->set_secondary_active_tab("modulepage");

if ($edit) {
    $PAGE->url->param('edit', $edit);
}

if ($id) { // submission is specified
    $submission = $peerreview->get_submission_by_id($id);

} else { // no submission specified
    if (!$submission = $peerreview->get_submission_by_author($USER->id)) {
        $submission = new stdclass();
        $submission->id = null;
        $submission->authorid = $USER->id;
        $submission->example = 0;
        $submission->grade = null;
        $submission->gradeover = null;
        $submission->published = null;
        $submission->feedbackauthor = null;
        $submission->feedbackauthorformat = editors_get_preferred_format();
        $submission->groupcoauthor = null;
    }
}

$peerreview->phase = 50;

$ownsubmission  = $submission->authorid == $USER->id;
$canviewall     = has_capability('mod/peerreview:viewallsubmissions', $peerreview->context);
$cansubmit      = has_capability('mod/peerreview:submit', $peerreview->context);
$canallocate    = has_capability('mod/peerreview:allocate', $peerreview->context);
$canpublish     = has_capability('mod/peerreview:publishsubmissions', $peerreview->context);
$canoverride    = (($peerreview->phase == peerreview::PHASE_EVALUATION) and has_capability('mod/peerreview:overridegrades', $peerreview->context));
$candeleteall   = has_capability('mod/peerreview:deletesubmissions', $peerreview->context);
$isgroupcoauthor = false;
// $ownsubmission = true;
if($submission->groupcoauthor) {
$groupcoauthor_emails = explode(';', $submission->groupcoauthor);
        // Check if the current user's email is in the array
        if (in_array($USER->email, $groupcoauthor_emails)) {
            $isgroupcoauthor = true;
        } 
    }

$currentPath = $_SERVER['REQUEST_URI'];

if(strpos($currentPath, 'submission.php') !== false) {
    $PAGE->add_body_class('peerreviewsubmissionpage');
}

if(strpos($currentPath, 'assessment.php') !== false) {
    $PAGE->add_body_class('peerreviewassessmentpage');
}

if(is_siteadmin($USER)) {
    $PAGE->add_body_class('peerreviewuserisadmin');
}

if($ownsubmission) {
    $PAGE->add_body_class('peerreviewuserissubmissionauthor');
} elseif ($isgroupcoauthor) {
    $PAGE->add_body_class('peerreviewuserisnotsubmissionauthor');
    $PAGE->add_body_class('peerreviewuserissubmissioncoauthor');
} else {
    $PAGE->add_body_class('peerreviewuserisnotsubmissionauthor');
}


/* EDIT: Be sure a assessment exists */

$assessment = $DB->get_record('peerreview_assessments', array('submissionid' => $submission->id, 'reviewerid' => $USER->id), '*');

if (!$assessment && $submission->authorid != $USER->id) {
    $current_time = time();

    $newassessment = new stdClass();
    $newassessment->submissionid = $submission->id;
    $newassessment->reviewerid = $USER->id;
    $newassessment->weight = 1;
    $newassessment->timecreated = $current_time;
    $newassessment->timemodified = 0;
    $newassessment->grade = null;
    $newassessment->gradinggrade = null;  
    $newassessment->gradinggradeover = null;  
    $newassessment->gradinggradeoverby = null;  
    $newassessment->feedbackauthor = '';
    $newassessment->feedbackauthorformat = 0;
    $newassessment->feedbackauthorattachment = 0;
    $newassessment->feedbackreviewer = '';
    $newassessment->feedbackreviewerformat = 0;
    $newassessment->anonymousreview = 0;

    $newassessment->id = $DB->insert_record('peerreview_assessments', $newassessment);
    //redirect(new moodle_url('/mod/peerreview/assessment.php', array('asid' => $newassessment->id))); 
}

$userassessment = $peerreview->get_assessment_of_submission_by_user($submission->id, $USER->id);
$isreviewer     = !empty($userassessment);

$editable       = $ownsubmission;
$deletable      = $candeleteall;
$ispublished    = ($peerreview->phase == peerreview::PHASE_CLOSED
                    and $submission->published == 1
                    and has_capability('mod/peerreview:viewpublishedsubmissions', $peerreview->context));

            
// Edit: Author is aways able to edit the subission
if (empty($submission->id) and !$peerreview->creating_submission_allowed($USER->id)) {
    $editable = false;
}
if ($submission->id and !$peerreview->modifying_submission_allowed($USER->id)) {
    $editable = false;
}

// EDIT: Everyone can see all
// $canviewall = $canviewall && $peerreview->check_group_membership($submission->authorid);
$canviewall = true;

$edit = ($editable and $edit);


   
if (!$candeleteall and $ownsubmission and $editable) {
    // Only allow the student to delete their own submission if it's still editable and hasn't been assessed.
    if (count($peerreview->get_assessments_of_submission($submission->id)) > 0) {
        $deletable = false;
    } else {
        $deletable = true;
    }
}

if ($submission->id and $delete and $confirm and $deletable) {
    require_sesskey();
    $peerreview->delete_submission($submission);

    redirect($peerreview->view_url());
}

$seenaspublished = false; // is the submission seen as a published submission?

if ($submission->id and ($ownsubmission or $canviewall or $isreviewer)) {
    // ok you can go
} elseif ($submission->id and $ispublished) {
    // ok you can go
    $seenaspublished = true;
} elseif (is_null($submission->id) and $cansubmit) {
    // ok you can go
} else {
    throw new \moodle_exception('nopermissions', 'error', $peerreview->view_url(), 'view or create submission');
}

if ($submission->id) {
    // Trigger submission viewed event.
    $peerreview->set_submission_viewed($submission);
}

if ($assess and $submission->id and !$isreviewer and $canallocate and $peerreview->assessing_allowed($USER->id)) {
    require_sesskey();
    $assessmentid = $peerreview->add_allocation($submission, $USER->id);
    redirect($peerreview->assess_url($assessmentid));
}

if ($edit) {
    require_once(__DIR__.'/submission_form.php');

    $submission = file_prepare_standard_editor($submission, 'content', $peerreview->submission_content_options(),
        $peerreview->context, 'mod_peerreview', 'submission_content', $submission->id);
        /* EDIT: add description field */
        $submission = file_prepare_standard_editor($submission, 'description', $peerreview->submission_content_options(),
        $peerreview->context, 'mod_peerreview', 'description_content', $submission->id);

    $submission = file_prepare_standard_filemanager($submission, 'attachment', $peerreview->submission_attachment_options(),
        $peerreview->context, 'mod_peerreview', 'submission_attachment', $submission->id);
    /* EDIT: add description field */
    $mform = new peerreview_submission_form($PAGE->url, array('current' => $submission, 'peerreview' => $peerreview,
        'contentopts' => $peerreview->submission_content_options(), 'descriptionopts' => $peerreview->submission_content_options(),
        'attachmentopts' => $peerreview->submission_attachment_options()));

    if ($mform->is_cancelled()) {
        redirect($peerreview->view_url());

    } elseif ($cansubmit and $formdata = $mform->get_data()) {

        $formdata->id = $submission->id;
        // Creates or updates submission.
        $submission->id = $peerreview->edit_submission($formdata);

        redirect($peerreview->submission_url($submission->id));
    }
}

// load the form to override grade and/or publish the submission and process the submitted data eventually
if (!$edit and ($canoverride or $canpublish)) {
    $options = array(
        'editable' => true,
        'editablepublished' => $canpublish,
        'overridablegrade' => $canoverride);
    $feedbackform = $peerreview->get_feedbackauthor_form($PAGE->url, $submission, $options);
    if ($data = $feedbackform->get_data()) {
        $peerreview->evaluate_submission($submission, $data, $canpublish, $canoverride);
        redirect($peerreview->view_url());
    }
}

$PAGE->set_title($peerreview->name);
$PAGE->set_heading($course->fullname);
$PAGE->activityheader->set_attrs([
    'hidecompletion' => true,
    'description' => ''
]);
if ($edit) {
    $PAGE->navbar->add(get_string('mysubmission', 'peerreview'), $peerreview->submission_url(), navigation_node::TYPE_CUSTOM);
    $PAGE->navbar->add(get_string('editingsubmission', 'peerreview'));
} elseif ($ownsubmission) {
    $PAGE->navbar->add(get_string('mysubmission', 'peerreview'));
} else {
    $PAGE->navbar->add(get_string('submission', 'peerreview'));
}

// Output starts here
$output = $PAGE->get_renderer('mod_peerreview');
echo $output->header();


// if($ownsubmission) {
    if($ownsubmission || is_siteadmin($USER)) {
    echo $output->heading(get_string('mysubmission', 'peerreview'), 3);
    // Add script to collapse the project and reviews.
    echo '<script type="text/javascript">
document.addEventListener("DOMContentLoaded", function() {
    var headers = document.querySelectorAll(".header");
    var firstHeaderClicked = false;
    var firstHeader = headers[0]; // Assume the first header is the one to be clicked to show/hide the h4 element

    headers.forEach(function(header) {
        // Add the arrow icon to each header
        var arrow = document.createElement("span");
        arrow.classList.add("arrow", "down");
        header.appendChild(arrow);

        var nextElement = header.nextElementSibling;
        var count = 0;
        
        // Hide the next two sibling divs initially
        while (nextElement && count < 3) {
            if (nextElement.tagName === "DIV") {
                nextElement.classList.add("hidden");
                count++;
            }
            nextElement = nextElement.nextElementSibling;
        }

        header.addEventListener("click", function() {
            var nextElement = header.nextElementSibling;
            var count = 0;
            
            // Toggle visibility of the next two sibling divs
            while (nextElement && count < 3) {
                if (nextElement.tagName === "DIV") {
                    nextElement.classList.toggle("hidden");
                    count++;
                }
                nextElement = nextElement.nextElementSibling;
            }

            // Toggle the arrow direction
            arrow.classList.toggle("down");
            arrow.classList.toggle("up");

            // Check if the clicked header is the first header
            if (header === firstHeader) {
                var h4Element = document.querySelector(".path-mod-peerreview .submission-full > h4.my-3");
                if (h4Element) {
                    h4Element.classList.toggle("hidden");
                }
                firstHeaderClicked = !firstHeaderClicked;
            }
        });
    });

    // Hide the element .path-mod-peerreview .submission-full > h4.my-3 initially
    var h4Element = document.querySelector(".path-mod-peerreview .submission-full > h4.my-3");
    if (h4Element) {
        h4Element.classList.add("hidden");
    }

    // Add CSS to hide elements and style the arrows
    var style = document.createElement("style");
    style.innerHTML = ".hidden { display: none; }" +
                      ".arrow { margin-left: 10px; cursor: pointer; }";
    document.head.appendChild(style);
});

</script>';
}





// show instructions for submitting as thay may contain some list of questions and we need to know them
// while reading the submitted answer
if (trim($peerreview->instructauthors)) {
    $instructions = file_rewrite_pluginfile_urls($peerreview->instructauthors, 'pluginfile.php', $PAGE->context->id,
        'mod_peerreview', 'instructauthors', null, peerreview::instruction_editors_options($PAGE->context));
    print_collapsible_region_start('', 'peerreview-viewlet-instructauthors', get_string('instructauthors', 'peerreview'),
            'peerreview-viewlet-instructauthors-collapsed');
    echo $output->box(format_text($instructions, $peerreview->instructauthorsformat, array('overflowdiv'=>true)), array('generalbox', 'instructions'));
    print_collapsible_region_end();
}

// if in edit mode, display the form to edit the submission

if ($edit) {
    if (!empty($CFG->enableplagiarism)) {
        require_once($CFG->libdir.'/plagiarismlib.php');
        echo plagiarism_print_disclosure($cm->id);
    }
    $mform->display();
    echo $output->footer();
    die();
}

// Confirm deletion (if requested).
if ($deletable and $delete) {
    $prompt = get_string('submissiondeleteconfirm', 'peerreview');
    if ($candeleteall) {
        $count = count($peerreview->get_assessments_of_submission($submission->id));
        if ($count > 0) {
            $prompt = get_string('submissiondeleteconfirmassess', 'peerreview', ['count' => $count]);
        }
    }
    echo $output->confirm($prompt, new moodle_url($PAGE->url, ['delete' => 1, 'confirm' => 1]), $peerreview->view_url());
}

// else display the submission

if ($submission->id) {
    if ($seenaspublished) {
        $showauthor = has_capability('mod/peerreview:viewauthorpublished', $peerreview->context);
    } else {
        $showauthor = has_capability('mod/peerreview:viewauthornames', $peerreview->context);
    }
    echo $output->render($peerreview->prepare_submission($submission, $showauthor));
} else {
    echo $output->box(get_string('noyoursubmission', 'peerreview'));
}

// If not at removal confirmation screen, some action buttons can be displayed.
if (!$delete) {
    // Display create/edit button.
    if ($editable) {
        if ($submission->id) {
            $btnurl = new moodle_url($PAGE->url, array('edit' => 'on', 'id' => $submission->id));
            $btntxt = get_string('editsubmission', 'peerreview');
        } else {
            $btnurl = new moodle_url($PAGE->url, array('edit' => 'on'));
            $btntxt = get_string('createsubmission', 'peerreview');
        }
        echo $output->box($output->single_button($btnurl, $btntxt, 'get'), 'mr-1 inline');
    }

    // Display delete button.
    if ($submission->id and $deletable) {
        $url = new moodle_url($PAGE->url, array('delete' => 1));
        echo $output->box($output->single_button($url, get_string('deletesubmission', 'peerreview'), 'get'), 'mr-1 inline');
    }

    // Display assess button.
    if ($submission->id and !$edit and !$isreviewer and $canallocate and $peerreview->assessing_allowed($USER->id)) {
        $url = new moodle_url($PAGE->url, array('assess' => 1));
        echo $output->box($output->single_button($url, get_string('assess', 'peerreview'), 'post'), 'mr-1 inline');
    }
}

if (($peerreview->phase == peerreview::PHASE_CLOSED) and ($ownsubmission or $canviewall)) {
    if (!empty($submission->gradeoverby) and strlen(trim($submission->feedbackauthor)) > 0) {
        echo $output->render(new peerreview_feedback_author($submission));
    }
}

// and possibly display the submission's review(s)

if ($isreviewer) {
    // user's own assessment
    $strategy   = $peerreview->grading_strategy_instance();
    $mform      = $strategy->get_assessment_form($PAGE->url, 'assessment', $userassessment, false);
    $options    = array(
        'showreviewer'  => true,
        'showauthor'    => $showauthor,
        'showform'      => !is_null($userassessment->grade),
        'showweight'    => true,
    );
    $assessment = $peerreview->prepare_assessment($userassessment, $mform, $options);

    if ($peerreview->assessing_allowed($USER->id)) {
        if (is_null($userassessment->grade)) {
            $assessment->add_action($peerreview->assess_url($assessment->id), get_string('assess', 'peerreview'));
        } else {
            $assessment->add_action($peerreview->assess_url($assessment->id), get_string('reassess', 'peerreview'));
        }
    }
    if ($canoverride) {
        $assessment->add_action($peerreview->assess_url($assessment->id), get_string('assessmentsettings', 'peerreview'));
    }

    echo $output->render($assessment);

    // EDIT: Remove this to avoid error message
    // if ($peerreview->phase == peerreview::PHASE_CLOSED) {
    //     if (strlen(trim($userassessment->feedbackreviewer)) > 0) {
    //         echo $output->render(new peerreview_feedback_reviewer($userassessment));
    //     }
    // }
}

if (has_capability('mod/peerreview:viewallassessments', $peerreview->context) or ($ownsubmission and $peerreview->assessments_available())) {
    // other assessments
    $strategy       = $peerreview->grading_strategy_instance();
    $assessments    = $peerreview->get_assessments_of_submission($submission->id);
    $showreviewer   = has_capability('mod/peerreview:viewreviewernames', $peerreview->context);
    foreach ($assessments as $assessment) {
        if ($assessment->reviewerid == $USER->id) {
            // own assessment has been displayed already
            continue;
        }
        if (is_null($assessment->grade) and !has_capability('mod/peerreview:viewallassessments', $peerreview->context)) {
            // students do not see peer-assessment that are not graded yet
            continue;
        }
        $mform      = $strategy->get_assessment_form($PAGE->url, 'assessment', $assessment, false);
        $options    = array(
            'showreviewer'  => $showreviewer,
            'showauthor'    => $showauthor,
            'showform'      => !is_null($assessment->grade),
            'showweight'    => true,
        );
        $displayassessment = $peerreview->prepare_assessment($assessment, $mform, $options);
        if ($canoverride) {
            $displayassessment->add_action($peerreview->assess_url($assessment->id), get_string('assessmentsettings', 'peerreview'));
        }
        echo $output->render($displayassessment);

        if ($peerreview->phase == peerreview::PHASE_CLOSED and has_capability('mod/peerreview:viewallassessments', $peerreview->context)) {
            if (strlen(trim($assessment->feedbackreviewer)) > 0) {
                echo $output->render(new peerreview_feedback_reviewer($assessment));
            }
        }
    }
}

if (!$edit and $canoverride) {
    // display a form to override the submission grade
    $feedbackform->display();
}

// If portfolios are enabled and we are not on the edit/removal confirmation screen, display a button to export this page.
// The export is not offered if the submission is seen as a published one (it has no relation to the current user.
if (!empty($CFG->enableportfolios)) {
    if (!$delete and !$edit and !$seenaspublished and $submission->id and ($ownsubmission or $canviewall or $isreviewer)) {
        if (has_capability('mod/peerreview:exportsubmissions', $peerreview->context)) {
            require_once($CFG->libdir.'/portfoliolib.php');

            $button = new portfolio_add_button();
            $button->set_callback_options('mod_peerreview_portfolio_caller', array(
                'id' => $peerreview->cm->id,
                'submissionid' => $submission->id,
            ), 'mod_peerreview');
            $button->set_formats(PORTFOLIO_FORMAT_RICHHTML);
            echo html_writer::start_tag('div', array('class' => 'singlebutton'));
            echo $button->to_html(PORTFOLIO_ADD_FULL_FORM, get_string('exportsubmission', 'peerreview'));
            echo html_writer::end_tag('div');
        }
    }
}

echo $output->footer();
