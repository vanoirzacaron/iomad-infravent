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
 * Plugin administration pages are defined here.
 *
 * @package     local_edwiserreports
 * @category    admin
 * @copyright   2019 wisdmlabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edwiserreports\blocks;

use local_edwiserreports\block_base;
use local_edwiserreports\utility;
use context_course;
use stdClass;

/**
 * Class Todays Activity. To get the data related to todays activvity block.
 */
class todaysactivityblock extends block_base {
    /**
     * Preapre layout for each block
     * @return Object Layout object
     */
    public function get_layout() {

        // Layout related data.
        $this->layout->id = 'todaysactivityblock';
        $this->layout->name = get_string('todaysactivityheader', 'local_edwiserreports');
        $this->layout->info = get_string('todaysactivityblockhelp', 'local_edwiserreports');
        $this->layout->filters = '<div class="flatpickr-wrapper">';
        $this->layout->filters .= '<input class="btn btn-sm dropdown-toggle input-group-addon"';
        $this->layout->filters .= 'id="flatpickrCalender-todaysactivity" placeholder="' .
        get_string('selectdate', 'local_edwiserreports') .
        '" data-input/></div>';

        // Add block view in layout.
        $this->layout->blockview = $this->render_block('todaysactivityblock', $this->block);

        // Set block edit capabilities.
        $this->set_block_edit_capabilities($this->layout->id);

        // Return blocks layout.
        return $this->layout;
    }

    /**
     * Get todays activity data
     * @param Object $params Parameters
     */
    public function get_data($params = false) {
        global $DB;

        $userids_sql = '';
        $sql = "SELECT valor 
        FROM {infrasvenhelper} 
        WHERE userid = :userid 
        AND action = 'selecteddept' 
        ORDER BY id DESC 
        LIMIT 1";

        $params = ['userid' => $_SESSION['USER']->id];
        $selecteddep = $DB->get_field_sql($sql, $params);

        $userlist = \company::get_recursive_department_users($selecteddep);

        // Extract user IDs from the user list
        if($selecteddep != -1) {
            if (!empty($userlist)) {
                $userids = array_column($userlist, 'userid');
                $userids_sql = implode(',', array_map('intval', $userids)); // Safely cast IDs to integers
            } else {
                // Set to 0 if the user list is empty
                $userids_sql = '0';
            }
        } else {
            $userids_sql = -1;
        }

        $date = isset($params->date) ? $params->date : false;
        $response = new stdClass();
        $response->data = $this->get_todaysactivity($date, $userids_sql);
        return $response;
    }

    /**
     * Get todays enrolments
     * @param  Integer $starttime Start Time
     * @param  Integer $endtime   End Time
     * @return Integer            Todays Course Enrolment Count
     */
    public function count_user_enrolments($userstable, $starttime, $endtime, $userids_sql) {
        global $DB;
        if($userids_sql != -1) {
            $sql = "SELECT COUNT(ue.id)
            FROM {{$userstable}} ut
            JOIN {user_enrolments} ue ON ut.tempid = ue.userid
           WHERE ue.timecreated >= :starttime 
              AND ue.userid IN ($userids_sql)
             AND ue.timecreated < :endtime";
        } else {

            $sql = "SELECT COUNT(ue.id)
                  FROM {{$userstable}} ut
                  JOIN {user_enrolments} ue ON ut.tempid = ue.userid
                 WHERE ue.timecreated >= :starttime
                   AND ue.timecreated < :endtime";
        }
        return $DB->count_records_sql($sql, ['starttime' => $starttime, 'endtime' => $endtime]);
    }

