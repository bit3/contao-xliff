<?php

class ModuleXliff
    extends BackendModule
{
    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'be_xliff';

    public function __construct(DataContainer $objDc = null)
    {
        parent::__construct($objDc);

        $this->import('Files');
    }

    /**
     * Compile the current element
     */
    protected function compile()
    {
        $arrModules = $this->Config->getActiveModules();

        if ($this->Input->post('FORM_SUBMIT') == 'xliff_generate') {
            $strModule         = $this->Input->post('module');
            $strLang           = $this->Input->post('lang');
            $strSourceLanguage = $this->Input->post('source-language');
            $strTargetLanguage = $this->Input->post('target-language');

            $strSourceFile = sprintf('system/modules/%s/languages/%s/%s.php',
                                     $strModule,
                                     $strSourceLanguage,
                                     $strLang);
            $strTargetFile = sprintf('system/modules/%s/languages/%s/%s.xliff',
                                     $strModule,
                                     $strTargetLanguage,
                                     $strLang);

            if (!file_exists(TL_ROOT . '/' . $strSourceFile)) {
                $_SESSION['TL_ERROR'][] = 'Missing source file ' . $strSourceFile . '!';
            }

            else if (file_exists(TL_ROOT . '/' . $strTargetFile)) {
                $_SESSION['TL_ERROR'][] = 'Target file ' . $strTargetFile . ' allready exists!';
            }

            else {
                unset($GLOBALS['TL_LANG'][$strLang]);
                require(TL_ROOT . '/' . $strSourceFile);
                $arrSourceLanguage = deserialize(serialize($GLOBALS['TL_LANG'][$strLang]));

                $doc = Xliff::getInstance()
                    ->generateXliff($strSourceFile,
                                    'php',
                                    filemtime(TL_ROOT . '/' . $strSourceFile),
                                    $strLang,
                                    $strSourceLanguage,
                                    $arrSourceLanguage,
                                    $strTargetLanguage);

                // output should formated
                $doc->formatOutput = true;

                // generate the xml for output
                $xml = $doc->saveXML();

                // generate directories
                $this->mkdirs(dirname($strTargetFile));

                // write the file
                $objFile = new File($strTargetFile);
                $objFile->write($xml);
                $objFile->close();

                $_SESSION['TL_INFO'][] = sprintf('Create new file %s.', $strTargetFile);
            }

            $this->reload();
        }

        if ($this->Input->post('FORM_SUBMIT') == 'xliff_update') {
            $strModule         = $this->Input->post('module');
            $strLang           = $this->Input->post('lang');
            $strTargetLanguage = $this->Input->post('target-language');

            $strTargetFile = sprintf('system/modules/%s/languages/%s/%s.xliff',
                                     $strModule,
                                     $strTargetLanguage,
                                     $strLang);

            if (!file_exists(TL_ROOT . '/' . $strTargetFile)) {
                $_SESSION['TL_ERROR'][] = 'Missing xliff file ' . $strTargetFile . '!';
            }

            // load the xliff document
            $doc = new DOMDocument();
            $doc->load($strTargetFile);

            // create xpath object
            $xpath = new DOMXPath($doc);

            // register namespace to xpath engine
            $xpath->registerNamespace('xliff',
                                      Xliff::NS);

            // search file elements
            /** @var DOMNodeList $transUnits */
            $files = $xpath->query('/xliff:xliff/xliff:file');

            // walk over the file elements
            for ($i = 0; $i < $files->length; $i++) {
                /** @var DOMElement $file */
                $file = $files->item($i);

                $strSourceFile = $file->getAttribute('original');

                if (!file_exists(TL_ROOT . '/' . $strSourceFile)) {
                    $_SESSION['TL_ERROR'][] = 'Missing source file ' . $strSourceFile . '!';
                }

                else {
                    unset($GLOBALS['TL_LANG'][$strLang]);
                    require(TL_ROOT . '/' . $strSourceFile);
                    $arrSourceLanguage = deserialize(serialize($GLOBALS['TL_LANG'][$strLang]));

                    $doc = Xliff::getInstance()
                        ->updateXliffSource($doc,
                                        $strLang,
                                    filemtime(TL_ROOT . '/' . $strSourceFile),
                                        $strSourceLanguage,
                                        $arrSourceLanguage);


                    // output should formated
                    $doc->formatOutput = true;

                    // generate the xml for output
                    $xml = $doc->saveXML();

                    // generate directories
                    $this->mkdirs(dirname($strTargetFile));

                    // write the file
                    $objFile = new File($strTargetFile);
                    $objFile->write($xml);
                    $objFile->close();

                    $_SESSION['TL_INFO'][] = sprintf('Create new file %s.', $strTargetFile);
                }
            }

            $this->reload();
        }

        $GLOBALS['TL_CSS']['xliff']        = 'system/modules/xliff/public/backend.css';
        $GLOBALS['TL_JAVASCRIPT']['xliff'] = 'system/modules/xliff/public/backend.js';

        $arrFiles     = array();
        $arrLanguages = array();
        foreach ($arrModules as $strModule) {
            $strLanguagesPath = TL_ROOT . '/system/modules/' . $strModule . '/languages';
            if (is_dir($strLanguagesPath)) {
                $arrModuleLanguages = scan($strLanguagesPath);

                foreach ($arrModuleLanguages as $strLanguage) {
                    $arrLanguages[] = $strLanguage;

                    $strLanguagePath = $strLanguagesPath . '/' . $strLanguage;

                    if (is_dir($strLanguagePath)) {
                        $arrLanguageFiles = scan($strLanguagePath);

                        foreach ($arrLanguageFiles as $strLanguageFile) {
                            // absolute path to the php file
                            $strLanguageFile = $strLanguagePath . '/' . $strLanguageFile;

                            if (preg_match('#\.php$#',
                                           $strLanguageFile)
                            ) {
                                // extract the language key (first part of the TL_LANG array
                                $strLanguageKey = basename($strLanguageFile,
                                                           '.php');

                                // store the php file timestamp
                                $arrFiles[$strModule][$strLanguageKey]['php'][$strLanguage]['mtime'] = filemtime($strLanguageFile);
                            }

                            if (preg_match('#\.xliff$#',
                                           $strLanguageFile)
                            ) {
                                // extract the language key (first part of the TL_LANG array
                                $strLanguageKey = basename($strLanguageFile,
                                                           '.xliff');

                                // store the xliff file timestamp
                                $arrFiles[$strModule][$strLanguageKey]['xliff'][$strLanguage]['mtime'] = filemtime($strLanguageFile);

                                // load the xliff document
                                $doc = new DOMDocument();
                                $doc->load($strLanguageFile);

                                // create xpath object
                                $xpath = new DOMXPath($doc);

                                // register namespace to xpath engine
                                $xpath->registerNamespace('xliff',
                                                          Xliff::NS);

                                // search file elements
                                /** @var DOMNodeList $transUnits */
                                $files = $xpath->query('/xliff:xliff/xliff:file');

                                // walk over the file elements
                                for ($i = 0; $i < $files->length; $i++) {
                                    /** @var DOMElement $file */
                                    $file = $files->item($i);

                                    $strOriginal = $file->getAttribute('original');
                                    $intDate = strtotime($file->getAttribute('date'));
                                    $strSourceLanguage = $file->getAttribute('source-language');

                                    // check original timestamp
                                    if (file_exists(TL_ROOT . '/' . $strOriginal)) {
                                        if (filemtime(TL_ROOT . '/' . $strOriginal) <= $intDate) {
                                            $arrFiles[$strModule][$strLanguageKey]['xliff'][$strLanguage]['status'] = 'uptodate';
                                        }
                                        else {
                                            $arrFiles[$strModule][$strLanguageKey]['xliff'][$strLanguage]['status'] = 'outdated';
                                        }
                                    }
                                    else {
                                        $arrFiles[$strModule][$strLanguageKey]['xliff'][$strLanguage]['status'] = 'missingsource';
                                    }

                                    $arrFiles[$strModule][$strLanguageKey]['php'][$strSourceLanguage]['translations'][] = $strLanguage;
                                }

                                unset($files, $xpath, $doc);
                            }
                        }
                    }
                }
            }
        }

        natcasesort($arrModules);
        ksort($arrFiles);
        natcasesort($arrLanguages);

        $this->Template->modules   = $arrModules;
        $this->Template->files     = $arrFiles;
        $this->Template->languages = array_values(array_unique($arrLanguages));
        // $this->Template->languages = $this->getLanguages();
    }

    protected function mkdirs($path)
    {
        if (!is_dir(TL_ROOT . '/' . $path)) {
            $this->mkdirs(dirname($path));
            $this->Files->mkdir($path);
        }
    }
}