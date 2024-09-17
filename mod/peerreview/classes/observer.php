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
 * Event observers used in peerreview.
 *
 * @package    mod_peerreview
 * @copyright  2013 Rajesh Taneja <rajesh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for mod_peerreview.
 */
require_once($CFG->dirroot . '/message/lib.php');

class mod_peerreview_observer {


    /**
     * Event handler for submission_assessed event.
     *
     * @param \mod_peerreview\event\submission_assessed $event
     * @return void
     */
    public static function submission_assessed(\mod_peerreview\event\submission_assessed $event) {
        global $DB;
        
            // Convert event data to string format for logging
    $eventData = $event->get_data();

        try {
            $user = $DB->get_record('user', ['id' => $eventData['relateduserid']]);
            if (!$user) {
                throw new Exception('User not found');
            }

            $a = new \stdClass();
            $a->courseid = $eventData['courseid'];
            $a->entrydetails = $eventData['anonymous'];
            $a->useridnumber = $user->idnumber;
            $a->username = fullname($user);
            $a->userusername = $user->username;
            $a->contexturl = new \moodle_url('/mod/peerreview/submission.php', ['cmid' => $eventData['contextinstanceid']]);
            $a->contexturlname = get_string('pluginname', 'mod_peerreview');
            $a->subject = get_string('submissionassessedsubject', 'mod_peerreview');
            $a->fullmessage = get_string('submissionassessedfullmessage', 'mod_peerreview');
            $a->smallmessage = get_string('submissionassessedfullmessage', 'mod_peerreview');
    
            peerreview_send_alert($user, $a);
    
        } catch (Exception $e) {
            error_log('Error in submission_assessed: ' . $e->getMessage());
        }
    }
    

}
