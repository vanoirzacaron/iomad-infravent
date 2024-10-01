<?php
require_once('../../config.php');
global $DB, $USER;

// Define URL for the page.
$url = new moodle_url('/local/learningagreement/index.php');

// Set the page context.
$context = context_system::instance();
$PAGE->set_context($context);

// Set up the page.
$PAGE->set_url($url);
$PAGE->set_title(get_string('projectagreement', 'local_learningagreement'));
$PAGE->set_heading(get_string('projectagreement', 'local_learningagreement'));
$PAGE->set_pagelayout('standard');

// Check if the user is logged in and has the required capability.
require_login();
// require_capability('moodle/site:config', $context);

// Check if the user has already signed the learning agreement
$alreadysignedlearningagreement = false;
$alreadysignedlearningagreement = $DB->get_record('learningagreement', array('userid' => $USER->id));

// Start output to the browser.
echo $OUTPUT->header();

// // Display investment agreement information
// echo html_writer::tag('h4', get_string('investmentagreement', 'local_learningagreement'), array('class' => 'mt-5'));
// echo html_writer::tag('p', get_string('investmentagreementtext', 'local_learningagreement'));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($alreadysignedlearningagreement) {
        if (isset($_POST['docid'])) { 
        // TODO: USE the $alreadysignedlearningagreement to create an update_record for the USER DB entry
        $alreadysignedlearningagreement->timecreated = time();
        $alreadysignedlearningagreement->completedat = isset($_POST['completedat']) ? $_POST['completedat'] : 0;
        $alreadysignedlearningagreement->ip = isset($_POST['ip']) ? $_POST['ip'] : '1.1.1.1';
        $alreadysignedlearningagreement->email = isset($_POST['email']) ? $_POST['email'] : 'erro01010101';
        $alreadysignedlearningagreement->docid = isset($_POST['docid']) ? $_POST['docid'] : 1;
        // Update the record in the learningagreement table
        $DB->update_record('learningagreement', $alreadysignedlearningagreement);
    } else {
            echo "erro01010202";
        }
    } else {
        $record = new stdClass();
        $record->userid = $USER->id;
        $record->timecreated = time();
        $record->completedat = 0;
        $record->ip = 0;
        $record->email = 0;
        $record->docid = 0;

        if (isset($_POST['docid'])) {
            $record->completedat = $_POST['completedat'];
            $record->ip = $_POST['ip'];
            $record->email = $_POST['email'];
            $record->docid = $_POST['docid'];
        }

        // Insert the record into the learningagreement table
        $DB->insert_record('learningagreement', $record);
    }
    // Redirects to dashboard
    redirect(new moodle_url('/'));

}

// if($alreadysignedlearningagreement == false or (!$alreadysignedlearningagreement->docid)) {
// echo '
// <div id="live_example" class="card card-compact bg-base-200/30 mb-2 mt-4 z-10">
//     <div class="card-body">
//         <script src="https://cdn.docuseal.co/js/form.js" async=""></script>
//         <docuseal-form data-src="https://docuseal.co/d/KxraD95MXDiXQn" data-with-send-copy-button="false" 
//             data-go-to-last="true" 
//             data-completed-message-title="Learning Agreement signed" 
//             data-completed-message-body="You can proceed on 
//             [https://studio.infrastructure.ventures/](https://studio.infrastructure.ventures/)" style="display: block; max-height: 800px; overflow: auto;">
//         </docuseal-form>

//         <script>
//             window.addEventListener("load", () => {
//                 const formElement = document.querySelector("docuseal-form");
//                 if (formElement) {
//                     formElement.addEventListener("completed", (e) => {
//                         // Fill the form fields with the event data
//                         document.getElementById("completedat").value = e.detail.completedat;
//                         document.getElementById("ip").value = e.detail.ip;
//                         document.getElementById("email").value = e.detail.email;
//                         document.getElementById("docid").value = e.detail.id;
//                         document.getElementById("moodlesignaction").submit();
//                     });
//                 } else {
//                     console.error("DocuSeal form element not found.");
//                 }
//             });
//         </script>
//     </div>
// </div>
// ';
//         } else {
//             echo '<button type="submit" class="btn btn-primary disabled">' . get_string('alreadysigned', 'local_learningagreement') . '</button>';

//         }

        // Display learning agreement information
echo html_writer::tag('h4', get_string('learningagreement', 'local_learningagreement'), array('class' => 'mt-4'));
echo html_writer::tag('p', get_string('learningagreementtext', 'local_learningagreement'), array('class' => 'mt-2 mb-3'));
$learningagreementcontent = html_writer::tag('p', get_string('learningagreementcontent', 'local_learningagreement'));
echo html_writer::tag('div', $learningagreementcontent, array('id' => 'learningagreementcontent'));

// Display the form if the user hasn't signed the agreement yet
if (!$alreadysignedlearningagreement) {
    echo '<form id="moodlesignaction" method="post">
            <input type="hidden" id="completedat" name="completedat">
            <input type="hidden" id="ip" name="ip">
            <input type="hidden" id="email" name="email">
            <input type="hidden" id="docid" name="docid">
            <button type="submit" class="btn btn-primary">' . get_string('submitlearningonly', 'local_learningagreement') . '</button>
          </form>';
} else {   
    echo '<form id="moodlesignaction" method="post">
    <input type="hidden" id="completedat" name="completedat">
    <input type="hidden" id="ip" name="ip">
    <input type="hidden" id="email" name="email">
    <input type="hidden" id="docid" name="docid">
  </form>';
    echo '<button type="submit" class="btn btn-primary disabled">' . get_string('alreadyagreelearningonly', 'local_learningagreement') . '</button>';
}
// Finish the page.
echo $OUTPUT->footer();
?>
