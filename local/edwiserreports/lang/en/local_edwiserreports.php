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
 * Plugin strings are defined here.
 *
 * @package     local_edwiserreports
 * @category    string
 * @copyright   2019 wisdmlabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use function Complex\ln;

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Site Reports';
$string['reportsdashboard'] = 'Site Reports';
$string['reportsandanalytics'] = 'Reports & Analytics';
$string['all'] = 'All';
$string['refresh'] = 'Refresh';
$string['noaccess'] = 'Sorry. You don\'t have rights to access this page.';
$string['showdatafor'] = 'SHOW DATA FOR';
$string['showingdatafor'] = 'Showing data for';
$string['dashboard'] = 'Site Reports Dashboard';
$string['permissionwarning'] = 'You have allowed following users to see this block which is not recommended. Please hide this block from those users. Once you hide this block, it will not appear again.';
$string['showentries'] = 'Show Entries';
$string['viewdetails'] = 'View details';
$string['invalidreport'] = 'Invalid report';
$string['reportnotexist'] = 'The report does not exist';
$string['noaccess'] = "You do not have access. Either you are not enrolled in any program or access to All Groups is prevented, and you are not part of any group.";
$string['programs'] = "Programs";

// Filter strings.
$string['cohort'] = 'Cohort';
$string['group'] = 'Group';
$string['user'] = 'User';
$string['search'] = 'Search';
$string['courseandcategories'] = 'Program & Categories';
$string['enrollment'] = 'Enrollment';
$string['show'] = 'Show';
$string['section'] = 'Section';
$string['activity'] = 'Activity';
$string['inactive'] = 'Inactive';
$string['certificate'] = 'Certificate';

/* Blocks Name */
$string['realtimeusers'] = 'Real Time Users';
$string['activeusersheader'] = 'Site Overview Status';
$string['courseprogress'] = 'Program Progress';
$string['courseprogressheader'] = 'Program Progress';
$string['studentengagementheader'] = 'Participants Engagement';
$string['gradeheader'] = 'Grades';
$string['learnerheader'] = 'Learner Block';
$string['courseengagement'] = 'Program Engagement';
$string['activecoursesheader'] = 'Popular Programs';
$string['certificatestats'] = 'Certificates Stats';
$string['certificatestatsheader'] = 'Certificates Stats';
$string['certificatesheader'] = 'Certificates Stats';
$string['accessinfo'] = 'Site Access Information';
$string['siteaccessheader'] = 'Site Access Information';
$string['inactiveusers'] = 'Inactive Users List';
$string['inactiveusersheader'] = 'Inactive Users List';
$string['liveusersheader'] = 'Live Users Block';
$string['todaysactivityheader'] = 'Daily Activities';
$string['overallengagementheader'] = 'Overall Engagement in Programs';
$string['inactiveusersexportheader'] = 'Inactive Users Report';
$string['inactiveusersblockexportheader'] = 'Inactive Users Report';
$string['date'] = 'Date';
$string['time'] = 'Time';
$string['venue'] = 'Venue';
$string['signups'] = 'Signups';
$string['attendees'] = 'Attendees';
$string['name'] = 'Name';
$string['course'] = 'Program';
$string['issued'] = 'Issued';
$string['notissued'] = 'Not Issued';
$string['nocertificates'] = 'There is no certificate created';
$string['nocertificatesawarded'] = 'No certificates are awarded';
$string['unselectall'] = 'Unselect All';
$string['selectall'] = 'Select All';
$string['activity'] = 'Activity';
$string['cohorts'] = 'Cohorts';
$string['nographdata'] = 'No data';

// Breakdown the tooltip string to display in 2 lines.
$string['cpblocktooltip1'] = '{$a->per} program completed';
$string['cpblocktooltip2'] = 'by {$a->val} users';
$string['fullname'] = 'Full Name';
$string['onlinesince'] = 'Online Since';
$string['status'] = 'Status';
$string['todayslogin'] = 'Login';
$string['learner'] = 'Learner';
$string['learners'] = 'Participants';
$string['teachers'] = 'Teachers';
$string['eventtoday'] = 'Events of the day';
$string['value'] = 'Value';
$string['count'] = 'Count';
$string['enrollments'] = 'Enrollments';
$string['activitycompletion'] = 'Activity Completions';
$string['coursecompletion'] = 'Program Completion';
$string['newregistration'] = 'New Registrations';
$string['timespent'] = 'Time Spent';
$string['sessions'] = 'Sessions';
$string['totalusers'] = 'Total Users';
$string['sitevisits'] = 'Site Visits Per Hour';
$string['lastupdate'] = 'Last updated <span class="minute">0</span> min ago';
$string['loading'] = 'Loading...';
$string['last7days'] = 'Last 7 days';
$string['lastweek'] = 'Last Week';
$string['lastmonth'] = 'Last Month';
$string['lastyear'] = 'Last Year';
$string['customdate'] = 'Custom Date';
$string['rank'] = 'Rank';
$string['enrolments'] = 'Enrolments';
$string['visits'] = 'Visits';
$string['totalvisits'] = 'Total visits';
$string['averagevisits'] = 'Average visits';
$string['completions'] = 'Completions';
$string['selectdate'] = 'Select Date';
$string['never'] = 'Never';
$string['before1month'] = 'Before 1 Month';
$string['before3month'] = 'Before 3 Month';
$string['before6month'] = 'Before 6 Month';
$string['recipient'] = 'Recipient';
$string['duplicateemail'] = 'Duplicate email';
$string['invalidemail'] = 'Invalid email';
$string['subject'] = 'Subject';
$string['message'] = 'Message';
$string['reset'] = 'Reset';
$string['send'] = 'Send Now';
$string['filterdata'] = 'Send filtered data';
$string['editblocksetting'] = 'Edit Block Setting';
$string['editblockcapabilities'] = 'Edit Block Capabilities';
$string['editreportcapabilities'] = 'Edit Report Capabilities';
$string['hour'] = 'hour';
$string['hours'] = 'hours';
$string['minute'] = 'minute';
$string['minutes'] = 'minutes';
$string['second'] = 'second';
$string['seconds'] = 'seconds';
$string['hourshort'] = 'h';
$string['minuteshort'] = 'min';
$string['secondshort'] = 's';
$string['zerorecords'] = 'No matching records found';
$string['nodata'] = 'No data';
$string['tableinfo'] = 'Showing _START_ to _END_ of _TOTAL_ entries';
$string['infoempty'] = 'Showing 0 to 0 of 0 entries';
$string['allgroups'] = 'All Groups';
$string['nogroups'] = 'No Groups';
$string['pleaseselectcourse'] = 'Please select program to see groups';
$string['lastaccess'] = 'Last Access';
$string['progress'] = 'Progress';
$string['avgprogress'] = 'Avg Progress';
$string['notyet'] = 'Not Yet';

