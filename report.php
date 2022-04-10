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

class quiz_exportresults_report extends quiz_default_report {
  /**
   * Function to display report
   * @param $cm the course-module for this quiz.
   * @param $course the coures we are in.
   * @param $quiz this quiz.
   */
  public function display($quiz, $cm, $course) {
    // Get database
    global $DB, $context;

    // Display page
    $this->print_header_and_tabs($cm, $course, $quiz, 'quiz_exportresults');

    // Display form (introduced at the end)
    //quiz_has_attempts

    // Check if groups activated
    if(groups_get_course_groupmode($course) != 0) {
      $groups = groups_get_all_groups($course->id); // Get all groups / groups_get_user_groups
    }else {
      $groups = array((object) array("id" => 0, "courseid" => $course->id, "name" => $course->fullname) ); // No groups activated
    }

    /////////////////////////// Generate export ///////////////////////////
    $path = make_request_directory() . "/sexporsubmissionplugin/export/";
    $filename = 'export.zip';
    mkdir($path, 0777, true); // Generate temp path

    $export = new ZipArchive();
    $export->open($path . $filename, ZipArchive::CREATE); // Initialise zip

    // Loop groups
    foreach($groups as $group) {
      // Add folder to export zip
      $export->addEmptyDir($group->name);

      // Handle attempts
      if(groups_get_course_groupmode($course)) {
        $members = groups_get_members($group->id); // Get members of group
      }else {
        $members = get_enrolled_users($context); // Get members of course
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

        // Get acctual value
        foreach($attempts as $attempt) {
          $params['id'] = $attempt->id; // Attempt ID
          $attempt_value = $DB->get_records_select('question_attempts', 'id=:id', $params, 'timemodified DESC'); // Request attempts

          // Options:
          // download attempts
          // Include question
          // Groups selection
          //
          // Word:
          // Margin
          // Font Size
          // Line Height
          // Font Family

          // Prepare values for odt
          $content[0]["val"][0]["val"][0]["val"][0]["name"] = 'text:p';
          $content[0]["val"][0]["val"][0]["val"][0]["att"] = array('text:style-name' => 'Standard');
          $content[0]["val"][0]["val"][0]["val"][0]["val"] = $attempt_value[3]->responsesummary;

          $meta[0]["val"][0]["val"][0]["name"] = 'meta:initial-creator';
          $meta[0]["val"][0]["val"][0]["val"] = 'Moodle Exportresults Plugin';
          $meta[0]["val"][0]["val"][1]["name"] = 'meta:creation-date';
          $meta[0]["val"][0]["val"][1]["val"] = date("Y-m-d\TH:i:sp");
          $meta[0]["val"][0]["val"][3]["name"] = 'meta:creator';
          $meta[0]["val"][0]["val"][3]["val"] = 'Moodle Exportresults Plugin';

          $styles[0]["val"][0]["val"][0]["name"] = 'office:automatic-styles';
          $styles[0]["val"][0]["val"][0]["val"][0]["name"] = 'style:page-layout';
          $styles[0]["val"][0]["val"][0]["val"][0]["att"] = array('style:name' => 'Mpm1');
          $styles[0]["val"][0]["val"][0]["val"][0]["val"][0]["name"] = 'style:page-layout-properties';
          $styles[0]["val"][0]["val"][0]["val"][0]["val"][0]["att"] = array(
                                                                        'fo:page-width' => '21.001cm',
                                                                        'fo:page-height' => '29.7cm',
                                                                        'fo:margin-bottom' => '5cm',
                                                                        'fo:margin-top' => '5cm',
                                                                        'fo:margin-left' => '5cm',
                                                                        'fo:margin-right' => '5cm',
                                                                      );
          $styles[0]["val"][0]["val"][1]["name"] = 'office:styles';
          $styles[0]["val"][0]["val"][1]["val"][0]["name"] = 'style:default-style';
          $styles[0]["val"][0]["val"][1]["val"][0]["att"] = array('style:family' => 'paragraph');
          $styles[0]["val"][0]["val"][1]["val"][0]["val"][0]["name"] = 'style:text-properties';
          $styles[0]["val"][0]["val"][1]["val"][0]["val"][0]["att"] = array(
                                                                        'fo:font-size' => '20pt',
                                                                        'style:font-name' => 'Arial',
                                                                      );

          // Generate odt and add to export
          $odt = $this->odt($content, $meta, array(), $styles);

          $pattern = '/[^A-Za-z0-9-_]/'; // Pattern to secure filename
          $export->addFile($odt, $group->name . "/" . preg_replace($pattern, '', $member->username) . "_" . preg_replace($pattern, '', $member->firstname) . "_" . preg_replace($pattern, '', $member->lastname) . ".odt");
        }
      }
    }

    // Convert to zip and enable downlod
    $export->close();

    // Copy odt into download Zip
    // $fs = get_file_storage();

    // Prepare file record object
    // $fileinfo = array(
    //     'contextid' => $context->id, // ID of context
    //     'component' => 'mod_mymodule',     // usually = table name
    //     'filearea' => 'myarea',     // usually = table name
    //     'itemid' => 0,               // usually = ID of row in table
    //     'filepath' => '/',           // any path beginning and ending in /
    //     'filename' => 'myfile.txt'); // any filename
    //
    // // Create file containing text 'hello world'
    // $fs->create_file_from_string($fileinfo, 'hello world');
    copy($path . $filename, 'C:\Users\svenw\Downloads\export.zip');
    // https://docs.moodle.org/dev/File_API
  }

