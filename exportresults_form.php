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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   quiz_exportresults
 * @copyright 2022, Sven Waser <sven.waser@ksso.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Form class where the form is placed together
 *
 * @package    exportresults
 * @category   form
 * @copyright  2022 Sven Waser
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_exportresults_form extends moodleform {
    public function definition() {
        // Get global variables.
        global $COURSE, $USER;

        // Start Moodle form.
        $mform = $this->_form; // Don't forget the underscore!

        /******************************** General settings ********************************/
        $mform->addElement('header',
                            'exportoptions',
                            get_string('exportresults_exportoptions_header', 'quiz_exportresults'),
                            'quiz_exportresults'
                          );

        // Select export attempts.
        $mform->addElement(
          'select',
          'attempts',
          get_string('exportresults_exportoptions_select_info', 'quiz_exportresults'),
          array(
            'highest' => get_string('exportresults_exportoptions_option_highest_grade', 'quiz_exportresults'), // Add select-option.
            'first' => get_string('exportresults_exportoptions_option_first_attempt', 'quiz_exportresults'), // Add select-option.
            'last' => get_string('exportresults_exportoptions_option_last_attempt', 'quiz_exportresults'), // Add select-option.
            'all' => get_string('exportresults_exportoptions_option_all_attempts', 'quiz_exportresults') // Add select-option.
          )
        );

        // Check if group mode enabled.
        if (groups_get_course_groupmode($COURSE) != 0) {
            // Get all groups as option.
            foreach (groups_get_user_groups($COURSE->id, $USER->id)[0] as $group) {
                $options[$group] = groups_get_group_name($group);
            }

            // Add group selection to form.
            $groupselect = $mform->addElement(
                'select',
                'groups',
                get_string('exportresults_exportoptions_select_groups', 'quiz_exportresults'),
                $options ?? null,
            );
            $groupselect->setMultiple(true);
        }

        // Checkbox to include questiontext.
        $mform->addElement(
            'advcheckbox',
            'questions',
            get_string('exportresults_exportoptions_checkbox_question', 'quiz_exportresults'),
            get_string('exportresults_exportoptions_checkbox_question_label', 'quiz_exportresults')
        );
        $mform->setDefault('questions', 1);

        /******************************** File Settings ********************************/
        $mform->addElement(
            'header',
            'fileoptions',
            get_string('exportresults_fileoptions_header', 'quiz_exportresults'),
            'quiz_exportresults'
        );
        $mform->setExpanded('fileoptions', false);

        // Includde margin-options.
        $mform->addElement('text',
                            'margintop',
                            get_string('exportresults_fileoption_margintop', 'quiz_exportresults'),
                            "value='2cm'",
                          );
        $mform->setTYpe('margintop', PARAM_RAW);
        $mform->addElement('text',
                            'marginright',
                            get_string('exportresults_fileoption_marginright', 'quiz_exportresults'),
                            "value='2cm'",
                          );
        $mform->setTYpe('marginright', PARAM_RAW);
        $mform->addElement('text',
                            'marginbottom',
                            get_string('exportresults_fileoption_marginbottom', 'quiz_exportresults'),
                            "value='2cm'",
                          );
        $mform->setTYpe('marginbottom', PARAM_RAW);
        $mform->addElement('text',
                            'marginleft',
                            get_string('exportresults_fileoption_marginleft', 'quiz_exportresults'),
                            "value='2cm'",
                            );
        $mform->setTYpe('marginleft', PARAM_RAW);

        // Include font-size option.
        $mform->addElement('text',
                            'fontsize',
                            get_string('exportresults_fileoption_fontsize', 'quiz_exportresults'),
                            "value='12pt'"
                          );
        $mform->setTYpe('fontsize', PARAM_RAW);

        // Include font-family option.
        $options = array(
            'Arial' => 'Arial',
            'Times New Roman' => 'Times New Roman',
            'Frutiger LT Com 55 Roman' => 'Frutiger LT Com 55 Roman',
        );
        $fontfamily = $mform->addElement('select',
                                          'fontfamily',
                                          get_string('exportresults_fileoption_fontfamily',
                                          'quiz_exportresults'),
                                          $options
                                        );
        $fontfamily->setSelected('Arial');

        // Include line-height option.
        $mform->addElement('text',
                            'lineheight',
                            get_string('exportresults_fileoption_lineheight', 'quiz_exportresults'),
                            "value='1.5'"
                          );
        $mform->setTYpe('lineheight', PARAM_RAW);

        // Include submit button.
        $mform->closeHeaderBefore('submitbutton');
        $mform->addElement('submit', 'submitbutton', get_string('exportresutls_submit', 'quiz_exportresults'));
    }
}
