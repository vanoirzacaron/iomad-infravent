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
 * TODO describe file notificationpanel
 *
 * @package    mod_peerreview
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/peerreview/locallib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

$cmid = required_param('cmid', PARAM_INT); // Capture the cmid parameter

$url = new moodle_url('/mod/peerreview/notificationpanel.php', array('cmid' => $cmid));
$courseid = $PAGE->course->id;

$context = context_course::instance($courseid);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('notificationpanel', 'mod_peerreview'));
$PAGE->set_heading(get_string('notificationpanel', 'mod_peerreview'));

require_login($courseid);

// Fetch filter parameters
$eventname = optional_param('eventname', '', PARAM_RAW); // Event name
$day = optional_param('day', '', PARAM_INT); // Day
$month = optional_param('month', '', PARAM_INT); // Month
$username = optional_param('username', '', PARAM_RAW); // User name
$page = optional_param('page', 0, PARAM_INT); // Page number

// Number of records per page
$perpage = 10;

echo $OUTPUT->header();
echo html_writer::tag('h3', get_string('notifystudent', 'mod_peerreview'));

$context = context_course::instance($courseid);
$students = get_enrolled_users($context, '', 0, 'u.id', null, 0, 0, false);

// Fetch user details
if (!empty($students)) {
    $userids = array_keys($students);
    list($usql, $params) = $DB->get_in_or_equal($userids);
    $userdetails = $DB->get_records_sql("SELECT id, firstname, lastname, email FROM {user} WHERE id $usql", $params);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student']) && isset($_POST['message'])) {
    $studentid = required_param('student', PARAM_INT);
    $messagecontent = required_param('message', PARAM_TEXT);
    
    // Fetch student details
    $student = $DB->get_record('user', ['id' => $studentid]);
    if ($student) {
        // Prepare data for the notification
        $a = new \stdClass();
        $a->courseid = $courseid;
        $a->entrydetails = 'a new message';
        $a->useridnumber = $student->idnumber;
        $a->username = fullname($student);
        $a->userusername = $student->username;
        $a->contexturl = new \moodle_url('/mod/peerreview/view.php', ['id' => $cmid]);
        $a->contexturlname = get_string('peerreviewpeerreview', 'mod_peerreview');
        $a->subject = 'You got a new message';
        $a->fullmessage = $messagecontent;
        $a->smallmessage = $messagecontent;
        // Send notification
        peerreview_send_alert($student, $a);

        echo html_writer::tag('p', 'Message sent successfully to ' . fullname($student));
    } else {
        echo html_writer::tag('p', 'Invalid student selected.');
    }
}

// Display students dropdown and message textbox
echo '<form class="logselecform mb-5" action="' . $url->out(false, array('page' => $page)) . '" method="post">';
echo '<div>';
echo '<label class="accesshide" for="student">Student</label>';
echo '<select class="select custom-select mr-2 mb-2" id="student" name="student">';
echo '<option value="">Select a student</option>';
if (!empty($userdetails)) {
    foreach ($userdetails as $user) {
        echo '<option value="' . $user->id . '">' . $user->firstname . ' ' . $user->lastname . '</option>';
    }
}
echo '</select>';

echo '<label class="accesshide" for="message">Message</label>';
echo '<textarea id="message" name="message" class="form-control mb-2" placeholder="Enter your message" rows="4" cols="50"></textarea>';

echo '<input type="hidden" name="cmid" value="' . $cmid . '">';
echo '<input type="submit" value="Notify Student" class="btn btn-primary">';
echo '</div>';
echo '</form>';

echo html_writer::tag('h3', get_string('notificationlog', 'mod_peerreview'));