  /**
   * Function to generate odt file
   * @param array $newcontent
   * @param array $newmeta
   * @param array $newsettings
   * @param array $newstyles
   * @return string filepath
   */
  protected function odt($newcontent = array(), $newmeta = array(), $newsettings = array(), $newstyles = array()) {
    // Default array for content.xml
    $content["declaration"] = '<?xml version="1.0" encoding="UTF-8"?>';
    $content[0]["name"] = 'office:document-content';
    $content[0]["att"] = array(
                          'xmlns:office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
                          'xmlns:text' => 'urn:oasis:names:tc:opendocument:xmlns:text:1.0',
                          'office:version' => 1.2,
                        );
    $content[0]["val"][0]["name"] = 'office:body';
    $content[0]["val"][0]["val"][0]["name"] = 'office:text';

    // Default array for meta.xml
    $meta["declaration"] = '<?xml version="1.0" encoding="UTF-8"?>';
    $meta[0]["name"] = 'office:document-meta';
    $meta[0]["att"] = array(
                          'xmlns:office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
                          'xmlns:meta' => 'urn:oasis:names:tc:opendocument:xmlns:meta:1.0',
                          'office:version' => 1.2,
                        );
    $meta[0]["val"][0]["name"] = 'office:meta';

    // Default array for settings.xml
    $settings["declaration"] = '<?xml version="1.0" encoding="UTF-8"?>';
    $settings[0]["name"] = 'office:document-settings';
    $settings[0]["att"] = array(
                          'xmlns:office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
                          'xmlns:config' => 'urn:oasis:names:tc:opendocument:xmlns:config:1.0',
                          'office:version' => 1.2,
                        );
    $settings[0]["val"][0]["name"] = 'office:settings';

    // Default array for styles.xml
    $styles["declaration"] = '<?xml version="1.0" encoding="UTF-8"?>';
    $styles[0]["name"] = 'office:document-styles';
    $styles[0]["att"] = array(
                          'xmlns:office' => 'urn:oasis:names:tc:opendocument:xmlns:office:1.0',
                          'xmlns:style' => 'urn:oasis:names:tc:opendocument:xmlns:style:1.0',
                          'xmlns:fo' => 'urn:oasis:nasmes:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
                          'office:version' => 1.2,
                        );
    $styles[0]["val"][0]["name"] = 'office:styles';

    // Default array for META-INF/manifest.xml
    $manifest["declaration"] = '<?xml version="1.0" encoding="UTF-8"?>';
    $manifest[0]["name"] = 'manifest:manifest';
    $manifest[0]["att"] = array(
                          'xmlns:manifest' => 'urn:oasis:names:tc:opendocument:xmlns:manifest:1.0',
                          'manifest:version' => 1.2,
                        );
    $manifest[0]["val"][0]["name"] = 'manifest:file-entry';
    $manifest[0]["val"][0]["att"] = array(
                                      'manifest:media-type' => 'application/vnd.oasis.opendocument.text',
                                      'manifest:version' => 1.2,
                                      'manifest:full-path' => '/',
                                    );
    $manifest[0]["val"][1]["name"] = 'manifest:file-entry';
    $manifest[0]["val"][1]["att"] = array(
                                      'manifest:media-type' => 'text/xml',
                                      'manifest:full-path' => 'content.xml',
                                    );
    $manifest[0]["val"][2]["name"] = 'manifest:file-entry';
    $manifest[0]["val"][2]["att"] = array(
                                      'manifest:media-type' => 'text/xml',
                                      'manifest:full-path' => 'settings.xml',
                                    );
    $manifest[0]["val"][3]["name"] = 'manifest:file-entry';
    $manifest[0]["val"][3]["att"] = array(
                                      'manifest:media-type' => 'text/xml',
                                      'manifest:full-path' => 'styles.xml',
                                    );
    $manifest[0]["val"][4]["name"] = 'manifest:file-entry';
    $manifest[0]["val"][4]["att"] = array(
                                      'manifest:media-type' => 'text/xml',
                                      'manifest:full-path' => 'meta.xml',
                                    );

    // Extend arrays
    $content = array_replace_recursive($content, $newcontent);
    $meta = array_replace_recursive($meta, $newmeta);
    $settings = array_replace_recursive($settings, $newsettings);
    $styles = array_replace_recursive($styles, $newstyles);

    // Generate xml
    $path = make_request_directory() . "/exporsubmissionplugin/";
    $filename = 'generated.odt';
    mkdir($path, 0777, true); // Generate temp path
    $odt = new ZipArchive();
    $odt->open($path . $filename, ZipArchive::CREATE); // Initialise zip/odt

    $odt->addFromString('content.xml', $this->array_to_xml($content)); // Content
    $odt->addFromString('meta.xml', $this->array_to_xml($meta)); // meta
    $odt->addFromString('settings.xml', $this->array_to_xml($settings)); // settings
    $odt->addFromString('styles.xml', $this->array_to_xml($styles)); // styles

    $odt->addFromString('mimetype', 'application/vnd.oasis.opendocument.text'); // mimetype

    $odt->addEmptyDir("META-INF");
    $odt->addFromString("META-INF/manifest.xml", $this->array_to_xml($manifest));

    $odt->close();

    // Return temp path to copy
    return $path . '/' . $filename;
  }

