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

class quiz_exportresults_report extends quiz_default_report {
  public function display($quiz, $cm, $course) {
    // Get database
    global $DB;

    // Display page
    $this->print_header_and_tabs($cm, $course, $quiz, 'quiz_exportresults');

    // Display form (introduced at the end)

    // Check if groups activated
    if(groups_get_course_groupmode($course) != 0) {
      $groups = groups_get_all_groups($course->id); // Get all groups / groups_get_user_groups
    }else {
      $groups = array((object) array("name" => $course->fullname) ); // No groups activated
    }

    /////////////////////////// Generate export ///////////////////////////
    // Get current export path
    $tempPath = make_request_directory() . "/plugins/exportresults/" . substr(md5(time()), 0, 8); // Remove files 

    // Loop groups
    foreach($groups as $group) {
      // Temporary path to group folder
      $groupTempPath = $tempPath . "/" . $group->name;

      // Handle attempts
      if(groups_get_course_groupmode($course)) {
        $members = groups_get_members($group->id); // Get members of group
      }else {
        $members = ''; // Get members of course
      }

      foreach($members as $member) {
        // Options: highest grade, first attempt, last attempts, all attempts
        switch('all') {
          case 'highest': // Highest attempt
            $params['quiz'] = $quiz->id; // Quiz ID
            $params['userid'] = $member->id; // User ID

            $attempts = $DB->get_records_select('quiz_attempts', 'quiz=:quiz AND userid=:userid', $params, 'sumgrades DESC', '*', 0, 1); // Request attempts
            break;
          case 'first': // first attempt
            $params['quiz'] = $quiz->id; // Quiz ID
            $params['userid'] = $member->id; // User ID

            $attempts = $DB->get_records_select('quiz_attempts', 'quiz=:quiz AND userid=:userid', $params, 'attempt ASC', '*', 0, 1); // Request attempts
            break;
          case 'last': // Last attempt
            $params['quiz'] = $quiz->id; // Quiz ID
            $params['userid'] = $member->id; // User ID

            $attempts = $DB->get_records_select('quiz_attempts', 'quiz=:quiz AND userid=:userid', $params, 'attempt DESC', '*', 0, 1); // Request attempts
            break;
          case 'all': // All attempts
          default:
            $params['quiz'] = $quiz->id; // Quiz ID
            $params['userid'] = $member->id; // User ID

            $attempts = $DB->get_records_select('quiz_attempts', 'quiz=:quiz AND userid=:userid', $params, 'attempt DESC'); // Request attempts
            break;
        }
      }

      // Get acctual value
      foreach($attempts as $attempt) {
        $params['id'] = $attempt->id; // Attempt ID

        $attempt_values = $DB->get_records_select('question_attempts', 'id=:id', $params, 'timemodified DESC'); // Request attempts
      }

      // Generate files

    }

    // Convert to zip and enable downlod
    // https://docs.moodle.org/dev/File_API



    ///////////////// TEST ENVIRONMENT /////////////////
    echo '<pre>';
      var_dump($attempt_values);
    echo '</pre>';
  }
}
?>
