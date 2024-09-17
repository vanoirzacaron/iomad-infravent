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

namespace mod_peerreview;

/**
 * Genarator tests class for mod_peerreview.
 *
 * @package    mod_peerreview
 * @category   test
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generator_test extends \advanced_testcase {

    public function test_create_instance() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('peerreview', array('course' => $course->id)));
        $peerreview = $this->getDataGenerator()->create_module('peerreview', array('course' => $course));
        $records = $DB->get_records('peerreview', array('course' => $course->id), 'id');
        $this->assertEquals(1, count($records));
        $this->assertTrue(array_key_exists($peerreview->id, $records));

        $params = array('course' => $course->id, 'name' => 'Another peerreview');
        $peerreview = $this->getDataGenerator()->create_module('peerreview', $params);
        $records = $DB->get_records('peerreview', array('course' => $course->id), 'id');
        $this->assertEquals(2, count($records));
        $this->assertEquals('Another peerreview', $records[$peerreview->id]->name);
    }

    public function test_create_submission() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', array('course' => $course));
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $peerreviewgenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerreview');

        $id = $peerreviewgenerator->create_submission($peerreview->id, $user->id, array(
            'title' => 'My custom title',
        ));

        $submissions = $DB->get_records('peerreview_submissions', array('peerreviewid' => $peerreview->id));
        $this->assertEquals(1, count($submissions));
        $this->assertTrue(isset($submissions[$id]));
        $this->assertEquals($submissions[$id]->authorid, $user->id);
        $this->assertSame('My custom title', $submissions[$id]->title);
    }

    public function test_create_assessment() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $peerreview = $this->getDataGenerator()->create_module('peerreview', array('course' => $course));
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $peerreviewgenerator = $this->getDataGenerator()->get_plugin_generator('mod_peerreview');

        $submissionid1 = $peerreviewgenerator->create_submission($peerreview->id, $user1->id);
        $submissionid2 = $peerreviewgenerator->create_submission($peerreview->id, $user2->id);

        $assessmentid1 = $peerreviewgenerator->create_assessment($submissionid1, $user2->id, array(
            'weight' => 3,
            'grade' => 95.00000,
        ));
        $assessmentid2 = $peerreviewgenerator->create_assessment($submissionid2, $user1->id);

        $assessments = $DB->get_records('peerreview_assessments');
        $this->assertTrue(isset($assessments[$assessmentid1]));
        $this->assertTrue(isset($assessments[$assessmentid2]));
        $this->assertEquals(3, $assessments[$assessmentid1]->weight);
        $this->assertEquals(95.00000, $assessments[$assessmentid1]->grade);
        $this->assertEquals(1, $assessments[$assessmentid2]->weight);
        $this->assertNull($assessments[$assessmentid2]->grade);
    }
}