/* Block help tooltips */
$string['activeusersblocktitlehelp'] = 'An overview of daily activity on your site. Essential for Managers to check overall activity in the site.';
$string['activeusersblockhelp'] = 'This block will show graph of active users over the period with program enrolment and program completion.';
$string['courseprogressblockhelp'] = 'This block will show the pie chart of a program with percantage.';
$string['activecoursesblockhelp'] = 'This block will show the most active programs based on the visits enrolment and completions.';
$string['studentengagementblockhelp'] = 'Participants engagement reports displays timespent by participantss on sites, programs and the total visits on the program.';
$string['gradeblockhelp'] = 'This block shows grades.';
$string['learnerblockhelp'] = 'Track your program progress and timespent on site.';
$string['certificatestatsblockhelp'] = 'This block will show all created custom certificates and how many enrolled users awarded with this certificates.';
$string['realtimeusersblockhelp'] = 'This block will show all logged in users in this site.';
$string['accessinfoblockhelp'] = 'This block will show the average usage of the site in a week.';
$string['todaysactivityblockhelp'] = 'This block will show the daily activities performed in this site.';
$string['inactiveusersblockhelp'] = 'This block will show list of users inactive in this site.';
$string['inactiveusersexporthelp'] = 'This report will show inactivity of users in the website';
$string['none'] = 'None';

/* Block Course Progress */
$string['averagecourseprogress'] = 'Average Program Progress';
$string['nocourses'] = 'No Programs Found';
$string['activity'] = 'Activity';
$string['activities'] = 'Activities';
$string['student'] = 'Participants';
$string['students'] = 'Participantss';

/* Block Learning Program */
$string['nolearningprograms'] = 'No Learning Programs Found';

/* Block Site access information */
$string['siteaccessinformationtask'] = 'Calculate site access information';
$string['siteaccessrecalculate'] = 'Plugin is just upgraded. Please <a target="_blank" href="{$a}">run</a> <strong>Calculate site access information</strong> task to see the result.';
$string['siteaccessinformationcronwarning'] = '<strong>Calculate site access information</strong> task should run every 24 hours. Please <a target="_blank" href="{$a}">run now</a> to see accurate result.';
$string['busiest'] = 'Most Busy';
$string['quietest'] = 'Least Busy';

/* Block Inactive Users */
$string['siteaccess'] = 'Site Access:';
$string['before1month'] = 'Before 1 Month';
$string['before3month'] = 'Before 3 Month';
$string['before6month'] = 'Before 6 Month';
$string['noinactiveusers'] = 'No inactive users are available';

/* Site Overview block */
$string['activeuserstask'] = 'Calculate active users block data';
$string['averageactiveusers'] = 'Average active users';
$string['totalactiveusers'] = 'Total active users';
$string['noactiveusers'] = 'There are no active users';
$string['nousers'] = 'There are no users';
$string['courseenrolment'] = 'Program Enrolment';
$string['coursecompletionrate'] = 'Program Completion Rate';
$string['totalcourseenrolments'] = 'Total program enrolments';
$string['totalcoursecompletions'] = 'Total program completions';
$string['clickondatapoint'] = 'Click on data points for more info';

/* Student Engagement block */
$string['alllearnersummary'] = 'All Learner Summary';
$string['studentengagementexportheader'] = 'All Learner Summary';
$string['visitsonlms'] = 'Visits On Site';
$string['timespentonlms'] = 'Time Spent On Site';
$string['timespentonsite'] = 'Time Spent On Site';
$string['timespentoncourse'] = 'Time Spent On Program';
$string['assignmentsubmitted'] = 'Assignments submitted';
$string['visitsoncourse'] = 'Visits on program';
$string['studentengagementtask'] = 'Participants Engagement Data';
$string['searchuser'] = 'by user';
$string['emptytable'] = 'No records to show';
$string['courseactivitystatus'] = 'Assignment submitted, activities completed';
$string['courseactivitystatus-submissions'] = 'Assignment submitted';
$string['courseactivitystatus-completions'] = 'Activities completed';
$string['lastaccesson'] = 'Last Access on';
$string['enrolledcourses'] = 'Enrolled programs';
$string['inprogresscourse'] = 'In-progress programs';
$string['completecourse'] = 'Completed programs';
$string['completionprogress'] = 'Completion Progress';
$string['completedassign'] = 'Completed assignments';
$string['completedquiz'] = 'Completed quizzes';
$string['completedscorm'] = 'Completed scorms';

/* Learner block */
$string['learnercourseprogressreport'] = 'Learner Program Progress Report';
$string['learnercourseprogress'] = 'Learner Program Progress';
$string['learnercourseprogressexportheader'] = 'Learner Program Progress';
$string['learnercourseprogressheader'] = 'Learner Program Progress';
$string['learnerreportexportheader'] = 'Learner Report';
$string['learnerreportheader'] = 'Learner Report';
$string['searchcourse'] = 'by program';
$string['own'] = 'Own';

/* Active Users Page */
$string['noofactiveusers'] = 'No. of active users';
$string['noofenrolledusers'] = 'No. of enrollments';
$string['noofcompletedusers'] = 'No. of completions';
$string['email'] = 'Email';
$string['emailscheduled'] = 'Email Scheduled';
$string['usersnotavailable'] = 'No Users are available for this day';
$string['activeusersmodaltitle'] = 'Users active on {$a->date}';
$string['enrolmentsmodaltitle'] = 'Users enrolled into programs on {$a->date}';
$string['completionsmodaltitle'] = 'Users who have completed a program on {$a->date}';
$string['recordnotfound'] = 'Record not found';
$string['jsondecodefailed'] = 'Json decode failed';
$string['emaildataisnotasarray'] = 'Email data is not an array';
$string['sceduledemailnotexist'] = 'Schedule email not exist';
$string['searchdate'] = 'by date';
$string['january'] = 'January';
$string['february'] = 'February';
$string['march'] = 'March';
$string['april'] = 'April';
$string['may'] = 'May';
$string['june'] = 'June';
$string['july'] = 'July';
$string['august'] = 'August';
$string['september'] = 'September';
$string['october'] = 'October';
$string['november'] = 'November';
$string['december'] = 'December';

/* Active courses block */
$string['activecoursestask'] = 'Calculate active programs data';

/* Grades block */
$string['allcompletions'] = 'All completions';
$string['gradeblockview'] = 'Grade Block View';
$string['gradeblockeditadvance'] = 'Grade Block Edit';
$string['coursegrades'] = 'Program grades';
$string['studentgrades'] = 'Participants grades';
$string['activitygrades'] = 'Activity grades';
$string['averagegrade'] = 'Average grade';
$string['completedassignments'] = 'Completed assignments';
$string['completedquizzes'] = 'Completed quizzes';
$string['completedscorms'] = 'Completed scorms';
$string['clickonchartformoreinfo'] = 'Click on chart for more info';

