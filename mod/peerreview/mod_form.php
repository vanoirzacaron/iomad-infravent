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
 * The main peerreview configuration form
 *
 * The UI mockup has been proposed in MDL-18688
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: https://moodledev.io/docs/apis/subsystems/form
 *
 * @package    mod_peerreview
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(__DIR__ . '/locallib.php');
require_once($CFG->libdir . '/filelib.php');

use core_grades\component_gradeitems;
/**
 * Module settings form for peerreview instances
 */
class mod_peerreview_mod_form extends moodleform_mod {

    /** @var object the course this instance is part of */
    protected $course = null;

    /**
     * Constructor
     */
    public function __construct($current, $section, $cm, $course) {
        $this->course = $course;
        parent::__construct($current, $section, $cm, $course);
    }

    /**
     * Defines the peerreview instance configuration form
     *
     * @return void
     */
    public function definition() {
        global $CFG, $PAGE;

        $peerreviewconfig = get_config('peerreview');
        $mform = $this->_form;

        // General --------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // peerreview name
        $label = get_string('peerreviewname', 'peerreview');
        $mform->addElement('text', 'name', $label, array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Introduction
        $this->standard_intro_elements(get_string('introduction', 'peerreview'));

        // Grading settings -----------------------------------------------------------
        $mform->addElement('header', 'gradingsettings', get_string('gradingsettings', 'peerreview'));
        $mform->setExpanded('gradingsettings');

        $label = get_string('strategy', 'peerreview');
        $mform->addElement('select', 'strategy', $label, peerreview::available_strategies_list());
        $mform->setDefault('strategy', $peerreviewconfig->strategy);
        $mform->addHelpButton('strategy', 'strategy', 'peerreview');

        $grades = peerreview::available_maxgrades_list();
        $gradecategories = grade_get_categories_menu($this->course->id);

        $label = get_string('submissiongrade', 'peerreview');
        $mform->addGroup(array(
            $mform->createElement('select', 'grade', '', $grades),
            $mform->createElement('select', 'gradecategory', '', $gradecategories),
            ), 'submissiongradegroup', $label, ' ', false);
        $mform->setDefault('grade', $peerreviewconfig->grade);
        $mform->addHelpButton('submissiongradegroup', 'submissiongrade', 'peerreview');

        $mform->addElement('float', 'submissiongradepass', get_string('gradetopasssubmission', 'peerreview'));
        $mform->addHelpButton('submissiongradepass', 'gradepass', 'grades');
        $mform->setDefault('submissiongradepass', '');

        $label = get_string('gradinggrade', 'peerreview');
        $mform->addGroup(array(
            $mform->createElement('select', 'gradinggrade', '', $grades),
            $mform->createElement('select', 'gradinggradecategory', '', $gradecategories),
            ), 'gradinggradegroup', $label, ' ', false);
        $mform->setDefault('gradinggrade', $peerreviewconfig->gradinggrade);
        $mform->addHelpButton('gradinggradegroup', 'gradinggrade', 'peerreview');

        $mform->addElement('float', 'gradinggradepass', get_string('gradetopassgrading', 'peerreview'));
        $mform->addHelpButton('gradinggradepass', 'gradepass', 'grades');
        $mform->setDefault('gradinggradepass', '');

        $options = array();
        for ($i = 5; $i >= 0; $i--) {
            $options[$i] = $i;
        }
        $label = get_string('gradedecimals', 'peerreview');
        $mform->addElement('select', 'gradedecimals', $label, $options);
        $mform->setDefault('gradedecimals', $peerreviewconfig->gradedecimals);

        // Submission settings --------------------------------------------------------
        $mform->addElement('header', 'submissionsettings', get_string('submissionsettings', 'peerreview'));

        $label = get_string('instructauthors', 'peerreview');
        $mform->addElement('editor', 'instructauthorseditor', $label, null,
                            peerreview::instruction_editors_options($this->context));

        $typeelements = [];
        foreach (['submissiontypetext', 'submissiontypefile'] as $type) {
            $available = $type . 'available';
            $required = $type . 'required';
            $availablelabel = get_string($available, 'peerreview');
            $requiredlabel = get_string($required, 'peerreview');
            $typeelements[] = $mform->createElement('advcheckbox', $available, '', $availablelabel);
            $typeelements[] = $mform->createElement('advcheckbox', $required, '', $requiredlabel);
            $mform->setDefault($available, 1);
        }
        // We can't use <br> as the separator as it does not work well in this case with the Boost theme.
        // Instead, separate both tuples with a full-width empty div.
        $mform->addGroup($typeelements, 'submissiontypes', get_string('submissiontypes', 'peerreview'),
            array(' ', '<div style="width:100%"></div>'), false);

        $options = array();
        for ($i = 7; $i >= 1; $i--) {
            $options[$i] = $i;
        }
        $label = get_string('nattachments', 'peerreview');
        $mform->addElement('select', 'nattachments', $label, $options);
        $mform->setDefault('nattachments', 1);
        $mform->hideIf('nattachments', 'submissiontypefileavailable');

        $label = get_string('allowedfiletypesforsubmission', 'peerreview');
        $mform->addElement('filetypes', 'submissionfiletypes', $label);
        $mform->addHelpButton('submissionfiletypes', 'allowedfiletypesforsubmission', 'peerreview');
        $mform->hideIf('submissionfiletypes', 'submissiontypefileavailable');

        $options = get_max_upload_sizes($CFG->maxbytes, $this->course->maxbytes, 0, $peerreviewconfig->maxbytes);
        $mform->addElement('select', 'maxbytes', get_string('maxbytes', 'peerreview'), $options);
        $mform->setDefault('maxbytes', $peerreviewconfig->maxbytes);
        $mform->hideIf('maxbytes', 'submissiontypefileavailable');

        $label = get_string('latesubmissions', 'peerreview');
        $text = get_string('latesubmissions_desc', 'peerreview');
        $mform->addElement('checkbox', 'latesubmissions', $label, $text);
        $mform->addHelpButton('latesubmissions', 'latesubmissions', 'peerreview');

        // Assessment settings --------------------------------------------------------
        $mform->addElement('header', 'assessmentsettings', get_string('assessmentsettings', 'peerreview'));

        $label = get_string('instructreviewers', 'peerreview');
        $mform->addElement('editor', 'instructreviewerseditor', $label, null,
                            peerreview::instruction_editors_options($this->context));

        $label = get_string('useselfassessment', 'peerreview');
        $text = get_string('useselfassessment_desc', 'peerreview');
        $mform->addElement('checkbox', 'useselfassessment', $label, $text);
        $mform->addHelpButton('useselfassessment', 'useselfassessment', 'peerreview');

        // Feedback -------------------------------------------------------------------
        $mform->addElement('header', 'feedbacksettings', get_string('feedbacksettings', 'peerreview'));

        $mform->addElement('select', 'overallfeedbackmode', get_string('overallfeedbackmode', 'mod_peerreview'), array(
            0 => get_string('overallfeedbackmode_0', 'mod_peerreview'),
            1 => get_string('overallfeedbackmode_1', 'mod_peerreview'),
            2 => get_string('overallfeedbackmode_2', 'mod_peerreview')));
        $mform->addHelpButton('overallfeedbackmode', 'overallfeedbackmode', 'mod_peerreview');
        $mform->setDefault('overallfeedbackmode', 1);

        $options = array();
        for ($i = 7; $i >= 0; $i--) {
            $options[$i] = $i;
        }
        $mform->addElement('select', 'overallfeedbackfiles', get_string('overallfeedbackfiles', 'peerreview'), $options);
        $mform->setDefault('overallfeedbackfiles', 0);
        $mform->hideIf('overallfeedbackfiles', 'overallfeedbackmode', 'eq', 0);

        $label = get_string('allowedfiletypesforoverallfeedback', 'peerreview');
        $mform->addElement('filetypes', 'overallfeedbackfiletypes', $label);
        $mform->addHelpButton('overallfeedbackfiletypes', 'allowedfiletypesforoverallfeedback', 'peerreview');
        $mform->hideIf('overallfeedbackfiletypes', 'overallfeedbackfiles', 'eq', 0);

        $options = get_max_upload_sizes($CFG->maxbytes, $this->course->maxbytes);
        $mform->addElement('select', 'overallfeedbackmaxbytes', get_string('overallfeedbackmaxbytes', 'peerreview'), $options);
        $mform->setDefault('overallfeedbackmaxbytes', $peerreviewconfig->maxbytes);
        $mform->hideIf('overallfeedbackmaxbytes', 'overallfeedbackmode', 'eq', 0);
        $mform->hideIf('overallfeedbackmaxbytes', 'overallfeedbackfiles', 'eq', 0);

        $label = get_string('conclusion', 'peerreview');
        $mform->addElement('editor', 'conclusioneditor', $label, null,
                            peerreview::instruction_editors_options($this->context));
        $mform->addHelpButton('conclusioneditor', 'conclusion', 'peerreview');

        // Example submissions --------------------------------------------------------
        $mform->addElement('header', 'examplesubmissionssettings', get_string('examplesubmissions', 'peerreview'));

        $label = get_string('useexamples', 'peerreview');
        $text = get_string('useexamples_desc', 'peerreview');
        $mform->addElement('checkbox', 'useexamples', $label, $text);
        $mform->addHelpButton('useexamples', 'useexamples', 'peerreview');

        $label = get_string('examplesmode', 'peerreview');
        $options = peerreview::available_example_modes_list();
        $mform->addElement('select', 'examplesmode', $label, $options);
        $mform->setDefault('examplesmode', $peerreviewconfig->examplesmode);
        $mform->hideIf('examplesmode', 'useexamples');

        // Availability ---------------------------------------------------------------
        $mform->addElement('header', 'accesscontrol', get_string('availability', 'core'));

        $label = get_string('submissionstart', 'peerreview');
        $mform->addElement('date_time_selector', 'submissionstart', $label, array('optional' => true));

        $label = get_string('submissionend', 'peerreview');
        $mform->addElement('date_time_selector', 'submissionend', $label, array('optional' => true));

        $label = get_string('submissionendswitch', 'mod_peerreview');
        $mform->addElement('checkbox', 'phaseswitchassessment', $label);
        $mform->hideIf('phaseswitchassessment', 'submissionend[enabled]');
        $mform->addHelpButton('phaseswitchassessment', 'submissionendswitch', 'mod_peerreview');

        $label = get_string('assessmentstart', 'peerreview');
        $mform->addElement('date_time_selector', 'assessmentstart', $label, array('optional' => true));

        $label = get_string('assessmentend', 'peerreview');
        $mform->addElement('date_time_selector', 'assessmentend', $label, array('optional' => true));

        // Common module settings, Restrict availability, Activity completion etc. ----
        $features = array('groups' => true, 'groupings' => true,
                'outcomes' => true, 'gradecat' => false, 'idnumber' => false);

        $this->standard_coursemodule_elements();

        // Standard buttons, common to all modules ------------------------------------
        $this->add_action_buttons();

        $PAGE->requires->js_call_amd('mod_peerreview/modform', 'init');
    }

    /**
     * Prepares the form before data are set
     *
     * Additional wysiwyg editor are prepared here, the introeditor is prepared automatically by core.
     * Grade items are set here because the core modedit supports single grade item only.
     *
     * @param array $data to be set
     * @return void
     */
    public function data_preprocessing(&$data) {
        if ($this->current->instance) {
            // editing an existing peerreview - let us prepare the added editor elements (intro done automatically)
            $draftitemid = file_get_submitted_draft_itemid('instructauthors');
            $data['instructauthorseditor']['text'] = file_prepare_draft_area($draftitemid, $this->context->id,
                                'mod_peerreview', 'instructauthors', 0,
                                peerreview::instruction_editors_options($this->context),
                                $data['instructauthors']);
            $data['instructauthorseditor']['format'] = $data['instructauthorsformat'];
            $data['instructauthorseditor']['itemid'] = $draftitemid;

            $draftitemid = file_get_submitted_draft_itemid('instructreviewers');
            $data['instructreviewerseditor']['text'] = file_prepare_draft_area($draftitemid, $this->context->id,
                                'mod_peerreview', 'instructreviewers', 0,
                                peerreview::instruction_editors_options($this->context),
                                $data['instructreviewers']);
            $data['instructreviewerseditor']['format'] = $data['instructreviewersformat'];
            $data['instructreviewerseditor']['itemid'] = $draftitemid;

            $draftitemid = file_get_submitted_draft_itemid('conclusion');
            $data['conclusioneditor']['text'] = file_prepare_draft_area($draftitemid, $this->context->id,
                                'mod_peerreview', 'conclusion', 0,
                                peerreview::instruction_editors_options($this->context),
                                $data['conclusion']);
            $data['conclusioneditor']['format'] = $data['conclusionformat'];
            $data['conclusioneditor']['itemid'] = $draftitemid;
            // Set submission type checkboxes.
            foreach (['submissiontypetext', 'submissiontypefile'] as $type) {
                $data[$type . 'available'] = 1;
                $data[$type . 'required'] = 0;
                if ($data[$type] == peerreview_SUBMISSION_TYPE_DISABLED) {
                    $data[$type . 'available'] = 0;
                } else if ($data[$type] == peerreview_SUBMISSION_TYPE_REQUIRED) {
                    $data[$type . 'required'] = 1;
                }
            }
        } else {
            // adding a new peerreview instance
            $draftitemid = file_get_submitted_draft_itemid('instructauthors');
            file_prepare_draft_area($draftitemid, null, 'mod_peerreview', 'instructauthors', 0);    // no context yet, itemid not used
            $data['instructauthorseditor'] = array('text' => '', 'format' => editors_get_preferred_format(), 'itemid' => $draftitemid);

            $draftitemid = file_get_submitted_draft_itemid('instructreviewers');
            file_prepare_draft_area($draftitemid, null, 'mod_peerreview', 'instructreviewers', 0);    // no context yet, itemid not used
            $data['instructreviewerseditor'] = array('text' => '', 'format' => editors_get_preferred_format(), 'itemid' => $draftitemid);

            $draftitemid = file_get_submitted_draft_itemid('conclusion');
            file_prepare_draft_area($draftitemid, null, 'mod_peerreview', 'conclusion', 0);    // no context yet, itemid not used
            $data['conclusioneditor'] = array('text' => '', 'format' => editors_get_preferred_format(), 'itemid' => $draftitemid);
        }
    }

    /**
     * Combine submission type checkboxes into integer values for the database.
     *
     * @param stdClass $data The submitted form data.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);

        foreach (['text', 'file'] as $type) {
            $field = 'submissiontype' . $type;
            $available = $field . 'available';
            $required = $field . 'required';
            if ($data->$required) {
                $data->$field = peerreview_SUBMISSION_TYPE_REQUIRED;
            } else if ($data->$available) {
                $data->$field = peerreview_SUBMISSION_TYPE_AVAILABLE;
            } else {
                $data->$field = peerreview_SUBMISSION_TYPE_DISABLED;
            }
            unset($data->$available);
            unset($data->$required);
        }
    }

    /**
     * Set the grade item categories when editing an instance
     */
    public function definition_after_data() {

        $mform =& $this->_form;

        if ($id = $mform->getElementValue('update')) {
            $instance   = $mform->getElementValue('instance');

            $gradeitems = grade_item::fetch_all(array(
                'itemtype'      => 'mod',
                'itemmodule'    => 'peerreview',
                'iteminstance'  => $instance,
                'courseid'      => $this->course->id));

            if (!empty($gradeitems)) {
                foreach ($gradeitems as $gradeitem) {
                    // here comes really crappy way how to set the value of the fields
                    // gradecategory and gradinggradecategory - grrr QuickForms
                    $decimalpoints = $gradeitem->get_decimals();
                    if ($gradeitem->itemnumber == 0) {
                        $mform->setDefault('submissiongradepass', format_float($gradeitem->gradepass, $decimalpoints));
                        $group = $mform->getElement('submissiongradegroup');
                        $elements = $group->getElements();
                        foreach ($elements as $element) {
                            if ($element->getName() == 'gradecategory') {
                                $element->setValue($gradeitem->categoryid);
                            }
                        }
                    } else if ($gradeitem->itemnumber == 1) {
                        $mform->setDefault('gradinggradepass', format_float($gradeitem->gradepass, $decimalpoints));
                        $group = $mform->getElement('gradinggradegroup');
                        $elements = $group->getElements();
                        foreach ($elements as $element) {
                            if ($element->getName() == 'gradinggradecategory') {
                                $element->setValue($gradeitem->categoryid);
                            }
                        }
                    }
                }
            }
        }
        $typevalues = $mform->getElementValue('submissiontypes');
        foreach (['submissiontypetext', 'submissiontypefile'] as $type) {
            // Don't leave a disabled "required" checkbox checked.
            if (!$typevalues[$type . 'available']) {
                $mform->setDefault($type . 'required', 0);
            }
        }

        parent::definition_after_data();
    }

    /**
     * Validates the form input
     *
     * @param array $data submitted data
     * @param array $files submitted files
     * @return array eventual errors indexed by the field name
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // check the phases borders are valid
        if ($data['submissionstart'] > 0 and $data['submissionend'] > 0 and $data['submissionstart'] >= $data['submissionend']) {
            $errors['submissionend'] = get_string('submissionendbeforestart', 'mod_peerreview');
        }
        if ($data['assessmentstart'] > 0 and $data['assessmentend'] > 0 and $data['assessmentstart'] >= $data['assessmentend']) {
            $errors['assessmentend'] = get_string('assessmentendbeforestart', 'mod_peerreview');
        }

        // check the phases do not overlap
        if (max($data['submissionstart'], $data['submissionend']) > 0 and max($data['assessmentstart'], $data['assessmentend']) > 0) {
            $phasesubmissionend = max($data['submissionstart'], $data['submissionend']);
            $phaseassessmentstart = min($data['assessmentstart'], $data['assessmentend']);
            if ($phaseassessmentstart == 0) {
                $phaseassessmentstart = max($data['assessmentstart'], $data['assessmentend']);
            }
            if ($phasesubmissionend > 0 and $phaseassessmentstart > 0 and $phaseassessmentstart < $phasesubmissionend) {
                foreach (array('submissionend', 'submissionstart', 'assessmentstart', 'assessmentend') as $f) {
                    if ($data[$f] > 0) {
                        $errors[$f] = get_string('phasesoverlap', 'mod_peerreview');
                        break;
                    }
                }
            }
        }

        // Check that the submission grade pass is a valid number.
        if (!empty($data['submissiongradepass'])) {
            $submissiongradefloat = unformat_float($data['submissiongradepass'], true);
            if ($submissiongradefloat === false) {
                $errors['submissiongradepass'] = get_string('err_numeric', 'form');
            } else {
                if ($submissiongradefloat > $data['grade']) {
                    $errors['submissiongradepass'] = get_string('gradepassgreaterthangrade', 'grades', $data['grade']);
                }
            }
        }

        // Check that the grade pass is a valid number.
        if (!empty($data['gradinggradepass'])) {
            $gradepassfloat = unformat_float($data['gradinggradepass'], true);
            if ($gradepassfloat === false) {
                $errors['gradinggradepass'] = get_string('err_numeric', 'form');
            } else {
                if ($gradepassfloat > $data['gradinggrade']) {
                    $errors['gradinggradepass'] = get_string('gradepassgreaterthangrade', 'grades', $data['gradinggrade']);
                }
            }
        }

        // We need to do a custom completion validation because peerreview grade items identifiers divert from standard.
        // Refer to validation defined in moodleform_mod.php.
        if (isset($data['completionpassgrade']) && $data['completionpassgrade'] &&
            isset($data['completiongradeitemnumber'])) {
            $itemnames = component_gradeitems::get_itemname_mapping_for_component('mod_peerreview');
            $gradepassfield = $itemnames[(int) $data['completiongradeitemnumber']] . 'gradepass';
            // We need to make all the validations related with $gradepassfield
            // with them being correct floats, keeping the originals unmodified for
            // later validations / showing the form back...
            // TODO: Note that once MDL-73994 is fixed we'll have to re-visit this and
            // adapt the code below to the new values arriving here, without forgetting
            // the special case of empties and nulls.
            $gradepass = isset($data[$gradepassfield]) ? unformat_float($data[$gradepassfield]) : null;
            if (is_null($gradepass) || $gradepass == 0) {
                $errors['completionpassgrade'] = get_string(
                    'activitygradetopassnotset',
                    'completion'
                );
            } else {
                // We have validated grade pass. Unset any errors.
                unset($errors['completionpassgrade']);
            }
        }

        if (!$data['submissiontypetextavailable'] && !$data['submissiontypefileavailable']) {
            // One submission type must be available.
            $errors['submissiontypes'] = get_string('nosubmissiontype', 'peerreview');
        }

        return $errors;
    }
}
