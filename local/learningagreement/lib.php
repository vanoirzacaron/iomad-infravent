<?php


defined('MOODLE_INTERNAL') || die();

function local_learningagreement_before_http_headers() {
    global $PAGE, $CFG, $DB, $USER, $context;

// Query the database to check if the user has the 'student' role
$sql = "SELECT 1
          FROM {role_assignments} ra
          JOIN {role} r ON r.id = ra.roleid
         WHERE ra.userid = :userid
           AND r.shortname = 'student'";

$params = [
    'userid' => $USER->id,
];

// Check if any records are returned
$has_student_role = $DB->record_exists_sql($sql, $params);

// Output true or false
if ($has_student_role) {
    // URL of the learning agreement index page
    $agreementindex = new moodle_url('/local/learningagreement/index.php');

    // Skip the redirect if the user is on the change password page
    if (strpos($PAGE->url->out(), '/login/change_password.php') !== false) {
        return;
    }

    // Check if the user is logged in and is not a site admin
    if (isloggedin() && !is_siteadmin()) {
        // Check if the current page is not the learning agreement index page
        if (strpos($PAGE->url->out(), '/local/learningagreement/index.php') === false) {
            // Check if the user has a record in the learningagreement table
            $record = $DB->get_record('learningagreement', array('userid' => $USER->id), 'id');

            // If no record is found, redirect to the learning agreement index page
            if (!$record) {
                redirect($agreementindex);
            }
        }
    }
} 

    
}

