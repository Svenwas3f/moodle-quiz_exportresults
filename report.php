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
 * @package   quiz_exportresults
 * @copyright 2022, Sven Waser <sven.waser@ksso.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Links
// https://docs.moodle.org/dev/Groups_API


class quiz_exportresults_report extends quiz_default_report {
  public function display($cm, $quiz, $course) {
    // Display page
    $this->print_header_and_tabs($cm, $course, $quiz, 'quiz_exportresults');

    // Display form (introduced at the end)

    // Check if groups activated
    if(groups_get_course_groupmode($course) != 0) {
      $groups = groups_get_all_groups($course->id); // Get all groups
    }else {
      $groups = array(); // No groups activated
    }

    /////////////////////////// Generate export ///////////////////////////
    // Get current export path
    $tempPath = make_request_directory() . "/plugins/exportresults/" . substr(md5(time()), 0, 8);

    // Loop groups
    foreach($groups as $group) {
      // Temporary path to group folder
      $groupTempPath = $tempPath . "/" . $group->id;

      echo $groupTempPath;

      // Handle attempts (Loop)

      // Generate files
    }

    // Convert to zip and enable downlod
    // https://docs.moodle.org/dev/File_API



    ///////////////// TEST ENVIRONMENT /////////////////
    echo '<pre>';
      var_dump($groups);
    echo '</pre>';
  }
}
?>
