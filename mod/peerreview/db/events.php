<?php
defined('MOODLE_INTERNAL') || die();

$observers = array(

    // User logging out.
    array(
        'eventname' => '\mod_peerreview\event\submission_assessed',
        'callback' => 'mod_peerreview_observer::submission_assessed',
    )
);


// $observers = [
//     [
//         'eventname'   => '\mod_peerreview\event\submission_reassessed',
//         'callback'    => '\mod_peerreview\observer::submission_reassessed',
//         'includefile' => '/mod/peerreview/locallib.php',
//     ],
//     [
//         'eventname' => '\mod_peerreview\event\submission_assessed',
//         'callback' => '\mod_peerreview\observer::submission_assessed',
//         'includefile' => '/mod/peerreview/locallib.php',
//     ],
// ];