// Add the filter form for the notifications table
echo '<form class="logselecform mb-5" action="' . $url->out(false, array('page' => $page)) . '" method="get">';
echo '<div>';
echo '<label class="accesshide" for="username">User name</label>';
echo '<input type="text" class="form-control mr-2 mb-2" id="username" name="username" value="' . s($username) . '" placeholder="Search by user name" style="max-width: 200px;">';
echo '<input type="hidden" name="cmid" value="' . $cmid . '">';
echo '<input type="submit" value="Filter" class="btn btn-primary">';
echo '</div>';
echo '</form>';

// New Filtered Table for Notifications

// Set SQL for the table
$sql = "SELECT n.id, n.useridfrom, n.useridto, n.subject, n.fullmessage, n.component, n.eventtype, n.timecreated FROM {notifications} n
      WHERE n.component = 'mod_peerreview'";
$params = array();

// Add filter parameters to SQL query
if (!empty($username)) {
    $sql .= " AND (SELECT CONCAT(firstname, ' ', lastname) FROM {user} WHERE id = n.useridfrom) LIKE :username 
                  OR (SELECT CONCAT(firstname, ' ', lastname) FROM {user} WHERE id = n.useridto) LIKE :username";
    $params['username'] = '%' . $username . '%';
}

if (!empty($useridfrom)) {
    $sql .= " AND n.useridfrom = :useridfrom";
    $params['useridfrom'] = $useridfrom;
}

if (!empty($useridto)) {
    $sql .= " AND n.useridto = :useridto";
    $params['useridto'] = $useridto;
}

if (!empty($subject)) {
    $sql .= " AND " . $DB->sql_like('n.subject', ':subject', false);
    $params['subject'] = "%$subject%";
}

if (!empty($eventtype)) {
    $sql .= " AND n.eventtype = :eventtype";
    $params['eventtype'] = $eventtype;
}


// Count records for pagination
$count_sql = "SELECT COUNT(1) FROM {notifications} n WHERE n.component = 'mod_peerreview'";
$count_params = array_merge($params);

$totalcount = $DB->count_records_sql($count_sql, $count_params);

// Add limit and offset for pagination
$sql .= " ORDER BY n.timecreated DESC";
$sql .= " LIMIT $perpage OFFSET " . ($page * $perpage);

$notifications = $DB->get_records_sql($sql, $params);

// Start the table
echo html_writer::start_tag('table', array('class' => 'generaltable'));
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('timecreated', 'core'));
echo html_writer::tag('th', get_string('user', 'core'));
echo html_writer::tag('th', get_string('subject', 'core'));
echo html_writer::tag('th', get_string('message', 'core'));
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

foreach ($notifications as $notification) {
    $studentname = $DB->get_record('user', ['id' => $notification->useridto]);

    $studentname = fullname($studentname);
    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', userdate($notification->timecreated));
    echo html_writer::tag('td', $studentname);
    echo html_writer::tag('td', format_string($notification->subject));
    echo html_writer::tag('td', format_text($notification->fullmessage));
    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

// Add pagination
echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);

echo html_writer::tag('h3', get_string('eventlog', 'mod_peerreview'));

echo '<form class="logselecform" action="' . $url->out(false, array('page' => $page)) . '" method="get">';
echo '<div>';

echo '<label class="accesshide" for="eventname">Event name</label>';
echo '<select class="select custom-select mr-2 mb-2" id="eventname" name="eventname">';
echo '<option value="">All events</option>';
$eventnames = [
    '\mod_peerreview\event\course_module_viewed',
    '\mod_peerreview\event\submission_viewed',
    '\mod_peerreview\event\submission_created',
    '\mod_peerreview\event\submission_updated',
    '\mod_peerreview\event\assessable_uploaded',
    '\mod_peerreview\event\submission_assessed',
    '\mod_peerreview\event\submission_reassessed',
];
foreach ($eventnames as $ename) {
    echo '<option value="' . $ename . '"' . ($eventname == $ename ? ' selected' : '') . '>' . $ename . '</option>';
}
echo '</select>';

