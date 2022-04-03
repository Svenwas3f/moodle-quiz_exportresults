<?php
class quiz_exportresults_report extends quiz_default_report {
  public function display($cm, $course, $quiz) {
    // Display page
    $this->print_header_and_tabs($cm, $quiz, $course, 'quiz_exportresults');

    // ...
  }
}
?>
