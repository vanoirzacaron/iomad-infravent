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

/**
 * Define all constants for use
 */

/* Course completion constant */
define('LOCAL_SITEREPORT_COURSE_COMPLETE_00PER', 0);
define('LOCAL_SITEREPORT_COURSE_COMPLETE_20PER', 0.2);
define('LOCAL_SITEREPORT_COURSE_COMPLETE_40PER', 0.4);
define('LOCAL_SITEREPORT_COURSE_COMPLETE_60PER', 0.6);
define('LOCAL_SITEREPORT_COURSE_COMPLETE_80PER', 0.8);
define('LOCAL_SITEREPORT_COURSE_COMPLETE_100PER', 1);

/* Percentage constant */
define('LOCAL_SITEREPORT_PERCENTAGE_00', "0%");
define('LOCAL_SITEREPORT_PERCENTAGE_20', "20%");
define('LOCAL_SITEREPORT_PERCENTAGE_40', "40%");
define('LOCAL_SITEREPORT_PERCENTAGE_60', "60%");
define('LOCAL_SITEREPORT_PERCENTAGE_80', "80%");
define('LOCAL_SITEREPORT_PERCENTAGE_100', "100%");

/* Time constant */
define('LOCAL_SITEREPORT_ONEDAY', 24 * 60 * 60);
define('LOCAL_SITEREPORT_ONEWEEK', 7 * 24 * 60 * 60);
define('LOCAL_SITEREPORT_ONEMONTH', 30 * 24 * 60 * 60);
define('LOCAL_SITEREPORT_ONEYEAR', 365 * 24 * 60 * 60);
define('LOCAL_SITEREPORT_ALL', "all");
define('LOCAL_SITEREPORT_WEEKLY', "weekly");
define('LOCAL_SITEREPORT_MONTHLY', "monthly");
define('LOCAL_SITEREPORT_YEARLY', "yearly");
define('LOCAL_SITEREPORT_WEEKLY_DAYS', 7);
define('LOCAL_SITEREPORT_MONTHLY_DAYS', 30);
define('LOCAL_SITEREPORT_YEARLY_DAYS', 365);

define('LOCAL_SITEREPORT_ESR_DAILY_EMAIL', 0);
define('LOCAL_SITEREPORT_ESR_LOCAL_SITEREPORT_WEEKLY_EMAIL', 1);
define('LOCAL_SITEREPORT_ESR_LOCAL_SITEREPORT_MONTHLY_EMAIL', 2);

define('LOCAL_SITEREPORT_ESR_0630AM', 0);
define('LOCAL_SITEREPORT_ESR_1000AM', 1);
define('LOCAL_SITEREPORT_ESR_0430PM', 2);
define('LOCAL_SITEREPORT_ESR_1030PM', 3);

// Define block type.
define('LOCAL_SITEREPORT_BLOCK_TYPE_DEFAULT', 0);
define('LOCAL_SITEREPORT_BLOCK_TYPE_CUSTOM', 1);

// Block View.
define('LOCAL_SITEREPORT_BLOCK_DESKTOP_VIEW', 'desktopview');
define('LOCAL_SITEREPORT_BLOCK_TABLET_VIEW', 'tabletview');
define('LOCAL_SITEREPORT_BLOCK_LARGE', 2);
define('LOCAL_SITEREPORT_BLOCK_MEDIUM', 1);
define('LOCAL_SITEREPORT_BLOCK_SMALL', 0);

// Course Progres Manager.
define('CPM_STUDENTS_ARCHETYPE', 'student');

global $DB;
$companyid = \iomad::get_my_companyid(context_system::instance(), false);

// Fetch both link and main colors in a single query
$colors = $DB->get_record('company', array('id' => $companyid), 'linkcolor, maincolor, headingcolor');

$linkColor = $colors->linkcolor ?: '#F98012'; // Default link color: Blue
$mainColor = $colors->maincolor ?: '#133F3F'; // Default main color: Light Gray
$headingColor = $colors->headingcolor ?: '#00A1A8'; // Default heading color: Dark Gray

// Color Themes.
define('LOCAL_EDWISERREPORTS_COLOR_THEMES', [
    [''.$colors->linkcolor.'', ''.$colors->maincolor.'', ''.$colors->headingcolor.'', '#444444', '#666666'],
    [''.$colors->linkcolor.'', ''.$colors->maincolor.'', ''.$colors->headingcolor.'', '#333333', '#999999'],
    [''.$colors->linkcolor.'', ''.$colors->maincolor.'', ''.$colors->headingcolor.'', '#222222', '#888888']
]);

// Exclude users.
define('SUSPENDEDUSERS', 0);
define('NOTSTARTED', 1);
define('INACTIVESINCE1MONTH', 2);
define('INACTIVESINCE1YEAR', 3);
