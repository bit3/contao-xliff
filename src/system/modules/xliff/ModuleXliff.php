<?php

class ModuleXliff
    extends BackendModule
{
    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'be_xliff';

    /**
     * Compile the current element
     */
    protected function compile()
    {
        if ($this->Input->post('FORM_SUBMIT') == 'xliff_export') {
            $strName           = $this->Input->post('name');
            $strSourceLanguage = $this->Input->post('source-language');
            $strTargetLanguage = $this->Input->post('target-language');

            if (!$strSourceLanguage) {
                $strSourceLanguage = $GLOBALS['TL_LANGUAGE'];
            }

            if ($strName) {
                // create the xliff object
                $xliff = new Xliff(ContaoXliffHelper::getInstance());

                // generate the xliff document
                $doc = $xliff->generateXliff($strName,
                                             $strSourceLanguage,
                                             $strTargetLanguage);

                // output should formated
                $doc->formatOutput = true;

                // generate the xml for output
                $xml = $doc->saveXML();

                // Open the "save as …" dialogue
                header('Content-Type: text/xml; charset=utf-8');
                header('Content-Transfer-Encoding: binary');
                header('Content-Disposition: attachment; filename="' . $strName . '.xliff"');
                header('Content-Length: ' . strlen($xml));
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Expires: 0');
                header('Connection: close');

                // send the xml
                echo $xml;
                exit;
            }

            $this->reload();
        }

        if ($this->Input->post('FORM_SUBMIT') == 'xliff_import') {
            $file = $_FILES['document'];

            // create the xliff instance
            $xliff = new Xliff(ContaoXliffHelper::getInstance());

            // parse the uploaded file
            $arrLanguage = $xliff->parseXliff($file['tmp_name']);

            // generate the php code
            $php = $xliff->generatePhpFromArray($arrLanguage);

            // get the first key of languages
            $strKey = array_shift(array_keys($arrLanguage));

            // Open the "save as …" dialogue
            header('Content-Type: text/php; charset=utf-8');
            header('Content-Transfer-Encoding: binary');
            header('Content-Disposition: attachment; filename="' . $strKey . '.php"');
            header('Content-Length: ' . strlen($php));
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Expires: 0');
            header('Connection: close');

            echo $php;
            exit;
        }

        $this->Template->languages = $this->getLanguages();
    }
}