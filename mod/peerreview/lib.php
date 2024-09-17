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
 * Library of peerreview module functions needed by Moodle core and other subsystems
 *
 * All the functions neeeded by Moodle core, gradebook, file subsystem etc
 * are placed here.
 *
 * @package    mod_peerreview
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/calendar/lib.php');

define('peerreview_EVENT_TYPE_SUBMISSION_OPEN',   'opensubmission');
define('peerreview_EVENT_TYPE_SUBMISSION_CLOSE',  'closesubmission');
define('peerreview_EVENT_TYPE_ASSESSMENT_OPEN',   'openassessment');
define('peerreview_EVENT_TYPE_ASSESSMENT_CLOSE',  'closeassessment');
define('peerreview_SUBMISSION_TYPE_DISABLED', 0);
define('peerreview_SUBMISSION_TYPE_AVAILABLE', 1);
define('peerreview_SUBMISSION_TYPE_REQUIRED', 2);

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information if the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function peerreview_supports($feature) {
    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE:   return true;
        case FEATURE_GROUPS:            return true;
        case FEATURE_GROUPINGS:         return true;
        case FEATURE_MOD_INTRO:         return true;
        case FEATURE_BACKUP_MOODLE2:    return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_SHOW_DESCRIPTION:  return true;
        case FEATURE_PLAGIARISM:        return true;
        case FEATURE_MOD_PURPOSE:       return MOD_PURPOSE_ASSESSMENT;
        default:                        return null;
    }
}

/**
 * Saves a new instance of the peerreview into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will save a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $peerreview An object from the form in mod_form.php
 * @return int The id of the newly inserted peerreview record
 */
