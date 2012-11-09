<?php

class ContaoXliffHelper
    extends System
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

    protected function __construct()
    {
        parent::__construct();

        $this->import('Xliff');
    }

    /**
     * @param $strName
     * @param $strLanguage
     */
    public function hookLoadLanguageFile($strName,
                                         $strLanguage)
    {
        if (version_compare(VERSION, '3.1', '<')) {
            $arrModules = $this->Config
                ->getActiveModules();

			foreach ($arrModules as $strModule) {
				$strPhpFile = sprintf('%s/system/modules/%s/languages/%s/%s.php',
								   TL_ROOT,
								   $strModule,
								   $strLanguage,
								   $strName);
				$strXliffFile = sprintf('%s/system/modules/%s/languages/%s/%s.xliff',
								   TL_ROOT,
								   $strModule,
								   $strLanguage,
								   $strName);

				if (!file_exists($strPhpFile) && file_exists($strXliffFile)) {
					// parse the xliff file and append to language array
					$this->xliff->parseXliff($strXliffFile,
											 $GLOBALS['TL_LANG']);
				}
			}
        }
    }
}
