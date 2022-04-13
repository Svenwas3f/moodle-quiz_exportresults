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

require_once($CFG->dirroot . '/mod/quiz/report/exportresults/exportresults_form.php');

class quiz_exportresults_report extends quiz_default_report {
  /**
   * Function to display report
   * @param $cm the course-module for this quiz.
   * @param $course the coures we are in.
   * @param $quiz this quiz.
   */
  public function display($quiz, $cm, $course) {
    // Get database
    global $DB, $PAGE, $context;

    // Initialise form
    $mform = new quiz_exportresults_form($PAGE->url);

    // Check if export requested
    if($data = $mform->get_data()) {
      // Check if groups activated
      if(groups_get_course_groupmode($course) == false || empty($data->groups)) {
        $groups = array((object) array("id" => 0, "courseid" => $course->id, "name" => $course->fullname) ); // No groups activated
      }else {
        foreach($data->groups as $group) {
          $groups[] = groups_get_group($group);
        }
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
        if(groups_get_course_groupmode($course) == false || empty($data->groups)) {
          $members = get_enrolled_users($context); // Get members of course
        }else {
          $members = groups_get_members($group->id); // Get members of group
        }

        foreach($members as $member) {
          // Options: highest grade, first attempt, last attempts, all attempts
          switch($data->attempts) {
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
            $params['questionusageid'] = $attempt->uniqueid; // uniqueid
            $questions = $DB->get_records_select('question_attempts', 'questionusageid=:questionusageid', $params, 'timemodified DESC'); // Request attempts

            // Prepare values for odt
            $lineheight = floatval($data->lineheight) * 100 . "%";
            $content[0]["val"][0]["val"][0]["name"] = 'office:automatic-styles';
            $content[0]["val"][0]["val"][0]["val"][0]["name"] = 'offce:style';
            $content[0]["val"][0]["val"][0]["val"][0]["att"]["styel:name"] = 'Standard';
            $content[0]["val"][0]["val"][0]["val"][0]["val"][0]["name"] = 'style:paragraph-properties';
            $content[0]["val"][0]["val"][0]["val"][0]["val"][0]["att"]['fo:line-height'] = $lineheight; // line height
            
            $count = 0;
            foreach($questions as $question) {
              if($data->questions == 1) {
                foreach(preg_split("/\r\n|\n|\r/", $question->questionsummary) as $line) {
                  $content[0]["val"][0]["val"][1]["val"][$count]["name"] = 'text:p';
                  $content[0]["val"][0]["val"][1]["val"][$count]["att"] = array('text:style-name' => 'Standard');
                  $content[0]["val"][0]["val"][1]["val"][$count]["val"] = $line;
                  $count++;
                }
              }

              // Display response
              foreach(preg_split("/\r\n|\n|\r/", $question->responsesummary) as $line) {
                $content[0]["val"][0]["val"][1]["val"][$count]["name"] = 'text:p';
                $content[0]["val"][0]["val"][1]["val"][$count]["att"] = array('text:style-name' => 'Standard');
                $content[0]["val"][0]["val"][1]["val"][$count]["val"] = $line;
                $count++;
              }
            }

            $meta[0]["val"][0]["val"][0]["name"] = 'meta:initial-creator';
            $meta[0]["val"][0]["val"][0]["val"] = 'Moodle Exportresults Plugin';
            $meta[0]["val"][0]["val"][1]["name"] = 'meta:creation-date';
            $meta[0]["val"][0]["val"][1]["val"] = date("Y-m-d\TH:i:sp");
            $meta[0]["val"][0]["val"][3]["name"] = 'meta:creator';
            $meta[0]["val"][0]["val"][3]["val"] = 'Moodle Exportresults Plugin';

            $styles[0]["val"][0]["name"] = 'office:font-face-decls';
            $styles[0]["val"][0]["val"][0]["name"] = 'style:font-face';
            $styles[0]["val"][0]["val"][0]["att"]["style:name"] = 'Times New Roman';
            $styles[0]["val"][0]["val"][0]["att"]["svg:font-family"] = '&apos;Times New Roman&apos;';
            $styles[0]["val"][0]["val"][1]["name"] = 'style:font-face';
            $styles[0]["val"][0]["val"][1]["att"]["style:name"] = 'Arial';
            $styles[0]["val"][0]["val"][1]["att"]["svg:font-family"] = 'Arial';
            $styles[0]["val"][0]["val"][1]["name"] = 'style:font-face';
            $styles[0]["val"][0]["val"][1]["att"]["style:name"] = 'Frutiger LT Com 55 Roman';
            $styles[0]["val"][0]["val"][1]["att"]["svg:font-family"] = 'Frutiger LT Com 55 Roman';

            $fontsize = preg_match('/[a-z]/i', $data->fontsize) ? $data->fontsize : $data->fontsize . "pt";
            $styles[0]["val"][1]["name"] = 'office:styles';
            $styles[0]["val"][1]["val"][0]["name"] = 'style:default-style';
            $styles[0]["val"][1]["val"][0]["att"]["style:family"] = 'paragraph';
            $styles[0]["val"][1]["val"][0]["val"][0]["name"] = 'style:text-properties';
            $styles[0]["val"][1]["val"][0]["val"][0]["att"]['fo:font-size'] = $fontsize; // Fontsize
            $styles[0]["val"][1]["val"][0]["val"][0]["att"]['style:font-name'] = $data->fontfamily; // Font family
            $styles[0]["val"][1]["val"][0]["val"][1]["name"] = 'style:paragraph-properties';
            $styles[0]["val"][1]["val"][0]["val"][1]["att"]['fo:line-height'] = $lineheight; // line height

            $margintop = preg_match('/[a-z]/i', $data->margintop) ? $data->margintop : $data->margintop . "cm"; // Prepare values
            $marginright = preg_match('/[a-z]/i', $data->marginright) ? $data->marginright : $data->marginright . "cm"; // Prepare values
            $marginbottom = preg_match('/[a-z]/i', $data->marginbottom) ? $data->marginbottom : $data->marginbottom . "cm"; // Prepare values
            $marginleft = preg_match('/[a-z]/i', $data->marginleft) ? $data->marginleft : $data->marginleft . "cm"; // Prepare values
            $styles[0]["val"][2]["name"] = 'office:automatic-styles';
            $styles[0]["val"][2]["val"][0]["name"] = 'style:page-layout';
            $styles[0]["val"][2]["val"][0]["att"]["style:name"] = 'mdl1';
            $styles[0]["val"][2]["val"][0]["val"][0]["name"] = 'style:page-layout-properties';
            $styles[0]["val"][2]["val"][0]["val"][0]["att"]["fo:margin-top"] = $margintop; // Margin
            $styles[0]["val"][2]["val"][0]["val"][0]["att"]["fo:margin-right"] = $marginright; // Margin
            $styles[0]["val"][2]["val"][0]["val"][0]["att"]["fo:margin-bottom"] = $marginbottom; // Margin
            $styles[0]["val"][2]["val"][0]["val"][0]["att"]["fo:margin-left"] = $marginleft; // Margin

            $styles[0]["val"][3]["name"] = 'office:master-styles';
            $styles[0]["val"][3]["val"][0]["name"] = 'style:master-page';
            $styles[0]["val"][3]["val"][0]["att"]["style:name"] = 'Standard';
            $styles[0]["val"][3]["val"][0]["att"]["style:page-layout-name"] = 'mdl1';

            // Generate odt and add to export
            $odt = $this->odt(($content ?? array()), $meta, array(), $styles);

            $pattern = '/[^A-Za-z0-9-_]/'; // Pattern to secure filename
            $export->addFile($odt, $group->name . "/" . preg_replace($pattern, '', $member->username) . "_" . preg_replace($pattern, '', $member->firstname) . "_" . preg_replace($pattern, '', $member->lastname) . ".odt");
          }
        }
      }

      // Convert to zip and enable downlod
      $export->close();

      // Copy odt into download Zip
      $fs = get_file_storage();

      // Prepare file record object
      $fileinfo = array(
          'contextid' => $context->id,         // ID of context
          'component' => 'quiz_exportresults', // usually = table name
          'filearea' => 'export',              // usually = table name
          'itemid' => $quiz->id,               // usually = ID of row in table
          'filepath' => '/',                   // any path beginning and ending in /
          'filename' => 'export.zip');         // any filename

      if($fs->file_exists($fileinfo["contextid"], $fileinfo["component"], $fileinfo["filearea"], $fileinfo["itemid"], $fileinfo["filepath"], $fileinfo["filename"])) {
        $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'])->delete(); // Remove file
      }

      // Create file in File API
      $exportfile = $fs->create_file_from_pathname($fileinfo, $path . $filename);

      // Serve to user
      send_stored_file($exportfile, 86400, 0, true);
    }

    // Display page
    $this->print_header_and_tabs($cm, $course, $quiz, 'quiz_exportresults');

    // Display notification
    if(!quiz_has_attempts($quiz->id)) {
      \core\notification::error( get_string('exportresults_no_attempts', 'quiz_exportresults') );
    }

    // Display form
    $mform->display();
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
    $content[0]["val"][0]["val"][1]["name"] = 'office:text';

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
                          'xmlns:fo' => 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0',
                          'xmlns:svg' => 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0',
                          'office:version' => 1.2,
                        );

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
   * @return string xml string
   */
  private function array_to_xml($array) {
    // Start xml
    $xml = '';

    // Loop array
    foreach($array as $key=>$info) {
      // Check declaration
      if($key === "declaration") {
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