function peerreview_add_instance(stdclass $peerreview) {
    global $CFG, $DB;
    require_once(__DIR__ . '/locallib.php');

    $peerreview->phase                 = peerreview::PHASE_SUBMISSION;
    $peerreview->timecreated           = time();
    $peerreview->timemodified          = $peerreview->timecreated;
    $peerreview->useexamples           = (int)!empty($peerreview->useexamples);
    $peerreview->usepeerassessment     = 1;
    $peerreview->useselfassessment     = (int)!empty($peerreview->useselfassessment);
    $peerreview->latesubmissions       = (int)!empty($peerreview->latesubmissions);
    $peerreview->phaseswitchassessment = (int)!empty($peerreview->phaseswitchassessment);
    $peerreview->evaluation            = 'best';

    if (isset($peerreview->gradinggradepass)) {
        $peerreview->gradinggradepass = (float)unformat_float($peerreview->gradinggradepass);
    }

    if (isset($peerreview->submissiongradepass)) {
        $peerreview->submissiongradepass = (float)unformat_float($peerreview->submissiongradepass);
    }

    if (isset($peerreview->submissionfiletypes)) {
        $filetypesutil = new \core_form\filetypes_util();
        $submissionfiletypes = $filetypesutil->normalize_file_types($peerreview->submissionfiletypes);
        $peerreview->submissionfiletypes = implode(' ', $submissionfiletypes);
    }

    if (isset($peerreview->overallfeedbackfiletypes)) {
        $filetypesutil = new \core_form\filetypes_util();
        $overallfeedbackfiletypes = $filetypesutil->normalize_file_types($peerreview->overallfeedbackfiletypes);
        $peerreview->overallfeedbackfiletypes = implode(' ', $overallfeedbackfiletypes);
    }

    // insert the new record so we get the id
    $peerreview->id = $DB->insert_record('peerreview', $peerreview);

    // we need to use context now, so we need to make sure all needed info is already in db
    $cmid = $peerreview->coursemodule;
    $DB->set_field('course_modules', 'instance', $peerreview->id, array('id' => $cmid));
    $context = context_module::instance($cmid);

    // process the custom wysiwyg editors
    if ($draftitemid = $peerreview->instructauthorseditor['itemid']) {
        $peerreview->instructauthors = file_save_draft_area_files($draftitemid, $context->id, 'mod_peerreview', 'instructauthors',
                0, peerreview::instruction_editors_options($context), $peerreview->instructauthorseditor['text']);
        $peerreview->instructauthorsformat = $peerreview->instructauthorseditor['format'];
    }

    if ($draftitemid = $peerreview->instructreviewerseditor['itemid']) {
        $peerreview->instructreviewers = file_save_draft_area_files($draftitemid, $context->id, 'mod_peerreview', 'instructreviewers',
                0, peerreview::instruction_editors_options($context), $peerreview->instructreviewerseditor['text']);
        $peerreview->instructreviewersformat = $peerreview->instructreviewerseditor['format'];
    }

    if ($draftitemid = $peerreview->conclusioneditor['itemid']) {
        $peerreview->conclusion = file_save_draft_area_files($draftitemid, $context->id, 'mod_peerreview', 'conclusion',
                0, peerreview::instruction_editors_options($context), $peerreview->conclusioneditor['text']);
        $peerreview->conclusionformat = $peerreview->conclusioneditor['format'];
    }

    // re-save the record with the replaced URLs in editor fields
    $DB->update_record('peerreview', $peerreview);

    // create gradebook items
    peerreview_grade_item_update($peerreview);
    peerreview_grade_item_category_update($peerreview);

    // create calendar events
    peerreview_calendar_update($peerreview, $peerreview->coursemodule);
    if (!empty($peerreview->completionexpected)) {
        \core_completion\api::update_completion_date_event($cmid, 'peerreview', $peerreview->id, $peerreview->completionexpected);
    }

    return $peerreview->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $peerreview An object from the form in mod_form.php
 * @return bool success
 */
function peerreview_update_instance(stdclass $peerreview) {
    global $CFG, $DB;
    require_once(__DIR__ . '/locallib.php');

    $peerreview->timemodified          = time();
    $peerreview->id                    = $peerreview->instance;
    $peerreview->useexamples           = (int)!empty($peerreview->useexamples);
    $peerreview->usepeerassessment     = 1;
    $peerreview->useselfassessment     = (int)!empty($peerreview->useselfassessment);
    $peerreview->latesubmissions       = (int)!empty($peerreview->latesubmissions);
    $peerreview->phaseswitchassessment = (int)!empty($peerreview->phaseswitchassessment);

    if (isset($peerreview->gradinggradepass)) {
        $peerreview->gradinggradepass = (float)unformat_float($peerreview->gradinggradepass);
    }

    if (isset($peerreview->submissiongradepass)) {
        $peerreview->submissiongradepass = (float)unformat_float($peerreview->submissiongradepass);
    }

    if (isset($peerreview->submissionfiletypes)) {
        $filetypesutil = new \core_form\filetypes_util();
        $submissionfiletypes = $filetypesutil->normalize_file_types($peerreview->submissionfiletypes);
        $peerreview->submissionfiletypes = implode(' ', $submissionfiletypes);
    }

    if (isset($peerreview->overallfeedbackfiletypes)) {
        $filetypesutil = new \core_form\filetypes_util();
        $overallfeedbackfiletypes = $filetypesutil->normalize_file_types($peerreview->overallfeedbackfiletypes);
        $peerreview->overallfeedbackfiletypes = implode(' ', $overallfeedbackfiletypes);
    }

    // todo - if the grading strategy is being changed, we may want to replace all aggregated peer grades with nulls

    $DB->update_record('peerreview', $peerreview);
    $context = context_module::instance($peerreview->coursemodule);

    // process the custom wysiwyg editors
    if ($draftitemid = $peerreview->instructauthorseditor['itemid']) {
        $peerreview->instructauthors = file_save_draft_area_files($draftitemid, $context->id, 'mod_peerreview', 'instructauthors',
                0, peerreview::instruction_editors_options($context), $peerreview->instructauthorseditor['text']);
        $peerreview->instructauthorsformat = $peerreview->instructauthorseditor['format'];
    }

    if ($draftitemid = $peerreview->instructreviewerseditor['itemid']) {
        $peerreview->instructreviewers = file_save_draft_area_files($draftitemid, $context->id, 'mod_peerreview', 'instructreviewers',
                0, peerreview::instruction_editors_options($context), $peerreview->instructreviewerseditor['text']);
        $peerreview->instructreviewersformat = $peerreview->instructreviewerseditor['format'];
    }

    if ($draftitemid = $peerreview->conclusioneditor['itemid']) {
        $peerreview->conclusion = file_save_draft_area_files($draftitemid, $context->id, 'mod_peerreview', 'conclusion',
                0, peerreview::instruction_editors_options($context), $peerreview->conclusioneditor['text']);
        $peerreview->conclusionformat = $peerreview->conclusioneditor['format'];
    }

    // re-save the record with the replaced URLs in editor fields
    $DB->update_record('peerreview', $peerreview);

    // update gradebook items
    peerreview_grade_item_update($peerreview);
    peerreview_grade_item_category_update($peerreview);

    // update calendar events
    peerreview_calendar_update($peerreview, $peerreview->coursemodule);
    $completionexpected = (!empty($peerreview->completionexpected)) ? $peerreview->completionexpected : null;
    \core_completion\api::update_completion_date_event($peerreview->coursemodule, 'peerreview', $peerreview->id, $completionexpected);

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function peerreview_delete_instance($id) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if (! $peerreview = $DB->get_record('peerreview', array('id' => $id))) {
        return false;
    }

    // delete all associated aggregations
    $DB->delete_records('peerreview_aggregations', array('peerreviewid' => $peerreview->id));

    // get the list of ids of all submissions
    $submissions = $DB->get_records('peerreview_submissions', array('peerreviewid' => $peerreview->id), '', 'id');

    // get the list of all allocated assessments
    $assessments = $DB->get_records_list('peerreview_assessments', 'submissionid', array_keys($submissions), '', 'id');

    // delete the associated records from the peerreview core tables
    $DB->delete_records_list('peerreview_grades', 'assessmentid', array_keys($assessments));
    $DB->delete_records_list('peerreview_assessments', 'id', array_keys($assessments));
    $DB->delete_records_list('peerreview_submissions', 'id', array_keys($submissions));

    // call the static clean-up methods of all available subplugins
    $strategies = core_component::get_plugin_list('peerreviewform');
    foreach ($strategies as $strategy => $path) {
        require_once($path.'/lib.php');
        $classname = 'peerreview_'.$strategy.'_strategy';
        call_user_func($classname.'::delete_instance', $peerreview->id);
    }

    $allocators = core_component::get_plugin_list('peerreviewallocation');
    foreach ($allocators as $allocator => $path) {
        require_once($path.'/lib.php');
        $classname = 'peerreview_'.$allocator.'_allocator';
        call_user_func($classname.'::delete_instance', $peerreview->id);
    }

    $evaluators = core_component::get_plugin_list('peerrevieweval');
    foreach ($evaluators as $evaluator => $path) {
        require_once($path.'/lib.php');
        $classname = 'peerreview_'.$evaluator.'_evaluation';
        call_user_func($classname.'::delete_instance', $peerreview->id);
    }

    // delete the calendar events
    $events = $DB->get_records('event', array('modulename' => 'peerreview', 'instance' => $peerreview->id));
    foreach ($events as $event) {
        $event = calendar_event::load($event);
        $event->delete();
    }

    // gradebook cleanup
    grade_update('mod/peerreview', $peerreview->course, 'mod', 'peerreview', $peerreview->id, 0, null, array('deleted' => true));
    grade_update('mod/peerreview', $peerreview->course, 'mod', 'peerreview', $peerreview->id, 1, null, array('deleted' => true));

    // finally remove the peerreview record itself
    // We must delete the module record after we delete the grade item.
    $DB->delete_records('peerreview', array('id' => $peerreview->id));

    return true;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every peerreview event in the site is checked, else
 * only peerreview events belonging to the course specified are checked.
 *
 * @param  integer $courseid The Course ID.
 * @param int|stdClass $instance peerreview module instance or ID.
 * @param int|stdClass $cm Course module object or ID.
 * @return bool Returns true if the calendar events were successfully updated.
 */
function peerreview_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $DB;

    // If we have instance information then we can just update the one event instead of updating all events.
    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('peerreview', array('id' => $instance), '*', MUST_EXIST);
        }
        if (isset($cm)) {
            if (!is_object($cm)) {
                $cm = (object)array('id' => $cm);
            }
        } else {
            $cm = get_coursemodule_from_instance('peerreview', $instance->id);
        }
        peerreview_calendar_update($instance, $cm->id);
        return true;
    }

    if ($courseid) {
        // Make sure that the course id is numeric.
        if (!is_numeric($courseid)) {
            return false;
        }
        if (!$peerreviews = $DB->get_records('peerreview', array('course' => $courseid))) {
            return false;
        }
    } else {
        if (!$peerreviews = $DB->get_records('peerreview')) {
            return false;
        }
    }
    foreach ($peerreviews as $peerreview) {
        if (!$cm = get_coursemodule_from_instance('peerreview', $peerreview->id, $courseid, false)) {
            continue;
        }
        peerreview_calendar_update($peerreview, $cm->id);
    }
    return true;
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function peerreview_get_view_actions() {
    return array('view', 'view all', 'view submission', 'view example');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function peerreview_get_post_actions() {
    return array('add', 'add assessment', 'add example', 'add submission',
                 'update', 'update assessment', 'update example', 'update submission');
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record.
 * @param stdClass $user The user record.
 * @param cm_info|stdClass $mod The course module info object or record.
 * @param stdClass $peerreview The peerreview instance record.
 * @return stdclass|null
 */
function peerreview_user_outline($course, $user, $mod, $peerreview) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    $grades = grade_get_grades($course->id, 'mod', 'peerreview', $peerreview->id, $user->id);

    $submissiongrade = null;
    $assessmentgrade = null;

    $info = '';
    $time = 0;

    if (!empty($grades->items[0]->grades)) {
        $submissiongrade = reset($grades->items[0]->grades);
        $time = max($time, $submissiongrade->dategraded);
        if (!$submissiongrade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
            $info .= get_string('submissiongrade', 'peerreview') . ': ' . $submissiongrade->str_long_grade
                . html_writer::empty_tag('br');
        } else {
            $info .= get_string('submissiongrade', 'peerreview') . ': ' . get_string('hidden', 'grades')
                . html_writer::empty_tag('br');
        }
    }
    if (!empty($grades->items[1]->grades)) {
        $assessmentgrade = reset($grades->items[1]->grades);
        $time = max($time, $assessmentgrade->dategraded);
        if (!$assessmentgrade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
            $info .= get_string('gradinggrade', 'peerreview') . ': ' . $assessmentgrade->str_long_grade;
        } else {
            $info .= get_string('gradinggrade', 'peerreview') . ': ' . get_string('hidden', 'grades');
        }
    }

    if (!empty($info) and !empty($time)) {
        $return = new stdclass();
        $return->time = $time;
        $return->info = $info;
        return $return;
    }

    return null;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course The course record.
 * @param stdClass $user The user record.
 * @param cm_info|stdClass $mod The course module info object or record.
 * @param stdClass $peerreview The peerreview instance record.
 * @return string HTML
 */
function peerreview_user_complete($course, $user, $mod, $peerreview) {
    global $CFG, $DB, $OUTPUT;
    require_once(__DIR__.'/locallib.php');
    require_once($CFG->libdir.'/gradelib.php');

    $peerreview   = new peerreview($peerreview, $mod, $course);
    $grades     = grade_get_grades($course->id, 'mod', 'peerreview', $peerreview->id, $user->id);

    if (!empty($grades->items[0]->grades)) {
        $submissiongrade = reset($grades->items[0]->grades);
        if (!$submissiongrade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
            $info = get_string('submissiongrade', 'peerreview') . ': ' . $submissiongrade->str_long_grade;
        } else {
            $info = get_string('submissiongrade', 'peerreview') . ': ' . get_string('hidden', 'grades');
        }
        echo html_writer::tag('li', $info, array('class'=>'submissiongrade'));
    }
    if (!empty($grades->items[1]->grades)) {
        $assessmentgrade = reset($grades->items[1]->grades);
        if (!$assessmentgrade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
            $info = get_string('gradinggrade', 'peerreview') . ': ' . $assessmentgrade->str_long_grade;
        } else {
            $info = get_string('gradinggrade', 'peerreview') . ': ' . get_string('hidden', 'grades');
        }
        echo html_writer::tag('li', $info, array('class'=>'gradinggrade'));
    }

    if (has_capability('mod/peerreview:viewallsubmissions', $peerreview->context)) {
        $canviewsubmission = true;
        if (groups_get_activity_groupmode($peerreview->cm) == SEPARATEGROUPS) {
            // user must have accessallgroups or share at least one group with the submission author
            if (!has_capability('moodle/site:accessallgroups', $peerreview->context)) {
                $usersgroups = groups_get_activity_allowed_groups($peerreview->cm);
                $authorsgroups = groups_get_all_groups($peerreview->course->id, $user->id, $peerreview->cm->groupingid, 'g.id');
                $sharedgroups = array_intersect_key($usersgroups, $authorsgroups);
                if (empty($sharedgroups)) {
                    $canviewsubmission = false;
                }
            }
        }
        if ($canviewsubmission and $submission = $peerreview->get_submission_by_author($user->id)) {
            $title      = format_string($submission->title);
            $url        = $peerreview->submission_url($submission->id);
            $link       = html_writer::link($url, $title);
            $info       = get_string('submission', 'peerreview').': '.$link;
            echo html_writer::tag('li', $info, array('class'=>'submission'));
        }
    }

    if (has_capability('mod/peerreview:viewallassessments', $peerreview->context)) {
        if ($assessments = $peerreview->get_assessments_by_reviewer($user->id)) {
            foreach ($assessments as $assessment) {
                $a = new stdclass();
                $a->submissionurl = $peerreview->submission_url($assessment->submissionid)->out();
                $a->assessmenturl = $peerreview->assess_url($assessment->id)->out();
                $a->submissiontitle = s($assessment->submissiontitle);
                echo html_writer::tag('li', get_string('assessmentofsubmission', 'peerreview', $a));
            }
        }
    }
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in peerreview activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param stdClass $course
 * @param bool $viewfullnames
 * @param int $timestart
 * @return boolean
 */
function peerreview_print_recent_activity($course, $viewfullnames, $timestart) {
    global $CFG, $USER, $DB, $OUTPUT;

    $userfieldsapi = \core_user\fields::for_name();
    $authoramefields = $userfieldsapi->get_sql('author', false, 'author', '', false)->selects;
    $reviewerfields = $userfieldsapi->get_sql('reviewer', false, 'reviewer', '', false)->selects;

    $sql = "SELECT s.id AS submissionid, s.title AS submissiontitle, s.timemodified AS submissionmodified,
                   author.id AS authorid, $authoramefields, a.id AS assessmentid, a.timemodified AS assessmentmodified,
                   reviewer.id AS reviewerid, $reviewerfields, cm.id AS cmid
              FROM {peerreview} w
        INNER JOIN {course_modules} cm ON cm.instance = w.id
        INNER JOIN {modules} md ON md.id = cm.module
        INNER JOIN {peerreview_submissions} s ON s.peerreviewid = w.id
        INNER JOIN {user} author ON s.authorid = author.id
         LEFT JOIN {peerreview_assessments} a ON a.submissionid = s.id
         LEFT JOIN {user} reviewer ON a.reviewerid = reviewer.id
             WHERE cm.course = ?
                   AND md.name = 'peerreview'
                   AND s.example = 0
                   AND (s.timemodified > ? OR a.timemodified > ?)
          ORDER BY s.timemodified";

    $rs = $DB->get_recordset_sql($sql, array($course->id, $timestart, $timestart));

    $modinfo = get_fast_modinfo($course); // reference needed because we might load the groups

    $submissions = array(); // recent submissions indexed by submission id
    $assessments = array(); // recent assessments indexed by assessment id
    $users       = array();

    foreach ($rs as $activity) {
        if (!array_key_exists($activity->cmid, $modinfo->cms)) {
            // this should not happen but just in case
            continue;
        }

        $cm = $modinfo->cms[$activity->cmid];
        if (!$cm->uservisible) {
            continue;
        }

        // remember all user names we can use later
        if (empty($users[$activity->authorid])) {
            $u = new stdclass();
            $users[$activity->authorid] = username_load_fields_from_object($u, $activity, 'author');
        }
        if ($activity->reviewerid and empty($users[$activity->reviewerid])) {
            $u = new stdclass();
            $users[$activity->reviewerid] = username_load_fields_from_object($u, $activity, 'reviewer');
        }

        $context = context_module::instance($cm->id);
        $groupmode = groups_get_activity_groupmode($cm, $course);

        if ($activity->submissionmodified > $timestart and empty($submissions[$activity->submissionid])) {
            $s = new stdclass();
            $s->title = $activity->submissiontitle;
            $s->authorid = $activity->authorid;
            $s->timemodified = $activity->submissionmodified;
            $s->cmid = $activity->cmid;
            if ($activity->authorid == $USER->id || has_capability('mod/peerreview:viewauthornames', $context)) {
                $s->authornamevisible = true;
            } else {
                $s->authornamevisible = false;
            }

            // the following do-while wrapper allows to break from deeply nested if-statements
            do {
                if ($s->authorid === $USER->id) {
                    // own submissions always visible
                    $submissions[$activity->submissionid] = $s;
                    break;
                }

                if (has_capability('mod/peerreview:viewallsubmissions', $context)) {
                    if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
                        if (isguestuser()) {
                            // shortcut - guest user does not belong into any group
                            break;
                        }

                        // this might be slow - show only submissions by users who share group with me in this cm
                        if (!$modinfo->get_groups($cm->groupingid)) {
                            break;
                        }
                        $authorsgroups = groups_get_all_groups($course->id, $s->authorid, $cm->groupingid);
                        if (is_array($authorsgroups)) {
                            $authorsgroups = array_keys($authorsgroups);
                            $intersect = array_intersect($authorsgroups, $modinfo->get_groups($cm->groupingid));
                            if (empty($intersect)) {
                                break;
                            } else {
                                // can see all submissions and shares a group with the author
                                $submissions[$activity->submissionid] = $s;
                                break;
                            }
                        }

                    } else {
                        // can see all submissions from all groups
                        $submissions[$activity->submissionid] = $s;
                    }
                }
            } while (0);
        }

        if ($activity->assessmentmodified > $timestart and empty($assessments[$activity->assessmentid])) {
            $a = new stdclass();
            $a->submissionid = $activity->submissionid;
            $a->submissiontitle = $activity->submissiontitle;
            $a->reviewerid = $activity->reviewerid;
            $a->timemodified = $activity->assessmentmodified;
            $a->cmid = $activity->cmid;
            if ($activity->reviewerid == $USER->id || has_capability('mod/peerreview:viewreviewernames', $context)) {
                $a->reviewernamevisible = true;
            } else {
                $a->reviewernamevisible = false;
            }

            // the following do-while wrapper allows to break from deeply nested if-statements
            do {
                if ($a->reviewerid === $USER->id) {
                    // own assessments always visible
                    $assessments[$activity->assessmentid] = $a;
                    break;
                }

                if (has_capability('mod/peerreview:viewallassessments', $context)) {
                    if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
                        if (isguestuser()) {
                            // shortcut - guest user does not belong into any group
                            break;
                        }

                        // this might be slow - show only submissions by users who share group with me in this cm
                        if (!$modinfo->get_groups($cm->groupingid)) {
                            break;
                        }
                        $reviewersgroups = groups_get_all_groups($course->id, $a->reviewerid, $cm->groupingid);
                        if (is_array($reviewersgroups)) {
                            $reviewersgroups = array_keys($reviewersgroups);
                            $intersect = array_intersect($reviewersgroups, $modinfo->get_groups($cm->groupingid));
                            if (empty($intersect)) {
                                break;
                            } else {
                                // can see all assessments and shares a group with the reviewer
                                $assessments[$activity->assessmentid] = $a;
                                break;
                            }
                        }

                    } else {
                        // can see all assessments from all groups
                        $assessments[$activity->assessmentid] = $a;
                    }
                }
            } while (0);
        }
    }
    $rs->close();

    $shown = false;

    if (!empty($submissions)) {
        $shown = true;
        echo $OUTPUT->heading(get_string('recentsubmissions', 'peerreview') . ':', 6);
        foreach ($submissions as $id => $submission) {
            $link = new moodle_url('/mod/peerreview/submission.php', array('id'=>$id, 'cmid'=>$submission->cmid));
            if ($submission->authornamevisible) {
                $author = $users[$submission->authorid];
            } else {
                $author = null;
            }
            print_recent_activity_note($submission->timemodified, $author, $submission->title, $link->out(), false, $viewfullnames);
        }
    }

    if (!empty($assessments)) {
        $shown = true;
        echo $OUTPUT->heading(get_string('recentassessments', 'peerreview') . ':', 6);
        core_collator::asort_objects_by_property($assessments, 'timemodified');
        foreach ($assessments as $id => $assessment) {
            $link = new moodle_url('/mod/peerreview/assessment.php', array('asid' => $id));
            if ($assessment->reviewernamevisible) {
                $reviewer = $users[$assessment->reviewerid];
            } else {
                $reviewer = null;
            }
            print_recent_activity_note($assessment->timemodified, $reviewer, $assessment->submissiontitle, $link->out(), false, $viewfullnames);
        }
    }

    if ($shown) {
        return true;
    }

    return false;
}

/**
 * Returns all activity in course peerreviews since a given time
 *
 * @param array $activities sequentially indexed array of objects
 * @param int $index
 * @param int $timestart
 * @param int $courseid
 * @param int $cmid
 * @param int $userid defaults to 0
 * @param int $groupid defaults to 0
 * @return void adds items into $activities and increases $index
 */
function peerreview_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
    global $CFG, $COURSE, $USER, $DB;

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id'=>$courseid));
    }

    $modinfo = get_fast_modinfo($course);

    $cm = $modinfo->cms[$cmid];

    $params = array();
    if ($userid) {
        $userselect = "AND (author.id = :authorid OR reviewer.id = :reviewerid)";
        $params['authorid'] = $userid;
        $params['reviewerid'] = $userid;
    } else {
        $userselect = "";
    }

    if ($groupid) {
        $groupselect = "AND (authorgroupmembership.groupid = :authorgroupid OR reviewergroupmembership.groupid = :reviewergroupid)";
        $groupjoin   = "LEFT JOIN {groups_members} authorgroupmembership ON authorgroupmembership.userid = author.id
                        LEFT JOIN {groups_members} reviewergroupmembership ON reviewergroupmembership.userid = reviewer.id";
        $params['authorgroupid'] = $groupid;
        $params['reviewergroupid'] = $groupid;
    } else {
        $groupselect = "";
        $groupjoin   = "";
    }

    $params['cminstance'] = $cm->instance;
    $params['submissionmodified'] = $timestart;
    $params['assessmentmodified'] = $timestart;

    $userfieldsapi = \core_user\fields::for_name();
    $authornamefields = $userfieldsapi->get_sql('author', false, 'author', '', false)->selects;
    $reviewerfields = $userfieldsapi->get_sql('reviewer', false, 'reviewer', '', false)->selects;

    $sql = "SELECT s.id AS submissionid, s.title AS submissiontitle, s.timemodified AS submissionmodified,
                   author.id AS authorid, $authornamefields, author.picture AS authorpicture, author.imagealt AS authorimagealt,
                   author.email AS authoremail, a.id AS assessmentid, a.timemodified AS assessmentmodified,
                   reviewer.id AS reviewerid, $reviewerfields, reviewer.picture AS reviewerpicture,
                   reviewer.imagealt AS reviewerimagealt, reviewer.email AS revieweremail
              FROM {peerreview_submissions} s
        INNER JOIN {peerreview} w ON s.peerreviewid = w.id
        INNER JOIN {user} author ON s.authorid = author.id
         LEFT JOIN {peerreview_assessments} a ON a.submissionid = s.id
         LEFT JOIN {user} reviewer ON a.reviewerid = reviewer.id
        $groupjoin
             WHERE w.id = :cminstance
                   AND s.example = 0
                   $userselect $groupselect
                   AND (s.timemodified > :submissionmodified OR a.timemodified > :assessmentmodified)
          ORDER BY s.timemodified ASC, a.timemodified ASC";

    $rs = $DB->get_recordset_sql($sql, $params);

    $groupmode       = groups_get_activity_groupmode($cm, $course);
    $context         = context_module::instance($cm->id);
    $grader          = has_capability('moodle/grade:viewall', $context);
    $accessallgroups = has_capability('moodle/site:accessallgroups', $context);
    $viewauthors     = has_capability('mod/peerreview:viewauthornames', $context);
    $viewreviewers   = has_capability('mod/peerreview:viewreviewernames', $context);

    $submissions = array(); // recent submissions indexed by submission id
    $assessments = array(); // recent assessments indexed by assessment id
    $users       = array();

    foreach ($rs as $activity) {

        // remember all user names we can use later
        if (empty($users[$activity->authorid])) {
            $u = new stdclass();
            $additionalfields = explode(',', implode(',', \core_user\fields::get_picture_fields()));
            $u = username_load_fields_from_object($u, $activity, 'author', $additionalfields);
            $users[$activity->authorid] = $u;
        }
        if ($activity->reviewerid and empty($users[$activity->reviewerid])) {
            $u = new stdclass();
            $additionalfields = explode(',', implode(',', \core_user\fields::get_picture_fields()));
            $u = username_load_fields_from_object($u, $activity, 'reviewer', $additionalfields);
            $users[$activity->reviewerid] = $u;
        }

        if ($activity->submissionmodified > $timestart and empty($submissions[$activity->submissionid])) {
            $s = new stdclass();
            $s->id = $activity->submissionid;
            $s->title = $activity->submissiontitle;
            $s->authorid = $activity->authorid;
            $s->timemodified = $activity->submissionmodified;
            if ($activity->authorid == $USER->id || has_capability('mod/peerreview:viewauthornames', $context)) {
                $s->authornamevisible = true;
            } else {
                $s->authornamevisible = false;
            }

            // the following do-while wrapper allows to break from deeply nested if-statements
            do {
                if ($s->authorid === $USER->id) {
                    // own submissions always visible
                    $submissions[$activity->submissionid] = $s;
                    break;
                }

                if (has_capability('mod/peerreview:viewallsubmissions', $context)) {
                    if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
                        if (isguestuser()) {
                            // shortcut - guest user does not belong into any group
                            break;
                        }

                        // this might be slow - show only submissions by users who share group with me in this cm
                        if (!$modinfo->get_groups($cm->groupingid)) {
                            break;
                        }
                        $authorsgroups = groups_get_all_groups($course->id, $s->authorid, $cm->groupingid);
                        if (is_array($authorsgroups)) {
                            $authorsgroups = array_keys($authorsgroups);
                            $intersect = array_intersect($authorsgroups, $modinfo->get_groups($cm->groupingid));
                            if (empty($intersect)) {
                                break;
                            } else {
                                // can see all submissions and shares a group with the author
                                $submissions[$activity->submissionid] = $s;
                                break;
                            }
                        }

                    } else {
                        // can see all submissions from all groups
                        $submissions[$activity->submissionid] = $s;
                    }
                }
            } while (0);
        }

        if ($activity->assessmentmodified > $timestart and empty($assessments[$activity->assessmentid])) {
            $a = new stdclass();
            $a->id = $activity->assessmentid;
            $a->submissionid = $activity->submissionid;
            $a->submissiontitle = $activity->submissiontitle;
            $a->reviewerid = $activity->reviewerid;
            $a->timemodified = $activity->assessmentmodified;
            if ($activity->reviewerid == $USER->id || has_capability('mod/peerreview:viewreviewernames', $context)) {
                $a->reviewernamevisible = true;
            } else {
                $a->reviewernamevisible = false;
            }

            // the following do-while wrapper allows to break from deeply nested if-statements
            do {
                if ($a->reviewerid === $USER->id) {
                    // own assessments always visible
                    $assessments[$activity->assessmentid] = $a;
                    break;
                }

                if (has_capability('mod/peerreview:viewallassessments', $context)) {
                    if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
                        if (isguestuser()) {
                            // shortcut - guest user does not belong into any group
                            break;
                        }

                        // this might be slow - show only submissions by users who share group with me in this cm
                        if (!$modinfo->get_groups($cm->groupingid)) {
                            break;
                        }
                        $reviewersgroups = groups_get_all_groups($course->id, $a->reviewerid, $cm->groupingid);
                        if (is_array($reviewersgroups)) {
                            $reviewersgroups = array_keys($reviewersgroups);
                            $intersect = array_intersect($reviewersgroups, $modinfo->get_groups($cm->groupingid));
                            if (empty($intersect)) {
                                break;
                            } else {
                                // can see all assessments and shares a group with the reviewer
                                $assessments[$activity->assessmentid] = $a;
                                break;
                            }
                        }

                    } else {
                        // can see all assessments from all groups
                        $assessments[$activity->assessmentid] = $a;
                    }
                }
            } while (0);
        }
    }
    $rs->close();

    $peerreviewname = format_string($cm->name, true);

    if ($grader) {
        require_once($CFG->libdir.'/gradelib.php');
        $grades = grade_get_grades($courseid, 'mod', 'peerreview', $cm->instance, array_keys($users));
    }

    foreach ($submissions as $submission) {
        $tmpactivity                = new stdclass();
        $tmpactivity->type          = 'peerreview';
        $tmpactivity->cmid          = $cm->id;
        $tmpactivity->name          = $peerreviewname;
        $tmpactivity->sectionnum    = $cm->sectionnum;
        $tmpactivity->timestamp     = $submission->timemodified;
        $tmpactivity->subtype       = 'submission';
        $tmpactivity->content       = $submission;
        if ($grader) {
            $tmpactivity->grade     = $grades->items[0]->grades[$submission->authorid]->str_long_grade;
        }
        if ($submission->authornamevisible and !empty($users[$submission->authorid])) {
            $tmpactivity->user      = $users[$submission->authorid];
        }
        $activities[$index++]       = $tmpactivity;
    }

    foreach ($assessments as $assessment) {
        $tmpactivity                = new stdclass();
        $tmpactivity->type          = 'peerreview';
        $tmpactivity->cmid          = $cm->id;
        $tmpactivity->name          = $peerreviewname;
        $tmpactivity->sectionnum    = $cm->sectionnum;
        $tmpactivity->timestamp     = $assessment->timemodified;
        $tmpactivity->subtype       = 'assessment';
        $tmpactivity->content       = $assessment;
        if ($grader) {
            $tmpactivity->grade     = $grades->items[1]->grades[$assessment->reviewerid]->str_long_grade;
        }
        if ($assessment->reviewernamevisible and !empty($users[$assessment->reviewerid])) {
            $tmpactivity->user      = $users[$assessment->reviewerid];
        }
        $activities[$index++]       = $tmpactivity;
    }
}

