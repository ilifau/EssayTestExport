# TestArchiveCreator

Copyright (c) 2019 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
GPLv3, see LICENSE

Author: Fred Neumann <fred.neumann@ili.fau.de>

This plugin for the LMS ILIAS open source allows the export the pure texts answers of essay questions in a test
to be compared with a plagiarism software, e.g. WCopyfind.

PLUGIN INSTALLATION
-------------------

1. Put the content of the plugin directory in a subdirectory under your ILIAS main directory:
Customizing/global/plugins/Modules/Test/Export/EssayTestExport

2. Open ILIAS > Administration > Plugins

3. Updateand Activate the plugin


USAGE
-----

1. Mover to the tab "Export" in the test.

2. Chose "Create Text Inputs Export"

3. Download the file from the list of export files

The exported archive will have one folder per question and the participant's inputs in separate .txt files.
