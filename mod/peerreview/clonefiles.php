<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

function clone_workshop_files($original_contextid, $new_contextid) {
    global $DB, $CFG;
    $fs = get_file_storage();

    echo "Starting file cloning process...<br>";

    // Get files associated with the original context
    $files = $DB->get_records('files', array('contextid' => $original_contextid, 'component' => 'mod_workshop'));

    echo "Number of files found: " . count($files) . "<br>";

    if (empty($files)) {
        echo "No files found for context ID: $original_contextid<br>";
        return;
    }

    foreach ($files as $file) {
        // Check if file exists
        $filepath = $CFG->dataroot . '/filedir/' . substr($file->contenthash, 0, 2) . '/' . substr($file->contenthash, 2, 2) . '/' . $file->contenthash;
        if (!file_exists($filepath)) {
            echo "File does not exist: $filepath<br>";
            continue;
        } else {
            echo "File exists: $filepath<br>";
        }

        // Prepare new file record as an associative array
        $new_file_record = array(
            'contextid' => $new_contextid,
            'component' => 'mod_peerreview',
            'filearea' => $file->filearea,
            'itemid' => $file->itemid,
            'filepath' => $file->filepath,
            'filename' => $file->filename,
            'userid' => $file->userid,
            'filesize' => $file->filesize,
            'mimetype' => !empty($file->mimetype) ? $file->mimetype : 'application/octet-stream',
            'status' => $file->status,
            'source' => !empty($file->source) ? $file->source : $file->filename,
            'author' => !empty($file->author) ? $file->author : 'unknown',
            'license' => !empty($file->license) ? $file->license : 'unknown',
            'timecreated' => $file->timecreated,
            'timemodified' => $file->timemodified,
            'sortorder' => $file->sortorder,
            'contenthash' => $file->contenthash,
            'pathnamehash' => $file->pathnamehash,
            'referencefileid' => !empty($file->referencefileid) ? $file->referencefileid : 0
        );

        // Create new file
        try {
            $newfile = $fs->create_file_from_storedfile($new_file_record, $file->id);
            echo "File cloned successfully: " . $file->filename . " with new file ID: " . $newfile->get_id() . "<br>";
        } catch (Exception $e) {
            echo "Error cloning file: " . $file->filename . " - " . $e->getMessage() . "<br>";
        }
    }

    echo "File cloning process completed.<br>";
}

if (!is_siteadmin()) {
    die('You do not have the required permissions to execute this script.');
}

// Parameters
$original_contextid = required_param('original_contextid', PARAM_INT);
$new_contextid = required_param('new_contextid', PARAM_INT);

// Check if the user is logged in and has the required capability
require_login();
if (!has_capability('moodle/site:config', context_system::instance())) {
    print_error('nopermissions', 'error', '', 'Clone files');
}

echo "Original context ID: $original_contextid<br>";
echo "New context ID: $new_contextid<br>";

// Verify context IDs
$original_context = $DB->get_record('context', array('id' => $original_contextid));
$new_context = $DB->get_record('context', array('id' => $new_contextid));

if (!$original_context) {
    echo "Invalid original context ID: $original_contextid<br>";
    die();
}

if (!$new_context) {
    echo "Invalid new context ID: $new_contextid<br>";
    die();
}

echo "Original context verified: ID = $original_contextid, instance ID = {$original_context->instanceid}, level = {$original_context->contextlevel}<br>";
echo "New context verified: ID = $new_contextid, instance ID = {$new_context->instanceid}, level = {$new_context->contextlevel}<br>";

// Run the cloning function
clone_workshop_files($original_contextid, $new_contextid);

echo "Files cloned successfully from context $original_contextid to context $new_contextid.<br>";
?>