echo '<label class="accesshide" for="day">Day</label>';
echo '<select class="select custom-select mr-2 mb-2" id="day" name="day">';
echo '<option value="">All days</option>';
for ($d = 1; $d <= 31; $d++) {
    echo '<option value="' . $d . '"' . ($day == $d ? ' selected' : '') . '>' . $d . '</option>';
}
echo '</select>';

echo '<label class="accesshide" for="month">Month</label>';
echo '<select class="select custom-select mr-2 mb-2" id="month" name="month">';
echo '<option value="">All months</option>';
for ($m = 1; $m <= 12; $m++) {
    echo '<option value="' . $m . '"' . ($month == $m ? ' selected' : '') . '>' . date('F', mktime(0, 0, 0, $m, 10)) . '</option>';
}
echo '</select>';

echo '<label class="accesshide" for="username">User name</label>';
echo '<input type="text" class="form-control mr-2 mb-2" id="username" name="username" value="' . s($username) . '" placeholder="Search by user name" style="max-width: 200px;">';

echo '<input type="hidden" name="cmid" value="' . $cmid . '">';
echo '<input type="submit" value="Get these logs" class="btn btn-primary">';
echo '</div>';
echo '</form>';

// Build the SQL query based on filters
$sql = "SELECT l.*, c.fullname as coursename FROM {logstore_standard_log} l
        JOIN {course} c ON l.courseid = c.id
        WHERE l.component = 'mod_peerreview'";
$params = [];

if ($eventname) {
    $sql .= " AND l.eventname = :eventname";
    $params['eventname'] = $eventname;
}
if ($day) {
    $sql .= " AND DAY(FROM_UNIXTIME(l.timecreated)) = :day";
    $params['day'] = $day;
}
if ($month) {
    $sql .= " AND MONTH(FROM_UNIXTIME(l.timecreated)) = :month";
    $params['month'] = $month;
}
if ($username) {
    $sql .= " AND l.userid IN (SELECT id FROM {user} WHERE CONCAT(firstname, ' ', lastname) LIKE :username)";
    $params['username'] = '%' . $username . '%';
}

// Count total records for pagination
$totalcount = $DB->count_records_sql("SELECT COUNT(1) FROM ($sql) sub", $params);

// Add limit and offset for pagination
$sql .= " ORDER BY l.timecreated DESC";
$sql .= " LIMIT $perpage OFFSET " . ($page * $perpage);

$events = $DB->get_records_sql($sql, $params);

// Display the number of events fetched
echo html_writer::tag('p', 'Number of events fetched: ' . $totalcount);

if (empty($events)) {
    echo html_writer::tag('p', 'No events found.');
} else {
    // Start the table
    echo html_writer::start_tag('table', array('class' => 'generaltable'));
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('time', 'core'));
    echo html_writer::tag('th', get_string('user', 'core'));
    echo html_writer::tag('th', get_string('affecteduser', 'mod_peerreview'));
    echo html_writer::tag('th', get_string('course', 'core'));
    echo html_writer::tag('th', get_string('event', 'mod_peerreview'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    // Fetch user full names
    $userids = array_unique(array_merge(array_column($events, 'userid'), array_column($events, 'relateduserid')));
    $userids = array_filter($userids); // Remove null values
    list($usql, $params) = $DB->get_in_or_equal($userids);
    $users = $DB->get_records_sql("SELECT id, CONCAT(firstname, ' ', lastname) AS fullname FROM {user} WHERE id $usql", $params);

    foreach ($events as $event) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', userdate($event->timecreated));
        echo html_writer::tag('td', isset($users[$event->userid]) ? $users[$event->userid]->fullname : '-');
        echo html_writer::tag('td', isset($users[$event->relateduserid]) ? $users[$event->relateduserid]->fullname : '-');
        echo html_writer::tag('td', $event->coursename);
        echo html_writer::tag('td', $event->eventname);
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    // Add pagination
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $url);
}

echo $OUTPUT->footer();
?>