/**
 * Print single activity item prepared by {@see peerreview_get_recent_mod_activity()}
 */
function peerreview_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    global $CFG, $OUTPUT;

    if (!empty($activity->user)) {
        echo html_writer::tag('div', $OUTPUT->user_picture($activity->user, array('courseid'=>$courseid)),
                array('style' => 'float: left; padding: 7px;'));
    }

    if ($activity->subtype == 'submission') {
        echo html_writer::start_tag('div', array('class'=>'submission', 'style'=>'padding: 7px; float:left;'));

        if ($detail) {
            echo html_writer::start_tag('h4', array('class'=>'peerreview'));
            $url = new moodle_url('/mod/peerreview/view.php', array('id'=>$activity->cmid));
            $name = s($activity->name);
            echo $OUTPUT->image_icon('monologo', $name, $activity->type);
            echo ' ' . $modnames[$activity->type];
            echo html_writer::link($url, $name, array('class'=>'name', 'style'=>'margin-left: 5px'));
            echo html_writer::end_tag('h4');
        }

        echo html_writer::start_tag('div', array('class'=>'title'));
        $url = new moodle_url('/mod/peerreview/submission.php', array('cmid'=>$activity->cmid, 'id'=>$activity->content->id));
        $name = s($activity->content->title);
        echo html_writer::tag('strong', html_writer::link($url, $name));
        echo html_writer::end_tag('div');

        if (!empty($activity->user)) {
            echo html_writer::start_tag('div', array('class'=>'user'));
            $url = new moodle_url('/user/view.php', array('id'=>$activity->user->id, 'course'=>$courseid));
            $name = fullname($activity->user);
            $link = html_writer::link($url, $name);
            echo get_string('submissionby', 'peerreview', $link);
            echo ' - '.userdate($activity->timestamp);
            echo html_writer::end_tag('div');
        } else {
            echo html_writer::start_tag('div', array('class'=>'anonymous'));
            echo get_string('submission', 'peerreview');
            echo ' - '.userdate($activity->timestamp);
            echo html_writer::end_tag('div');
        }

        echo html_writer::end_tag('div');
    }

    if ($activity->subtype == 'assessment') {
        echo html_writer::start_tag('div', array('class'=>'assessment', 'style'=>'padding: 7px; float:left;'));

        if ($detail) {
            echo html_writer::start_tag('h4', array('class'=>'peerreview'));
            $url = new moodle_url('/mod/peerreview/view.php', array('id'=>$activity->cmid));
            $name = s($activity->name);
            echo $OUTPUT->image_icon('monologo', $name, $activity->type);
            echo ' ' . $modnames[$activity->type];
            echo html_writer::link($url, $name, array('class'=>'name', 'style'=>'margin-left: 5px'));
            echo html_writer::end_tag('h4');
        }

        echo html_writer::start_tag('div', array('class'=>'title'));
        $url = new moodle_url('/mod/peerreview/assessment.php', array('asid'=>$activity->content->id));
        $name = s($activity->content->submissiontitle);
        echo html_writer::tag('em', html_writer::link($url, $name));
        echo html_writer::end_tag('div');

        if (!empty($activity->user)) {
            echo html_writer::start_tag('div', array('class'=>'user'));
            $url = new moodle_url('/user/view.php', array('id'=>$activity->user->id, 'course'=>$courseid));
            $name = fullname($activity->user);
            $link = html_writer::link($url, $name);
            echo get_string('assessmentbyfullname', 'peerreview', $link);
            echo ' - '.userdate($activity->timestamp);
            echo html_writer::end_tag('div');
        } else {
            echo html_writer::start_tag('div', array('class'=>'anonymous'));
            echo get_string('assessment', 'peerreview');
            echo ' - '.userdate($activity->timestamp);
            echo html_writer::end_tag('div');
        }

        echo html_writer::end_tag('div');
    }

    echo html_writer::empty_tag('br', array('style'=>'clear:both'));
}

