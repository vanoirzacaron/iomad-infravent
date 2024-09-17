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
 * Provides the {@see peerreviewform_comments\privacy\provider_test} class.
 *
 * @package     peerreviewform_comments
 * @category    test
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace peerreviewform_comments\privacy;

defined('MOODLE_INTERNAL') || die();

global $CFG;

use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;

/**
 * Unit tests for the privacy API implementation.
 *
 * @copyright 2018 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider_test extends provider_testcase {

    /** @var \testing_data_generator data generator. */
    protected $generator;

    /** @var \mod_peerreview_generator peerreview generator. */
    protected $peerreviewgenerator;

    /** @var \stdClass course data. */
    protected $course1;

    /** @var \stdClass student data. */
    protected $student1;

    /** @var \stdClass student data. */
    protected $student2;

    /** @var \stdClass first peerreview in course1 */
    protected $peerreview11;

    /** @var int ID of the submission in peerreview11 by student1 */
    protected $submission111;

    /** @var int ID of the assessment of submission111 by student2 */
    protected $assessment1112;

    /** @var bool|int true or new id */
    protected $dim1;

    /** @var bool|int true or new id */
    protected $dim2;

    /**
     * Test {@link peerreviewform_comments\privacy\provider::export_assessment_form()} implementation.
     */
    public function test_export_assessment_form() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->generator = $this->getDataGenerator();
        $this->peerreviewgenerator = $this->generator->get_plugin_generator('mod_peerreview');

        $this->course1 = $this->generator->create_course();

        $this->peerreview11 = $this->generator->create_module('peerreview', [
            'course' => $this->course1,
            'name' => 'peerreview11',
        ]);
        $DB->set_field('peerreview', 'phase', 100, ['id' => $this->peerreview11->id]);

        $this->dim1 = $DB->insert_record('peerreviewform_comments', [
            'peerreviewid' => $this->peerreview11->id,
            'sort' => 1,
            'description' => 'Aspect 1 description',
            'descriptionformat' => FORMAT_MARKDOWN,
        ]);

        $this->dim2 = $DB->insert_record('peerreviewform_comments', [
            'peerreviewid' => $this->peerreview11->id,
            'sort' => 2,
            'description' => 'Aspect 2 description',
            'descriptionformat' => FORMAT_MARKDOWN,
        ]);

        $this->student1 = $this->generator->create_user();
        $this->student2 = $this->generator->create_user();

        $this->submission111 = $this->peerreviewgenerator->create_submission($this->peerreview11->id, $this->student1->id);

        $this->assessment1112 = $this->peerreviewgenerator->create_assessment($this->submission111, $this->student2->id, [
            'grade' => 92,
        ]);

        $DB->insert_record('peerreview_grades', [
            'assessmentid' => $this->assessment1112,
            'strategy' => 'comments',
            'dimensionid' => $this->dim1,
            'grade' => 100,
            'peercomment' => 'Not awesome',
            'peercommentformat' => FORMAT_PLAIN,
        ]);

        $DB->insert_record('peerreview_grades', [
            'assessmentid' => $this->assessment1112,
            'strategy' => 'comments',
            'dimensionid' => $this->dim2,
            'grade' => 100,
            'peercomment' => 'All good',
            'peercommentformat' => FORMAT_PLAIN,
        ]);

        $contextlist = new \core_privacy\local\request\approved_contextlist($this->student2, 'mod_peerreview', [
            \context_module::instance($this->peerreview11->cmid)->id,
        ]);

        \mod_peerreview\privacy\provider::export_user_data($contextlist);

        $writer = writer::with_context(\context_module::instance($this->peerreview11->cmid));

        $form = $writer->get_data([
            get_string('myassessments', 'mod_peerreview'),
            $this->assessment1112,
            get_string('assessmentform', 'mod_peerreview'),
            get_string('pluginname', 'peerreviewform_comments'),
        ]);

        $this->assertEquals('Aspect 1 description', $form->aspects[0]->description);
        $this->assertEquals('All good', $form->aspects[1]->peercomment);
    }
}
