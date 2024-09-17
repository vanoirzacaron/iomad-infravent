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
 * Scheduled allocator's settings
 *
 * @package     peerreviewallocation_scheduled
 * @subpackage  mod_peerreview
 * @copyright   2012 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');
require_once(__DIR__ . '/../random/settings_form.php'); // parent form

/**
 * Allocator settings form
 *
 * This is used by {@see peerreview_scheduled_allocator::ui()} to set up allocation parameters.
 */
class peerreview_scheduled_allocator_form extends peerreview_random_allocator_form {

    /**
     * Definition of the setting form elements
     */
    public function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $peerreview = $this->_customdata['peerreview'];
        $current = $this->_customdata['current'];

        if (!empty($peerreview->submissionend)) {
            $strtimeexpected = peerreview::timestamp_formats($peerreview->submissionend);
        }

        if (!empty($current->timeallocated)) {
            $strtimeexecuted = peerreview::timestamp_formats($current->timeallocated);
        }

        $mform->addElement('header', 'scheduledallocationsettings', get_string('scheduledallocationsettings', 'peerreviewallocation_scheduled'));
        $mform->addHelpButton('scheduledallocationsettings', 'scheduledallocationsettings', 'peerreviewallocation_scheduled');

        $mform->addElement('checkbox', 'enablescheduled', get_string('enablescheduled', 'peerreviewallocation_scheduled'), get_string('enablescheduledinfo', 'peerreviewallocation_scheduled'), 1);

        $mform->addElement('header', 'scheduledallocationinfo', get_string('currentstatus', 'peerreviewallocation_scheduled'));

        if ($current === false) {
            $mform->addElement('static', 'infostatus', get_string('currentstatusexecution', 'peerreviewallocation_scheduled'),
                get_string('resultdisabled', 'peerreviewallocation_scheduled').' '. $OUTPUT->pix_icon('i/invalid', ''));

        } else {
            if (!empty($current->timeallocated)) {
                $mform->addElement('static', 'infostatus', get_string('currentstatusexecution', 'peerreviewallocation_scheduled'),
                    get_string('currentstatusexecution1', 'peerreviewallocation_scheduled', $strtimeexecuted).' '.
                    $OUTPUT->pix_icon('i/valid', ''));

                if ($current->resultstatus == peerreview_allocation_result::STATUS_EXECUTED) {
                    $strstatus = get_string('resultexecuted', 'peerreviewallocation_scheduled').' '.
                        $OUTPUT->pix_icon('i/valid', '');

                } else if ($current->resultstatus == peerreview_allocation_result::STATUS_FAILED) {
                    $strstatus = get_string('resultfailed', 'peerreviewallocation_scheduled').' '.
                        $OUTPUT->pix_icon('i/invalid', '');

                } else {
                    $strstatus = get_string('resultvoid', 'peerreviewallocation_scheduled').' '.
                        $OUTPUT->pix_icon('i/invalid', '');

                }

                if (!empty($current->resultmessage)) {
                    $strstatus .= html_writer::empty_tag('br').$current->resultmessage; // yes, this is ugly. better solution suggestions are welcome.
                }
                $mform->addElement('static', 'inforesult', get_string('currentstatusresult', 'peerreviewallocation_scheduled'), $strstatus);

                if ($current->timeallocated < $peerreview->submissionend) {
                    $mform->addElement('static', 'infoexpected', get_string('currentstatusnext', 'peerreviewallocation_scheduled'),
                        get_string('currentstatusexecution2', 'peerreviewallocation_scheduled', $strtimeexpected).' '.
                        $OUTPUT->pix_icon('i/caution', ''));
                    $mform->addHelpButton('infoexpected', 'currentstatusnext', 'peerreviewallocation_scheduled');
                } else {
                    $mform->addElement('checkbox', 'reenablescheduled', get_string('currentstatusreset', 'peerreviewallocation_scheduled'),
                       get_string('currentstatusresetinfo', 'peerreviewallocation_scheduled'));
                    $mform->addHelpButton('reenablescheduled', 'currentstatusreset', 'peerreviewallocation_scheduled');
                }

            } else if (empty($current->enabled)) {
                $mform->addElement('static', 'infostatus', get_string('currentstatusexecution', 'peerreviewallocation_scheduled'),
                    get_string('resultdisabled', 'peerreviewallocation_scheduled').' '.
                        $OUTPUT->pix_icon('i/invalid', ''));

            } else if ($peerreview->phase != peerreview::PHASE_SUBMISSION) {
                $mform->addElement('static', 'infostatus', get_string('currentstatusexecution', 'peerreviewallocation_scheduled'),
                    get_string('resultfailed', 'peerreviewallocation_scheduled').' '.
                    $OUTPUT->pix_icon('i/invalid', '') .
                    html_writer::empty_tag('br').
                    get_string('resultfailedphase', 'peerreviewallocation_scheduled'));

            } else if (empty($peerreview->submissionend)) {
                $mform->addElement('static', 'infostatus', get_string('currentstatusexecution', 'peerreviewallocation_scheduled'),
                    get_string('resultfailed', 'peerreviewallocation_scheduled').' '.
                    $OUTPUT->pix_icon('i/invalid', '') .
                    html_writer::empty_tag('br').
                    get_string('resultfaileddeadline', 'peerreviewallocation_scheduled'));

            } else if ($peerreview->submissionend < time()) {
                // next cron will execute it
                $mform->addElement('static', 'infostatus', get_string('currentstatusexecution', 'peerreviewallocation_scheduled'),
                    get_string('currentstatusexecution4', 'peerreviewallocation_scheduled').' '.
                    $OUTPUT->pix_icon('i/caution', ''));

            } else {
                $mform->addElement('static', 'infostatus', get_string('currentstatusexecution', 'peerreviewallocation_scheduled'),
                    get_string('currentstatusexecution3', 'peerreviewallocation_scheduled', $strtimeexpected).' '.
                    $OUTPUT->pix_icon('i/caution', ''));
            }
        }

        parent::definition();

        $mform->addHelpButton('randomallocationsettings', 'randomallocationsettings', 'peerreviewallocation_scheduled');
    }
}