/* Course Progress Page */
$string['coursename'] = 'Program Name';
$string['category'] = 'Category';
$string['notstarted'] = 'Not Started';
$string['inprogress'] = 'In Progress';
$string['atleastoneactivitystarted'] = 'At least one Activity started';
$string['totalactivities'] = 'Total activities';
$string['avgprogress'] = 'Avg. progress';
$string['avggrade'] = 'Avg. grade';
$string['highestgrade'] = 'Highest grade';
$string['lowestgrade'] = 'Lowest grade';
$string['totaltimespent'] = 'Total time spent';
$string['avgtimespent'] = 'Avg. time spent';
$string['enrolled'] = 'Enrolled';
$string['completed'] = 'Completed';
$string['activitycompletion'] = 'Activity completion';
$string['inprogress'] = 'In Progress';

/* Certificates Page */
$string['username'] = 'User Name';
$string['useremail'] = 'User Email';
$string['dateofissue'] = 'Date of Issue';
$string['dateofenrol'] = 'Date of Enrolment';
$string['grade'] = 'Grade';
$string['courseprogress'] = 'Program Progress';
$string['notenrolled'] = 'User Not Enrolled';
$string['searchcertificates'] = 'by learner';

/* Site Access Block*/
$string['sun'] = 'SUN';
$string['mon'] = 'MON';
$string['tue'] = 'TUE';
$string['wed'] = 'WED';
$string['thu'] = 'THU';
$string['fri'] = 'FRI';
$string['sat'] = 'SAT';
$string['time00'] = '12:00 AM';
$string['time01'] = '01:00 AM';
$string['time02'] = '02:00 AM';
$string['time03'] = '03:00 AM';
$string['time04'] = '04:00 AM';
$string['time05'] = '05:00 AM';
$string['time06'] = '06:00 AM';
$string['time07'] = '07:00 AM';
$string['time08'] = '08:00 AM';
$string['time09'] = '09:00 AM';
$string['time10'] = '10:00 AM';
$string['time11'] = '11:00 AM';
$string['time12'] = '12:00 PM';
$string['time13'] = '01:00 PM';
$string['time14'] = '02:00 PM';
$string['time15'] = '03:00 PM';
$string['time16'] = '04:00 PM';
$string['time17'] = '05:00 PM';
$string['time18'] = '06:00 PM';
$string['time19'] = '07:00 PM';
$string['time20'] = '08:00 PM';
$string['time21'] = '09:00 PM';
$string['time22'] = '10:00 PM';
$string['time23'] = '11:00 PM';
$string['siteaccessinfo'] = 'Avg users access';

/* Export Strings */
$string['csv'] = 'CSV';
$string['excel'] = 'Excel';
$string['pdf'] = 'PDF';
$string['email'] = 'Email';
$string['exporttocsv'] = 'Export to CSV';
$string['exporttoexcel'] = 'Export to Excel';
$string['exporttopdf'] = 'Export to PDF';
$string['exporttopng'] = 'Export to PNG';
$string['exporttojpeg'] = 'Export to JPEG';
$string['exporttosvg'] = 'Export to SVG';
$string['sendoveremail'] = 'Send over Email';
$string['copy'] = 'Copy';
$string['activeusers_status'] = 'User Active';
$string['enrolments_status'] = 'User Enrolled';
$string['completions_status'] = 'Program Completed';
$string['completedactivity'] = 'Completed Activity';
$string['coursecompletedusers'] = 'Program Completed By Users';
$string['emailsent'] = 'Email has been sent to your mail account';
$string['reportemailhelp'] = 'Report will be send to this email address.';
$string['emailnotsent'] = 'Failed to send email';
$string['subject'] = 'Subject';
$string['content'] = 'Content';
$string['emailexample'] = 'example1.mail.com, example2.mail.com;';
$string['format'] = 'Format';
$string['formatcsv'] = 'Comma Separated Value (.csv)';
$string['formatexcel'] = 'Excel Sheet (.xlsx)';
$string['formatpdf'] = 'Portable Document Format (.pdf)';
$string['formatpdfimage'] = $string['formatpdf'];
$string['formatpng'] = 'Portable Network Graphics (.png)';
$string['formatjpeg'] = 'Joint Photographic Experts Group (.jpeg)';
$string['formatsvg'] = 'Scalable Vector Graphics (.svg)';
$string['link'] = 'link';
$string['exportlink'] = 'Click on the {$a} to download the graph image.';
$string['inavalidkey'] = 'Invalid download key';
$string['supportedformats'] = 'Supported formats';
$string['unsupportedformat'] = 'Unsupported format';

$string['activeusersblockexportheader'] = 'Site activity overview';
$string['activeusersblockexporthelp'] = 'This report will show active users, program enrolment and program completion over the period.';
$string['courseprogressblockexportheader'] = 'All programs summary';
$string['courseprogressblockexporthelp'] = 'This report will show the All programs summary.';
$string['activecoursesblockexportheader'] = 'Most active program report';
$string['activecoursesblockexporthelp'] = 'This report will show the most active programs based on the enrolments, visits and completions.';
$string['certificatesblockexportheader'] = 'Awarded certificates report';
$string['certificatesblockexporthelp'] = 'This report will show the certificates who have issued or not issued to enrolled users.';
$string['courseengageblockexportheader'] = 'Program Engagement Report';
$string['courseengageblockexporthelp'] = 'This report will show the program engagement by the users.';
$string['completionblockexportheader'] = 'Program Completion Report';
$string['completionexportheader'] = 'Program Completion Report';
$string['completionblockexporthelp'] = 'Gives an overall idea of all activities performed in a program by participants and their completion.';
$string['completionexporthelp'] = 'Gives an overall idea of all activities performed in a program by participants and their completion.';
$string['studentengagementblockexportheader'] = 'All learner summary';
$string['gradeblockexportheader'] = 'Learner program activities';
$string['gradeblockexporthelp'] = 'This report will show learner program activities.';
$string['studentengagementexporthelp'] = 'Gives an overall idea of the learner\'s status in enrolled programs such as time spent, activities completed, assignments completed, quizzes, scorm, etc.';
$string['exportlpdetailedreports'] = 'Export Detailed Reports';
$string['inactiveusersblockexporthelp'] = 'This report will show inactivity of users in the website';

$string['times_0'] = '06:30 AM';
$string['times_1'] = '10:00 AM';
$string['times_2'] = '04:30 PM';
$string['times_3'] = '10:30 PM';
$string['week_0'] = 'Sunday';
$string['week_1'] = 'Monday';
$string['week_2'] = 'Tuesday';
$string['week_3'] = 'Wednesday';
$string['week_4'] = 'Thursday';
$string['week_5'] = 'Friday';
$string['week_6'] = 'Saturday';
$string['monthly_0'] = 'Month Start';
$string['monthly_1'] = 'Month Between';
$string['monthly_2'] = 'Month End';
$string['weeks_on'] = 'Weeks on';
$string['emailthisreport'] = 'Email this report';
$string['onevery'] = 'on every';
$string['duration_0'] = 'Daily';
$string['duration_1'] = 'Weekly';
$string['duration_2'] = 'Monthly';
$string['everydays'] = 'Everyday {$a->time}';
$string['everyweeks'] = 'Every {$a->day}';
$string['everymonths'] = 'Every month at {$a->time}';
$string['schedule'] = 'Schedule Email';
$string['downloadreport'] = 'Download Report';
$string['scheduledlist'] = 'All Scheduled Reports';
$string['reset'] = 'Reset';
$string['confirmemailremovaltitle'] = 'Delete Scheduled Email';
$string['confirmemailremovalquestion'] = '<p class="px-20">Do you really want to delete this sheduled email</p>';

