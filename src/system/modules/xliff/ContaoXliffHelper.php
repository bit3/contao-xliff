<?php

class ContaoXliffHelper
    extends System
    implements XliffHelper
{
    /**
     * @var ContaoXliffHelper
     */
    protected static $objInstance = null;

    /**
     * Get the current instance.
     *
     * @static
     * @return ContaoXliffHelper
     */
    public static function getInstance()
    {
        if (self::$objInstance === null) {
            self::$objInstance = new ContaoXliffHelper();
        }
        return self::$objInstance;
    }

    /**
     * The xliff instance.
     *
     * @var Xliff
     */
    protected $xliff;

    /**
     * Create an xliff instance.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->xliff = new Xliff($this);
    }

    /**
     * @param $strName
     * @param $strLanguage
     */
    public function hookLoadLanguageFile($strName,
                                         $strLanguage)
    {
        $arrModules = $this->Config
            ->getActiveModules();

        foreach ($arrModules as $strModule) {
            $strFile = sprintf('%s/system/modules/%s/languages/%s/%s.xliff',
                               TL_ROOT,
                               $strModule,
                               $strLanguage,
                               $strName);

            // parse the xliff file and append to language array
            $this->xliff->parseXliff($strFile,
                                     $GLOBALS['TL_LANG']);
        }
    }

    public function getLanguageArray($strName,
                                     $strLanguage)
    {
        // clean up before loading languages
        unset($GLOBALS['TL_LANG'][$strName]);

        // force load the language file in the specific source language
        $this->loadLanguageFile($strName,
                                $strLanguage,
                                true);

        // make a deep copy of the source language
        return unserialize(serialize($GLOBALS['TL_LANG'][$strName]));
    }
}