/**
 * @deprecated since Moodle 3.8
 */
function peerreview_scale_used() {
    throw new coding_exception('peerreview_scale_used() can not be used anymore. Plugins can implement ' .
        '<modname>_scale_used_anywhere, all implementations of <modname>_scale_used are now ignored');
}

/**
 * Is a given scale used by any instance of peerreview?
 *
 * The function asks all installed grading strategy subplugins. The peerreview
 * core itself does not use scales. Both grade for submission and grade for
 * assessments do not use scales.
 *
 * @param int $scaleid id of the scale to check
 * @return bool
 */
function peerreview_scale_used_anywhere($scaleid) {
    global $CFG; // other files included from here

    $strategies = core_component::get_plugin_list('peerreviewform');
    foreach ($strategies as $strategy => $strategypath) {
        $strategylib = $strategypath . '/lib.php';
        if (is_readable($strategylib)) {
            require_once($strategylib);
        } else {
            throw new coding_exception('the grading forms subplugin must contain library ' . $strategylib);
        }
        $classname = 'peerreview_' . $strategy . '_strategy';
        if (method_exists($classname, 'scale_used')) {
            if (call_user_func(array($classname, 'scale_used'), $scaleid)) {
                // no need to include any other files - scale is used
                return true;
            }
        }
    }

    return false;
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Creates or updates grade items for the give peerreview instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php. Also used by
 * {@link peerreview_update_grades()}.
 *
 * @param stdClass $peerreview instance object with extra cmidnumber property
 * @param stdClass $submissiongrades data for the first grade item
 * @param stdClass $assessmentgrades data for the second grade item
 * @return void
 */
function peerreview_grade_item_update(stdclass $peerreview, $submissiongrades=null, $assessmentgrades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $a = new stdclass();
    $a->peerreviewname = clean_param($peerreview->name, PARAM_NOTAGS);

    $item = array();
    $item['itemname'] = get_string('gradeitemsubmission', 'peerreview', $a);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = $peerreview->grade;
    $item['grademin']  = 0;
    grade_update('mod/peerreview', $peerreview->course, 'mod', 'peerreview', $peerreview->id, 0, $submissiongrades , $item);

    $item = array();
    $item['itemname'] = get_string('gradeitemassessment', 'peerreview', $a);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = $peerreview->gradinggrade;
    $item['grademin']  = 0;
    grade_update('mod/peerreview', $peerreview->course, 'mod', 'peerreview', $peerreview->id, 1, $assessmentgrades, $item);
}

/**
 * Update peerreview grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @category grade
 * @param stdClass $peerreview instance object with extra cmidnumber and modname property
 * @param int $userid        update grade of specific user only, 0 means all participants
 * @return void
 */
function peerreview_update_grades(stdclass $peerreview, $userid=0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    $whereuser = $userid ? ' AND authorid = :userid' : '';
    $params = array('peerreviewid' => $peerreview->id, 'userid' => $userid);
    $sql = 'SELECT authorid, grade, gradeover, gradeoverby, feedbackauthor, feedbackauthorformat, timemodified, timegraded
              FROM {peerreview_submissions}
             WHERE peerreviewid = :peerreviewid AND example=0' . $whereuser;
    $records = $DB->get_records_sql($sql, $params);
    $submissiongrades = array();
    foreach ($records as $record) {
        $grade = new stdclass();
        $grade->userid = $record->authorid;
        if (!is_null($record->gradeover)) {
            $grade->rawgrade = grade_floatval($peerreview->grade * $record->gradeover / 100);
            $grade->usermodified = $record->gradeoverby;
        } else {
            $grade->rawgrade = grade_floatval($peerreview->grade * $record->grade / 100);
        }
        $grade->feedback = $record->feedbackauthor;
        $grade->feedbackformat = $record->feedbackauthorformat;
        $grade->datesubmitted = $record->timemodified;
        $grade->dategraded = $record->timegraded;
        $submissiongrades[$record->authorid] = $grade;
    }

    $whereuser = $userid ? ' AND userid = :userid' : '';
    $params = array('peerreviewid' => $peerreview->id, 'userid' => $userid);
    $sql = 'SELECT userid, gradinggrade, timegraded
              FROM {peerreview_aggregations}
             WHERE peerreviewid = :peerreviewid' . $whereuser;
    $records = $DB->get_records_sql($sql, $params);
    $assessmentgrades = array();
    foreach ($records as $record) {
        $grade = new stdclass();
        $grade->userid = $record->userid;
        $grade->rawgrade = grade_floatval($peerreview->gradinggrade * $record->gradinggrade / 100);
        $grade->dategraded = $record->timegraded;
        $assessmentgrades[$record->userid] = $grade;
    }

    peerreview_grade_item_update($peerreview, $submissiongrades, $assessmentgrades);
}

/**
 * Update the grade items categories if they are changed via mod_form.php
 *
 * We must do it manually here in the peerreview module because modedit supports only
 * single grade item while we use two.
 *
 * @param stdClass $peerreview An object from the form in mod_form.php
 */
function peerreview_grade_item_category_update($peerreview) {

    $gradeitems = grade_item::fetch_all(array(
        'itemtype'      => 'mod',
        'itemmodule'    => 'peerreview',
        'iteminstance'  => $peerreview->id,
        'courseid'      => $peerreview->course));

    if (!empty($gradeitems)) {
        foreach ($gradeitems as $gradeitem) {
            if ($gradeitem->itemnumber == 0) {
                if (isset($peerreview->submissiongradepass) &&
                        $gradeitem->gradepass != $peerreview->submissiongradepass) {
                    $gradeitem->gradepass = $peerreview->submissiongradepass;
                    $gradeitem->update();
                }
                if ($gradeitem->categoryid != $peerreview->gradecategory) {
                    $gradeitem->set_parent($peerreview->gradecategory);
                }
            } else if ($gradeitem->itemnumber == 1) {
                if (isset($peerreview->gradinggradepass) &&
                        $gradeitem->gradepass != $peerreview->gradinggradepass) {
                    $gradeitem->gradepass = $peerreview->gradinggradepass;
                    $gradeitem->update();
                }
                if ($gradeitem->categoryid != $peerreview->gradinggradecategory) {
                    $gradeitem->set_parent($peerreview->gradinggradecategory);
                }
            }
        }
    }
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area peerreview_intro for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @package  mod_peerreview
 * @category files
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function peerreview_get_file_areas($course, $cm, $context) {
    $areas = array();
    $areas['instructauthors']          = get_string('areainstructauthors', 'peerreview');
    $areas['instructreviewers']        = get_string('areainstructreviewers', 'peerreview');
    $areas['submission_content']       = get_string('areasubmissioncontent', 'peerreview');
    $areas['description_content']       = get_string('areadescriptioncontent', 'peerreview');
    $areas['submission_attachment']    = get_string('areasubmissionattachment', 'peerreview');
    $areas['conclusion']               = get_string('areaconclusion', 'peerreview');
    $areas['overallfeedback_content']  = get_string('areaoverallfeedbackcontent', 'peerreview');
    $areas['overallfeedback_attachment'] = get_string('areaoverallfeedbackattachment', 'peerreview');

    return $areas;
}

/**
 * Serves the files from the peerreview file areas
 *
 * Apart from module intro (handled by pluginfile.php automatically), peerreview files may be
 * media inserted into submission content (like images) and submission attachments. For these two,
 * the fileareas submission_content and submission_attachment are used.
 * Besides that, areas instructauthors, instructreviewers and conclusion contain the media
 * embedded using the mod_form.php.
 *
 * @package  mod_peerreview
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the peerreview's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
// function peerreview_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
//     global $DB, $CFG, $USER;

//     if ($context->contextlevel != CONTEXT_MODULE) {
//         return false;
//     }

//     require_login($course, true, $cm);

//     if ($filearea === 'instructauthors' or $filearea === 'instructreviewers' or $filearea === 'conclusion') {
//         // The $args are supposed to contain just the path, not the item id.
//         $relativepath = implode('/', $args);
//         $fullpath = "/$context->id/mod_peerreview/$filearea/0/$relativepath";

//         $fs = get_file_storage();
//         if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
//             send_file_not_found();
//         }
//         send_stored_file($file, null, 0, $forcedownload, $options);

//     } else if ($filearea === 'submission_content' or $filearea === 'submission_attachment') {
//         $itemid = (int)array_shift($args);
//         if (!$peerreview = $DB->get_record('peerreview', array('id' => $cm->instance))) {
//             return false;
//         }
//         if (!$submission = $DB->get_record('peerreview_submissions', array('id' => $itemid, 'peerreviewid' => $peerreview->id))) {
//             return false;
//         }

//         // make sure the user is allowed to see the file
//         if (empty($submission->example)) {
//             if ($USER->id != $submission->authorid) {
//                 if ($submission->published == 1 and $peerreview->phase == 50
//                         and has_capability('mod/peerreview:viewpublishedsubmissions', $context)) {
//                     // Published submission, we can go (peerreview does not take the group mode
//                     // into account in this case yet).
//                 } else if (!$DB->record_exists('peerreview_assessments', array('submissionid' => $submission->id, 'reviewerid' => $USER->id))) {
//                     if (!has_capability('mod/peerreview:viewallsubmissions', $context)) {
//                         send_file_not_found();
//                     } else {
//                         $gmode = groups_get_activity_groupmode($cm, $course);
//                         if ($gmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
//                             // check there is at least one common group with both the $USER
//                             // and the submission author
//                             $sql = "SELECT 'x'
//                                       FROM {peerreview_submissions} s
//                                       JOIN {user} a ON (a.id = s.authorid)
//                                       JOIN {groups_members} agm ON (a.id = agm.userid)
//                                       JOIN {user} u ON (u.id = ?)
//                                       JOIN {groups_members} ugm ON (u.id = ugm.userid)
//                                      WHERE s.example = 0 AND s.peerreviewid = ? AND s.id = ? AND agm.groupid = ugm.groupid";
//                             $params = array($USER->id, $peerreview->id, $submission->id);
//                             if (!$DB->record_exists_sql($sql, $params)) {
//                                 send_file_not_found();
//                             }
//                         }
//                     }
//                 }
//             }
//         }

//         $fs = get_file_storage();
//         $relativepath = implode('/', $args);
//         $fullpath = "/$context->id/mod_peerreview/$filearea/$itemid/$relativepath";
//         if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
//             return false;
//         }
//         // finally send the file
//         // these files are uploaded by students - forcing download for security reasons
//         send_stored_file($file, 0, 0, true, $options);

//     } else if ($filearea === 'overallfeedback_content' or $filearea === 'overallfeedback_attachment') {
//         $itemid = (int)array_shift($args);
//         if (!$peerreview = $DB->get_record('peerreview', array('id' => $cm->instance))) {
//             return false;
//         }
//         if (!$assessment = $DB->get_record('peerreview_assessments', array('id' => $itemid))) {
//             return false;
//         }
//         if (!$submission = $DB->get_record('peerreview_submissions', array('id' => $assessment->submissionid, 'peerreviewid' => $peerreview->id))) {
//             return false;
//         }

//         if ($USER->id == $assessment->reviewerid) {
//             // Reviewers can always see their own files.
//         } else if ($USER->id == $submission->authorid and $peerreview->phase == 50) {
//             // Authors can see the feedback once the peerreview is closed.
//         } else if (!empty($submission->example) and $assessment->weight == 1) {
//             // Reference assessments of example submissions can be displayed.
//         } else if (!has_capability('mod/peerreview:viewallassessments', $context)) {
//             send_file_not_found();
//         } else {
//             $gmode = groups_get_activity_groupmode($cm, $course);
//             if ($gmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
//                 // Check there is at least one common group with both the $USER
//                 // and the submission author.
//                 $sql = "SELECT 'x'
//                           FROM {peerreview_submissions} s
//                           JOIN {user} a ON (a.id = s.authorid)
//                           JOIN {groups_members} agm ON (a.id = agm.userid)
//                           JOIN {user} u ON (u.id = ?)
//                           JOIN {groups_members} ugm ON (u.id = ugm.userid)
//                          WHERE s.example = 0 AND s.peerreviewid = ? AND s.id = ? AND agm.groupid = ugm.groupid";
//                 $params = array($USER->id, $peerreview->id, $submission->id);
//                 if (!$DB->record_exists_sql($sql, $params)) {
//                     send_file_not_found();
//                 }
//             }
//         }

//         $fs = get_file_storage();
//         $relativepath = implode('/', $args);
//         $fullpath = "/$context->id/mod_peerreview/$filearea/$itemid/$relativepath";
//         if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
//             return false;
//         }
//         // finally send the file
//         // these files are uploaded by students - forcing download for security reasons
//         send_stored_file($file, 0, 0, true, $options);
//     }

//     return false;
// }

function peerreview_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if ($filearea === 'instructauthors' or $filearea === 'instructreviewers' or $filearea === 'conclusion') {
        // The $args are supposed to contain just the path, not the item id.
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_peerreview/$filearea/0/$relativepath";

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            send_file_not_found();
        }
        send_stored_file($file, null, 0, $forcedownload, $options);

    } else if ($filearea === 'submission_content' or $filearea === 'submission_attachment' or $filearea === 'description_content') {
        $itemid = (int)array_shift($args);
        if (!$peerreview = $DB->get_record('peerreview', array('id' => $cm->instance))) {
            return false;
        }
        if (!$submission = $DB->get_record('peerreview_submissions', array('id' => $itemid, 'peerreviewid' => $peerreview->id))) {
            return false;
        }

        // Make sure the user is allowed to see the file.
        if (empty($submission->example)) {
            if ($USER->id != $submission->authorid) {
                if ($submission->published == 1 and $peerreview->phase == 50
                        and has_capability('mod/peerreview:viewpublishedsubmissions', $context)) {
                    // Published submission, we can go (peerreview does not take the group mode
                    // into account in this case yet).
                } else if (!$DB->record_exists('peerreview_assessments', array('submissionid' => $submission->id, 'reviewerid' => $USER->id))) {
                    if (!has_capability('mod/peerreview:viewallsubmissions', $context)) {
                        send_file_not_found();
                    } else {
                        $gmode = groups_get_activity_groupmode($cm, $course);
                        if ($gmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
                            // Check there is at least one common group with both the $USER
                            // and the submission author.
                            $sql = "SELECT 'x'
                                      FROM {peerreview_submissions} s
                                      JOIN {user} a ON (a.id = s.authorid)
                                      JOIN {groups_members} agm ON (a.id = agm.userid)
                                      JOIN {user} u ON (u.id = ?)
                                      JOIN {groups_members} ugm ON (u.id = ugm.userid)
                                     WHERE s.example = 0 AND s.peerreviewid = ? AND s.id = ? AND agm.groupid = ugm.groupid";
                            $params = array($USER->id, $peerreview->id, $submission->id);
                            if (!$DB->record_exists_sql($sql, $params)) {
                                send_file_not_found();
                            }
                        }
                    }
                }
            }
        }

        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_peerreview/$filearea/$itemid/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }
        // Finally send the file.
        // These files are uploaded by students - forcing download for security reasons.
        send_stored_file($file, 0, 0, true, $options);

    } else if ($filearea === 'overallfeedback_content' or $filearea === 'overallfeedback_attachment') {
        $itemid = (int)array_shift($args);
        if (!$peerreview = $DB->get_record('peerreview', array('id' => $cm->instance))) {
            return false;
        }
        if (!$assessment = $DB->get_record('peerreview_assessments', array('id' => $itemid))) {
            return false;
        }
        if (!$submission = $DB->get_record('peerreview_submissions', array('id' => $assessment->submissionid, 'peerreviewid' => $peerreview->id))) {
            return false;
        }

        if ($USER->id == $assessment->reviewerid) {
            // Reviewers can always see their own files.
        } else if ($USER->id == $submission->authorid and $peerreview->phase == 50) {
            // Authors can see the feedback once the peerreview is closed.
        } else if (!empty($submission->example) and $assessment->weight == 1) {
            // Reference assessments of example submissions can be displayed.
        } else if (!has_capability('mod/peerreview:viewallassessments', $context)) {
            send_file_not_found();
        } else {
            $gmode = groups_get_activity_groupmode($cm, $course);
            if ($gmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
                // Check there is at least one common group with both the $USER
                // and the submission author.
                $sql = "SELECT 'x'
                          FROM {peerreview_submissions} s
                          JOIN {user} a ON (a.id = s.authorid)
                          JOIN {groups_members} agm ON (a.id = agm.userid)
                          JOIN {user} u ON (u.id = ?)
                          JOIN {groups_members} ugm ON (u.id = ugm.userid)
                         WHERE s.example = 0 AND s.peerreviewid = ? AND s.id = ? AND agm.groupid = ugm.groupid";
                $params = array($USER->id, $peerreview->id, $submission->id);
                if (!$DB->record_exists_sql($sql, $params)) {
                    send_file_not_found();
                }
            }
        }

        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_peerreview/$filearea/$itemid/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }
        // Finally send the file.
        // These files are uploaded by students - forcing download for security reasons.
        send_stored_file($file, 0, 0, true, $options);
    }

    return false;
}


/**
 * File browsing support for peerreview file areas
 *
 * @package  mod_peerreview
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function peerreview_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG, $DB, $USER;

    /** @var array internal cache for author names */
    static $submissionauthors = array();

    $fs = get_file_storage();

    if ($filearea === 'submission_content' or $filearea === 'submission_attachment' or $filearea === 'description_content') {

        if (!has_capability('mod/peerreview:viewallsubmissions', $context)) {
            return null;
        }

        if (is_null($itemid)) {
            // no itemid (submissionid) passed, display the list of all submissions
            require_once($CFG->dirroot . '/mod/peerreview/fileinfolib.php');
            return new peerreview_file_info_submissions_container($browser, $course, $cm, $context, $areas, $filearea);
        }

        // make sure the user can see the particular submission in separate groups mode
        $gmode = groups_get_activity_groupmode($cm, $course);

        if ($gmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
            // check there is at least one common group with both the $USER
            // and the submission author (this is not expected to be a frequent
            // usecase so we can live with pretty ineffective one query per submission here...)
            $sql = "SELECT 'x'
                      FROM {peerreview_submissions} s
                      JOIN {user} a ON (a.id = s.authorid)
                      JOIN {groups_members} agm ON (a.id = agm.userid)
                      JOIN {user} u ON (u.id = ?)
                      JOIN {groups_members} ugm ON (u.id = ugm.userid)
                     WHERE s.example = 0 AND s.peerreviewid = ? AND s.id = ? AND agm.groupid = ugm.groupid";
            $params = array($USER->id, $cm->instance, $itemid);
            if (!$DB->record_exists_sql($sql, $params)) {
                return null;
            }
        }

        // we are inside some particular submission container

        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        if (!$storedfile = $fs->get_file($context->id, 'mod_peerreview', $filearea, $itemid, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_peerreview', $filearea, $itemid);
            } else {
                // not found
                return null;
            }
        }

        // Checks to see if the user can manage files or is the owner.
        // TODO MDL-33805 - Do not use userid here and move the capability check above.
        if (!has_capability('moodle/course:managefiles', $context) && $storedfile->get_userid() != $USER->id) {
            return null;
        }

        // let us display the author's name instead of itemid (submission id)

        if (isset($submissionauthors[$itemid])) {
            $topvisiblename = $submissionauthors[$itemid];

        } else {

            $userfieldsapi = \core_user\fields::for_name();
            $userfields = $userfieldsapi->get_sql('u', false, '', '', false)->selects;
            $sql = "SELECT s.id, $userfields
                      FROM {peerreview_submissions} s
                      JOIN {user} u ON (s.authorid = u.id)
                     WHERE s.example = 0 AND s.peerreviewid = ?";
            $params = array($cm->instance);
            $rs = $DB->get_recordset_sql($sql, $params);

            foreach ($rs as $submissionauthor) {
                $title = s(fullname($submissionauthor)); // this is generally not unique...
                $submissionauthors[$submissionauthor->id] = $title;
            }
            $rs->close();

            if (!isset($submissionauthors[$itemid])) {
                // should not happen
                return null;
            } else {
                $topvisiblename = $submissionauthors[$itemid];
            }
        }

        $urlbase = $CFG->wwwroot . '/pluginfile.php';
        // do not allow manual modification of any files!
        return new file_info_stored($browser, $context, $storedfile, $urlbase, $topvisiblename, true, true, false, false);
    }

    if ($filearea === 'overallfeedback_content' or $filearea === 'overallfeedback_attachment') {

        if (!has_capability('mod/peerreview:viewallassessments', $context)) {
            return null;
        }

        if (is_null($itemid)) {
            // No itemid (assessmentid) passed, display the list of all assessments.
            require_once($CFG->dirroot . '/mod/peerreview/fileinfolib.php');
            return new peerreview_file_info_overallfeedback_container($browser, $course, $cm, $context, $areas, $filearea);
        }

        // Make sure the user can see the particular assessment in separate groups mode.
        $gmode = groups_get_activity_groupmode($cm, $course);
        if ($gmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
            // Check there is at least one common group with both the $USER
            // and the submission author.
            $sql = "SELECT 'x'
                      FROM {peerreview_submissions} s
                      JOIN {user} a ON (a.id = s.authorid)
                      JOIN {groups_members} agm ON (a.id = agm.userid)
                      JOIN {user} u ON (u.id = ?)
                      JOIN {groups_members} ugm ON (u.id = ugm.userid)
                     WHERE s.example = 0 AND s.peerreviewid = ? AND s.id = ? AND agm.groupid = ugm.groupid";
            $params = array($USER->id, $cm->instance, $itemid);
            if (!$DB->record_exists_sql($sql, $params)) {
                return null;
            }
        }

        // We are inside a particular assessment container.
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        if (!$storedfile = $fs->get_file($context->id, 'mod_peerreview', $filearea, $itemid, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_peerreview', $filearea, $itemid);
            } else {
                // Not found
                return null;
            }
        }

        // Check to see if the user can manage files or is the owner.
        if (!has_capability('moodle/course:managefiles', $context) and $storedfile->get_userid() != $USER->id) {
            return null;
        }

        $urlbase = $CFG->wwwroot . '/pluginfile.php';

        // Do not allow manual modification of any files.
        return new file_info_stored($browser, $context, $storedfile, $urlbase, $itemid, true, true, false, false);
    }

    if ($filearea == 'instructauthors' or $filearea == 'instructreviewers' or $filearea == 'conclusion') {
        // always only itemid 0

        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_peerreview', $filearea, 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_peerreview', $filearea, 0);
            } else {
                // not found
                return null;
            }
        }
        return new file_info_stored($browser, $context, $storedfile, $urlbase, $areas[$filearea], false, true, true, false);
    }
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding peerreview nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the peerreview module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function peerreview_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
    global $CFG;

    if (has_capability('mod/peerreview:submit', context_module::instance($cm->id))) {
        $url = new moodle_url('/mod/peerreview/submission.php', array('cmid' => $cm->id));
        $mysubmission = $navref->add(get_string('mysubmission', 'peerreview'), $url);
        $mysubmission->mainnavonly = true;
    }
}