/* Course Engagement Block */
$string['activitystart'] = 'At least one Activity Started';
$string['completedhalf'] = 'Completed 50% of Programs';
$string['coursecompleted'] = 'Program Completed';
$string['nousersavailable'] = 'No Users Available';

/* Course Completion Page */
$string['nostudentsenrolled'] = 'No users are enrolled as participants';
$string['completionheader'] = 'Program Completion';
$string['completionreports'] = 'Completion Reports';
$string['completionpercantage'] = 'Completion Percentage';
$string['activitycompleted'] = '{$a->completed} out of {$a->total}';
$string['allusers'] = 'All users';
$string['since1week'] = 'Since 1 Week';
$string['since2weeks'] = 'Since 2 Weeks';
$string['since1month'] = 'Since 1 Month';
$string['since1year'] = 'Since 1 Year';


/* Course Analytics Page */
$string['lastvisit'] = 'Last Visit';
$string['enrolledon'] = 'Enrolled On';
$string['enrolltype'] = 'Enrolment Type';
$string['noofvisits'] = 'Number of visits';
$string['completiontime'] = 'Completion Time';
$string['spenttime'] = 'Spent Time';
$string['completedon'] = 'Completed On';
$string['recentcompletion'] = 'Recent Completion';
$string['recentenrolment'] = 'Recent Enrolments';
$string['nousersincourse'] = 'No users has Enrolled in this program';
$string['nouserscompleted'] = 'No users has completed this program';
$string['nousersvisited'] = 'No users has visited this program';

/* Cron Task Strings */
$string['updatetables'] = 'Updating Reports and Analytics Table';
$string['updatingrecordstarted'] = 'Updating reports and analytics record is srated...';
$string['updatingrecordended'] = 'Updating reports and analytics record is ended...';
$string['updatinguserrecord'] = 'Updating userid {$a->userid} in courseid {$a->courseid}';
$string['deletingguserrecord'] = 'Deleting userid {$a->userid} in courseid {$a->courseid}';
$string['gettinguserrecord'] = 'Getting userid {$a->userid} in courseid {$a->courseid}';
$string['creatinguserrecord'] = 'Create records for users completions';
$string['sendscheduledemails'] = 'Send Scheduled Emails';
$string['sendingscheduledemails'] = 'Sending Scheduled Emails...';
$string['sending'] = 'Sending';

/* Cache Strings */
$string['cachedef_edwiserReport'] = 'This is the caches of Site Reports';

/* Capabilties */
$string['edwiserReport:view'] = 'View Reports and analytics dashboard';

/* Custom report block */
$string['downloadcustomtreport'] = 'Download Users Progress Report';
$string['selectdaterange'] = 'Select Date Range';
$string['learningprograms'] = 'Learning Programs';
$string['courses'] = 'Programs';
$string['shortname'] = 'Shortname';
$string['downloadreportincsv'] = 'Download Reports in CSV';
$string['startdate'] = 'Start date';
$string['enddate'] = 'End Date';
$string['select'] = 'Select';
$string['selectreporttype'] = 'Select Report Type';
$string['completedactivities'] = 'Activity Completed';
$string['completionspercentage'] = 'Completion(%)';
$string['firstname'] = 'First Name';
$string['lastname'] = 'Last Name';
$string['average'] = 'Average(%)';
$string['enrolmentstartdate'] = 'Enrolment Start Date';
$string['enrolmentenddate'] = 'Enrolment End Date';
$string['enrolmentrangeselector'] = 'Enrolment Date Range Selector';
$string['category'] = 'Category';
$string['customreportselectfailed'] = '<h4><i class="fa fa-check" aria-hidden="true"></i> Failed!</h4>Select any of the checkboxes to get reports.';
$string['customreportdatefailed'] = '<h4><i class="fa fa-check" aria-hidden="true"></i> Failed!</h4>Select valid date for enrolment.';
$string['customreportsuccess'] = '<h4><i class="fa fa-check" aria-hidden="true"></i> Success!</h4>Notifications sent successfully.';
$string['customreportfailed'] = '<h4><i class="fa fa-check" aria-hidden="true"></i> Failed!</h4>Select any of the checkboxes to get reports.';
$string['duration'] = 'Duration';
$string['na'] = 'NA';
$string['activityname'] = 'Activity Name';
$string['searchall'] = 'in all column';
$string['custom'] = 'Custom';

// Setting.
$string['edwiserReport_settings'] = 'Site Reports & Analytics Dashboard Settings';
$string['selectblocks'] = 'Select blocks to show for Reporting Managers: ';
$string['rpmblocks'] = 'Reporting Manager Blocks';
$string['addblocks'] = 'Add Blocks';
$string['notselected'] = 'Not Selected';
$string['colortheme'] = 'Color Theme';
$string['colorthemehelp'] = 'Choose color Theme for dashboard.';
$string['theme'] = 'Theme';

/* ERROR string */
$string['completiondatealert'] = 'Select correct completion date range';
$string['enroldatealert'] = 'Select correct enrolment date range';

/* Report name */
$string['reportname'] = 'custom_reports_{$a->date}.csv';
$string['totalgrade'] = 'Total Grade';
$string['attempt'] = 'Attempt';
$string['attemptstart'] = 'Attempt Start';
$string['attemptfinish'] = 'Attempt Finish';

$string['editblockview'] = 'Edit Block View';
$string['hide'] = 'Hide Block';
$string['unhide'] = 'Show Block';
$string['editcapability'] = 'Change Capability';

$string['desktopview'] = 'Desktop View';
$string['tabletview'] = 'Tablet View';
$string['large'] = 'Large';
$string['medium'] = 'Medium';
$string['small'] = 'Small';
$string['position'] = 'Position';

