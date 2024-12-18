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
 * @package    peerreviewform_accumulative
 * @copyright  2010 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Provides the information to backup accumulative grading strategy information
 */
class backup_peerreviewform_accumulative_subplugin extends backup_subplugin {

    /**
     * Returns the assessment form definition to attach to 'peerreview' XML element
     */
    protected function define_peerreview_subplugin_structure() {

        // XML nodes declaration
        $subplugin = $this->get_subplugin_element(); // virtual optigroup element
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        $subplugindimension = new backup_nested_element('peerreviewform_accumulative_dimension', array('id'), array(
            'sort', 'description', 'descriptionformat', 'grade', 'weight'));

        // connect XML elements into the tree
        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($subplugindimension);

        // set source to populate the data
        $subplugindimension->set_source_table('peerreviewform_accumulative', array('peerreviewid' => backup::VAR_ACTIVITYID));

        // id annotations
        $subplugindimension->annotate_ids('scale', 'grade');

        // file annotations
        $subplugindimension->annotate_files('peerreviewform_accumulative', 'description', 'id');

        return $subplugin;
    }

    /**
     * Returns the dimension grades to attach to 'referenceassessment' XML element
     */
    protected function define_referenceassessment_subplugin_structure() {
        return $this->dimension_grades_structure('peerreviewform_accumulative_referencegrade');
    }

    /**
     * Returns the dimension grades to attach to 'exampleassessment' XML element
     */
    protected function define_exampleassessment_subplugin_structure() {
        return $this->dimension_grades_structure('peerreviewform_accumulative_examplegrade');
    }

    /**
     * Returns the dimension grades to attach to 'assessment' XML element
     */
    protected function define_assessment_subplugin_structure() {
        return $this->dimension_grades_structure('peerreviewform_accumulative_grade');
    }

    ////////////////////////////////////////////////////////////////////////////
    // internal private methods
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Returns the structure of dimension grades
     *
     * @param string first parameter of {@link backup_nested_element} constructor
     */
    private function dimension_grades_structure($elementname) {

        // create XML elements
        $subplugin = $this->get_subplugin_element(); // virtual optigroup element
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        $subplugingrade = new backup_nested_element($elementname, array('id'), array(
            'dimensionid', 'grade', 'peercomment', 'peercommentformat'));

        // connect XML elements into the tree
        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($subplugingrade);

        // set source to populate the data
        $subplugingrade->set_source_sql(
            "SELECT id, dimensionid, grade, peercomment, peercommentformat
               FROM {peerreview_grades}
              WHERE strategy = 'accumulative' AND assessmentid = ?",
              array(backup::VAR_PARENTID));

        return $subplugin;
    }
}
