<?php


defined('MOODLE_INTERNAL') || die();

function local_learningagreement_before_http_headers() {
    global $PAGE, $CFG, $DB, $USER;

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

