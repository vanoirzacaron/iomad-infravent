<?php

// This file is part of Moodle - http://moodle.org/
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
 * Submit an assignment or edit the already submitted work
 *
 * @package    mod_peerreview
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class peerreview_submission_form extends moodleform {

    function definition() {
        $mform = $this->_form;

        $current        = $this->_customdata['current'];
        $peerreview       = $this->_customdata['peerreview'];
        $contentopts    = $this->_customdata['contentopts'];
        $attachmentopts = $this->_customdata['attachmentopts'];
        
        $mform->addElement('header', 'general', get_string('submission', 'peerreview'));

        $mform->addElement('text', 'title', get_string('submissiontitle', 'peerreview'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        /* EDIT: add description field */
        $mform->addElement('editor', 'description_editor', get_string('descriptioncontent', 'peerreview'), null, $contentopts);
        $mform->setType('description_editor', PARAM_RAW);
        $mform->addRule('description_editor', null, 'required', null, 'client');
                
        if ($peerreview->submissiontypetext != peerreview_SUBMISSION_TYPE_DISABLED) {
            $mform->addElement('editor', 'content_editor', get_string('submissioncontent', 'peerreview'), null, $contentopts);
            $mform->setType('content_editor', PARAM_RAW);
            if ($peerreview->submissiontypetext == peerreview_SUBMISSION_TYPE_REQUIRED) {
                $mform->addRule('content_editor', null, 'required', null, 'client');
            }
        }

        /* Add the text input element below the Submission content */
        $mform->addElement('text', 'groupcoauthor', get_string('groupcoauthor', 'peerreview'), 'size="50"');
        $mform->setType('groupcoauthor', PARAM_TEXT);
        // $mform->addRule('reviewers', null, 'required', null, 'client');
        $mform->addElement('static', 'groupcoauthor_tip', '', get_string('groupcoauthortip', 'peerreview'));

        if ($peerreview->submissiontypefile != peerreview_SUBMISSION_TYPE_DISABLED) {
            $mform->addElement('static', 'filemanagerinfo', get_string('nattachments', 'peerreview'), $peerreview->nattachments);
            $mform->addElement('filemanager', 'attachment_filemanager', get_string('submissionattachment', 'peerreview'),
                                null, $attachmentopts);
            if ($peerreview->submissiontypefile == peerreview_SUBMISSION_TYPE_REQUIRED) {
                $mform->addRule('attachment_filemanager', null, 'required', null, 'client');
            }
        }
        
        $mform->addElement('hidden', 'id', $current->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'cmid', $peerreview->cm->id);
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'edit', 1);
        $mform->setType('edit', PARAM_INT);

        $mform->addElement('hidden', 'example', 0);
        $mform->setType('example', PARAM_INT);

        $this->add_action_buttons();

        $this->set_data($current);
    }

    function validation($data, $files) {
        global $CFG, $USER, $DB;

        $errors = parent::validation($data, $files);

        $errors += $this->_customdata['peerreview']->validate_submission_data($data);

        return $errors;
    }
}