    /**
     * Get todays module completion count
     * @param  Integer $starttime Start Time
     * @param  Integer $endtime   End Time
     * @return Integer            Todays Module Completion Count
     */
    public function count_module_completions($userstable, $starttime, $endtime, $userids_sql) {
        global $DB;
        if($userids_sql != -1) {
            $sql = "SELECT COUNT(cmc.id)
            FROM {{$userstable}} ut
            JOIN {course_modules_completion} cmc ON ut.tempid = cmc.userid
           WHERE cmc.timemodified >= :starttime
           AND cmc.userid IN ($userids_sql)
            AND cmc.timemodified < :endtime
            AND cmc.completionstate <> 0";
        } else {

            $sql = "SELECT COUNT(cmc.id)
                  FROM {{$userstable}} ut
                  JOIN {course_modules_completion} cmc ON ut.tempid = cmc.userid
                 WHERE cmc.timemodified >= :starttime
                  AND cmc.timemodified < :endtime
                  AND cmc.completionstate <> 0";
        }
        return $DB->count_records_sql($sql, ['starttime' => $starttime, 'endtime' => $endtime]);
    }

    /**
     * Get todays course completion count
     * @param  Integer $starttime Start Time
     * @param  Integer $endtime   End Time
     * @return Integer            Todays Course Completion Count
     */
    public function count_course_completions($userstable, $starttime, $endtime, $userids_sql) {
        global $DB;
        if($userids_sql != -1) {
            $sql = "SELECT COUNT(ecp.id)
            FROM {{$userstable}} ut
            JOIN {edwreports_course_progress} ecp ON ut.tempid = ecp.userid
           WHERE ecp.completiontime >= :starttime
           AND ecp.userid IN ($userids_sql)
            AND ecp.completiontime < :endtime
            AND ecp.progress = 100";
        } else {
            $sql = "SELECT COUNT(ecp.id)
                  FROM {{$userstable}} ut
                  JOIN {edwreports_course_progress} ecp ON ut.tempid = ecp.userid
                 WHERE ecp.completiontime >= :starttime
                  AND ecp.completiontime < :endtime
                  AND ecp.progress = 100";
        }
        return $DB->count_records_sql($sql, ['starttime' => $starttime, 'endtime' => $endtime]);
    }

    /**
     * Get todays registrations count
     * @param  Integer $starttime Start Time
     * @param  Integer $endtime   End Time
     * @return Integer            Todays Registration Count
     */
    public function count_registrations_completions($starttime, $endtime, $userids_sql) {
        global $DB;
        if($userids_sql == -1) {
            $select = "timecreated >= $starttime
            AND timecreated < $endtime";
        } else {
        $select = "timecreated >= $starttime
                   AND timecreated < $endtime
                    AND IN ($userids_sql)";
                }
        return $DB->count_records_select('user', $select);
    }

    /**
     * Get todays site visit count
     * @param  Integer $starttime Start Time
     * @param  Integer $endtime   End Time
     * @return Integer            Todays Site Visits Count
     */
    public function count_site_visits($starttime, $endtime, $userids_sql) {
        global $DB;

        if($userids_sql == -1) {
        $visitsssql = "SELECT DISTINCT userid
            FROM {logstore_standard_log}
            WHERE timecreated >= :starttime
            AND timecreated < :endtime
            AND userid > 1";
        } else {
            $visitsssql = "SELECT DISTINCT userid
            FROM {logstore_standard_log}
            WHERE timecreated >= :starttime
            AND timecreated < :endtime
            AND userid IN ($userids_sql)";
        }
        $params = array(
            'starttime' => $starttime,
            'endtime' => $endtime
        );
        $visits = $DB->get_records_sql($visitsssql, $params);

        return count($visits);
    }

    /**
     * Get visits in every hours
     * @param  Integer $starttime Start Time
     * @param  Integer $endtime   End Time
     * @return Integer            Get Visits in Every Hours
     */
    public function get_visits_in_hours($starttime, $endtime, $userids_sql) {
        global $DB;

        // Prepare default array.
        $visitshour = array_fill(0, 24, []);

        if($userids_sql == -1) {
            $visitsssql = "SELECT id, userid, timecreated
            FROM {logstore_standard_log}
            WHERE timecreated >= :starttime
            AND timecreated < :endtime
            ORDER BY timecreated ASC";
        } else {
            $visitsssql = "SELECT id, userid, timecreated
            FROM {logstore_standard_log}
            WHERE timecreated >= :starttime
            AND timecreated < :endtime
            AND userid IN ($userids_sql)
            ORDER BY timecreated ASC";
        }

        $visits = $DB->get_records_sql($visitsssql, array(
            'starttime' => $starttime,
            'endtime' => $endtime
        ));

        foreach ($visits as $visit) {
            $hour = date('G', $visit->timecreated);
            $visitshour[$hour][$visit->userid] = true;
        }

        foreach ($visitshour as $key => $value) {
            $visitshour[$key] = count($value);
        }

        return $visitshour;
    }

