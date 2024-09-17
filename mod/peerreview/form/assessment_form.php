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
 * This file defines a base class for all assessment forms
 *
 * @package    mod_peerreview
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php'); // parent class definition

/**
 * Base class for all assessment forms
 *
 * This defines the common fields that all assessment forms need.
 * Strategies should define their own class that inherits from this one, and
 * implements the definition_inner() method.
 *
 * @uses moodleform
 */
class peerreview_assessment_form extends moodleform {

    /** @var string Mode to open the form in: preview/assessment. */
    public $mode;

    /** @var peerreview_strategy peerreview strategy */
    public $strategy;

    /** @var peerreview peerreview. */
    public $peerreview;

    /** @var array options. */
    public $options;

    /**
     * Add the fields that are common for all grading strategies.
     *
     * If the strategy does not support all these fields, then you can override
     * this method and remove the ones you don't want with
     * $mform->removeElement().
     * Strategy subclassess should define their own fields in definition_inner()
     *
     * @return void
     */
    public function definition() {
        global $CFG, $DB, $USER;

        $mform          = $this->_form;
        $this->mode     = $this->_customdata['mode'];       // influences the save buttons
        $this->strategy = $this->_customdata['strategy'];   // instance of the strategy api class
        $this->peerreview = $this->_customdata['peerreview'];   // instance of the peerreview api class
        $this->options  = $this->_customdata['options'];    // array with additional options
// die(var_dump($this->peerreview->dbrecord->id));

        // Disable shortforms
        $mform->setDisableShortforms();

        // add the strategy-specific fields
        $this->definition_inner($mform);

        // add the data common for all subplugins
        $mform->addElement('hidden', 'strategy', $this->peerreview->strategy);
        $mform->setType('strategy', PARAM_PLUGIN);

        if ($this->peerreview->overallfeedbackmode and $this->is_editable()) {
            $mform->addElement('header', 'overallfeedbacksection', get_string('overallfeedback', 'mod_peerreview'));
            $mform->addElement('editor', 'feedbackauthor_editor', get_string('feedbackauthor', 'mod_peerreview'), null,
                $this->peerreview->overall_feedback_content_options());
            if ($this->peerreview->overallfeedbackmode == 2) {
                $mform->addRule('feedbackauthor_editor', null, 'required', null, 'client');
            }
            if ($this->peerreview->overallfeedbackfiles) {
                $mform->addElement('filemanager', 'feedbackauthorattachment_filemanager',
                    get_string('feedbackauthorattachment', 'mod_peerreview'), null,
                    $this->peerreview->overall_feedback_attachment_options());
            }
        }

        if (!empty($this->options['editableweight']) and $this->is_editable()) {
            $mform->addElement('header', 'assessmentsettings', get_string('assessmentweight', 'peerreview'));
            $mform->addElement('select', 'weight',
                    get_string('assessmentweight', 'peerreview'), peerreview::available_assessment_weights_list());
            $mform->setDefault('weight', 1);
        }
            // Retrieve the saved value of 'anonymousreview'
            $anonymousreview_default = 0;
    if ($this->peerreview->dbrecord->id) {
        $submission = $DB->get_record('peerreview_assessments', array('submissionid' => $this->peerreview->dbrecord->id, 'reviewerid' => $USER->id));
        if($submission != false){
            $anonymousreview_default = $submission->anonymousreview;
        }
    } 
    
    // Add anonymous review checkbox with dynamic default value
    $mform->addElement('advcheckbox', 'anonymousreview', get_string('anonymousreview', 'mod_peerreview'), null, array('group' => 1), array(0, 1));
    $mform->setDefault('anonymousreview', $anonymousreview_default);


        $buttonarray = array();
        if ($this->mode == 'preview') {
            $buttonarray[] = $mform->createElement('cancel', 'backtoeditform', get_string('backtoeditform', 'peerreview'));
        }
        if ($this->mode == 'assessment') {
            if (!empty($this->options['pending'])) {
                $buttonarray[] = $mform->createElement('submit', 'saveandshownext', get_string('saveandshownext', 'peerreview'));
            }
            $buttonarray[] = $mform->createElement('submit', 'saveandclose', get_string('saveandclose', 'peerreview'));
            $buttonarray[] = $mform->createElement('submit', 'saveandcontinue', get_string('saveandcontinue', 'peerreview'));
            $buttonarray[] = $mform->createElement('cancel');
        }
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Add any strategy specific form fields.
     *
     * @param stdClass $mform the form being built.
     */
    protected function definition_inner(&$mform) {
        // By default, do nothing.
    }

    /**
     * Is the form frozen (read-only)?
     *
     * @return boolean
     */
    public function is_editable() {
        return !$this->_form->isFrozen();
    }

    /**
     * Return the form custom data.
     *
     * @return array an array containing the custom data
     * @since  Moodle 3.4
     */
    public function get_customdata() {
        return $this->_customdata;
    }
}