$string['capabilties'] = 'Capabilities';
$string['activeusersblockview'] = 'Site Overview Status View';
$string['activeusersblockedit'] = 'Site Overview Status Edit';
$string['activeusersblockeditadvance'] = 'Site Overview Status Advance Edit';
$string['activecoursesblockview'] = 'Popular Programs Block View';
$string['activecoursesblockedit'] = 'Popular Programs Block Edit';
$string['activecoursesblockeditadvance'] = 'Popular Programs Block Advance Edit';
$string['studentengagementblockview'] = 'Participants Engagement Block View';
$string['studentengagementblockedit'] = 'Participants Engagement Block Edit';
$string['studentengagementblockeditadvance'] = 'Participants Engagement Block Advance Edit';
$string['learnerblockview'] = 'Learner Block View';
$string['learnerblockedit'] = 'Learner Block Edit';
$string['learnerblockeditadvance'] = 'Learner Block Advance Edit';
$string['courseprogressblockview'] = 'Program Progress Block View';
$string['courseprogressblockedit'] = 'Program Progress Block Edit';
$string['courseprogressblockeditadvance'] = 'Program Progress Block Advance Edit';
$string['certificatesblockview'] = 'Certificates Block View';
$string['certificatesblockedit'] = 'Certificates Block Edit';
$string['certificatesblockeditadvance'] = 'Certificates Block Advance Edit';
$string['liveusersblockview'] = 'Real Time Users Block View';
$string['liveusersblockedit'] = 'Real Time Users Block Edit';
$string['liveusersblockeditadvance'] = 'Real Time Users Block Advance Edit';
$string['siteaccessblockview'] = 'Site Access Block View';
$string['siteaccessblockedit'] = 'Site Access Block Edit';
$string['siteaccessblockeditadvance'] = 'Site Access Block Advance Edit';
$string['todaysactivityblockview'] = 'Todays Activity Block View';
$string['todaysactivityblockedit'] = 'Todays Activity Block Edit';
$string['todaysactivityblockeditadvance'] = 'Todays Activity Block Advance Edit';
$string['inactiveusersblockview'] = 'Inactive Users Block View';
$string['inactiveusersblockedit'] = 'Inactive Users Block Edit';
$string['inactiveusersblockeditadvance'] = 'Inactive Users Block Advance Edit';

/* Course progress manager strings */
$string['update_course_progress_data'] = 'Update Program Progress Data';

/* Course Completion Event */
$string['coursecompletionevent'] = 'Program Completion Event';
$string['courseprogessupdated'] = 'Program Progress Updated';

/* Error Strings */
$string['invalidparam'] = 'Invalid Parameter Found';
$string['moduleidnotdefined'] = 'Module id is not defined';
$string['clicktogetuserslist'] = 'Click on numbers in order to get the users list';

/* Email Schedule Strings */
$string['enabledisableemail'] = 'Enable/Disable Email';
$string['scheduleerrormsg'] = '<div class="alert alert-danger"><b>ERROR:</b> Error while scheduling email</div>';
$string['schedulesuccessmsg'] = '<div class="alert alert-success"><b>SUCCESS:</b> Email scheduled successfully</div>';
$string['deletesuccessmsg'] = '<div class="alert alert-success"><b>SUCCESS:</b> Email deleted successfully</div>';
$string['deleteerrormsg'] = '<div class="alert alert-danger"><b>ERROR:</b> Email deletion failed</div>';
$string['emptyerrormsg'] = '<div class="alert alert-danger"><b>ERROR:</b> Name and Recepient Fields can not be empty</div>';
$string['emailinvaliderrormsg'] = '<div class="alert alert-danger"><b>ERROR:</b> Invalid email addresses (space not allowed)</div>';
$string['scheduledemaildisbled'] = '<div class="alert alert-success"><b>SUCCESS:</b> Scheduled Email Disabled</div>';
$string['scheduledemailenabled'] = '<div class="alert alert-success"><b>SUCCESS:</b> Scheduled Email Enabled</div>';
$string['noscheduleemails'] = 'There is no scheduled emails';
$string['nextrun'] = 'Next Run';
$string['frequency'] = 'Frequency';
$string['manage'] = 'Manage';
$string['scheduleemailfor'] = 'Schedule Emails for';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['report/edwiserreports_activeusersblock:editadvance'] = 'Edit Advance';

/* Custom Reports block related strings */
$string['customreport'] = 'Custom Report';
$string['customreportedit'] = 'Custom Reports';
$string['customreportexportpdfnote'] = 'If all columns are not visible, you can use CSV or Excel option to export the report.';
$string['reportspreview'] = 'Reports Preview';
$string['reportsfilter'] = 'Reports Filter';
$string['noreportspreview'] = 'No Preview Available';
$string['userfields'] = 'User Fields';
$string['coursefields'] = 'Program Fields';
$string['activityfields'] = 'Activity Fields';
$string['reportslist'] = 'Custom Reports List';
$string['noreportslist'] = 'No Custom Reports';
$string['allcohorts'] = 'All Cohorts';
$string['allstudents'] = 'All participantss';
$string['allactivities'] = 'All activities';
$string['save'] = 'Save';
$string['reportname'] = 'Report Name';
$string['reportshortname'] = 'Short Name';
$string['savecustomreport'] = 'Save Custom Report';
$string['downloadenable'] = 'Enable Download';
$string['emptyfullname'] = 'Report Name field is required';
$string['emptyshortname'] = 'Report Short Name field is required';
$string['nospecialchar'] = 'Report Short Name field doesn\'t allow special character';
$string['reportssavesuccess'] = 'Custom Reports Successfully saved';
$string['reportssaveerror'] = 'Custom Reports Failed to save';
$string['shortnameexist'] = 'Shortname Already Exist';
$string['createdby'] = 'Author';
$string['sno'] = 'S. No.';
$string['datecreated'] = 'Date Created';
$string['datemodified'] = 'Date Modified';
$string['enabledesktop'] = 'Desktop Enabled';
$string['noresult'] = 'No Result Found';
$string['enabledesktop'] = 'Add to Reports Dashboard';
$string['disabledesktop'] = 'Remove from Reports Dashboard';
$string['editreports'] = 'Edit Reports';
$string['deletereports'] = 'Delete Reports';
$string['deletesuccess'] = 'Reports Delete Successfully';
$string['deletefailed'] = 'Reports Delete Failed';
$string['deletecustomreportstitle'] = 'Delete Custom Reports Title';
$string['deletecustomreportsquestion'] = 'Do you really want to delete this custom reports?';
$string['createcustomreports'] = 'Create/Manage Custom Reports Block';
$string['searchreports'] = 'by reports';
$string['title'] = 'Title';
$string['createreports'] = 'Create New Report';
$string['updatereports'] = 'Update Reports';
$string['courseformat'] = 'Program Format';
$string['completionenable'] = 'Program Completion Enable';
$string['guestaccess'] = 'Program Guest Access';
$string['selectcourses'] = 'Select Programs';
$string['selectcohorts'] = 'Select Cohorts';
$string['createnewcustomreports'] = 'Create new Report';
$string['coursestartdate'] = 'Program Start Date';
$string['courseenddate'] = 'Program End Date';
$string['totalactivities'] = 'Total activities';
$string['incompletedactivities'] = 'Incompleted Activities';
$string['coursecategory'] = 'Program Category';
$string['courseenroldate'] = 'Program Enrol Date';
$string['coursecompletionstatus'] = 'Program Completion Status';
$string['learninghours'] = 'Learning Hours';
$string['invalidsecretkey'] = 'Invalid secret key. Please logout and login again.';
$string['newselectcohort'] = 'Select cohort';
$string['selectcourse'] = 'Select Program';
$string['selectuserfields'] = 'Select user fields';
$string['selectcoursefields'] = 'Select program fields';

