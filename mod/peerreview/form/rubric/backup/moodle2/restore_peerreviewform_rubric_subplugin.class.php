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
 * @package    peerreviewform_rubric
 * @copyright  2010 onwards David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * restore subplugin class that provides the necessary information
 * needed to restore one peerreviewform_rubric subplugin.
 */
class restore_peerreviewform_rubric_subplugin extends restore_subplugin {

    ////////////////////////////////////////////////////////////////////////////
    // mappings of XML paths to the processable methods
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the paths to be handled by the subplugin at peerreview level
     */
    protected function define_peerreview_subplugin_structure() {

        $paths = array();

        $elename = $this->get_namefor('config');
        $elepath = $this->get_pathfor('/peerreviewform_rubric_config');
        $paths[] = new restore_path_element($elename, $elepath);

        $elename = $this->get_namefor('dimension');
        $elepath = $this->get_pathfor('/peerreviewform_rubric_dimension');
        $paths[] = new restore_path_element($elename, $elepath);

        $elename = $this->get_namefor('level');
        $elepath = $this->get_pathfor('/peerreviewform_rubric_dimension/peerreviewform_rubric_level');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And we return the interesting paths
    }

    /**
     * Returns the paths to be handled by the subplugin at referenceassessment level
     */
    protected function define_referenceassessment_subplugin_structure() {

        $paths = array();

        $elename = $this->get_namefor('referencegrade');
        $elepath = $this->get_pathfor('/peerreviewform_rubric_referencegrade'); // we used get_recommended_name() so this works
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And we return the interesting paths
    }

    /**
     * Returns the paths to be handled by the subplugin at exampleassessment level
     */
    protected function define_exampleassessment_subplugin_structure() {

        $paths = array();

        $elename = $this->get_namefor('examplegrade');
        $elepath = $this->get_pathfor('/peerreviewform_rubric_examplegrade'); // we used get_recommended_name() so this works
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And we return the interesting paths
    }

    /**
     * Returns the paths to be handled by the subplugin at assessment level
     */
    protected function define_assessment_subplugin_structure() {

        $paths = array();

        $elename = $this->get_namefor('grade');
        $elepath = $this->get_pathfor('/peerreviewform_rubric_grade'); // we used get_recommended_name() so this works
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And we return the interesting paths
    }

    ////////////////////////////////////////////////////////////////////////////
    // defined path elements are dispatched to the following methods
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Processes the peerreviewform_rubric_map element
     */
    public function process_peerreviewform_rubric_config($data) {
        global $DB;

        $data = (object)$data;
        $data->peerreviewid = $this->get_new_parentid('peerreview');
        $DB->insert_record('peerreviewform_rubric_config', $data);
    }

    /**
     * Processes the peerreviewform_rubric_dimension element
     */
    public function process_peerreviewform_rubric_dimension($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->peerreviewid = $this->get_new_parentid('peerreview');

        $newitemid = $DB->insert_record('peerreviewform_rubric', $data);
        $this->set_mapping($this->get_namefor('dimension'), $oldid, $newitemid, true);

        // Process files for this peerreviewform_rubric->id only
        $this->add_related_files('peerreviewform_rubric', 'description', $this->get_namefor('dimension'), null, $oldid);
    }

    /**
     * Processes the peerreviewform_rubric_level element
     */
    public function process_peerreviewform_rubric_level($data) {
        global $DB;

        $data = (object)$data;
        $data->dimensionid = $this->get_new_parentid($this->get_namefor('dimension'));
        $DB->insert_record('peerreviewform_rubric_levels', $data);
    }

    /**
     * Processes the peerreviewform_rubric_referencegrade element
     */
    public function process_peerreviewform_rubric_referencegrade($data) {
        $this->process_dimension_grades_structure('peerreview_referenceassessment', $data);
    }

    /**
     * Processes the peerreviewform_rubric_examplegrade element
     */
    public function process_peerreviewform_rubric_examplegrade($data) {
        $this->process_dimension_grades_structure('peerreview_exampleassessment', $data);
    }

    /**
     * Processes the peerreviewform_rubric_grade element
     */
    public function process_peerreviewform_rubric_grade($data) {
        $this->process_dimension_grades_structure('peerreview_assessment', $data);
    }

    ////////////////////////////////////////////////////////////////////////////
    // internal private methods
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Process the dimension grades linked with the given type of assessment
     *
     * Populates the peerreview_grades table with new records mapped to the restored
     * instances of assessments.
     *
     * @param mixed $elementname the name of the assessment element
     * @param array $data parsed xml data
     */
    private function process_dimension_grades_structure($elementname, $data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->assessmentid = $this->get_new_parentid($elementname);
        $data->strategy = 'rubric';
        $data->dimensionid = $this->get_mappingid($this->get_namefor('dimension'), $data->dimensionid);

        $DB->insert_record('peerreview_grades', $data);
    }
}