/**
 * Extends the settings navigation with the peerreview settings

 * This function is called when the context for the page is a peerreview module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $peerreviewnode {@link navigation_node}
 */
function peerreview_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $peerreviewnode=null) {
    if (has_capability('mod/peerreview:editdimensions', $settingsnav->get_page()->cm->context)) {
        $url = new moodle_url('/mod/peerreview/editform.php', array('cmid' => $settingsnav->get_page()->cm->id));
        $peerreviewnode->add(get_string('assessmentform', 'peerreview'), $url,
        settings_navigation::TYPE_SETTING, null, 'peerreviewassessement');
    }
    if (has_capability('mod/peerreview:allocate', $settingsnav->get_page()->cm->context)) {
        $url = new moodle_url('/mod/peerreview/notificationpanel.php', array('cmid' => $settingsnav->get_page()->cm->id));
        $peerreviewnode->add(get_string('notificationpanel', 'peerreview'), $url, settings_navigation::TYPE_SETTING);
    }
    if (has_capability('mod/peerreview:allocate', $settingsnav->get_page()->cm->context)) {
        $url = new moodle_url('/mod/peerreview/allocation.php', array('cmid' => $settingsnav->get_page()->cm->id));
        $peerreviewnode->add(get_string('submissionsallocation', 'peerreview'), $url, settings_navigation::TYPE_SETTING);
    }
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function peerreview_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-peerreview-*'=>get_string('page-mod-peerreview-x', 'peerreview'));
    return $module_pagetype;
}