// Old log.
$string['oldloginfo'] = 'Select start date and set the maximum time between 2 clicks in a the same session';
$string['oldlogmintime'] = 'Set Start date';
$string['oldlogmintime_help'] = 'Start date description - Select any date from the previous period, before the installation of Site Reports, to fetch and compile old user log data from your Moodle site into Site Reports. This will add and process all historical data of your Moodle site as part of Site Reports. <br><strong>For eg:</strong> If you want to pull old user log data from the month of October in the previous year from your Moodle site. And make sure it’s saved and processed by Site Reports after plugin installation. You can set this date as 1st October 2020. Similarly any previous date can be set.';
$string['oldloglimit'] = 'Maximum time elapsed between 2 clicks';
$string['oldloglimit_help'] = 'This helps you set the session duration, that is the number of minutes, seconds or hours two clicks apart.';
$string['fetcholdlogs'] = 'Fetch old Moodle logs';
$string['fetchingoldlogs'] = 'Fetching old logs';
$string['fetcholdlogsquestion'] = 'All time log data will be cleaned and recalculated using Moodle logs. This will take time for large log data. You should run this task only once.';
$string['fetch'] = 'Fetch';
$string['calculate'] = 'Calculate';
$string['oldlognote'] = 'Note: Your Moodle site log between {$a->from} to {$a->to} is being complied and made compatible with Site Reports. Skipping this process will result in Moodle site old logs not being captured to Site Reports Dashboard.';
$string['fetcholdlogsdescription'] = 'Fetch Old User Log Data (Data saved in Moodle backend before installation of Site Reports)';
$string['fetchmodalcontent'] = 'It seems like your site has old logs which can be converted into Site Reports time tracking data. It will be one time process.<br>
conversion page link is always available in Site administration -> Plugins -> Site Reports menu.<br>
<strong>Continue:</strong> Proceed with conversion. <br>
<strong>Later:</strong> Show this popup after 7 days. <br>
<strong>Never:</strong> Do not convert and never show this popup.';
$string['overallprogress'] = 'Overall progress';
$string['later'] = 'Later';
$string['time'] = 'Time';
$string['oldlogmysqlwarning'] = 'Your MySQL version is {$a}. Your log will be retrieved slowly. MySQL version 8.0.0 or higher is required for faster log retrieval.';

// Settings.
$string['generalsettings'] = 'General Settings';
$string['blockssettings'] = 'Block\'s Settings';
$string['trackfrequency'] = 'Time Log update frequency';
$string['trackfrequencyhelp'] = 'This setting helps you to set the frequency of updating the user time log (detailed sequence of user activities with a time stamp) in the database.
';
$string['precalculated'] = 'Show pre calculated data ';
$string['precalculatedhelp'] = 'If enabled, it loads weekly, monthly and yearly reports quicker. They are continuously pre calculated, processed and stored in the background for faster loading of reports.

If disabled, this process of report generation stops running in the background. This way the reporting dashboard will pull, process and calculate the required data at that instant, only when requested, that is, when you filter reports, increasing the load time of reports.

We recommend enabling this feature.

<strong>Note:</strong> Cron task should be scheduled for every hour to get precise data. Turn off this setting if the cron task is not set to run frequently.
';
$string['positionhelp'] = 'Set block position on dashboard.';
$string['positionhelpupgrade'] = '<br><strong>Note: Do not modify this setting on upgrade page. You can rearrange blocks on dashboard and admin settings page.</strong>';
$string['desktopsize'] = 'Size in Desktop';
$string['desktopsizehelp'] = 'Size of block in Desktop devices';
$string['tabletsize'] = 'Size in Tablet';
$string['tabletsizehelp'] = 'Size of block in Tablet devices';
$string['rolesetting'] = 'Allowed roles';
$string['rolesettinghelp'] = 'Define which users can view this block';
$string['confignotfound'] = 'Configuration not found for this plugin';

// Settings for plugin upgrade.
$string['activeusersrolesetting'] = 'Site Overview Status block allowed roles';
$string['courseprogressrolesetting'] = 'Program Progress block allowed roles';
$string['studentengagementrolesetting'] = 'Participants Engagement block allowed roles';
$string['learnerrolesetting'] = 'Learner block allowed roles';
$string['activecoursesrolesetting'] = 'Popular Programs block allowed roles';
$string['certificatesrolesetting'] = 'Certificates block allowed roles';
$string['liveusersrolesetting'] = 'Live Users block allowed roles';
$string['siteaccessrolesetting'] = 'Site Access Information block allowed roles';
$string['todaysactivityrolesetting'] = 'Todays Activity block allowed roles';
$string['inactiveusersrolesetting'] = 'Inactive Users block allowed roles';
$string['graderolesetting'] = 'Grade block allowed roles';

$string['activeusersdesktopsize'] = 'Site Overview Status block size in Desktop';
$string['courseprogressdesktopsize'] = 'Program Progress Block size in Desktop';
$string['studentengagementdesktopsize'] = 'Participants Engagement Block size in Desktop';
$string['learnerdesktopsize'] = 'Learner Block size in Desktop';
$string['activecoursesdesktopsize'] = 'Popular Programs Block size in Desktop';
$string['certificatesdesktopsize'] = 'Certificates Block size in Desktop';
$string['liveusersdesktopsize'] = 'Live Users Block size in Desktop';
$string['siteaccessdesktopsize'] = 'Site Access Information Block size in Desktop';
$string['todaysactivitydesktopsize'] = 'Todays Activity Block size in Desktop';
$string['inactiveusersdesktopsize'] = 'Inactive Users Block size in Desktop';
$string['gradedesktopsize'] = 'Grade Block size in Desktop';

$string['activeuserstabletsize'] = 'Site Overview Status Block size in Tablet';
$string['courseprogresstabletsize'] = 'Program Progress Block size in Tablet';
$string['studentengagementtabletsize'] = 'Participants Engagement Block size in Tablet';
$string['learnertabletsize'] = 'Learner Block size in Tablet';
$string['activecoursestabletsize'] = 'Popular Programs Block size in Tablet';
$string['certificatestabletsize'] = 'Certificates Block size in Tablet';
$string['liveuserstabletsize'] = 'Live Users Block size in Tablet';
$string['siteaccesstabletsize'] = 'Site Access Information Block size in Tablet';
$string['todaysactivitytabletsize'] = 'Todays Activity Block size in Tablet';
$string['inactiveuserstabletsize'] = 'Inactive Users Block size in Tablet';
$string['gradetabletsize'] = 'Grade Block size in Tablet';

