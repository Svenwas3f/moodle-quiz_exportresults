# Exportresults - Moodle Quiz PlugIn
The Moodle quiz activity gives unlimited access to online testing. A lot of question types provide an included check but some question types (e.g., Essay) are not designed for an auto check. Correcting those question types online in Moodle is unwieldy. With this plugin the teacher can export students answer as an ODT file. The ODT filetype was chosen because it is open source and the filetypes simplicity and the wide range of possibilities. With the ODT filetype it is possible to modify some layout options during the export process. At the same place, the teacher is able to select what group(s) he wants to export. The export has a meaningful folder structure that makes it easier to find someone’s answer.

## Installation
Install plugin to mod/quiz/reports via Moodle PluginDB https://moodle.org/plugins/quiz_exportresults. More details at https://docs.moodle.org/39/en/Installing_plugins#Installing_a_plugin or follow the instruction given from moodle:  

 - Make sure you have all the required versions.
 - Download and unpack the module.
 - Place the folder (eg "myreport") in the "mod/quiz/report" subdirectory.
 - Visit http://yoursite.com/admin to finish the installation  

You can install using git. Type this commands in the root of your Moodle install.  
``git clone https://github.com/Svenwas3f/moodle-quiz_exportresults.git mod/quiz/report/exportresults``  
Then run the moodle update process Site administration > Notifications
