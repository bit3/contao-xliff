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

        if ($this->Input->get('act') == 'generate') {
            $strModule   = $this->Input->get('module');
            $strComp     = $this->Input->get('comp');
            $strLanguage = $this->Input->get('language');

            $strSourceFile = sprintf('system/modules/%s/languages/%s/%s.php',
                                     $strModule,
                                     $strLanguage,
                                     $strComp);
            $strTargetFile = sprintf('system/modules/%s/languages/%s/%s.xlf',
                                     $strModule,
                                     $strLanguage,
                                     $strComp);

            if (!file_exists(TL_ROOT . '/' . $strSourceFile)) {
                $_SESSION['TL_ERROR'][] = 'Missing source file ' . $strSourceFile . '!';
            }

            else {
                unset($GLOBALS['TL_LANG']);
                require(TL_ROOT . '/' . $strSourceFile);
                $arrSourceLanguage = deserialize(serialize($GLOBALS['TL_LANG']));

                $doc = Xliff::getInstance()->generateXliff(
                    $strSourceFile,
                    'php',
                    filemtime(TL_ROOT . '/' . $strSourceFile),
                    '',
                    $strLanguage,
                    $arrSourceLanguage,
                    $strLanguage
                );

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

            $this->redirect('contao/main.php?do=xliff');
        }

        $GLOBALS['TL_CSS']['xliff']        = 'system/modules/xliff/public/backend.css';
        $GLOBALS['TL_JAVASCRIPT']['xliff'] = 'system/modules/xliff/public/backend.js';

        $arrFiles     = array();
        $arrLanguages = array();
        foreach ($arrModules as $strModule) {
            if (in_array($strModule, array('calendar', 'comments', 'core', 'devtools', 'faq', 'listing', 'news', 'newsletter', 'repository'))) {
                continue;
            }

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

                            if (preg_match('#\.(xlf|php)$#', $strLanguageFile, $arrMatch)
                            ) {
                                // extract the language key (first part of the TL_LANG array
                                $strLanguageKey = basename($strLanguageFile, '.' . $arrMatch[1]);

                                // store the php file timestamp
                                $arrFiles[$strModule][$strLanguageKey][$strLanguage][$arrMatch[1]]['mtime'] = filemtime($strLanguageFile);
                            }
                        }
                    }
                }
            }
        }

        natcasesort($arrModules);
        ksort($arrFiles);
        $arrLanguages = array_unique(array_filter(array_values($arrLanguages)));
        natcasesort($arrLanguages);

        $this->Template->modules   = $arrModules;
        $this->Template->files     = $arrFiles;
        $this->Template->languages = $arrLanguages;
    }

    protected function mkdirs($path)
    {
        if (!is_dir(TL_ROOT . '/' . $path)) {
            $this->mkdirs(dirname($path));
            $this->Files->mkdir($path);
        }
    }
}