$string['activeusersposition'] = 'Site Overview Status Block\'s Position';
$string['courseprogressposition'] = 'Program Progress Block\'s Position';
$string['studentengagementposition'] = 'Participants Engagement Block\'s Position';
$string['learnerposition'] = 'Learner Block\'s Position';
$string['activecoursesposition'] = 'Popular Programs Block\'s Position';
$string['certificatesposition'] = 'Certificates Block\'s Position';
$string['liveusersposition'] = 'Live Users Block\'s Position';
$string['siteaccessposition'] = 'Site Access Information Block\'s Position';
$string['todaysactivityposition'] = 'Todays Activity Block\'s Position';
$string['inactiveusersposition'] = 'Inactive Users Block\'s Position';
$string['gradeposition'] = 'Grade Block\'s Position';

// License.
$string['licensestatus'] = 'Manage License';
$string['licensenotactive'] = '<strong>Alert!</strong> License is not activated , please <strong>activate</strong> the license in Site Reports settings.';
$string['licensenotactiveadmin'] = '<strong>Alert!</strong> License is not activated , please <strong>activate</strong> the license <a href="'.$CFG->wwwroot.'/admin/settings.php?section=local_edwiserreports" >here</a>.';
$string['activatelicense'] = 'Activate License';
$string['deactivatelicense'] = 'Deactivate License';
$string['renewlicense'] = 'Renew License';
$string['active'] = 'Active';
$string['suspended'] = 'Suspended';
$string['notactive'] = 'Not Active';
$string['expired'] = 'Expired';
$string['no_activations_left'] = 'Limit exceeded';
$string['licensekey'] = 'License key';
$string['noresponsereceived'] = 'No response received from the server. Please try again later.';
$string['licensekeydeactivated'] = 'License Key is deactivated.';
$string['siteinactive'] = 'Site inactive (Press Activate license to activate plugin).';
$string['entervalidlicensekey'] = 'Please enter valid license key.';
$string['nolicenselimitleft'] = 'Maximum activation limit reached, No activations left.';
$string['licensekeyisdisabled'] = 'Your license key is Disabled.';
$string['licensekeyhasexpired'] = 'Your license key has Expired. Please, Renew it.';
$string['licensekeyactivated'] = 'Your license key is activated.';
$string['enterlicensekey'] = 'Please enter correct license key.';

// Visits On Site block.
$string['visitsonsiteheader'] = 'Visits On Site';
$string['visitsonsiteblockhelp'] = 'The number of visits users had on your site in a given user session. Session duration is defined in Site Reports settings.';
$string['visitsonsiteblockview'] = 'Visits On Site View';
$string['visitsonsiteblockedit'] = 'Visits On Site Edit';
$string['visitsonsiterolesetting'] = 'Visits On Site allowed roles';
$string['visitsonsitedesktopsize'] = 'Visits On Site size in Desktop';
$string['visitsonsitetabletsize'] = 'Visits On Site size in Tablet';
$string['visitsonsiteposition'] = 'Visits On Site\'s Position';
$string['visitsonsiteblockexportheader'] = 'Visits On Site Report';
$string['visitsonsiteblockexporthelp'] = 'This report will show the Visits On Site exported data.';
$string['visitsonsiteblockeditadvance'] = 'Visits On Site Block Advance Edit';
$string['averagesitevisits'] = 'Average site visits';
$string['totalsitevisits'] = 'Total site visits';

// Time spent on site block.
$string['timespentonsiteheader'] = 'Time Spent On Site';
$string['timespentonsiteblockhelp'] = 'Time spent by the users on your site in a day.';
$string['timespentonsiteblockview'] = 'Time spent on site View';
$string['timespentonsiteblockedit'] = 'Time spent on site Edit';
$string['timespentonsiterolesetting'] = 'Time spent on site allowed roles';
$string['timespentonsitedesktopsize'] = 'Time spent on site size in Desktop';
$string['timespentonsitetabletsize'] = 'Time spent on site size in Tablet';
$string['timespentonsiteposition'] = 'Time spent on site\'s Position';
$string['timespentonsiteblockexportheader'] = 'Time spent on site Report';
$string['timespentonsiteblockexporthelp'] = 'This report will show the Time spent on site exported data.';
$string['timespentonsiteblockeditadvance'] = 'Time spent on site Block Advance Edit';
$string['averagetimespent'] = 'Average time spent';

// Time spent on course block.
$string['timespentoncourseheader'] = 'Time Spent On Program';
$string['timespentoncourseblockhelp'] = 'Time spent by the participants in a particular programs in a day.';
$string['timespentoncourseblockview'] = 'Time spent on program View';
$string['timespentoncourseblockedit'] = 'Time spent on program Edit';
$string['timespentoncourserolesetting'] = 'Time spent on program allowed roles';
$string['timespentoncoursedesktopsize'] = 'Time spent on program size in Desktop';
$string['timespentoncoursetabletsize'] = 'Time spent on program size in Tablet';
$string['timespentoncourseposition'] = 'Time spent on program\'s Position';
$string['timespentoncourseblockexportheader'] = 'Time spent on program Report';
$string['timespentoncourseblockexporthelp'] = 'This report will show the Time spent on program exported data.';
$string['timespentoncourseblockeditadvance'] = 'Time spent on program Block Advance Edit';

// Course activity block.
$string['courseactivitystatusheader'] = 'Program Activity Status';
$string['courseactivitystatusblockhelp'] = 'Program activities performed by the participants. It is a combination of activities completed and assignments submitted line graphs.';
$string['courseactivitystatusblockview'] = 'Program activity status View';
$string['courseactivitystatusblockedit'] = 'Program activity status Edit';
$string['courseactivitystatusrolesetting'] = 'Program activity status allowed roles';
$string['courseactivitystatusdesktopsize'] = 'Program activity status size in Desktop';
$string['courseactivitystatustabletsize'] = 'Program activity status size in Tablet';
$string['courseactivitystatusposition'] = 'Program activity status\'s Position';
$string['courseactivitystatusblockexportheader'] = 'Program activity status Report';
$string['courseactivitystatusblockexporthelp'] = 'This report will show the Program activity status exported data.';
$string['courseactivitystatusblockeditadvance'] = 'Program activity status Block Advance Edit';
$string['averagecompletion'] = 'Average activity completed';
$string['totalassignment'] = 'Total assignment submitted';
$string['totalcompletion'] = 'Total activity completed';

// Learner Course Progress block.
$string['learnercourseprogressheader'] = 'My Program Progress';
$string['learnercourseprogressblockhelp'] = 'Your program completion progress in a particular program.';
$string['learnercourseprogressblockview'] = 'My Program Progress View';
$string['learnercourseprogressblockedit'] = 'My Program Progress Edit';
$string['learnercourseprogressrolesetting'] = 'My Program Progress allowed roles';
$string['learnercourseprogressdesktopsize'] = 'My Program Progress size in Desktop';
$string['learnercourseprogresstabletsize'] = 'My Program Progress size in Tablet';
$string['learnercourseprogressposition'] = 'My Program Progress\'s Position';
$string['learnercourseprogressblockexportheader'] = 'My Program Progress Report';
$string['learnercourseprogressblockexporthelp'] = 'This report will show the My Program Progress exported data.';
$string['learnercourseprogressblockeditadvance'] = 'My Program Progress Block Advance Edit';

