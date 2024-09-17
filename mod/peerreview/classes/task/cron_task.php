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
 * A scheduled task for peerreview cron.
 *
 * @package    mod_peerreview
 * @copyright  2019 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 namespace mod_peerreview\task;

 defined('MOODLE_INTERNAL') || die();
 
 require_once($CFG->dirroot . '/mod/peerreview/locallib.php');
 /**
  * The main scheduled task for the peerreview.
  *
  * @package   mod_peerreview
  * @copyright 2019 
  * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */
 class cron_task extends \core\task\scheduled_task {
 
     /**
      * Get a descriptive name for this task (shown to admins).
      *
      * @return string
      */
     public function get_name() {
         return get_string('crontask', 'mod_peerreview');
     }
 
     /**
      * Run peerreview cron.
      */
      public function execute() {
        global $CFG, $DB;
    
        require_once($CFG->dirroot . '/mod/peerreview/locallib.php');
    
        $now = time();
        $seven_days_ago = $now - (7 * 24 * 60 * 60); // Timestamp for 7 days ago
        $start_of_day = strtotime("midnight", $seven_days_ago);
        $end_of_day = strtotime("tomorrow", $start_of_day) - 1;

        // SQL query to get all peerreview_submissions with less than peerreview_assessments and timecreated 7 days ago
        $sql = "
            SELECT ps.*
            FROM {peerreview_submissions} ps
            JOIN (
                SELECT submissionid
                FROM {peerreview_assessments}
                GROUP BY submissionid
                HAVING COUNT(*) < 3
            ) pa ON ps.id = pa.submissionid
            WHERE ps.timecreated BETWEEN :start_of_day AND :end_of_day
        ";
    
        // Execute the query with the date parameters
        $params = [
            'start_of_day' => $start_of_day,
            'end_of_day' => $end_of_day,
        ];
        $submissions = $DB->get_records_sql($sql, $params);
        // Process the results
        $admins = get_admins();

        // Process the results
        foreach ($submissions as $submission) {
            // Get the course module ID
            $cmid = get_coursemodule_from_instance('peerreview', $submission->peerreviewid);
    
            // Loop through each administrator
            foreach ($admins as $admin) {
                // Prepare the alert
                $a = new \stdClass();
                $a->courseid = $submission->courseid;
                $a->entrydetails = 'Some details about the entry';
                $a->useridnumber = $admin->idnumber;
                $a->username = fullname($admin);
                $a->userusername = $admin->username;
                $a->contexturl = new \moodle_url('/mod/peerreview/submission.php', ['cmid' => $cmid->id, 'id' => $submission->id]);
                $a->contexturlname = get_string('pluginname', 'mod_peerreview');
                $a->subject = get_string('cronsubject', 'mod_peerreview');
                $a->fullmessage = get_string('cronfullmessage', 'mod_peerreview', $submission->title);
                $a->smallmessage = get_string('cronfullmessage', 'mod_peerreview', $submission->title);
    
                // Send notification to the admin
                peerreview_send_alert($admin, $a); 
            }
        }
    }
 }
