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
 * @package   mod_peerreview
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_peerreview_activity_task
 */

/**
 * Structure step to restore one peerreview activity
 */
class restore_peerreview_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();

        $userinfo = $this->get_setting_value('userinfo'); // are we including userinfo?

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - non-user data
        ////////////////////////////////////////////////////////////////////////

        // root element describing peerreview instance
        $peerreview = new restore_path_element('peerreview', '/activity/peerreview');
        $paths[] = $peerreview;

        // Apply for 'peerreviewform' subplugins optional paths at peerreview level
        $this->add_subplugin_structure('peerreviewform', $peerreview);

        // Apply for 'peerrevieweval' subplugins optional paths at peerreview level
        $this->add_subplugin_structure('peerrevieweval', $peerreview);

        // example submissions
        $paths[] = new restore_path_element('peerreview_examplesubmission',
                       '/activity/peerreview/examplesubmissions/examplesubmission');

        // reference assessment of the example submission
        $referenceassessment = new restore_path_element('peerreview_referenceassessment',
                                   '/activity/peerreview/examplesubmissions/examplesubmission/referenceassessment');
        $paths[] = $referenceassessment;

        // Apply for 'peerreviewform' subplugins optional paths at referenceassessment level
        $this->add_subplugin_structure('peerreviewform', $referenceassessment);

        // End here if no-user data has been selected
        if (!$userinfo) {
            return $this->prepare_activity_structure($paths);
        }

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - user data
        ////////////////////////////////////////////////////////////////////////

        // assessments of example submissions
        $exampleassessment = new restore_path_element('peerreview_exampleassessment',
                                 '/activity/peerreview/examplesubmissions/examplesubmission/exampleassessments/exampleassessment');
        $paths[] = $exampleassessment;

        // Apply for 'peerreviewform' subplugins optional paths at exampleassessment level
        $this->add_subplugin_structure('peerreviewform', $exampleassessment);

        // submissions
        $paths[] = new restore_path_element('peerreview_submission', '/activity/peerreview/submissions/submission');

        // allocated assessments
        $assessment = new restore_path_element('peerreview_assessment',
                          '/activity/peerreview/submissions/submission/assessments/assessment');
        $paths[] = $assessment;

        // Apply for 'peerreviewform' subplugins optional paths at assessment level
        $this->add_subplugin_structure('peerreviewform', $assessment);

        // aggregations of grading grades in this peerreview
        $paths[] = new restore_path_element('peerreview_aggregation', '/activity/peerreview/aggregations/aggregation');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_peerreview($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        $data->submissionstart = $this->apply_date_offset($data->submissionstart);
        $data->submissionend = $this->apply_date_offset($data->submissionend);
        $data->assessmentstart = $this->apply_date_offset($data->assessmentstart);
        $data->assessmentend = $this->apply_date_offset($data->assessmentend);

        if ($data->nattachments == 0) {
            // Convert to the new method for disabling file submissions.
            $data->submissiontypefile = peerreview_SUBMISSION_TYPE_DISABLED;
            $data->submissiontypetext = peerreview_SUBMISSION_TYPE_REQUIRED;
            $data->nattachments = 1;
        }

        // insert the peerreview record
        $newitemid = $DB->insert_record('peerreview', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_peerreview_examplesubmission($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->peerreviewid = $this->get_new_parentid('peerreview');
        $data->example = 1;
        $data->authorid = $this->task->get_userid();

        $newitemid = $DB->insert_record('peerreview_submissions', $data);
        $this->set_mapping('peerreview_examplesubmission', $oldid, $newitemid, true); // Mapping with files
    }

    protected function process_peerreview_referenceassessment($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->submissionid = $this->get_new_parentid('peerreview_examplesubmission');
        $data->reviewerid = $this->task->get_userid();

        $newitemid = $DB->insert_record('peerreview_assessments', $data);
        $this->set_mapping('peerreview_referenceassessment', $oldid, $newitemid, true); // Mapping with files
    }

    protected function process_peerreview_exampleassessment($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->submissionid = $this->get_new_parentid('peerreview_examplesubmission');
        $data->reviewerid = $this->get_mappingid('user', $data->reviewerid);

        $newitemid = $DB->insert_record('peerreview_assessments', $data);
        $this->set_mapping('peerreview_exampleassessment', $oldid, $newitemid, true); // Mapping with files
    }

    protected function process_peerreview_submission($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->peerreviewid = $this->get_new_parentid('peerreview');
        $data->example = 0;
        $data->authorid = $this->get_mappingid('user', $data->authorid);

        $newitemid = $DB->insert_record('peerreview_submissions', $data);
        $this->set_mapping('peerreview_submission', $oldid, $newitemid, true); // Mapping with files
    }

    protected function process_peerreview_assessment($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->submissionid = $this->get_new_parentid('peerreview_submission');
        $data->reviewerid = $this->get_mappingid('user', $data->reviewerid);

        $newitemid = $DB->insert_record('peerreview_assessments', $data);
        $this->set_mapping('peerreview_assessment', $oldid, $newitemid, true); // Mapping with files
    }

    protected function process_peerreview_aggregation($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->peerreviewid = $this->get_new_parentid('peerreview');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('peerreview_aggregations', $data);
        $this->set_mapping('peerreview_aggregation', $oldid, $newitemid, true);
    }

    protected function after_execute() {
        // Add peerreview related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_peerreview', 'intro', null);
        $this->add_related_files('mod_peerreview', 'instructauthors', null);
        $this->add_related_files('mod_peerreview', 'instructreviewers', null);
        $this->add_related_files('mod_peerreview', 'conclusion', null);

        // Add example submission related files, matching by 'peerreview_examplesubmission' itemname
        $this->add_related_files('mod_peerreview', 'submission_content', 'peerreview_examplesubmission');
        $this->add_related_files('mod_peerreview', 'submission_attachment', 'peerreview_examplesubmission');

        // Add reference assessment related files, matching by 'peerreview_referenceassessment' itemname
        $this->add_related_files('mod_peerreview', 'overallfeedback_content', 'peerreview_referenceassessment');
        $this->add_related_files('mod_peerreview', 'overallfeedback_attachment', 'peerreview_referenceassessment');

        // Add example assessment related files, matching by 'peerreview_exampleassessment' itemname
        $this->add_related_files('mod_peerreview', 'overallfeedback_content', 'peerreview_exampleassessment');
        $this->add_related_files('mod_peerreview', 'overallfeedback_attachment', 'peerreview_exampleassessment');

        // Add submission related files, matching by 'peerreview_submission' itemname
        $this->add_related_files('mod_peerreview', 'submission_content', 'peerreview_submission');
        $this->add_related_files('mod_peerreview', 'submission_attachment', 'peerreview_submission');

        // Add assessment related files, matching by 'peerreview_assessment' itemname
        $this->add_related_files('mod_peerreview', 'overallfeedback_content', 'peerreview_assessment');
        $this->add_related_files('mod_peerreview', 'overallfeedback_attachment', 'peerreview_assessment');
    }
}