// Learner Time spent on site block.
$string['learnertimespentonsiteheader'] = 'My Time Spent On Site';
$string['learnertimespentonsiteblockhelp'] = 'Your time spent on the site in a day.';
$string['learnertimespentonsiteblockview'] = 'My Time spent on site View';
$string['learnertimespentonsiteblockedit'] = 'My Time spent on site Edit';
$string['learnertimespentonsiterolesetting'] = 'My Time spent on site allowed roles';
$string['learnertimespentonsitedesktopsize'] = 'My Time spent on site size in Desktop';
$string['learnertimespentonsitetabletsize'] = 'My Time spent on site size in Tablet';
$string['learnertimespentonsiteposition'] = 'My Time spent on site\'s Position';
$string['learnertimespentonsiteblockexportheader'] = 'My Time spent on site Report';
$string['learnertimespentonsiteblockexporthelp'] = 'This report will show the My Time spent on site exported data.';
$string['learnertimespentonsiteblockeditadvance'] = 'My Time spent on site Block Advance Edit';
$string['site'] = 'Site';
$string['completed-y'] = 'Completed';
$string['completed-n'] = 'Not Completed';

// Course Engagement block.
$string['courseengagementheader'] = 'Program Engagement block';
$string['courseengagementblockhelp'] = 'This block will show the Program Engagement data.';
$string['courseengagementblockview'] = 'Program Engagement Block View';
$string['courseengagementblockedit'] = 'Program Engagement Block Edit';
$string['courseengagementrolesetting'] = 'Program Engagement block allowed roles';
$string['courseengagementdesktopsize'] = 'Program Engagement block size in Desktop';
$string['courseengagementtabletsize'] = 'Program Engagement block size in Tablet';
$string['courseengagementposition'] = 'Program Engagement block\'s Position';
$string['courseengagementblockexportheader'] = 'Program Engagement block Report';
$string['courseengagementblockexporthelp'] = 'This report will show the Program Engagement block exported data.';
$string['courseengagementblockeditadvance'] = 'Program Engagement Block Advance Edit';
$string['categoryname'] = 'Category Name';

// Top page insights.
$string['newregistrations'] = 'New registrations';
$string['courseenrolments'] = 'Program enrolments';
$string['coursecompletions'] = 'Program completions';
$string['activeusers'] = 'Active users';
$string['activitycompletions'] = 'Activity completions';
$string['timespentoncourses'] = 'Time spent on programs';
$string['totalcoursesenrolled'] = 'Total programs enrolled';
$string['coursecompleted'] = 'Program completed';
$string['activitiescompleted'] = 'Activities completed';

// Course activities summary report page.
$string['courseactivitiessummaryview'] = 'Program Activities Summary View';
$string['courseactivitiessummaryeditadvance'] = 'Program Activities Summary Edit';
$string['courseactivitiessummary'] = 'Program Activities Summary';
$string['courseactivitiessummaryexportheader'] = 'Program Activities Summary';
$string['courseactivitiessummaryheader'] = 'Program Activities Summary';
$string['courseactivitiessummaryexporthelp'] = 'Here managers/teachers can understand which activities and activity types are performing well and which are not.';
$string['allsections'] = 'All sections';
$string['allmodules'] = 'All modules';
$string['exclude'] = 'Exclude';
$string['notstarted'] = 'Not started';
$string['inactivesince1month'] = 'Inactive users since 1 Month';
$string['inactivesince1year'] = 'Inactive users since 1 year';
$string['learnerscompleted'] = 'Participants Completed';
$string['completionrate'] = 'Completion rate';
$string['passgrade'] = 'Pass grade';
$string['averagegrade'] = 'Average grade';
$string['searchactivity'] = 'by activity';
$string['alltime'] = 'All Time';

// Learner Course Progress Reports.
$string['learnercourseprogressexporthelp'] = 'Managers and teachers can see a report of all enrolled programs of a particular learner.';
$string['learnercourseprogressview'] = 'Learner Program Progress View';
$string['learnercourseprogresseditadvance'] = 'Learner Program Progress Edit';
$string['learnerview'] = 'Learner Program Progress View(For Learner)';
$string['learnereditadvance'] = 'Learner Program Progress Edit(For Learner)';
$string['completedactivities'] = 'Completed activities';
$string['attemptedactivities'] = 'Attempted activities';
$string['notenrolledincourse'] = 'User {$a->user} is not enrolled in program {$a->course}';
$string['gradedon'] = 'Graded on';
$string['attempts'] = 'Attempts';
$string['firstaccess'] = 'First access';
$string['notyetstarted'] = 'Not started yet';

// All courses summary.
$string['allcoursessummary'] = 'All Programs Summary';
$string['allcoursessummaryexportheader'] = 'All Programs Summary';
$string['allcoursessummaryheader'] = 'All Programs Summary';
$string['allcoursessummaryview'] = 'All Programs Summary View';
$string['allcoursessummaryeditadvance'] = 'All Programs Summary Edit';
$string['allcoursessummaryexporthelp'] = 'Program overview report to understand overall engagement and activity in the program such as total learner enrollments, completions, etc.';

// Learner Course Activities.
$string['learnercourseactivities'] = 'Learner Program Activities';
$string['learnercourseactivitiesheader'] = 'Learner Program Activities';
$string['learnercourseactivitiesexportheader'] = 'Learner Program Activities';
$string['learnercourseactivitiesexporthelp'] = 'Gives an idea of all grades received in all gradable activities of a program.';
$string['learnercourseactivitiespdfcontent'] = 'Program: {$a->course} <br>Participants: {$a->student}';
$string['learnercourseactivitiesview'] = 'Learner Program Activities View';
$string['learnercourseactivitieseditadvance'] = 'Learner Program Activities Edit';
$string['completionstatus'] = 'Completion status';

// Course Activity Completion.
$string['courseactivitycompletion'] = 'Program Activity Completion';
$string['courseactivitycompletionheader'] = 'Program Activity Completion';
$string['courseactivitycompletionexportheader'] = 'Program Activity Completion';
$string['courseactivitycompletionexporthelp'] = 'Gives an idea of all grades received in all gradable activities by a program.';
$string['courseactivitycompletionview'] = 'Program Activity Completion View';
$string['courseactivitycompletioneditadvance'] = 'Program Activity Completion Edit';


// Summary card.
$string['avgvisits'] = 'Avg. visits';
$string['avgtimespent'] = 'Avg. time spent';
$string['totalsections'] = 'Total sections';
$string['highgrade'] = 'Highest grade';
$string['lowgrade'] = 'Lowest grade';
$string['totallearners'] = 'Total participants';
$string['totalmarks'] = 'Total marks';
$string['marks'] = 'Marks';
$string['enrolmentdate'] = 'Enrollment date';
$string['activitytype'] = 'Activity type';
$string['totaltimespentoncourse'] = 'Total time spent on program(s)';
$string['avgtimespentoncourse'] = 'Avg time spent on program(s)';

// Breadcrumbs strings.