    /**
     * Get Todays Activity information
     * @param  String $date Date filter in proprtdat format
     * @return Array        Array of todays activities information
     */
    public function get_todaysactivity($date, $userids_sql) {
        global $DB;
        $rtl = get_string('thisdirection', 'langconfig') == 'rtl' ? 1 : 0;

        // Set time according to the filter.
        if ($date) {
            if($rtl){
                $split = str_split($date);
                $date = $split[9] . $split[10] . ' ' . $split[5] . $split[6] . $split[7] . ' ' . $split[0] . $split[1] . $split[2] . $split[3];


                $starttime = strtotime($date);
                $endtime = $starttime + 24 * 60 * 60;
            } else {
                $starttime = strtotime($date);
                $endtime = $starttime + 24 * 60 * 60;
            }
            
        } else {
            $endtime = time();
            $starttime = strtotime(date("Ymd", $endtime));
        }


        $userid = $this->get_current_user();
        $users = $this->get_users_of_courses($userid, $this->get_courses_of_user());

        // Temporary users table.
        $userstable = utility::create_temp_table('tmp_ta_c', array_keys($users));

        $todaysactivity = array();
        $todaysactivity["enrollments"] = $this->count_user_enrolments($userstable, $starttime, $endtime, $userids_sql);
        $todaysactivity["activitycompletions"] = $this->count_module_completions($userstable, $starttime, $endtime, $userids_sql);
        $todaysactivity["coursecompletions"] = $this->count_course_completions($userstable, $starttime, $endtime, $userids_sql);
        $todaysactivity["registrations"] = $this->count_registrations_completions($starttime, $endtime, $userids_sql);
        $todaysactivity["visits"] = $this->count_site_visits($starttime, $endtime, $userids_sql);
        $todaysactivity["visitshour"] = $this->get_visits_in_hours($starttime, $endtime, $userids_sql);

        $todaysactivity["onlinelearners"] = $todaysactivity["onlineteachers"] = 0;

        // Capability 'moodle/course:ignoreavailabilityrestrictions' - is allowed to only teachers.
        $teacherscap = "moodle/course:ignoreavailabilityrestrictions";

        // Capability 'moodle/course:isincompletionreports' - is allowed to only students.
        $learnerscap = "moodle/course:isincompletionreports";

        $visitsssql = "SELECT DISTINCT lsl.userid
            FROM {logstore_standard_log} lsl
            JOIN {{$userstable}} ut ON lsl.userid = ut.tempid
            WHERE lsl.timecreated >= :starttime
            AND lsl.timecreated < :endtime";
        $params = array(
            'starttime' => $starttime,
            'endtime' => $endtime
        );
        $visits = $DB->get_records_sql($visitsssql, $params);
        foreach ($visits as $user) {
            $isteacher = $islearner = false;
            $courses = enrol_get_users_courses($user->userid);
            foreach ($courses as $course) {
                $coursecontext = context_course::instance($course->id);
                $isteacher = has_capability($teacherscap, $coursecontext, $user->userid);
                $islearner = has_capability($learnerscap, $coursecontext, $user->userid);
            }

            if ($isteacher) {
                $todaysactivity["onlineteachers"]++;
            }

            if ($islearner) {
                $todaysactivity["onlinelearners"]++;
            }
        }

        // Droppping course table.
        utility::drop_temp_table($userstable);
        return $todaysactivity;
    }
}
