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

require_once(__DIR__ .'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('classes/output/renderable.php');
require_once('classes/export.php');

global $OUTPUT, $DB, $USER;

// Strings for js.
local_edwiserreports_get_required_strings_for_js();

// Load color themes from constants.
local_edwiserreports\utility::load_color_pallets();

// Set external page admin.
$context = context_system::instance();
$component = "local_edwiserreports";

require_login();

// If use want to edit page.
$edit = optional_param('edit', false, PARAM_BOOL);
if ($edit !== false) {
    $USER->editing = $edit;
    redirect($CFG->wwwroot . '/local/edwiserreports/index.php');
}

// Retrieve the selected department from the POST request
$selecteddepartmentid = optional_param('selecteddepartament', '', PARAM_INT);

// Check if a department was selected
if (!empty($selecteddepartmentid)) {
    // Prepare the fields for the database record
    $record = new stdClass();
    $record->userid = $USER->id; // User ID
    $record->valor = $selecteddepartmentid; // Selected department ID
    $record->action = 'selecteddept';

    try {
        // Check if a record for this user and action already exists
        $existingrecord = $DB->get_record('infrasvenhelper', [
            'userid' => $USER->id,
            'action' => 'selecteddept'
        ]);

        if ($existingrecord) {
            // If a record exists, update it with the new value
            $record->id = $existingrecord->id; // Set the record ID to update
            $DB->update_record('infrasvenhelper', $record);
        } else {
            // If no record exists, insert a new one
            $DB->insert_record('infrasvenhelper', $record);
        }
    } catch (dml_exception $e) {
        // Handle the exception, log error or display message
        error_log("Failed to save record in 'infrasvenhelper': " . $e->getMessage());
        // Optionally display an error message to the user
        throw new moodle_exception('dberror', 'error', '', null, $e->getMessage());
    }
}


/*
$coursesids_sql = '';
$sql = "SELECT valor 
FROM {infrasvenhelper} 
WHERE userid = :userid 
AND action = 'selecteddept' 
ORDER BY id DESC 
LIMIT 1";

$params = ['userid' => $_SESSION['USER']->id];
$selecteddep = $DB->get_field_sql($sql, $params);

$courselist = \company::get_recursive_department_courses($selecteddep);
// Extract user IDs from the user list
if($selecteddep != -1) {
    if (!empty($courselist)) {
        $courseids = array_column($courselist, 'courseid');
        $coursesids_sql = implode(',', array_map('intval', $courseids)); // Safely cast IDs to integers
    } else {
        // Set to 0 if the user list is empty
        $coursesids_sql = '0';
    }
} else {
    $coursesids_sql = -1;
}
*/
//die(var_dump($coursesids_sql)); 


// If use want to edit page.
$reset = optional_param('reset', false, PARAM_BOOL);
if ($reset !== false) {
    $USER->editing = false;
    reset_edwiserreports_page_default();
}

// Check capability.
$capname = 'moodle/course:isincompletionreports';
if (
    (!has_capability($capname, $context) && !can_view_block($capname)) ||
    (is_siteadmin() || has_capability('moodle/site:configview', $context) || can_view_block('moodle/site:configview'))
) {
    $blockbase = new local_edwiserreports\block_base();
    $courses = $blockbase->get_courses_of_user($USER->id);
} else {
    $courses = enrol_get_users_courses($USER->id);
}
if (empty($courses)) {
    throw new moodle_exception('noaccess', 'local_edwiserreports');
}

// Allow users preferences set remotly.
\local_edwiserreports\utility::allow_update_userpreferences_remotly();

// Page URL.
$pageurl = new moodle_url($CFG->wwwroot."/local/edwiserreports/index.php");

// Load all js files from externaljs folder.
foreach (scandir($CFG->dirroot . '/local/edwiserreports/externaljs/build/') as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) != 'js') {
        continue;
    }
    $PAGE->requires->js(new moodle_url('/local/edwiserreports/externaljs/build/' . $file));
}

// Require JS for index page.
$PAGE->requires->js_call_amd('local_edwiserreports/main', 'init');

if (get_config('local_edwiserreports', 'fetch') == false &&
    (get_config('local_edwiserreports', 'fetchlater') == false ||
    get_config('local_edwiserreports', 'fetchlater') < time())) {
    // Initialize migration modal.
    $PAGE->requires->js_call_amd('local_edwiserreports/main', 'initMigration');
}

// Require CSS for index page.
$PAGE->requires->css('/local/edwiserreports/styles/edwiserreports.min.css');

// Set page context.
$PAGE->set_context($context);

// Set page URL.
$PAGE->set_url($pageurl);

// Set Page layout.
$PAGE->set_pagelayout('base');

// Add theme class to body.
$PAGE->add_body_classes(array('theme_' . $PAGE->theme->name, 'local-edwiserreports'));

// Get renderable.
$renderable = new \local_edwiserreports\output\edwiserreports_renderable();

$output = $PAGE->get_renderer($component)->render($renderable);

// Set page heading.
$PAGE->set_heading('');
$PAGE->set_title(get_string("reportsdashboard", "local_edwiserreports"));

// Print output in page.
echo $OUTPUT->header();
echo $output;
echo $OUTPUT->footer();
