<?php

// Copyright (c) 2019 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

/**
 * Plugin Creation of test export
 */
class ilEssayTestExportPlugin extends ilTestExportPlugin
{

    /**
     * Get Plugin Name. Must be same as in class name il<Name>Plugin
     * and must correspond to plugins subdirectory name.
     *
     * Must be overwritten in plugin class of plugin
     *
     * @return    string    Plugin Name
     */
    function getPluginName()
    {
        return "EssayTestExport";
    }

    /**
     * This method is called if the user wants to export a test of YOUR export type
     * If you throw an exception of type ilException with a respective language variable, ILIAS presents a translated failure message.
     *
     * @param string $export_path The path to store the export file
     * @throws Exception
     */
    protected function buildExportFile(ilTestExportFilename $export_path)
    {
        $filename = $export_path->getPathname("zip", "textinputs");
        $directory = dirname($filename);
        ilUtil::makeDirParents($directory);

        $this->includeClass('class.ilEssayTestExport.php');
        $exportObj = new ilEssayTestExport($this,  $this->getTest());
        $exportObj->createExportFile($filename);
    }

    /**
     * A unique identifier which describes your export type, e.g. imsm
     * There is currently no mapping implemented concerning the filename.
     * Feel free to create csv, xml, zip files ....
     *
     * @return string
     */
    protected function getFormatIdentifier()
    {
        return "estex";
    }

    /**
     * This method should return a human readable label for your export
     * @return string
     */
    public function getFormatLabel()
    {
        return $this->txt('format_label');
    }
}