  /**
   * Function to generate xml
   * @param array $array
   * @return string
   */
  private function array_to_xml($array) {
    // Start xml
    $xml = '';

    // Loop array
    foreach($array as $key=>$info) {
      // Check declaration
      if($key == "declaration") {
        $xml .= $info;
      }else {
        if(array_key_exists('att', $info) && is_array($info["att"])) {
          $atts = " "; // Initial whitspace
          $atts .= implode(' ', array_map(function ($v, $k) {return $k . '="' . $v . '"';}, $info["att"], array_keys($info["att"]))); // Get all attributes
        }

        if(array_key_exists('val', $info) && empty($info["val"]) && $info["val"] != 0) {
          $endTag = false; // End has no closing tag
        }else {
          $endTag = true; // End requires closing tag
        }

        // Create tag
        $xml .= '<' . $info["name"] . ($atts ?? '') . ($endTag ? '>' : '/>');

        // Get value
        if(array_key_exists('val', $info) && is_array($info["val"])) {
          $xml .= $this->array_to_xml($info["val"]); // Display value
        }elseif($endTag) {
          $xml .= $info["val"] ?? '';
        }

        // Add end tag
        if($endTag) {
          $xml .= '</' . $info["name"] . '>';
        }
      }
    }

    // Retrun xml
    return $xml;
  }
}
?>