////////////////////////////////////////////////////////////////////////////////
// Calendar API                                                               //
////////////////////////////////////////////////////////////////////////////////

/**
 * Updates the calendar events associated to the given peerreview
 *
 * @param stdClass $peerreview the peerreview instance record
 * @param int $cmid course module id
 */
function peerreview_calendar_update(stdClass $peerreview, $cmid) {
    global $DB;

    // get the currently registered events so that we can re-use their ids
    $currentevents = $DB->get_records('event', array('modulename' => 'peerreview', 'instance' => $peerreview->id));

    // the common properties for all events
    $base = new stdClass();
    $base->description  = format_module_intro('peerreview', $peerreview, $cmid, false);
    $base->format       = FORMAT_HTML;
    $base->courseid     = $peerreview->course;
    $base->groupid      = 0;
    $base->userid       = 0;
    $base->modulename   = 'peerreview';
    $base->instance     = $peerreview->id;
    $base->visible      = instance_is_visible('peerreview', $peerreview);
    $base->timeduration = 0;

    if ($peerreview->submissionstart) {
        $event = clone($base);
        $event->name = get_string('submissionstartevent', 'mod_peerreview', $peerreview->name);
        $event->eventtype = peerreview_EVENT_TYPE_SUBMISSION_OPEN;
        $event->type = empty($peerreview->submissionend) ? CALENDAR_EVENT_TYPE_ACTION : CALENDAR_EVENT_TYPE_STANDARD;
        $event->timestart = $peerreview->submissionstart;
        $event->timesort  = $peerreview->submissionstart;
        if ($reusedevent = array_shift($currentevents)) {
            $event->id = $reusedevent->id;
        } else {
            // should not be set but just in case
            unset($event->id);
        }
        // update() will reuse a db record if the id field is set
        $eventobj = new calendar_event($event);
        $eventobj->update($event, false);
    }

    if ($peerreview->submissionend) {
        $event = clone($base);
        $event->name = get_string('submissionendevent', 'mod_peerreview', $peerreview->name);
        $event->eventtype = peerreview_EVENT_TYPE_SUBMISSION_CLOSE;
        $event->type      = CALENDAR_EVENT_TYPE_ACTION;
        $event->timestart = $peerreview->submissionend;
        $event->timesort  = $peerreview->submissionend;
        if ($reusedevent = array_shift($currentevents)) {
            $event->id = $reusedevent->id;
        } else {
            // should not be set but just in case
            unset($event->id);
        }
        // update() will reuse a db record if the id field is set
        $eventobj = new calendar_event($event);
        $eventobj->update($event, false);
    }

    if ($peerreview->assessmentstart) {
        $event = clone($base);
        $event->name = get_string('assessmentstartevent', 'mod_peerreview', $peerreview->name);
        $event->eventtype = peerreview_EVENT_TYPE_ASSESSMENT_OPEN;
        $event->type      = empty($peerreview->assessmentend) ? CALENDAR_EVENT_TYPE_ACTION : CALENDAR_EVENT_TYPE_STANDARD;
        $event->timestart = $peerreview->assessmentstart;
        $event->timesort  = $peerreview->assessmentstart;
        if ($reusedevent = array_shift($currentevents)) {
            $event->id = $reusedevent->id;
        } else {
            // should not be set but just in case
            unset($event->id);
        }
        // update() will reuse a db record if the id field is set
        $eventobj = new calendar_event($event);
        $eventobj->update($event, false);
    }

    if ($peerreview->assessmentend) {
        $event = clone($base);
        $event->name = get_string('assessmentendevent', 'mod_peerreview', $peerreview->name);
        $event->eventtype = peerreview_EVENT_TYPE_ASSESSMENT_CLOSE;
        $event->type      = CALENDAR_EVENT_TYPE_ACTION;
        $event->timestart = $peerreview->assessmentend;
        $event->timesort  = $peerreview->assessmentend;
        if ($reusedevent = array_shift($currentevents)) {
            $event->id = $reusedevent->id;
        } else {
            // should not be set but just in case
            unset($event->id);
        }
        // update() will reuse a db record if the id field is set
        $eventobj = new calendar_event($event);
        $eventobj->update($event, false);
    }

    // delete any leftover events
    foreach ($currentevents as $oldevent) {
        $oldevent = calendar_event::load($oldevent);
        $oldevent->delete();
    }
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_peerreview_core_calendar_provide_event_action(calendar_event $event,
        \core_calendar\action_factory $factory, int $userid = 0) {
    global $USER;

    if (!$userid) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['peerreview'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('viewpeerreviewsummary', 'peerreview'),
        new \moodle_url('/mod/peerreview/view.php', array('id' => $cm->id)),
        1,
        true
    );
}

/**
 * This function calculates the minimum and maximum cutoff values for the timestart of
 * the given event.
 *
 * It will return an array with two values, the first being the minimum cutoff value and
 * the second being the maximum cutoff value. Either or both values can be null, which
 * indicates there is no minimum or maximum, respectively.
 *
 * If a cutoff is required then the function must return an array containing the cutoff
 * timestamp and error string to display to the user if the cutoff value is violated.
 *
 * A minimum and maximum cutoff return value will look like:
 * [
 *     [1505704373, 'The date must be after this date'],
 *     [1506741172, 'The date must be before this date']
 * ]
 *
 * @param calendar_event $event The calendar event to get the time range for
 * @param stdClass $peerreview The module instance to get the range from
 * @return array Returns an array with min and max date.
 */
function mod_peerreview_core_calendar_get_valid_event_timestart_range(\calendar_event $event, \stdClass $peerreview) : array {
    $mindate = null;
    $maxdate = null;

    $phasesubmissionend = max($peerreview->submissionstart, $peerreview->submissionend);
    $phaseassessmentstart = min($peerreview->assessmentstart, $peerreview->assessmentend);
    if ($phaseassessmentstart == 0) {
        $phaseassessmentstart = max($peerreview->assessmentstart, $peerreview->assessmentend);
    }

    switch ($event->eventtype) {
        case peerreview_EVENT_TYPE_SUBMISSION_OPEN:
            if (!empty($peerreview->submissionend)) {
                $maxdate = [
                    $peerreview->submissionend - 1,   // The submissionstart and submissionend cannot be exactly the same.
                    get_string('submissionendbeforestart', 'mod_peerreview')
                ];
            } else if ($phaseassessmentstart) {
                $maxdate = [
                    $phaseassessmentstart,
                    get_string('phasesoverlap', 'mod_peerreview')
                ];
            }
            break;
        case peerreview_EVENT_TYPE_SUBMISSION_CLOSE:
            if (!empty($peerreview->submissionstart)) {
                $mindate = [
                    $peerreview->submissionstart + 1, // The submissionstart and submissionend cannot be exactly the same.
                    get_string('submissionendbeforestart', 'mod_peerreview')
                ];
            }
            if ($phaseassessmentstart) {
                $maxdate = [
                    $phaseassessmentstart,
                    get_string('phasesoverlap', 'mod_peerreview')
                ];
            }
            break;
        case peerreview_EVENT_TYPE_ASSESSMENT_OPEN:
            if ($phasesubmissionend) {
                $mindate = [
                    $phasesubmissionend,
                    get_string('phasesoverlap', 'mod_peerreview')
                ];
            }
            if (!empty($peerreview->assessmentend)) {
                $maxdate = [
                    $peerreview->assessmentend - 1,   // The assessmentstart and assessmentend cannot be exactly the same.
                    get_string('assessmentendbeforestart', 'mod_peerreview')
                ];
            }
            break;
        case peerreview_EVENT_TYPE_ASSESSMENT_CLOSE:
            if (!empty($peerreview->assessmentstart)) {
                $mindate = [
                    $peerreview->assessmentstart + 1, // The assessmentstart and assessmentend cannot be exactly the same.
                    get_string('assessmentendbeforestart', 'mod_peerreview')
                ];
            } else if ($phasesubmissionend) {
                $mindate = [
                    $phasesubmissionend,
                    get_string('phasesoverlap', 'mod_peerreview')
                ];
            }
            break;
    }

    return [$mindate, $maxdate];
}

/**
 * This function will update the peerreview module according to the
 * event that has been modified.
 *
 * @param \calendar_event $event
 * @param stdClass $peerreview The module instance to get the range from
 */
function mod_peerreview_core_calendar_event_timestart_updated(\calendar_event $event, \stdClass $peerreview) : void {
    global $DB;

    $courseid = $event->courseid;
    $modulename = $event->modulename;
    $instanceid = $event->instance;

    // Something weird going on. The event is for a different module so
    // we should ignore it.
    if ($modulename != 'peerreview') {
        return;
    }

    if ($peerreview->id != $instanceid) {
        return;
    }

    if (!in_array(
            $event->eventtype,
            [
                peerreview_EVENT_TYPE_SUBMISSION_OPEN,
                peerreview_EVENT_TYPE_SUBMISSION_CLOSE,
                peerreview_EVENT_TYPE_ASSESSMENT_OPEN,
                peerreview_EVENT_TYPE_ASSESSMENT_CLOSE
            ]
    )) {
        return;
    }

    $coursemodule = get_fast_modinfo($courseid)->instances[$modulename][$instanceid];
    $context = context_module::instance($coursemodule->id);

    // The user does not have the capability to modify this activity.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    $modified = false;

    switch ($event->eventtype) {
        case peerreview_EVENT_TYPE_SUBMISSION_OPEN:
            if ($event->timestart != $peerreview->submissionstart) {
                $peerreview->submissionstart = $event->timestart;
                $modified = true;
            }
            break;
        case peerreview_EVENT_TYPE_SUBMISSION_CLOSE:
            if ($event->timestart != $peerreview->submissionend) {
                $peerreview->submissionend = $event->timestart;
                $modified = true;
            }
            break;
        case peerreview_EVENT_TYPE_ASSESSMENT_OPEN:
            if ($event->timestart != $peerreview->assessmentstart) {
                $peerreview->assessmentstart = $event->timestart;
                $modified = true;
            }
            break;
        case peerreview_EVENT_TYPE_ASSESSMENT_CLOSE:
            if ($event->timestart != $peerreview->assessmentend) {
                $peerreview->assessmentend = $event->timestart;
                $modified = true;
            }
            break;
    }

    if ($modified) {
        $peerreview->timemodified = time();
        // Persist the assign instance changes.
        $DB->update_record('peerreview', $peerreview);
        $event = \core\event\course_module_updated::create_from_cm($coursemodule, $context);
        $event->trigger();
    }
}

////////////////////////////////////////////////////////////////////////////////
// Course reset API                                                           //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the course reset form with peerreview specific settings.
 *
 * @param MoodleQuickForm $mform
 */
function peerreview_reset_course_form_definition($mform) {

    $mform->addElement('header', 'peerreviewheader', get_string('modulenameplural', 'mod_peerreview'));

    $mform->addElement('advcheckbox', 'reset_peerreview_submissions', get_string('resetsubmissions', 'mod_peerreview'));
    $mform->addHelpButton('reset_peerreview_submissions', 'resetsubmissions', 'mod_peerreview');

    $mform->addElement('advcheckbox', 'reset_peerreview_assessments', get_string('resetassessments', 'mod_peerreview'));
    $mform->addHelpButton('reset_peerreview_assessments', 'resetassessments', 'mod_peerreview');
    $mform->disabledIf('reset_peerreview_assessments', 'reset_peerreview_submissions', 'checked');

    $mform->addElement('advcheckbox', 'reset_peerreview_phase', get_string('resetphase', 'mod_peerreview'));
    $mform->addHelpButton('reset_peerreview_phase', 'resetphase', 'mod_peerreview');
}

/**
 * Provides default values for the peerreview settings in the course reset form.
 *
 * @param stdClass $course The course to be reset.
 */
function peerreview_reset_course_form_defaults(stdClass $course) {

    $defaults = array(
        'reset_peerreview_submissions'    => 1,
        'reset_peerreview_assessments'    => 1,
        'reset_peerreview_phase'          => 1,
    );

    return $defaults;
}

/**
 * Performs the reset of all peerreview instances in the course.
 *
 * @param stdClass $data The actual course reset settings.
 * @return array List of results, each being array[(string)component, (string)item, (string)error]
 */
function peerreview_reset_userdata(stdClass $data) {
    global $CFG, $DB;

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.
    shift_course_mod_dates('peerreview', array('submissionstart', 'submissionend', 'assessmentstart', 'assessmentend'),
        $data->timeshift, $data->courseid);
    $status = array();
    $status[] = array('component' => get_string('modulenameplural', 'peerreview'), 'item' => get_string('datechanged'),
        'error' => false);

    if (empty($data->reset_peerreview_submissions)
            and empty($data->reset_peerreview_assessments)
            and empty($data->reset_peerreview_phase) ) {
        // Nothing to do here.
        return $status;
    }

    $peerreviewrecords = $DB->get_records('peerreview', array('course' => $data->courseid));

    if (empty($peerreviewrecords)) {
        // What a boring course - no peerreviews here!
        return $status;
    }

    require_once($CFG->dirroot . '/mod/peerreview/locallib.php');

    $course = $DB->get_record('course', array('id' => $data->courseid), '*', MUST_EXIST);

    foreach ($peerreviewrecords as $peerreviewrecord) {
        $cm = get_coursemodule_from_instance('peerreview', $peerreviewrecord->id, $course->id, false, MUST_EXIST);
        $peerreview = new peerreview($peerreviewrecord, $cm, $course);
        $status = array_merge($status, $peerreview->reset_userdata($data));
    }

    return $status;
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_peerreview_get_fontawesome_icon_map() {
    return [
        'mod_peerreview:userplan/task-info' => 'fa-info text-info',
        'mod_peerreview:userplan/task-todo' => 'fa-square-o',
        'mod_peerreview:userplan/task-done' => 'fa-check text-success',
        'mod_peerreview:userplan/task-fail' => 'fa-remove text-danger',
    ];
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.4
 */
function peerreview_check_updates_since(cm_info $cm, $from, $filter = array()) {
    global $DB, $USER;

    $updates = course_check_module_updates_since($cm, $from, array('instructauthors', 'instructreviewers', 'conclusion'), $filter);

    // Check if there are new submissions, assessments or assessments grades in the peerreview.
    $updates->submissions = (object) array('updated' => false);
    $updates->assessments = (object) array('updated' => false);
    $updates->assessmentgrades = (object) array('updated' => false);

    $select = 'peerreviewid = ? AND authorid = ? AND (timecreated > ? OR timegraded > ? OR timemodified > ?)';
    $params = array($cm->instance, $USER->id, $from, $from, $from);
    $submissions = $DB->get_records_select('peerreview_submissions', $select, $params, '', 'id');
    if (!empty($submissions)) {
        $updates->submissions->updated = true;
        $updates->submissions->itemids = array_keys($submissions);
    }

    // Get assessments updates (both submissions reviewed by me or reviews by others).
    $select = "SELECT a.id
                 FROM {peerreview_assessments} a
                 JOIN {peerreview_submissions} s ON a.submissionid = s.id
                 WHERE s.peerreviewid = ? AND (a.timecreated > ? OR a.timemodified > ?) AND (s.authorid = ? OR a.reviewerid = ?)";
    $params = array($cm->instance, $from, $from, $USER->id, $USER->id);
    $assessments = $DB->get_records_sql($select, $params);
    if (!empty($assessments)) {
        $updates->assessments->updated = true;
        $updates->assessments->itemids = array_keys($assessments);
    }
    // Finally assessment aggregated grades.
    $select = 'peerreviewid = ? AND userid = ? AND timegraded > ?';
    $params = array($cm->instance, $USER->id, $from);
    $assessmentgrades = $DB->get_records_select('peerreview_aggregations', $select, $params, '', 'id');
    if (!empty($assessmentgrades)) {
        $updates->assessmentgrades->updated = true;
        $updates->assessmentgrades->itemids = array_keys($assessmentgrades);
    }

    // Now, teachers should see other students updates.
    $canviewallsubmissions = has_capability('mod/peerreview:viewallsubmissions', $cm->context);
    $canviewallassessments = has_capability('mod/peerreview:viewallassessments', $cm->context);
    if ($canviewallsubmissions || $canviewallassessments) {

        $insql = '';
        $inparams = array();
        // To filter by users in my groups when separated groups are forced.
        if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) {
            $groupusers = array_keys(groups_get_activity_shared_group_members($cm));
            if (empty($groupusers)) {
                return $updates;
            }
            list($insql, $inparams) = $DB->get_in_or_equal($groupusers);
        }

        if ($canviewallsubmissions) {
            $updates->usersubmissions = (object) array('updated' => false);
            $select = 'peerreviewid = ? AND (timecreated > ? OR timegraded > ? OR timemodified > ?)';
            $params = array($cm->instance, $from, $from, $from);
            if (!empty($insql)) {
                $select .= " AND authorid $insql";
                $params = array_merge($params, $inparams);
            }
            $usersubmissions = $DB->get_records_select('peerreview_submissions', $select, $params, '', 'id');
            if (!empty($usersubmissions)) {
                $updates->usersubmissions->updated = true;
                $updates->usersubmissions->itemids = array_keys($usersubmissions);
            }
        }

        if ($canviewallassessments) {
            $updates->userassessments = (object) array('updated' => false);
            $select = "SELECT a.id
                         FROM {peerreview_assessments} a
                         JOIN {peerreview_submissions} s ON a.submissionid = s.id
                        WHERE s.peerreviewid = ? AND (a.timecreated > ? OR a.timemodified > ?)";
            $params = array($cm->instance, $from, $from);
            if (!empty($insql)) {
                $select .= " AND s.reviewerid $insql";
                $params = array_merge($params, $inparams);
            }
            $userassessments = $DB->get_records_sql($select, $params);
            if (!empty($userassessments)) {
                $updates->userassessments->updated = true;
                $updates->userassessments->itemids = array_keys($userassessments);
            }

            $updates->userassessmentgrades = (object) array('updated' => false);
            $select = 'peerreviewid = ? AND timegraded > ?';
            $params = array($cm->instance, $USER->id);
            if (!empty($insql)) {
                $select .= " AND userid $insql";
                $params = array_merge($params, $inparams);
            }
            $userassessmentgrades = $DB->get_records_select('peerreview_aggregations', $select, $params, '', 'id');
            if (!empty($userassessmentgrades)) {
                $updates->userassessmentgrades->updated = true;
                $updates->userassessmentgrades->itemids = array_keys($userassessmentgrades);
            }
        }
    }
    return $updates;
}

/**
 * Given an array with a file path, it returns the itemid and the filepath for the defined filearea.
 *
 * @param  string $filearea The filearea.
 * @param  array  $args The path (the part after the filearea and before the filename).
 * @return array|null The itemid and the filepath inside the $args path, for the defined filearea.
 */
function mod_peerreview_get_path_from_pluginfile(string $filearea, array $args) : ?array {
    if ($filearea !== 'instructauthors' && $filearea !== 'instructreviewers' && $filearea !== 'conclusion') {
        return null;
    }

    // peerreview only has empty itemid for some of the fileareas.
    array_shift($args);

    // Get the filepath.
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    return [
        'itemid' => 0,
        'filepath' => $filepath,
    ];
}

/**
 * Add a get_coursemodule_info function in case any feedback type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info|false An object on information that the courses will know about (most noticeably, an icon).
 */
function peerreview_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, submissionstart, submissionend, assessmentstart, assessmentend';
    if (!$peerreview = $DB->get_record('peerreview', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $peerreview->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('peerreview', $peerreview, $coursemodule->id, false);
    }

    // Populate some other values that can be used in calendar or on dashboard.
    if ($peerreview->submissionstart) {
        $result->customdata['submissionstart'] = $peerreview->submissionstart;
    }
    if ($peerreview->submissionend) {
        $result->customdata['submissionend'] = $peerreview->submissionend;
    }
    if ($peerreview->assessmentstart) {
        $result->customdata['assessmentstart'] = $peerreview->assessmentstart;
    }
    if ($peerreview->assessmentend) {
        $result->customdata['assessmentend'] = $peerreview->assessmentend;
    }

    return $result;
}

/**
 * Get the current user preferences that are available
 *
 * @return array[]
 */
function mod_peerreview_user_preferences(): array {
    $preferencedefinition = [
        'type' => PARAM_BOOL,
        'null' => NULL_NOT_ALLOWED,
        'default' => false,
        'permissioncallback' => [core_user::class, 'is_current_user'],
    ];

    return [
        'peerreview-viewlet-allexamples-collapsed' => $preferencedefinition,
        'peerreview-viewlet-allsubmissions-collapsed' => $preferencedefinition,
        'peerreview-viewlet-assessmentform-collapsed' => $preferencedefinition,
        'peerreview-viewlet-assignedassessments-collapsed' => $preferencedefinition,
        'peerreview-viewlet-cleargrades-collapsed' => $preferencedefinition,
        'peerreview-viewlet-conclusion-collapsed' => $preferencedefinition,
        'peerreview-viewlet-examples-collapsed' => $preferencedefinition,
        'peerreview-viewlet-examplesfail-collapsed' => $preferencedefinition,
        'peerreview-viewlet-gradereport-collapsed' => $preferencedefinition,
        'peerreview-viewlet-instructauthors-collapsed' => $preferencedefinition,
        'peerreview-viewlet-instructreviewers-collapsed' => $preferencedefinition,
        'peerreview-viewlet-intro-collapsed' => $preferencedefinition,
        'peerreview-viewlet-ownsubmission-collapsed' => $preferencedefinition,
        'peerreview-viewlet-publicsubmissions-collapsed' => $preferencedefinition,
        'peerreview-viewlet-yourgrades-collapsed' => $preferencedefinition,
    ];
}

/**
 * Callback to fetch the activity event type lang string.
 *
 * @param string $eventtype The event type.
 * @return lang_string The event type lang string.
 */
function mod_peerreview_core_calendar_get_event_action_string($eventtype): string {
    $modulename = get_string('modulename', 'peerreview');

    switch ($eventtype) {
        case peerreview_EVENT_TYPE_SUBMISSION_OPEN:
            $identifier = 'submissionstartevent';
            break;
        case peerreview_EVENT_TYPE_SUBMISSION_CLOSE:
            $identifier = 'submissionendevent';
            break;
        case peerreview_EVENT_TYPE_ASSESSMENT_OPEN:
            $identifier = 'assessmentstartevent';
            break;
        case peerreview_EVENT_TYPE_ASSESSMENT_CLOSE;
            $identifier = 'assessmentendevent';
            break;
        default:
            return get_string('requiresaction', 'calendar', $modulename);
    }

    return get_string($identifier, 'peerreview', $modulename);
}
