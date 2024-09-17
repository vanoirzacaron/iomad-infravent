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
 * peerreview module renderering methods are defined here
 *
 * @package    mod_peerreview
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * peerreview module renderer class
 *
 * @copyright 2009 David Mudrak <david.mudrak@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_peerreview_renderer extends plugin_renderer_base
{

    ////////////////////////////////////////////////////////////////////////////
    // External API - methods to render peerreview renderable components
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Renders the tertiary nav for the allocation pages
     *
     * @param \mod_peerreview\output\actionbar $actionbar
     * @return bool|string the rendered output
     */
    public function render_allocation_menu(\mod_peerreview\output\actionbar $actionbar): string
    {
        return $this->render_from_template('mod_peerreview/action_bar', $actionbar->export_for_template($this));
    }

    /**
     * Renders peerreview message
     *
     * @param peerreview_message $message to display
     * @return string html code
     */
    protected function render_peerreview_message(peerreview_message $message)
    {

        $text   = $message->get_message();
        $url    = $message->get_action_url();
        $label  = $message->get_action_label();

        if (empty($text) and empty($label)) {
            return '';
        }

        switch ($message->get_type()) {
            case peerreview_message::TYPE_OK:
                $sty = 'ok';
                break;
            case peerreview_message::TYPE_ERROR:
                $sty = 'error';
                break;
            default:
                $sty = 'info';
        }

        $o = html_writer::tag('span', $message->get_message());

        if (!is_null($url) and !is_null($label)) {
            $o .= $this->output->single_button($url, $label, 'get');
        }

        return $this->output->container($o, array('message', $sty));
    }


    /**
     * Renders full peerreview submission
     *
     * @param peerreview_submission $submission
     * @return string HTML
     */
    protected function render_peerreview_submission(peerreview_submission $submission)
    {
        global $CFG, $USER, $PAGE;

        $o  = '';    // output HTML code
        $anonymous = $submission->is_anonymous();
        $classes = 'submission-full';
        $content = '';
        $currentuserisauthor = false;

        $currentPath = $_SERVER['REQUEST_URI'];
        $showprojectfullcontent = false;

        if (strpos($currentPath, 'assessment.php') !== false) {
            $showprojectfullcontent = true;
        }

        if ($USER->id == $submission->authorid) {
            $currentuserisauthor = true;
        }

        if ($anonymous) {
            $classes .= ' anonymous';
        }
        $o .= $this->output->container_start($classes);
        $o .= $this->output->container_start('header');

        $title = format_string($submission->title);

        if ($this->page->url != $submission->url) {
            $title = html_writer::link($submission->url, $title);
        }

        $o .= $this->output->heading($title, 3, 'title');

        if (!$anonymous) {
            $author = new stdclass();
            $additionalfields = explode(',', implode(',', \core_user\fields::get_picture_fields()));
            $author = username_load_fields_from_object($author, $submission, 'author', $additionalfields);
            $userpic            = $this->output->user_picture($author, array('courseid' => $this->page->course->id, 'size' => 64));
            $userurl            = new moodle_url(
                '/user/view.php',
                array('id' => $author->id, 'course' => $this->page->course->id)
            );
            $a                  = new stdclass();
            $a->name            = fullname($author);
            $a->url             = $userurl->out();
            $byfullname         = get_string('byfullname', 'peerreview', $a);
            $oo  = $this->output->container($userpic, 'picture');
            $oo .= $this->output->container($byfullname, 'fullname');

            $o .= $this->output->container($oo, 'author');
        }

        $created = get_string('userdatecreated', 'peerreview', userdate($submission->timecreated));
        $o .= $this->output->container($created, 'userdate created');

        if ($submission->timemodified > $submission->timecreated) {
            $modified = get_string('userdatemodified', 'peerreview', userdate($submission->timemodified));
            $o .= $this->output->container($modified, 'userdate modified');
        }

        $o .= $this->output->container_end(); // end of header

        $description = file_rewrite_pluginfile_urls(
            $submission->description,
            'pluginfile.php',
            $this->page->context->id,
            'mod_peerreview',
            'description_content',
            $submission->id
        );

        $description = format_text($description, $submission->descriptionformat, array('overflowdiv' => true));
        if (!empty($description)) {
            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');
                $description .= plagiarism_get_links(array(
                    'userid' => $submission->authorid,
                    'content' => $submission->description,
                    'cmid' => $this->page->cm->id,
                    'course' => $this->page->course
                ));
            }
        }

        $o .= $this->output->container($description, 'description');

        // $o .= $this->helper_description_attachments($submission->id, 'html');

        // Check if the URL contains 'assessment.php' to add full content
        if ($showprojectfullcontent || $currentuserisauthor) {
            $content = file_rewrite_pluginfile_urls(
                $submission->content,
                'pluginfile.php',
                $this->page->context->id,
                'mod_peerreview',
                'submission_content',
                $submission->id
            );
            $content = format_text($content, $submission->contentformat, array('overflowdiv' => true));
            if (!empty($content)) {
                if (!empty($CFG->enableplagiarism)) {
                    require_once($CFG->libdir . '/plagiarismlib.php');
                    $content .= plagiarism_get_links(array(
                        'userid' => $submission->authorid,
                        'content' => $submission->content,
                        'cmid' => $this->page->cm->id,
                        'course' => $this->page->course
                    ));
                }
            }
            $o .= $this->output->container($content, 'content');
        }





        if ($showprojectfullcontent || $currentuserisauthor) {
            $o .= html_writer::tag('h4', get_string('files', 'mod_peerreview'), array('class' => 'my-3'));
            $o .= $this->helper_submission_attachments($submission->id, 'html');
        }


        $o .= $this->output->container_end(); // end of submission-full

        return $o;
    }

    /**
     * Renders short summary of the submission
     *
     * @param peerreview_submission_summary $summary
     * @return string text to be echo'ed
     */
    protected function render_peerreview_submission_summary(peerreview_submission_summary $summary)
    {

        $o  = '';    // output HTML code
        $anonymous = $summary->is_anonymous();
        $classes = 'submission-summary';

        if ($anonymous) {
            $classes .= ' anonymous';
        }

        $gradestatus = '';

        if ($summary->status == 'notgraded') {
            $classes    .= ' notgraded';
            $gradestatus = $this->output->container(get_string('nogradeyet', 'peerreview'), 'grade-status');
        } else if ($summary->status == 'graded') {
            $classes    .= ' graded';
            $gradestatus = $this->output->container(get_string('alreadygraded', 'peerreview'), 'grade-status');
        }

        $o .= $this->output->container_start($classes);  // main wrapper
        $o .= html_writer::link($summary->url, format_string($summary->title), array('class' => 'title'));

        if (!$anonymous) {
            $author             = new stdClass();
            $additionalfields = explode(',', implode(',', \core_user\fields::get_picture_fields()));
            $author = username_load_fields_from_object($author, $summary, 'author', $additionalfields);
            $userpic            = $this->output->user_picture($author, array('courseid' => $this->page->course->id, 'size' => 35));
            $userurl            = new moodle_url(
                '/user/view.php',
                array('id' => $author->id, 'course' => $this->page->course->id)
            );
            $a                  = new stdClass();
            $a->name            = fullname($author);
            $a->url             = $userurl->out();
            $byfullname         = get_string('byfullname', 'peerreview', $a);

            $oo  = $this->output->container($userpic, 'picture');
            $oo .= $this->output->container($byfullname, 'fullname');
            $o  .= $this->output->container($oo, 'author');
        }

        $created = get_string('userdatecreated', 'peerreview', userdate($summary->timecreated));
        $o .= $this->output->container($created, 'userdate created');

        if ($summary->timemodified > $summary->timecreated) {
            $modified = get_string('userdatemodified', 'peerreview', userdate($summary->timemodified));
            $o .= $this->output->container($modified, 'userdate modified');
        }

        $o .= $gradestatus;
        $o .= $this->output->container_end(); // end of the main wrapper
        return $o;
    }

    /**
     * Renders full peerreview example submission
     *
     * @param peerreview_example_submission $example
     * @return string HTML
     */
    protected function render_peerreview_example_submission(peerreview_example_submission $example)
    {

        $o  = '';    // output HTML code
        $classes = 'submission-full example';
        $o .= $this->output->container_start($classes);
        $o .= $this->output->container_start('header');
        $o .= $this->output->container(format_string($example->title), array('class' => 'title'));
        $o .= $this->output->container_end(); // end of header

        $content = file_rewrite_pluginfile_urls(
            $example->content,
            'pluginfile.php',
            $this->page->context->id,
            'mod_peerreview',
            'submission_content',
            $example->id
        );
        $content = format_text($content, $example->contentformat, array('overflowdiv' => true));
        $o .= $this->output->container($content, 'content');

        $o .= $this->helper_submission_attachments($example->id, 'html');

        $o .= $this->output->container_end(); // end of submission-full

        return $o;
    }

    /**
     * Renders short summary of the example submission
     *
     * @param peerreview_example_submission_summary $summary
     * @return string text to be echo'ed
     */
    protected function render_peerreview_example_submission_summary(peerreview_example_submission_summary $summary)
    {

        $o  = '';    // output HTML code

        // wrapping box
        $o .= $this->output->box_start('generalbox example-summary ' . $summary->status);

        // title
        $o .= $this->output->container_start('example-title');
        $o .= html_writer::link($summary->url, format_string($summary->title), array('class' => 'title'));

        if ($summary->editable) {
            $o .= $this->output->action_icon($summary->editurl, new pix_icon('i/edit', get_string('edit')));
        }
        $o .= $this->output->container_end();

        // additional info
        if ($summary->status == 'notgraded') {
            $o .= $this->output->container(get_string('nogradeyet', 'peerreview'), 'example-info nograde');
        } else {
            $o .= $this->output->container(get_string('gradeinfo', 'peerreview', $summary->gradeinfo), 'example-info grade');
        }

        // button to assess
        $button = new single_button($summary->assessurl, $summary->assesslabel, 'get');
        $o .= $this->output->container($this->output->render($button), 'example-actions');

        // end of wrapping box
        $o .= $this->output->box_end();

        return $o;
    }

    /**
     * Renders the user plannner tool
     *
     * @param peerreview_user_plan $plan prepared for the user
     * @return string html code to be displayed
     */
    protected function render_peerreview_user_plan(peerreview_user_plan $plan)
    {

        return '';
    }

    /**
     * Renders the result of the submissions allocation process
     *
     * @param peerreview_allocation_result $result as returned by the allocator's init() method
     * @return string HTML to be echoed
     */
    protected function render_peerreview_allocation_result(peerreview_allocation_result $result)
    {
        global $CFG;

        $status = $result->get_status();

        if (is_null($status) or $status == peerreview_allocation_result::STATUS_VOID) {
            debugging('Attempt to render peerreview_allocation_result with empty status', DEBUG_DEVELOPER);
            return '';
        }

        switch ($status) {
            case peerreview_allocation_result::STATUS_FAILED:
                if ($message = $result->get_message()) {
                    $message = new peerreview_message($message, peerreview_message::TYPE_ERROR);
                } else {
                    $message = new peerreview_message(get_string('allocationerror', 'peerreview'), peerreview_message::TYPE_ERROR);
                }
                break;

            case peerreview_allocation_result::STATUS_CONFIGURED:
                if ($message = $result->get_message()) {
                    $message = new peerreview_message($message, peerreview_message::TYPE_INFO);
                } else {
                    $message = new peerreview_message(get_string('allocationconfigured', 'peerreview'), peerreview_message::TYPE_INFO);
                }
                break;

            case peerreview_allocation_result::STATUS_EXECUTED:
                if ($message = $result->get_message()) {
                    $message = new peerreview_message($message, peerreview_message::TYPE_OK);
                } else {
                    $message = new peerreview_message(get_string('allocationdone', 'peerreview'), peerreview_message::TYPE_OK);
                }
                break;

            default:
                throw new coding_exception('Unknown allocation result status', $status);
        }

        // start with the message
        $o = $this->render($message);

        // display the details about the process if available
        $logs = $result->get_logs();
        if (is_array($logs) and !empty($logs)) {
            $o .= html_writer::start_tag('ul', array('class' => 'allocation-init-results'));
            foreach ($logs as $log) {
                if ($log->type == 'debug' and !$CFG->debugdeveloper) {
                    // display allocation debugging messages for developers only
                    continue;
                }
                $class = $log->type;
                if ($log->indent) {
                    $class .= ' indent';
                }
                $o .= html_writer::tag('li', $log->message, array('class' => $class)) . PHP_EOL;
            }
            $o .= html_writer::end_tag('ul');
        }

        return $o;
    }

    /**
     * Renders the peerreview grading report
     *
     * @param peerreview_grading_report $gradingreport
     * @return string html code
     */
    protected function render_peerreview_grading_report(peerreview_grading_report $gradingreport)
    {

        $data       = $gradingreport->get_data();
        $options    = $gradingreport->get_options();
        $grades     = $data->grades;
        $userinfo   = $data->userinfo;

        if (empty($grades)) {
            return $this->output->notification(get_string('nothingtodisplay'), 'success', false);
        }

        $table = new html_table();
        $table->attributes['class'] = 'grading-report table-striped table-hover';

        $sortbyfirstname = $this->helper_sortable_heading(get_string('firstname'), 'firstname', $options->sortby, $options->sorthow);
        $sortbylastname = $this->helper_sortable_heading(get_string('lastname'), 'lastname', $options->sortby, $options->sorthow);
        if (self::fullname_format() == 'lf') {
            $sortbyname = $sortbylastname . ' / ' . $sortbyfirstname;
        } else {
            $sortbyname = $sortbyfirstname . ' / ' . $sortbylastname;
        }

        $sortbysubmisstiontitle = $this->helper_sortable_heading(
            get_string('submission', 'peerreview'),
            'submissiontitle',
            $options->sortby,
            $options->sorthow
        );
        $sortbysubmisstionlastmodified = $this->helper_sortable_heading(
            get_string('submissionlastmodified', 'peerreview'),
            'submissionmodified',
            $options->sortby,
            $options->sorthow
        );
        $sortbysubmisstion = $sortbysubmisstiontitle . ' / ' . $sortbysubmisstionlastmodified;

        $table->head = array();
        $table->head[] = $sortbyname;
        $table->head[] = $sortbysubmisstion;

        // If we are in submission phase ignore the following headers (columns).
        if ($options->peerreviewphase != peerreview::PHASE_SUBMISSION) {
            $table->head[] = $this->helper_sortable_heading(get_string('receivedgrades', 'peerreview'));
            if ($options->showsubmissiongrade) {
                $table->head[] = $this->helper_sortable_heading(
                    get_string('submissiongradeof', 'peerreview', $data->maxgrade),
                    'submissiongrade',
                    $options->sortby,
                    $options->sorthow
                );
            }
            $table->head[] = $this->helper_sortable_heading(get_string('givengrades', 'peerreview'));
            if ($options->showgradinggrade) {
                $table->head[] = $this->helper_sortable_heading(
                    get_string('gradinggradeof', 'peerreview', $data->maxgradinggrade),
                    'gradinggrade',
                    $options->sortby,
                    $options->sorthow
                );
            }
        }
        $table->rowclasses  = array();
        $table->colclasses  = array();
        $table->data        = array();

        foreach ($grades as $participant) {
            $numofreceived  = count($participant->reviewedby);
            $numofgiven     = count($participant->reviewerof);
            $published      = $participant->submissionpublished;

            // compute the number of <tr> table rows needed to display this participant
            if ($numofreceived > 0 and $numofgiven > 0) {
                $numoftrs       = peerreview::lcm($numofreceived, $numofgiven);
                $spanreceived   = $numoftrs / $numofreceived;
                $spangiven      = $numoftrs / $numofgiven;
            } elseif ($numofreceived == 0 and $numofgiven > 0) {
                $numoftrs       = $numofgiven;
                $spanreceived   = $numoftrs;
                $spangiven      = $numoftrs / $numofgiven;
            } elseif ($numofreceived > 0 and $numofgiven == 0) {
                $numoftrs       = $numofreceived;
                $spanreceived   = $numoftrs / $numofreceived;
                $spangiven      = $numoftrs;
            } else {
                $numoftrs       = 1;
                $spanreceived   = 1;
                $spangiven      = 1;
            }

            for ($tr = 0; $tr < $numoftrs; $tr++) {
                $row = new html_table_row();
                if ($published) {
                    $row->attributes['class'] = 'published';
                }
                // column #1 - participant - spans over all rows
                if ($tr == 0) {
                    $cell = new html_table_cell();
                    $cell->text = $this->helper_grading_report_participant($participant, $userinfo);
                    $cell->rowspan = $numoftrs;
                    $cell->attributes['class'] = 'participant';
                    $row->cells[] = $cell;
                }
                // column #2 - submission - spans over all rows
                if ($tr == 0) {
                    $cell = new html_table_cell();
                    $cell->text = $this->helper_grading_report_submission($participant);
                    $cell->rowspan = $numoftrs;
                    $cell->attributes['class'] = 'submission';
                    $row->cells[] = $cell;
                }

                // If we are in submission phase ignore the following columns.
                if ($options->peerreviewphase == peerreview::PHASE_SUBMISSION) {
                    $table->data[] = $row;
                    continue;
                }

                // column #3 - received grades
                if ($tr % $spanreceived == 0) {
                    $idx = intval($tr / $spanreceived);
                    $assessment = self::array_nth($participant->reviewedby, $idx);
                    $cell = new html_table_cell();
                    $cell->text = $this->helper_grading_report_assessment(
                        $assessment,
                        $options->showreviewernames,
                        $userinfo,
                        get_string('gradereceivedfrom', 'peerreview')
                    );
                    $cell->rowspan = $spanreceived;
                    $cell->attributes['class'] = 'receivedgrade';
                    if (is_null($assessment) or is_null($assessment->grade)) {
                        $cell->attributes['class'] .= ' null';
                    } else {
                        $cell->attributes['class'] .= ' notnull';
                    }
                    $row->cells[] = $cell;
                }
                // column #4 - total grade for submission
                if ($options->showsubmissiongrade and $tr == 0) {
                    $cell = new html_table_cell();
                    $cell->text = $this->helper_grading_report_grade($participant->submissiongrade, $participant->submissiongradeover);
                    $cell->rowspan = $numoftrs;
                    $cell->attributes['class'] = 'submissiongrade';
                    $row->cells[] = $cell;
                }
                // column #5 - given grades
                if ($tr % $spangiven == 0) {
                    $idx = intval($tr / $spangiven);
                    $assessment = self::array_nth($participant->reviewerof, $idx);
                    $cell = new html_table_cell();
                    $cell->text = $this->helper_grading_report_assessment(
                        $assessment,
                        $options->showauthornames,
                        $userinfo,
                        get_string('gradegivento', 'peerreview')
                    );
                    $cell->rowspan = $spangiven;
                    $cell->attributes['class'] = 'givengrade';
                    if (is_null($assessment) or is_null($assessment->grade)) {
                        $cell->attributes['class'] .= ' null';
                    } else {
                        $cell->attributes['class'] .= ' notnull';
                    }
                    $row->cells[] = $cell;
                }
                // column #6 - total grade for assessment
                if ($options->showgradinggrade and $tr == 0) {
                    $cell = new html_table_cell();
                    $cell->text = $this->helper_grading_report_grade($participant->gradinggrade);
                    $cell->rowspan = $numoftrs;
                    $cell->attributes['class'] = 'gradinggrade';
                    $row->cells[] = $cell;
                }

                $table->data[] = $row;
            }
        }

        return html_writer::table($table);
    }

    /**
     * Renders the feedback for the author of the submission
     *
     * @param peerreview_feedback_author $feedback
     * @return string HTML
     */
    protected function render_peerreview_feedback_author(peerreview_feedback_author $feedback)
    {
        return $this->helper_render_feedback($feedback);
    }

    /**
     * Renders the feedback for the reviewer of the submission
     *
     * @param peerreview_feedback_reviewer $feedback
     * @return string HTML
     */
    protected function render_peerreview_feedback_reviewer(peerreview_feedback_reviewer $feedback)
    {
        return $this->helper_render_feedback($feedback);
    }

    /**
     * Helper method to rendering feedback
     *
     * @param peerreview_feedback_author|peerreview_feedback_reviewer $feedback
     * @return string HTML
     */
    private function helper_render_feedback($feedback)
    {

        $o  = '';    // output HTML code
        $o .= $this->output->container_start('feedback feedbackforauthor');
        $o .= $this->output->container_start('header');
        $o .= $this->output->heading(get_string('feedbackby', 'peerreview', s(fullname($feedback->get_provider()))), 3, 'title');

        $userpic = $this->output->user_picture($feedback->get_provider(), array('courseid' => $this->page->course->id, 'size' => 32));
        $o .= $this->output->container($userpic, 'picture');
        $o .= $this->output->container_end(); // end of header

        $content = format_text($feedback->get_content(), $feedback->get_format(), array('overflowdiv' => true));
        $o .= $this->output->container($content, 'content');

        $o .= $this->output->container_end();

        return $o;
    }

    /**
     * Renders the full assessment
     *
     * @param peerreview_assessment $assessment
     * @return string HTML
     */
    protected function render_peerreview_assessment(peerreview_assessment $assessment)
    {
        global $DB;
        $o = ''; // output HTML code

        // Fetch the assessment record from the peerreview_assessments table
        $assessment_record = $DB->get_record('peerreview_assessments', array('id' => $assessment->id));

        // Determine if the assessment is anonymous
        $anonymous = $assessment_record->anonymousreview;
        $classes = 'assessment-full';
        if ($anonymous) {
            $classes .= ' anonymous';
        }

        $o .= $this->output->container_start($classes);
        $o .= $this->output->container_start('header');

        // Use the anonymousreview column to set the title
        if ($anonymous) {
            $title = get_string('peerreviewnumber', 'peerreview', $assessment->id);
        } else {
            // Retrieve the author's full name using the reviewerid
            $authorname = fullname($DB->get_record('user', array('id' => $assessment_record->reviewerid)));
            $title = get_string('peerreviewby', 'peerreview', $authorname);
        }

        if (($assessment->url instanceof moodle_url) and ($this->page->url != $assessment->url)) {
            $o .= $this->output->container_start('button-group');

            $o .= $this->output->container($title, 'title');
            // Use Bootstrap class to display buttons inline
            if (is_null($assessment->realgrade)) {
                $o .= $this->output->container(
                    get_string('notassessed', 'peerreview'),
                    'grade nograde'
                );
                $o .= html_writer::link($assessment->url, 'Review project', array('class' => 'btn btn-primary d-inline-block'));
            } else {
                if (!is_null($assessment->realgrade)) {
                    $a              = new stdClass();
                    $a->max         = $assessment->maxgrade;
                    $a->received    = $assessment->realgrade;
                    $o .= $this->output->container(
                        get_string('gradeinfo', 'peerreview', $a),
                        'grade'
                    );

                    if (!is_null($assessment->weight) and $assessment->weight != 1) {
                        $o .= $this->output->container(
                            get_string('weightinfo', 'peerreview', $assessment->weight),
                            'weight'
                        );
                    }
                }
                $o .= html_writer::link($assessment->url, 'Edit review', array('class' => 'btn btn-primary d-inline-block'));
            }
            $peerreviewid = $assessment->peerreview->id;
            $cm = get_coursemodule_from_instance('peerreview', $peerreviewid);
            if ($cm) {
                $url = new moodle_url('/mod/peerreview/view.php', array('id' => $cm->id));
                $o .= html_writer::link(new moodle_url('/mod/peerreview/view.php', array('id' => 3)), get_string('othersubmissions', 'peerreview'), array('class' => 'btn btn-secondary d-inline-block m-2'));
            }
            global $CFG;
            require_once($CFG->dirroot . '/mod/peerreview/locallib.php');


            $o .= $this->render_next_submission_link($peerreviewid, $assessment->submissionid);






            $o .= $this->output->container_end(); // Close button-group container
        } else {
            $o .= $this->output->container($title, 'title');
        }

        $o .= $this->output->container_start('actions');
        foreach ($assessment->actions as $action) {
            $o .= $this->output->single_button($action->url, $action->label, $action->method);
        }
        $o .= $this->output->container_end(); // actions

        $o .= $this->output->container_end(); // header

        if (!is_null($assessment->form)) {
            $o .= print_collapsible_region_start(
                'assessment-form-wrapper',
                uniqid('peerreview-assessment'),
                get_string('assessmentform', 'peerreview'),
                'peerreview-viewlet-assessmentform-collapsed',
                false,
                true
            );
            $o .= $this->output->container(self::moodleform($assessment->form), 'assessment-form');
            $o .= print_collapsible_region_end(true);

            if (!$assessment->form->is_editable()) {
                $o .= $this->overall_feedback($assessment);
            }
        }

        $o .= $this->output->container_end(); // main wrapper

        return $o;
    }

    /**
     * Renders the assessment of an example submission
     *
     * @param peerreview_example_assessment $assessment
     * @return string HTML
     */
    protected function render_peerreview_example_assessment(peerreview_example_assessment $assessment)
    {
        return $this->render_peerreview_assessment($assessment);
    }

    /**
     * Renders the reference assessment of an example submission
     *
     * @param peerreview_example_reference_assessment $assessment
     * @return string HTML
     */
    protected function render_peerreview_example_reference_assessment(peerreview_example_reference_assessment $assessment)
    {
        return $this->render_peerreview_assessment($assessment);
    }

    /**
     * Renders the overall feedback for the author of the submission
     *
     * @param peerreview_assessment $assessment
     * @return string HTML
     */
    protected function overall_feedback(peerreview_assessment $assessment)
    {

        $content = $assessment->get_overall_feedback_content();

        if ($content === false) {
            return '';
        }

        $o = '';

        if (!is_null($content)) {
            $o .= $this->output->container($content, 'content');
        }

        $attachments = $assessment->get_overall_feedback_attachments();

        if (!empty($attachments)) {
            $o .= $this->output->container_start('attachments');
            $images = '';
            $files = '';
            foreach ($attachments as $attachment) {
                $icon = $this->output->pix_icon(
                    file_file_icon($attachment),
                    get_mimetype_description($attachment),
                    'moodle',
                    array('class' => 'icon')
                );
                $link = html_writer::link($attachment->fileurl, $icon . ' ' . substr($attachment->filepath . $attachment->filename, 1));
                if (file_mimetype_in_typegroup($attachment->mimetype, 'web_image')) {
                    $preview = html_writer::empty_tag('img', array('src' => $attachment->previewurl, 'alt' => '', 'class' => 'preview'));
                    $preview = html_writer::tag('a', $preview, array('href' => $attachment->fileurl));
                    $images .= $this->output->container($preview);
                } else {
                    $files .= html_writer::tag('li', $link, array('class' => $attachment->mimetype));
                }
            }
            if ($images) {
                $images = $this->output->container($images, 'images');
            }

            if ($files) {
                $files = html_writer::tag('ul', $files, array('class' => 'files'));
            }

            $o .= $images . $files;
            $o .= $this->output->container_end();
        }

        if ($o === '') {
            return '';
        }

        $o = $this->output->box($o, 'overallfeedback');
        $o = print_collapsible_region(
            $o,
            'overall-feedback-wrapper',
            uniqid('peerreview-overall-feedback'),
            get_string('overallfeedback', 'peerreview'),
            'peerreview-viewlet-overallfeedback-collapsed',
            false,
            true
        );

        return $o;
    }

    /**
     * Renders a perpage selector for peerreview listings
     *
     * The scripts using this have to define the $PAGE->url prior to calling this
     * and deal with eventually submitted value themselves.
     *
     * @param int $current current value of the perpage parameter
     * @return string HTML
     */
    public function perpage_selector($current = 10)
    {

        $options = array();
        foreach (array(10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 200, 300, 400, 500, 1000) as $option) {
            if ($option != $current) {
                $options[$option] = $option;
            }
        }
        $select = new single_select($this->page->url, 'perpage', $options, '', array('' => get_string('showingperpagechange', 'mod_peerreview')));
        $select->label = get_string('showingperpage', 'mod_peerreview', $current);
        $select->method = 'post';

        return $this->output->container($this->output->render($select), 'perpagewidget');
    }

    /**
     * Render the initials bars for peerreview.
     *
     * @param peerreview $peerreview the current peerreview of initial bars.
     * @param moodle_url $url base URL object.
     * @return string HTML.
     */
    public function initials_bars(peerreview $peerreview, moodle_url $url): string
    {
        $ifirst = $peerreview->get_initial_first();
        $ilast = $peerreview->get_initial_last();

        $html = $this->output->initials_bar($ifirst, 'firstinitial', get_string('firstname'), 'ifirst', $url);
        $html .= $this->output->initials_bar($ilast, 'lastinitial', get_string('lastname'), 'ilast', $url);
        return $html;
    }

    /**
     * Renders the user's final grades
     *
     * @param peerreview_final_grades $grades with the info about grades in the gradebook
     * @return string HTML
     */
    protected function render_peerreview_final_grades(peerreview_final_grades $grades)
    {

        $out = html_writer::start_tag('div', array('class' => 'finalgrades'));

        if (!empty($grades->submissiongrade)) {
            $cssclass = 'grade submissiongrade';
            if ($grades->submissiongrade->hidden) {
                $cssclass .= ' hiddengrade';
            }
            $out .= html_writer::tag(
                'div',
                html_writer::tag('div', get_string('submissiongrade', 'mod_peerreview'), array('class' => 'gradetype')) .
                    html_writer::tag('div', $grades->submissiongrade->str_long_grade, array('class' => 'gradevalue')),
                array('class' => $cssclass)
            );
        }

        if (!empty($grades->assessmentgrade)) {
            $cssclass = 'grade assessmentgrade';
            if ($grades->assessmentgrade->hidden) {
                $cssclass .= ' hiddengrade';
            }
            $out .= html_writer::tag(
                'div',
                html_writer::tag('div', get_string('gradinggrade', 'mod_peerreview'), array('class' => 'gradetype')) .
                    html_writer::tag('div', $grades->assessmentgrade->str_long_grade, array('class' => 'gradevalue')),
                array('class' => $cssclass)
            );
        }

        $out .= html_writer::end_tag('div');

        return $out;
    }

    public function render_next_submission_link($peerreviewid, $submissionid)
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/peerreview/locallib.php');

        $o = '';

        // Fetch the list of submissions directly from the database
        $submissions = $DB->get_records('peerreview_submissions', array('peerreviewid' => $peerreviewid));

        // Convert submissions to an array and sort them by ID
        $submissions = array_values($submissions);
        usort($submissions, function ($a, $b) {
            return $a->id - $b->id;
        });

        // Find the ID of the last element in the list
        $lastSubmissionId = end($submissions)->id;

        // If $submissionid is not the ID of the last element in the list
        if ($submissionid != $lastSubmissionId) {
            // Iterate the list starting at position $submissionid until the ID of the last element
            for ($i = 0; $i < count($submissions); $i++) {
                if ($submissions[$i]->id == $submissionid && isset($submissions[$i + 1])) {
                    $nextSubmission = $submissions[$i + 1];
                    break;
                }
            }
        } else {
            // If $submissionid is the ID of the last element, wrap around to the first
            $nextSubmission = $submissions[0];
        }

        // Retrieve the cmid (course module id)
        $cm = get_coursemodule_from_instance('peerreview', $peerreviewid);
        $cmid = $cm->id;

        // Construct the URL for the next submission
        $nextSubmissionUrl = new moodle_url('/mod/peerreview/submission.php', array('cmid' => $cmid, 'id' => $nextSubmission->id));

        // Use the new URL in the html_writer::link function
        $o .= html_writer::link($nextSubmissionUrl, get_string('nextproject', 'peerreview'), array('class' => 'btn btn-secondary d-inline-block'));

        return $o;
    }


    ////////////////////////////////////////////////////////////////////////////
    // Internal rendering helper methods
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Renders a list of files attached to the submission
     *
     * If format==html, then format a html string. If format==text, then format a text-only string.
     * Otherwise, returns html for non-images and html to display the image inline.
     *
     * @param int $submissionid submission identifier
     * @param string format the format of the returned string - html|text
     * @return string formatted text to be echoed
     */
    protected function helper_submission_attachments($submissionid, $format = 'html')
    {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $fs     = get_file_storage();
        $ctx    = $this->page->context;
        $files  = $fs->get_area_files($ctx->id, 'mod_peerreview', 'submission_attachment', $submissionid);

        $outputimgs     = '';   // images to be displayed inline
        $outputfiles    = '';   // list of attachment files

        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }

            $filepath   = $file->get_filepath();
            $filename   = $file->get_filename();
            $fileurl    = moodle_url::make_pluginfile_url(
                $ctx->id,
                'mod_peerreview',
                'submission_attachment',
                $submissionid,
                $filepath,
                $filename,
                true
            );
            $embedurl   = moodle_url::make_pluginfile_url(
                $ctx->id,
                'mod_peerreview',
                'submission_attachment',
                $submissionid,
                $filepath,
                $filename,
                false
            );
            $embedurl   = new moodle_url($embedurl, array('preview' => 'bigthumb'));
            $type       = $file->get_mimetype();
            $image      = $this->output->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon'));

            $linkhtml   = html_writer::link($fileurl, $image . substr($filepath, 1) . $filename);
            $linktxt    = "$filename [$fileurl]";

            if ($format == 'html') {
                if (file_mimetype_in_typegroup($type, 'web_image')) {
                    $preview     = html_writer::empty_tag('img', array('src' => $embedurl, 'alt' => '', 'class' => 'preview'));
                    $preview     = html_writer::tag('a', $preview, array('href' => $fileurl));
                    $outputimgs .= $this->output->container($preview);
                } else {
                    $outputfiles .= html_writer::tag('li', $linkhtml, array('class' => $type));
                }
            } else if ($format == 'text') {
                $outputfiles .= $linktxt . PHP_EOL;
            }

            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');
                $outputfiles .= plagiarism_get_links(array(
                    'userid' => $file->get_userid(),
                    'file' => $file,
                    'cmid' => $this->page->cm->id,
                    'course' => $this->page->course->id
                ));
            }
        }

        if ($format == 'html') {
            if ($outputimgs) {
                $outputimgs = $this->output->container($outputimgs, 'images');
            }

            if ($outputfiles) {
                $outputfiles = html_writer::tag('ul', $outputfiles, array('class' => 'files'));
            }

            return $this->output->container($outputimgs . $outputfiles, 'attachments');
        } else {
            return $outputfiles;
        }
    }

    /**
     * Renders the tasks for the single phase in the user plan
     *
     * @param stdClass $tasks
     * @return string html code
     */
    protected function helper_user_plan_tasks(array $tasks)
    {
        $out = '';
        foreach ($tasks as $taskcode => $task) {
            $classes = '';
            $accessibilitytext = '';
            $icon = null;
            if ($task->completed === true) {
                $classes .= ' completed';
                $accessibilitytext .= get_string('taskdone', 'peerreview') . ' ';
            } else if ($task->completed === false) {
                $classes .= ' fail';
                $accessibilitytext .= get_string('taskfail', 'peerreview') . ' ';
            } else if ($task->completed === 'info') {
                $classes .= ' info';
                $accessibilitytext .= get_string('taskinfo', 'peerreview') . ' ';
            } else {
                $accessibilitytext .= get_string('tasktodo', 'peerreview') . ' ';
            }
            if (is_null($task->link)) {
                $title = html_writer::tag('span', $accessibilitytext, array('class' => 'accesshide'));
                $title .= $task->title;
            } else {
                $title = html_writer::tag('span', $accessibilitytext, array('class' => 'accesshide'));
                $title .= html_writer::link($task->link, $task->title);
            }
            $title = $this->output->container($title, 'title');
            $details = $this->output->container($task->details, 'details');
            $out .= html_writer::tag('li', $title . $details, array('class' => $classes));
        }
        if ($out) {
            $out = html_writer::tag('ul', $out, array('class' => 'tasks'));
        }
        return $out;
    }

    /**
     * Renders a text with icons to sort by the given column
     *
     * This is intended for table headings.
     *
     * @param string $text    The heading text
     * @param string $sortid  The column id used for sorting
     * @param string $sortby  Currently sorted by (column id)
     * @param string $sorthow Currently sorted how (ASC|DESC)
     *
     * @return string
     */
    protected function helper_sortable_heading($text, $sortid = null, $sortby = null, $sorthow = null)
    {

        $out = html_writer::tag('span', $text, array('class' => 'text'));

        if (!is_null($sortid)) {
            if ($sortby !== $sortid or $sorthow !== 'ASC') {
                $url = new moodle_url($this->page->url);
                $url->params(array('sortby' => $sortid, 'sorthow' => 'ASC'));
                $out .= $this->output->action_icon(
                    $url,
                    new pix_icon('t/sort_asc', get_string('sortasc', 'peerreview')),
                    null,
                    array('class' => 'iconsort sort asc')
                );
            }
            if ($sortby !== $sortid or $sorthow !== 'DESC') {
                $url = new moodle_url($this->page->url);
                $url->params(array('sortby' => $sortid, 'sorthow' => 'DESC'));
                $out .= $this->output->action_icon(
                    $url,
                    new pix_icon('t/sort_desc', get_string('sortdesc', 'peerreview')),
                    null,
                    array('class' => 'iconsort sort desc')
                );
            }
        }
        return $out;
    }

    /**
     * @param stdClass $participant
     * @param array $userinfo
     * @return string
     */
    protected function helper_grading_report_participant(stdclass $participant, array $userinfo)
    {
        $userid = $participant->userid;
        $out  = $this->output->user_picture($userinfo[$userid], array('courseid' => $this->page->course->id, 'size' => 35));
        $out .= html_writer::tag('span', fullname($userinfo[$userid]));

        return $out;
    }

    /**
     * @param stdClass $participant
     * @return string
     */
    protected function helper_grading_report_submission(stdclass $participant)
    {
        global $CFG;

        if (is_null($participant->submissionid)) {
            $out = $this->output->container(get_string('nosubmissionfound', 'peerreview'), 'info');
        } else {
            $url = new moodle_url(
                '/mod/peerreview/submission.php',
                array('cmid' => $this->page->context->instanceid, 'id' => $participant->submissionid)
            );
            $out = html_writer::link($url, format_string($participant->submissiontitle), array('class' => 'title'));

            $lastmodified = get_string('userdatemodified', 'peerreview', userdate($participant->submissionmodified));
            $out .= html_writer::tag('div', $lastmodified, array('class' => 'lastmodified'));
        }

        return $out;
    }

    /**
     * @todo Highlight the nulls
     * @param stdClass|null $assessment
     * @param bool $shownames
     * @param string $separator between the grade and the reviewer/author
     * @return string
     */
    protected function helper_grading_report_assessment($assessment, $shownames, array $userinfo, $separator)
    {
        global $CFG;

        if (is_null($assessment)) {
            return get_string('nullgrade', 'peerreview');
        }
        $a = new stdclass();
        $a->grade = is_null($assessment->grade) ? get_string('nullgrade', 'peerreview') : $assessment->grade;
        $a->gradinggrade = is_null($assessment->gradinggrade) ? get_string('nullgrade', 'peerreview') : $assessment->gradinggrade;
        $a->weight = $assessment->weight;
        // grrr the following logic should really be handled by a future language pack feature
        if (is_null($assessment->gradinggradeover)) {
            if ($a->weight == 1) {
                $grade = get_string('formatpeergrade', 'peerreview', $a);
            } else {
                $grade = get_string('formatpeergradeweighted', 'peerreview', $a);
            }
        } else {
            $a->gradinggradeover = $assessment->gradinggradeover;
            if ($a->weight == 1) {
                $grade = get_string('formatpeergradeover', 'peerreview', $a);
            } else {
                $grade = get_string('formatpeergradeoverweighted', 'peerreview', $a);
            }
        }
        $url = new moodle_url(
            '/mod/peerreview/assessment.php',
            array('asid' => $assessment->assessmentid)
        );
        $grade = html_writer::link($url, $grade, array('class' => 'grade'));

        if ($shownames) {
            $userid = $assessment->userid;
            $name   = $this->output->user_picture($userinfo[$userid], array('courseid' => $this->page->course->id, 'size' => 16));
            $name  .= html_writer::tag('span', fullname($userinfo[$userid]), array('class' => 'fullname'));
            $name   = $separator . html_writer::tag('span', $name, array('class' => 'user'));
        } else {
            $name   = '';
        }

        return $this->output->container($grade . $name, 'assessmentdetails');
    }

    /**
     * Formats the aggreagated grades
     */
    protected function helper_grading_report_grade($grade, $over = null)
    {
        $a = new stdclass();
        $a->grade = is_null($grade) ? get_string('nullgrade', 'peerreview') : $grade;
        if (is_null($over)) {
            $text = get_string('formataggregatedgrade', 'peerreview', $a);
        } else {
            $a->over = is_null($over) ? get_string('nullgrade', 'peerreview') : $over;
            $text = get_string('formataggregatedgradeover', 'peerreview', $a);
        }
        return $text;
    }

    ////////////////////////////////////////////////////////////////////////////
    // Static helpers
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Helper method dealing with the fact we can not just fetch the output of moodleforms
     *
     * @param moodleform $mform
     * @return string HTML
     */
    protected static function moodleform(moodleform $mform)
    {

        ob_start();
        $mform->display();
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }

    /**
     * Helper function returning the n-th item of the array
     *
     * @param array $a
     * @param int   $n from 0 to m, where m is th number of items in the array
     * @return mixed the $n-th element of $a
     */
    protected static function array_nth(array $a, $n)
    {
        $keys = array_keys($a);
        if ($n < 0 or $n > count($keys) - 1) {
            return null;
        }
        $key = $keys[$n];
        return $a[$key];
    }

    /**
     * Tries to guess the fullname format set at the site
     *
     * @return string fl|lf
     */
    protected static function fullname_format()
    {
        $fake = new stdclass(); // fake user
        $fake->lastname = 'LLLL';
        $fake->firstname = 'FFFF';
        $fullname = get_string('fullnamedisplay', '', $fake);
        if (strpos($fullname, 'LLLL') < strpos($fullname, 'FFFF')) {
            return 'lf';
        } else {
            return 'fl';
        }
    }

    /**
     * Generates the action buttons.
     *
     * @param peerreview $peerreview The current peerreview.
     * @param peerreview_user_plan $userplan An individual peerreview plan for the user.
     * @return string HTML to display.
     */
    public function render_action_buttons(peerreview $peerreview, peerreview_user_plan $userplan): string
    {
        global $USER;
        $output = '';

        switch ($peerreview->phase) {
            case peerreview::PHASE_SUBMISSION:
                // Does the user have to assess examples before submitting their own work?
                $examplesmust = ($peerreview->useexamples && $peerreview->examplesmode == peerreview::EXAMPLES_BEFORE_SUBMISSION);

                // Is the assessment of example submissions considered finished?
                $examplesdone = has_capability('mod/peerreview:manageexamples', $peerreview->context);

                if (
                    $peerreview->assessing_examples_allowed() && has_capability('mod/peerreview:submit', $peerreview->context) &&
                    !has_capability('mod/peerreview:manageexamples', $peerreview->context)
                ) {
                    $examples = $userplan->get_examples();
                    $left = 0;
                    // Make sure the current user has all examples allocated.
                    foreach ($examples as $exampleid => $example) {
                        if (is_null($example->grade)) {
                            $left++;
                            break;
                        }
                    }
                    if ($left > 0 && $peerreview->examplesmode != peerreview::EXAMPLES_VOLUNTARY) {
                        $examplesdone = false;
                    } else {
                        $examplesdone = true;
                    }
                }

                if (has_capability('mod/peerreview:submit', $this->page->context) && (!$examplesmust || $examplesdone)) {
                    if (!$peerreview->get_submission_by_author($USER->id)) {
                        $btnurl = new moodle_url($peerreview->submission_url(), ['edit' => 'on']);

                        $btntxt = get_string('createsubmission', 'peerreview');
                        $output .= html_writer::tag('h2', get_string('mysubmission', 'mod_peerreview'), array('class' => 'my-4'));
                        $output .= $this->single_button($btnurl, $btntxt, 'get', ['type' => single_button::BUTTON_PRIMARY]);
                    }
                }
                break;

            case peerreview::PHASE_ASSESSMENT:
                if (has_capability('mod/peerreview:submit', $this->page->context)) {
                    if (!$peerreview->get_submission_by_author($USER->id)) {
                        if ($peerreview->creating_submission_allowed($USER->id)) {
                            $btnurl = new moodle_url($peerreview->submission_url(), ['edit' => 'on']);
                            $btntxt = get_string('createsubmission', 'peerreview');
                            $output .= $this->single_button($btnurl, $btntxt, 'get', ['type' => single_button::BUTTON_PRIMARY]);
                        }
                    }
                }
        }

        return $output;
    }

    /**
     * Generates the view page.
     *
     * @param peerreview $peerreview The current peerreview.
     * @param peerreview_user_plan $userplan An individual peerreview plan for the user.
     * @param string $currentphasetitle The current phase title.
     * @param int $page The current page (for the pagination).
     * @param string $sortby Lastname|firstname|submissiontitle|submissiongrade|gradinggrade.
     * @param string $sorthow ASC|DESC.
     * @return string HTML to display.
     */
    public function view_page(
        peerreview $peerreview,
        peerreview_user_plan $userplan,
        string $currentphasetitle,
        int $page,
        string $sortby,
        string $sorthow
    ): string {
        $output = '';

        $output .= $this->render_action_buttons($peerreview, $userplan);
        $output .= $this->heading(format_string($currentphasetitle), 3, null, 'mod_peerreview-userplanheading');
        $output .= $this->render($userplan);
        $output .= $this->view_submissions_report($peerreview, $userplan, $page, $sortby, $sorthow);

        return $output;
    }

    /**
     * Generates the submission report.
     *
     * @param peerreview $peerreview The current peerreview.
     * @param peerreview_user_plan $userplan An individual peerreview plan for the user.
     * @param int $page The current page (for the pagination).
     * @param string $sortby Lastname|firstname|submissiontitle|submissiongrade|gradinggrade.
     * @param string $sorthow ASC|DESC.
     * @return string HTML to display.
     */
    public function view_submissions_report(
        peerreview $peerreview,
        peerreview_user_plan $userplan,
        int $page,
        string $sortby,
        string $sorthow
    ): string {
        global $USER;
        $output = '';

        switch ($peerreview->phase) {
            case peerreview::PHASE_SETUP:
                if (trim($peerreview->intro)) {
                    $output .= print_collapsible_region_start(
                        '',
                        'peerreview-viewlet-intro',
                        get_string('introduction', 'peerreview'),
                        'peerreview-viewlet-intro-collapsed',
                        false,
                        true
                    );
                    $output .= $this->box(format_module_intro('peerreview', $peerreview, $peerreview->cm->id), 'generalbox');
                    $output .= print_collapsible_region_end(true);
                }
                if ($peerreview->useexamples && has_capability('mod/peerreview:manageexamples', $this->page->context)) {
                    $output .= print_collapsible_region_start(
                        '',
                        'peerreview-viewlet-allexamples',
                        get_string('examplesubmissions', 'peerreview'),
                        'peerreview-viewlet-allexamples-collapsed',
                        false,
                        true
                    );
                    $output .= $this->box_start('generalbox examples');
                    if ($peerreview->grading_strategy_instance()->form_ready()) {
                        if (!$examples = $peerreview->get_examples_for_manager()) {
                            $output .= $this->container(get_string('noexamples', 'peerreview'), 'noexamples');
                        }
                        foreach ($examples as $example) {
                            $summary = $peerreview->prepare_example_summary($example);
                            $summary->editable = true;
                            $output .= $this->render($summary);
                        }
                        $aurl = new moodle_url($peerreview->exsubmission_url(0), ['edit' => 'on']);
                        $output .= $this->single_button($aurl, get_string('exampleadd', 'peerreview'), 'get');
                    } else {
                        $output .= $this->container(get_string('noexamplesformready', 'peerreview'));
                    }
                    $output .= $this->box_end();
                    $output .= print_collapsible_region_end(true);
                }
                break;
            case peerreview::PHASE_SUBMISSION:
                // Edit: The Peerreview is aways on submission phase
                // $examplesmust = ($peerreview->useexamples && $peerreview->examplesmode == peerreview::EXAMPLES_BEFORE_SUBMISSION);
                // $examplesdone = has_capability('mod/peerreview:manageexamples', $peerreview->context);
                // if (trim($peerreview->instructauthors)) {
                //     $instructions = file_rewrite_pluginfile_urls($peerreview->instructauthors,
                //         'pluginfile.php', $this->page->context->id,
                //         'mod_peerreview', 'instructauthors', null, peerreview::instruction_editors_options($this->page->context));
                //     $output .= print_collapsible_region_start('', 'peerreview-viewlet-instructauthors',
                //         get_string('instructauthors', 'peerreview'),
                //         'peerreview-viewlet-instructauthors-collapsed', false, true);
                //     $output .= $this->box(format_text($instructions, $peerreview->instructauthorsformat, ['overflowdiv' => true]),
                //         ['generalbox', 'instructions']);
                //     $output .= print_collapsible_region_end(true);
                // }

                // if ($peerreview->assessing_examples_allowed()
                //     && has_capability('mod/peerreview:submit', $peerreview->context)
                //     && !has_capability('mod/peerreview:manageexamples', $peerreview->context)) {
                //     $examples = $userplan->get_examples();
                //     $total = count($examples);
                //     $output .= print_collapsible_region_start('', 'peerreview-viewlet-examples',
                //         get_string('exampleassessments', 'peerreview'),
                //         'peerreview-viewlet-examples-collapsed', $examplesdone, true);
                //     $output .= $this->box_start('generalbox exampleassessments');
                //     if ($total == 0) {
                //         $output .= $this->heading(get_string('noexamples', 'peerreview'), 3);
                //     } else {
                //         foreach ($examples as $example) {
                //             $summary = $peerreview->prepare_example_summary($example);
                //             $output .= $this->render($summary);
                //         }
                //     }
                //     $output .= $this->box_end();
                //     $output .= print_collapsible_region_end(true);
                // }

                // if (has_capability('mod/peerreview:submit', $this->page->context) && (!$examplesmust || $examplesdone)) {
                //     $output .= print_collapsible_region_start('', 'peerreview-viewlet-ownsubmission',
                //         get_string('yoursubmission', 'peerreview'),
                //         'peerreview-viewlet-ownsubmission-collapsed', false, true);
                //     $output .= $this->box_start('generalbox ownsubmission');
                //     if ($submission = $peerreview->get_submission_by_author($USER->id)) {
                //         $output .= $this->render($peerreview->prepare_submission_summary($submission, true));
                //     } else {
                //         $output .= $this->container(get_string('noyoursubmission', 'peerreview'));
                //     }

                //     $output .= $this->box_end();
                //     $output .= print_collapsible_region_end(true);
                // }

                // if (has_capability('mod/peerreview:viewallsubmissions', $this->page->context)) {
                //     $groupmode = groups_get_activity_groupmode($peerreview->cm);
                //     $groupid = groups_get_activity_group($peerreview->cm, true);

                //     if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $peerreview->context)) {
                //         $allowedgroups = groups_get_activity_allowed_groups($peerreview->cm);
                //         if (empty($allowedgroups)) {
                //             $output .= $this->container(get_string('groupnoallowed', 'mod_peerreview'), 'groupwidget error');
                //             break;
                //         }
                //         if (!in_array($groupid, array_keys($allowedgroups))) {
                //             $output .= $this->container(get_string('groupnotamember', 'core_group'), 'groupwidget error');
                //             break;
                //         }
                //     }

                //     $output .= print_collapsible_region_start('', 'peerreview-viewlet-allsubmissions',
                //         get_string('submissionsreport', 'peerreview'),
                //         'peerreview-viewlet-allsubmissions-collapsed', false, true);

                //     $perpage = get_user_preferences('peerreview_perpage', 10);
                //     $data = $peerreview->prepare_grading_report_data($USER->id, $groupid, $page, $perpage, $sortby, $sorthow);
                //     if ($data) {
                //         $countparticipants = $peerreview->count_participants();
                //         $countsubmissions = $peerreview->count_submissions(array_keys($data->grades), $groupid);
                //         $a = new stdClass();
                //         $a->submitted = $countsubmissions;
                //         $a->notsubmitted = $data->totalcount - $countsubmissions;

                //         $output .= html_writer::tag('div', get_string('submittednotsubmitted', 'peerreview', $a));

                //         $output .= $this->container(
                //             groups_print_activity_menu($peerreview->cm, $this->page->url, true), 'groupwidget');

                //         // Prepare the paging bar.
                //         $baseurl = new moodle_url($this->page->url, ['sortby' => $sortby, 'sorthow' => $sorthow]);
                //         $pagingbar = new paging_bar($data->totalcount, $page, $perpage, $baseurl, 'page');

                //         // Populate the display options for the submissions report.
                //         $reportopts = new stdclass();
                //         $reportopts->showauthornames = has_capability('mod/peerreview:viewauthornames', $peerreview->context);
                //         $reportopts->showreviewernames = has_capability('mod/peerreview:viewreviewernames', $peerreview->context);
                //         $reportopts->sortby = $sortby;
                //         $reportopts->sorthow = $sorthow;
                //         $reportopts->showsubmissiongrade = false;
                //         $reportopts->showgradinggrade = false;
                //         $reportopts->peerreviewphase = $peerreview->phase;
                //         $output .= $this->initials_bars($peerreview, $baseurl);
                //         $output .= $this->render($pagingbar);
                //         $output .= $this->render(new peerreview_grading_report($data, $reportopts));
                //         $output .= $this->render($pagingbar);
                //         $output .= $this->perpage_selector($perpage);
                //     } else {
                //         $output .= html_writer::tag('div', get_string('nothingfound', 'peerreview'), ['class' => 'nothingfound']);
                //     }
                //     $output .= print_collapsible_region_end(true);
                // }

                $submissions = $peerreview->get_submissions();

                if (empty($submissions)) {
                    return html_writer::tag('div', get_string('nosubmissions', 'mod_peerreview'), array('class' => 'alert alert-info'));
                }

                $user_submissions = [];
                $other_submissions = [];

                // Separate submissions
                foreach ($submissions as $submission) {
                    if ($submission->authorid == $USER->id) {
                        $user_submissions[] = $submission;
                    } else {
                        // Split the groupcoauthor string into an array
                        $groupcoauthor_emails = explode(';', $submission->groupcoauthor);
                        // Check if the current user's email is in the array
                        if (in_array($USER->email, $groupcoauthor_emails)) {
                            $user_submissions[] = $submission;
                        } else {
                            $other_submissions[] = $submission;
                        }
                    }
                }

                $output = html_writer::start_tag('div', array('class' => 'submissions-list'));

                // Render user submissions
                if (!empty($user_submissions)) {
                    $output .= html_writer::tag('h2', get_string('mysubmission', 'mod_peerreview'), array('class' => 'my-4'));
                    $output .= $this->render_submissions_cards($user_submissions, $peerreview->cm->id);
                }

                // Render other submissions
                if (!empty($other_submissions)) {
                    $output .= html_writer::tag('h2', get_string('othersubmissions', 'mod_peerreview'), array('class' => 'my-4'));
                    $output .= $this->render_submissions_cards($other_submissions, $peerreview->cm->id);
                }

                $output .= html_writer::end_tag('div'); // Close submissions-list div

                return $output;

                break;

            case peerreview::PHASE_ASSESSMENT:

                $ownsubmissionexists = null;
                if (has_capability('mod/peerreview:submit', $this->page->context)) {
                    if ($ownsubmission = $peerreview->get_submission_by_author($USER->id)) {
                        $output .= print_collapsible_region_start(
                            '',
                            'peerreview-viewlet-ownsubmission',
                            get_string('yoursubmission', 'peerreview'),
                            'peerreview-viewlet-ownsubmission-collapsed',
                            true,
                            true
                        );
                        $output .= $this->box_start('generalbox ownsubmission');
                        $output .= $this->render($peerreview->prepare_submission_summary($ownsubmission, true));
                        $ownsubmissionexists = true;
                    } else {
                        $output .= print_collapsible_region_start(
                            '',
                            'peerreview-viewlet-ownsubmission',
                            get_string('yoursubmission', 'peerreview'),
                            'peerreview-viewlet-ownsubmission-collapsed',
                            false,
                            true
                        );
                        $output .= $this->box_start('generalbox ownsubmission');
                        $output .= $this->container(get_string('noyoursubmission', 'peerreview'));
                        $ownsubmissionexists = false;
                    }

                    $output .= $this->box_end();
                    $output .= print_collapsible_region_end(true);
                }

                if (has_capability('mod/peerreview:viewallassessments', $this->page->context)) {
                    $perpage = get_user_preferences('peerreview_perpage', 10);
                    $groupid = groups_get_activity_group($peerreview->cm, true);
                    $data = $peerreview->prepare_grading_report_data($USER->id, $groupid, $page, $perpage, $sortby, $sorthow);
                    if ($data) {
                        $showauthornames = has_capability('mod/peerreview:viewauthornames', $peerreview->context);
                        $showreviewernames = has_capability('mod/peerreview:viewreviewernames', $peerreview->context);

                        // Prepare paging bar.
                        $baseurl = new moodle_url($this->page->url, ['sortby' => $sortby, 'sorthow' => $sorthow]);
                        $pagingbar = new paging_bar($data->totalcount, $page, $perpage, $baseurl, 'page');

                        // Grading report display options.
                        $reportopts = new stdclass();
                        $reportopts->showauthornames = $showauthornames;
                        $reportopts->showreviewernames = $showreviewernames;
                        $reportopts->sortby = $sortby;
                        $reportopts->sorthow = $sorthow;
                        $reportopts->showsubmissiongrade = false;
                        $reportopts->showgradinggrade = false;
                        $reportopts->peerreviewphase = $peerreview->phase;

                        $output .= print_collapsible_region_start(
                            '',
                            'peerreview-viewlet-gradereport',
                            get_string('gradesreport', 'peerreview'),
                            'peerreview-viewlet-gradereport-collapsed',
                            false,
                            true
                        );
                        $output .= $this->box_start('generalbox gradesreport');
                        $output .= $this->container(groups_print_activity_menu(
                            $peerreview->cm,
                            $this->page->url,
                            true
                        ), 'groupwidget');
                        $output .= $this->initials_bars($peerreview, $baseurl);
                        $output .= $this->render($pagingbar);
                        $output .= $this->render(new peerreview_grading_report($data, $reportopts));
                        $output .= $this->render($pagingbar);
                        $output .= $this->perpage_selector($perpage);
                        $output .= $this->box_end();
                        $output .= print_collapsible_region_end(true);
                    }
                }
                if (trim($peerreview->instructreviewers)) {
                    $instructions = file_rewrite_pluginfile_urls(
                        $peerreview->instructreviewers,
                        'pluginfile.php',
                        $this->page->context->id,
                        'mod_peerreview',
                        'instructreviewers',
                        null,
                        peerreview::instruction_editors_options($this->page->context)
                    );
                    $output .= print_collapsible_region_start(
                        '',
                        'peerreview-viewlet-instructreviewers',
                        get_string('instructreviewers', 'peerreview'),
                        'peerreview-viewlet-instructreviewers-collapsed',
                        false,
                        true
                    );
                    $output .= $this->box(format_text(
                        $instructions,
                        $peerreview->instructreviewersformat,
                        ['overflowdiv' => true]
                    ), ['generalbox', 'instructions']);
                    $output .= print_collapsible_region_end(true);
                }

                // Does the user have to assess examples before assessing other's work?
                $examplesmust = ($peerreview->useexamples && $peerreview->examplesmode == peerreview::EXAMPLES_BEFORE_ASSESSMENT);

                // Is the assessment of example submissions considered finished?
                $examplesdone = has_capability('mod/peerreview:manageexamples', $peerreview->context);

                // Can the examples be assessed?
                $examplesavailable = true;

                if (!$examplesdone && $examplesmust && ($ownsubmissionexists === false)) {
                    $output .= print_collapsible_region_start(
                        '',
                        'peerreview-viewlet-examplesfail',
                        get_string('exampleassessments', 'peerreview'),
                        'peerreview-viewlet-examplesfail-collapsed',
                        false,
                        true
                    );
                    $output .= $this->box(get_string('exampleneedsubmission', 'peerreview'));
                    $output .= print_collapsible_region_end(true);
                    $examplesavailable = false;
                }

                if (
                    $peerreview->assessing_examples_allowed()
                    && has_capability('mod/peerreview:submit', $peerreview->context)
                    && !has_capability('mod/peerreview:manageexamples', $peerreview->context)
                    && $examplesavailable
                ) {
                    $examples = $userplan->get_examples();
                    $total = count($examples);
                    $left = 0;
                    // Make sure the current user has all examples allocated.
                    foreach ($examples as $exampleid => $example) {
                        if (is_null($example->assessmentid)) {
                            $examples[$exampleid]->assessmentid = $peerreview->add_allocation($example, $USER->id, 0);
                        }
                        if (is_null($example->grade)) {
                            $left++;
                        }
                    }
                    if ($left > 0 && $peerreview->examplesmode != peerreview::EXAMPLES_VOLUNTARY) {
                        $examplesdone = false;
                    } else {
                        $examplesdone = true;
                    }
                    $output .= print_collapsible_region_start(
                        '',
                        'peerreview-viewlet-examples',
                        get_string('exampleassessments', 'peerreview'),
                        'peerreview-viewlet-examples-collapsed',
                        $examplesdone,
                        true
                    );
                    $output .= $this->box_start('generalbox exampleassessments');
                    if ($total == 0) {
                        $output .= $this->heading(get_string('noexamples', 'peerreview'), 3);
                    } else {
                        foreach ($examples as $example) {
                            $summary = $peerreview->prepare_example_summary($example);
                            $output .= $this->render($summary);
                        }
                    }
                    $output .= $this->box_end();
                    $output .= print_collapsible_region_end(true);
                }
                if (!$examplesmust || $examplesdone) {
                    $output .= print_collapsible_region_start(
                        '',
                        'peerreview-viewlet-assignedassessments',
                        get_string('assignedassessments', 'peerreview'),
                        'peerreview-viewlet-assignedassessments-collapsed',
                        false,
                        true
                    );
                    if (!$assessments = $peerreview->get_assessments_by_reviewer($USER->id)) {
                        $output .= $this->box_start('generalbox assessment-none');
                        $output .= $this->notification(get_string('assignedassessmentsnone', 'peerreview'));
                        $output .= $this->box_end();
                    } else {
                        $shownames = has_capability('mod/peerreview:viewauthornames', $this->page->context);
                        foreach ($assessments as $assessment) {
                            $submission = new stdClass();
                            $submission->id = $assessment->submissionid;
                            $submission->title = $assessment->submissiontitle;
                            $submission->timecreated = $assessment->submissioncreated;
                            $submission->timemodified = $assessment->submissionmodified;
                            $userpicturefields = explode(',', implode(',', \core_user\fields::get_picture_fields()));
                            foreach ($userpicturefields as $userpicturefield) {
                                $prefixedusernamefield = 'author' . $userpicturefield;
                                $submission->$prefixedusernamefield = $assessment->$prefixedusernamefield;
                            }

                            // Transform the submission object into renderable component.
                            $submission = $peerreview->prepare_submission_summary($submission, $shownames);

                            if (is_null($assessment->grade)) {
                                $submission->status = 'notgraded';
                                $class = ' notgraded';
                                $buttontext = get_string('assess', 'peerreview');
                            } else {
                                $submission->status = 'graded';
                                $class = ' graded';
                                $buttontext = get_string('reassess', 'peerreview');
                            }

                            $output .= $this->box_start('generalbox assessment-summary' . $class);
                            $output .= $this->render($submission);
                            $aurl = $peerreview->assess_url($assessment->id);
                            $output .= $this->single_button($aurl, $buttontext, 'get');
                            $output .= $this->box_end();
                        }
                    }
                    $output .= print_collapsible_region_end(true);
                }
                break;
            case peerreview::PHASE_EVALUATION:
                if (has_capability('mod/peerreview:viewallassessments', $this->page->context)) {
                    $perpage = get_user_preferences('peerreview_perpage', 10);
                    $groupid = groups_get_activity_group($peerreview->cm, true);
                    $data = $peerreview->prepare_grading_report_data($USER->id, $groupid, $page, $perpage, $sortby, $sorthow);
                    if ($data) {
                        $showauthornames = has_capability('mod/peerreview:viewauthornames', $peerreview->context);
                        $showreviewernames = has_capability('mod/peerreview:viewreviewernames', $peerreview->context);

                        if (has_capability('mod/peerreview:overridegrades', $this->page->context)) {
                            // Print a drop-down selector to change the current evaluation method.
                            $selector = new single_select(
                                $this->page->url,
                                'eval',
                                peerreview::available_evaluators_list(),
                                $peerreview->evaluation,
                                false,
                                'evaluationmethodchooser'
                            );
                            $selector->set_label(get_string('evaluationmethod', 'mod_peerreview'));
                            $selector->set_help_icon('evaluationmethod', 'mod_peerreview');
                            $selector->method = 'post';
                            $output .= $this->render($selector);
                            // Load the grading evaluator.
                            $evaluator = $peerreview->grading_evaluation_instance();
                            $form = $evaluator->get_settings_form(new moodle_url(
                                $peerreview->aggregate_url(),
                                compact('sortby', 'sorthow', 'page')
                            ));
                            $form->display();
                        }

                        // Prepare paging bar.
                        $baseurl = new moodle_url($this->page->url, ['sortby' => $sortby, 'sorthow' => $sorthow]);
                        $pagingbar = new paging_bar($data->totalcount, $page, $perpage, $baseurl, 'page');

                        // Grading report display options.
                        $reportopts = new stdclass();
                        $reportopts->showauthornames = $showauthornames;
                        $reportopts->showreviewernames = $showreviewernames;
                        $reportopts->sortby = $sortby;
                        $reportopts->sorthow = $sorthow;
                        $reportopts->showsubmissiongrade = true;
                        $reportopts->showgradinggrade = true;
                        $reportopts->peerreviewphase = $peerreview->phase;

                        $output .= print_collapsible_region_start(
                            '',
                            'peerreview-viewlet-gradereport',
                            get_string('gradesreport', 'peerreview'),
                            'peerreview-viewlet-gradereport-collapsed',
                            false,
                            true
                        );
                        $output .= $this->box_start('generalbox gradesreport');
                        $output .= $this->container(groups_print_activity_menu(
                            $peerreview->cm,
                            $this->page->url,
                            true
                        ), 'groupwidget');
                        $output .= $this->initials_bars($peerreview, $baseurl);
                        $output .= $this->render($pagingbar);
                        $output .= $this->render(new peerreview_grading_report($data, $reportopts));
                        $output .= $this->render($pagingbar);
                        $output .= $this->perpage_selector($perpage);
                        $output .= $this->box_end();
                        $output .= print_collapsible_region_end(true);
                    }
                }
                if (has_capability('mod/peerreview:overridegrades', $peerreview->context)) {
                    $output .= print_collapsible_region_start(
                        '',
                        'peerreview-viewlet-cleargrades',
                        get_string('toolbox', 'peerreview'),
                        'peerreview-viewlet-cleargrades-collapsed',
                        true,
                        true
                    );
                    $output .= $this->box_start('generalbox toolbox');

                    // Clear aggregated grades.
                    $url = new moodle_url($peerreview->toolbox_url('clearaggregatedgrades'));
                    $btn = new single_button($url, get_string('clearaggregatedgrades', 'peerreview'), 'post');
                    $btn->add_confirm_action(get_string('clearaggregatedgradesconfirm', 'peerreview'));
                    $output .= $this->container_start('toolboxaction');
                    $output .= $this->render($btn);
                    $output .= $this->help_icon('clearaggregatedgrades', 'peerreview');
                    $output .= $this->container_end();
                    // Clear assessments.
                    $url = new moodle_url($peerreview->toolbox_url('clearassessments'));
                    $btn = new single_button($url, get_string('clearassessments', 'peerreview'), 'post');
                    $btn->add_confirm_action(get_string('clearassessmentsconfirm', 'peerreview'));
                    $output .= $this->container_start('toolboxaction');
                    $output .= $this->render($btn);
                    $output .= $this->help_icon('clearassessments', 'peerreview');

                    $output .= $this->output->pix_icon('i/risk_dataloss', get_string('riskdatalossshort', 'admin'));
                    $output .= $this->container_end();

                    $output .= $this->box_end();
                    $output .= print_collapsible_region_end(true);
                }
                if (has_capability('mod/peerreview:submit', $this->page->context)) {
                    $output .= print_collapsible_region_start(
                        '',
                        'peerreview-viewlet-ownsubmission',
                        get_string('yoursubmission', 'peerreview'),
                        'peerreview-viewlet-ownsubmission-collapsed',
                        false,
                        true
                    );
                    $output .= $this->box_start('generalbox ownsubmission');
                    if ($submission = $peerreview->get_submission_by_author($USER->id)) {
                        $output .= $this->render($peerreview->prepare_submission_summary($submission, true));
                    } else {
                        $output .= $this->container(get_string('noyoursubmission', 'peerreview'));
                    }
                    $output .= $this->box_end();
                    $output .= print_collapsible_region_end(true);
                }
                if ($assessments = $peerreview->get_assessments_by_reviewer($USER->id)) {
                    $output .= print_collapsible_region_start(
                        '',
                        'peerreview-viewlet-assignedassessments',
                        get_string('assignedassessments', 'peerreview'),
                        'peerreview-viewlet-assignedassessments-collapsed',
                        false,
                        true
                    );
                    $shownames = has_capability('mod/peerreview:viewauthornames', $this->page->context);
                    foreach ($assessments as $assessment) {
                        $submission = new stdclass();
                        $submission->id = $assessment->submissionid;
                        $submission->title = $assessment->submissiontitle;
                        $submission->timecreated = $assessment->submissioncreated;
                        $submission->timemodified = $assessment->submissionmodified;
                        $userpicturefields = explode(',', implode(',', \core_user\fields::get_picture_fields()));
                        foreach ($userpicturefields as $userpicturefield) {
                            $prefixedusernamefield = 'author' . $userpicturefield;
                            $submission->$prefixedusernamefield = $assessment->$prefixedusernamefield;
                        }

                        if (is_null($assessment->grade)) {
                            $class = ' notgraded';
                            $submission->status = 'notgraded';
                            $buttontext = get_string('assess', 'peerreview');
                        } else {
                            $class = ' graded';
                            $submission->status = 'graded';
                            $buttontext = get_string('reassess', 'peerreview');
                        }
                        $output .= $this->box_start('generalbox assessment-summary' . $class);
                        $output .= $this->render($peerreview->prepare_submission_summary($submission, $shownames));
                        $output .= $this->box_end();
                    }
                    $output .= print_collapsible_region_end(true);
                }
                break;
            case peerreview::PHASE_CLOSED:
                if (trim($peerreview->conclusion)) {
                    $conclusion = file_rewrite_pluginfile_urls(
                        $peerreview->conclusion,
                        'pluginfile.php',
                        $peerreview->context->id,
                        'mod_peerreview',
                        'conclusion',
                        null,
                        peerreview::instruction_editors_options($peerreview->context)
                    );
                    $output .= print_collapsible_region_start(
                        '',
                        'peerreview-viewlet-conclusion',
                        get_string('conclusion', 'peerreview'),
                        'peerreview-viewlet-conclusion-collapsed',
                        false,
                        true
                    );
                    $output .= $this->box(
                        format_text($conclusion, $peerreview->conclusionformat, ['overflowdiv' => true]),
                        ['generalbox', 'conclusion']
                    );
                    $output .= print_collapsible_region_end(true);
                }
                $finalgrades = $peerreview->get_gradebook_grades($USER->id);
                if (!empty($finalgrades)) {
                    $output .= print_collapsible_region_start(
                        '',
                        'peerreview-viewlet-yourgrades',
                        get_string('yourgrades', 'peerreview'),
                        'peerreview-viewlet-yourgrades-collapsed',
                        false,
                        true
                    );
                    $output .= $this->box_start('generalbox grades-yourgrades');
                    $output .= $this->render($finalgrades);
                    $output .= $this->box_end();
                    $output .= print_collapsible_region_end(true);
                }
                if (has_capability('mod/peerreview:viewallassessments', $this->page->context)) {
                    $perpage = get_user_preferences('peerreview_perpage', 10);
                    $groupid = groups_get_activity_group($peerreview->cm, true);
                    $data = $peerreview->prepare_grading_report_data($USER->id, $groupid, $page, $perpage, $sortby, $sorthow);
                    if ($data) {
                        $showauthornames = has_capability('mod/peerreview:viewauthornames', $peerreview->context);
                        $showreviewernames = has_capability('mod/peerreview:viewreviewernames', $peerreview->context);

                        // Prepare paging bar.
                        $baseurl = new moodle_url($this->page->url, ['sortby' => $sortby, 'sorthow' => $sorthow]);
                        $pagingbar = new paging_bar($data->totalcount, $page, $perpage, $baseurl, 'page');

                        // Grading report display options.
                        $reportopts = new stdclass();
                        $reportopts->showauthornames = $showauthornames;
                        $reportopts->showreviewernames = $showreviewernames;
                        $reportopts->sortby = $sortby;
                        $reportopts->sorthow = $sorthow;
                        $reportopts->showsubmissiongrade = true;
                        $reportopts->showgradinggrade = true;
                        $reportopts->peerreviewphase = $peerreview->phase;

                        $output .= print_collapsible_region_start(
                            '',
                            'peerreview-viewlet-gradereport',
                            get_string('gradesreport', 'peerreview'),
                            'peerreview-viewlet-gradereport-collapsed',
                            false,
                            true
                        );
                        $output .= $this->box_start('generalbox gradesreport');
                        $output .= $this->container(groups_print_activity_menu(
                            $peerreview->cm,
                            $this->page->url,
                            true
                        ), 'groupwidget');
                        $output .= $this->initials_bars($peerreview, $baseurl);
                        $output .= $this->render($pagingbar);
                        $output .= $this->render(new peerreview_grading_report($data, $reportopts));
                        $output .= $this->render($pagingbar);
                        $output .= $this->perpage_selector($perpage);
                        $output .= $this->box_end();
                        $output .= print_collapsible_region_end(true);
                    }
                }
                if (has_capability('mod/peerreview:submit', $this->page->context)) {
                    $output .= print_collapsible_region_start(
                        '',
                        'peerreview-viewlet-ownsubmission',
                        get_string('yoursubmissionwithassessments', 'peerreview'),
                        'peerreview-viewlet-ownsubmission-collapsed',
                        false,
                        true
                    );
                    $output .= $this->box_start('generalbox ownsubmission');
                    if ($submission = $peerreview->get_submission_by_author($USER->id)) {
                        $output .= $this->render($peerreview->prepare_submission_summary($submission, true));
                    } else {
                        $output .= $this->container(get_string('noyoursubmission', 'peerreview'));
                    }
                    $output .= $this->box_end();

                    if (!empty($submission->gradeoverby) && strlen(trim($submission->feedbackauthor)) > 0) {
                        $output .= $this->render(new peerreview_feedback_author($submission));
                    }

                    $output .= print_collapsible_region_end(true);
                }
                if (has_capability('mod/peerreview:viewpublishedsubmissions', $peerreview->context)) {
                    $shownames = has_capability('mod/peerreview:viewauthorpublished', $peerreview->context);
                    if ($submissions = $peerreview->get_published_submissions()) {
                        $output .= print_collapsible_region_start(
                            '',
                            'peerreview-viewlet-publicsubmissions',
                            get_string('publishedsubmissions', 'peerreview'),
                            'peerreview-viewlet-publicsubmissions-collapsed',
                            false,
                            true
                        );
                        foreach ($submissions as $submission) {
                            $output .= $this->box_start('generalbox submission-summary');
                            $output .= $this->render($peerreview->prepare_submission_summary($submission, $shownames));
                            $output .= $this->box_end();
                        }
                        $output .= print_collapsible_region_end();
                    }
                }
                if ($assessments = $peerreview->get_assessments_by_reviewer($USER->id)) {
                    $output .= print_collapsible_region_start(
                        '',
                        'peerreview-viewlet-assignedassessments',
                        get_string('assignedassessments', 'peerreview'),
                        'peerreview-viewlet-assignedassessments-collapsed',
                        false,
                        true
                    );
                    $shownames = has_capability('mod/peerreview:viewauthornames', $this->page->context);
                    foreach ($assessments as $assessment) {
                        $submission = new stdclass();
                        $submission->id = $assessment->submissionid;
                        $submission->title = $assessment->submissiontitle;
                        $submission->timecreated = $assessment->submissioncreated;
                        $submission->timemodified = $assessment->submissionmodified;
                        $userpicturefields = explode(',', implode(',', \core_user\fields::get_picture_fields()));
                        foreach ($userpicturefields as $userpicturefield) {
                            $prefixedusernamefield = 'author' . $userpicturefield;
                            $submission->$prefixedusernamefield = $assessment->$prefixedusernamefield;
                        }

                        if (is_null($assessment->grade)) {
                            $class = ' notgraded';
                            $submission->status = 'notgraded';
                            $buttontext = get_string('assess', 'peerreview');
                        } else {
                            $class = ' graded';
                            $submission->status = 'graded';
                            $buttontext = get_string('reassess', 'peerreview');
                        }
                        $output .= $this->box_start('generalbox assessment-summary' . $class);
                        $output .= $this->render($peerreview->prepare_submission_summary($submission, $shownames));
                        $output .= $this->box_end();

                        if (!empty($assessment->feedbackreviewer) && strlen(trim($assessment->feedbackreviewer)) > 0) {
                            $output .= $this->render(new peerreview_feedback_reviewer($assessment));
                        }
                    }
                    $output .= print_collapsible_region_end(true);
                }
                break;
            default:
        }

        return $output;
    }

    /* EDIT: function to render the submission card on view.php */

    // Method to render a group of submissions as Bootstrap cards
    private function render_submissions_cards($submissions, $cmid)
    {
        global $DB, $USER; // Ensure $DB and $USER are available

        // Arrays to hold reviewed and non-reviewed submissions
        $reviewed_submissions = [];
        $non_reviewed_submissions = [];

        // Process each submission to get the necessary data
        foreach ($submissions as $submission) {
            // Get the data from table peerreview_assessments for the current $submission
            $reviews = $DB->get_records('peerreview_assessments', array('submissionid' => $submission->id));

            // Count the lines and add the number to $submission->totalreviews
            $submission->totalreviews = count($reviews);

            // Check if the current $USER has an entry in the data from $reviews
            $submission->userhasreviewed = false;
            foreach ($reviews as $review) {
                if ($review->reviewerid == $USER->id && $review->grade !== null) {
                    $submission->userhasreviewed = true;
                    break;
                }
            }


            // Add the submission to the appropriate array
            if ($submission->userhasreviewed) {
                $reviewed_submissions[] = $submission;
            } else {
                $non_reviewed_submissions[] = $submission;
            }
        }

        // Sort reviewed submissions by totalreviews (least first)
        usort($reviewed_submissions, function ($a, $b) {
            return $a->totalreviews - $b->totalreviews;
        });

        // Sort non-reviewed submissions by totalreviews (least first)
        usort($non_reviewed_submissions, function ($a, $b) {
            return $a->totalreviews - $b->totalreviews;
        });

        // Merge reviewed submissions first, followed by non-reviewed submissions
        $sorted_submissions = array_merge($reviewed_submissions, $non_reviewed_submissions);

        // Start generating the output
        $output = html_writer::start_tag('div', array('class' => 'row'));

        // Render each submission card after sorting
        foreach ($sorted_submissions as $submission) {
            $output .= html_writer::start_tag('div', array('class' => 'col-lg-4 col-md-6 col-12 mb-4'));
            $output .= $this->render_submission_card($submission, $cmid);
            $output .= html_writer::end_tag('div'); // Close col-md-4 div
        }

        $output .= html_writer::end_tag('div'); // Close row div

        return $output;
    }



    // Method to render a single submission as a Bootstrap card
    private function render_submission_card($submission, $cmid)
    {



        global $DB, $CFG, $USER;
        $context = context_module::instance($cmid);

        // Create the URL for the review link
        $review_url = new moodle_url('/mod/peerreview/submission.php', array('cmid' => $cmid, 'id' => $submission->id));


        // Looks for thumbnail images
        
        $sql = "SELECT * FROM {files}
        WHERE filearea = :filearea
        AND contextid = :contextid
        AND component = :component
        AND mimetype LIKE :mimetype
        AND filename LIKE :filename
        AND userid = :userid
        LIMIT 1";

        $params = array(
            'filearea' => 'submission_attachment',
            'contextid' => $context->id,
            'component' => 'mod_peerreview',
            'mimetype' => 'image/%',
            'filename' => 'thumbnail.%',
            'userid' => $submission->authorid
        );

        $file = $DB->get_record_sql($sql, $params);


        // die(var_dump($files));

        // Start the anchor tag to make the entire card clickable
        $output = html_writer::start_tag('a', array('href' => $review_url, 'class' => 'card', 'style' => 'text-decoration: none; color: inherit;'));

        // Start the card-body div
        // $output .= html_writer::start_tag('div', array('class' => 'card-body', 'style' => 'position: relative; overflow: hidden; width: ' . $slice_width . 'px; height: ' . $slice_height . 'px;'));

        $slice_width = 348; // Width of each slice
        $slice_height = 350; // Height of each slice
        $slices_per_row = 4; // Number of slices per row in the large image
        $output .= html_writer::start_tag('div', array('class' => 'card-body', 'style' => 'position: relative; overflow: hidden; height: ' . $slice_height . 'px;'));

        if (empty($file)) {
            // Large image dimensions and source
            $large_image_url = '/mod/peerreview/pix/projectsbg.jpeg'; // Replace with the actual path to your large image

            // Calculate the position of the slice based on the submission ID
            $slice_index = ($submission->id - 1) % ($slices_per_row * $slices_per_row); // Assuming 100 slices in the large image (10x10)
            $x_offset = ($slice_index % $slices_per_row) * $slice_width;
            $y_offset = floor($slice_index / $slices_per_row) * $slice_height;

            // Array of saturation values in the specified order
            $saturation_values = [15, 200, 50, 20, 100, 140, 15, 220];
            // Determine the saturation value based on the submission ID
            $saturation = $saturation_values[($submission->id - 1) % count($saturation_values)];

            // Add the large image inside the card-body, positioned to show the correct slice
            $output .= html_writer::empty_tag('img', array(
                'src' => $large_image_url,
                'alt' => 'Image',
                'style' => 'position: absolute; top: -' . $y_offset . 'px; left: -' . $x_offset . 'px; width: ' . ($slices_per_row * $slice_width) . 'px; height: auto; filter: saturate(' . $saturation . '%);'
            ));
        } else {

            // Generate the URL for the file
            $file_url = moodle_url::make_pluginfile_url(
                $file->contextid,
                $file->component,
                $file->filearea,
                $file->itemid,
                $file->filepath,
                $file->filename
            );

            // Create a link to the file

            // Add the large image inside the card-body, positioned to show the correct slice
            $output .= html_writer::empty_tag('img', array(
                'src' => $file_url,
                'alt' => 'Image',
                'style' => ' width: 100%; height: 100%; object-fit: cover;'
            ));
        }


        // Check if the user has reviewed the submission and add a badge if true
        if ($submission->userhasreviewed) {
            $badge = html_writer::tag('span', get_string('reviewed', 'mod_peerreview'), array('class' => 'badge badge-success', 'style' => 'position: absolute; top: 10px; right: 10px; padding: 8px; font-size: 15px;'));
            $output .= $badge;
        }

        // Close the card-body div
        $output .= html_writer::end_tag('div');

        // Add the card-header with the title
        $output .= html_writer::tag('div', format_string($submission->title), array('class' => 'card-header'));

        // Close the anchor tag
        $output .= html_writer::end_tag('a');

        return $output;
    }



    /**
     * Renders a list of files attached to the description
     *
     * If format==html, then format a html string. If format==text, then format a text-only string.
     * Otherwise, returns html for non-images and html to display the image inline.
     *
     * @param int $submissionid submission identifier
     * @param string $format the format of the returned string - html|text
     * @return string formatted text to be echoed
     */
    protected function helper_description_attachments($submissionid, $format = 'html')
    {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $fs     = get_file_storage();
        $ctx    = $this->page->context;
        $files  = $fs->get_area_files($ctx->id, 'mod_peerreview', 'description_content', $submissionid);

        $outputimgs     = '';   // images to be displayed inline
        $outputfiles    = '';   // list of attachment files

        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }

            $filepath   = $file->get_filepath();
            $filename   = $file->get_filename();
            $fileurl    = moodle_url::make_pluginfile_url(
                $ctx->id,
                'mod_peerreview',
                'description_content',
                $submissionid,
                $filepath,
                $filename,
                true
            );
            $embedurl   = moodle_url::make_pluginfile_url(
                $ctx->id,
                'mod_peerreview',
                'description_content',
                $submissionid,
                $filepath,
                $filename,
                false
            );
            $embedurl   = new moodle_url($embedurl, array('preview' => 'bigthumb'));
            $type       = $file->get_mimetype();
            $image      = $this->output->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon'));

            $linkhtml   = html_writer::link($fileurl, $image . substr($filepath, 1) . $filename);
            $linktxt    = "$filename [$fileurl]";

            if ($format == 'html') {
                if (file_mimetype_in_typegroup($type, 'web_image')) {
                    $preview     = html_writer::empty_tag('img', array('src' => $embedurl, 'alt' => '', 'class' => 'preview'));
                    $preview     = html_writer::tag('a', $preview, array('href' => $fileurl));
                    $outputimgs .= $this->output->container($preview);
                } else {
                    $outputfiles .= html_writer::tag('li', $linkhtml, array('class' => $type));
                }
            } else if ($format == 'text') {
                $outputfiles .= $linktxt . PHP_EOL;
            }

            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');
                $outputfiles .= plagiarism_get_links(array(
                    'userid' => $file->get_userid(),
                    'file' => $file,
                    'cmid' => $this->page->cm->id,
                    'course' => $this->page->course->id
                ));
            }
        }

        if ($format == 'html') {
            if ($outputimgs) {
                $outputimgs = $this->output->container($outputimgs, 'images');
            }

            if ($outputfiles) {
                $outputfiles = html_writer::tag('ul', $outputfiles, array('class' => 'files'));
            }

            return $this->output->container($outputimgs . $outputfiles, 'attachments');
        } else {
            return $outputfiles;
        }
    }
}
