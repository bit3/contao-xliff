<?php

class Xliff
{
    /**
     * xliff Namespace
     */
    const NS = 'urn:oasis:names:tc:xliff:document:1.2';

    /**
     * @var Xliff
     */
    protected static $objInstance = null;

    /**
     * @static
     * @return Xliff
     */
    public static function getInstance()
    {
        if (self::$objInstance === null) {
            self::$objInstance = new Xliff();
        }
        return self::$objInstance;
    }

    /**
     * Singleton constructor.
     */
    protected function __construct()
    {
    }

    /**
     * Parse an xliff document into an array.
     *
     * @param       $strFile
     * @param array $arrLanguage
     *
     * @return array
     */
    public function parseXliff($strFile,
                               &$arrLanguage = array())
    {
        if (file_exists($strFile)) {
            // load the xliff document
            $doc = new DOMDocument();
            $doc->load($strFile);

            // create xpath object
            $xpath = new DOMXPath($doc);

            // register namespace to xpath engine
            $xpath->registerNamespace('xliff',
                                      self::NS);

            // search trans-unit elements
            /** @var DOMNodeList $transUnits */
            $transUnits = $xpath->query('/xliff:xliff/xliff:file/xliff:body/xliff:trans-unit');

            // walk over the trans-unit elements
            for ($i = 0; $i < $transUnits->length; $i++) {
                // get current trans-unit element
                /** @var DOMElement $transUnit */
                $transUnit = $transUnits->item($i);

                // search for the target element
                /** @var DOMElement $trans */
                $trans = $xpath
                    ->query('xliff:target',
                            $transUnit)
                    ->item(0);

                // use source element if target element not found
                if (!$trans) {
                    // search for the source element
                    /** @var DOMElement $target */
                    $trans = $xpath
                        ->query('xliff:source',
                                $transUnit)
                        ->item(0);
                }

                // get the path
                $path = explode('.',
                                $transUnit->getAttribute('id'));

                if (count($path)) {
                    // walk over the path
                    $refLanguage = &$arrLanguage;
                    foreach ($path as $part) {
                        // convert into numeric key
                        if (is_numeric($part)) {
                            // key is a float
                            if (strpos($part,
                                       '.') !== false
                            ) {
                                $part = (float) $part;
                            } // key is an integer
                            else {
                                $part = (int) $part;
                            }
                        }

                        // add key if not exists
                        if (!isset($refLanguage[$part])) {
                            $refLanguage[$part] = array();
                        }

                        $refLanguage = &$refLanguage[$part];
                    }

                    // set the value
                    $refLanguage = $xpath->evaluate('string(text())',
                                                    $trans);
                }
            }
        }

        return $arrLanguage;
    }

    /**
     * Export language file into an xliff document.
     *
     * @param string $strName
     * @param string $strSourceLanguage
     *
     * @return DOMDocument
     */
    public function generateXliff($strOriginal,
                                  $strDataType,
                                  $intDate,
                                  $strName,
                                  $strSourceLanguage,
                                  array $arrSourceLanguage,
                                  $strTargetLanguage,
                                  array $arrTargetLanguage = array())
    {
        // create the new document
        $doc = new DOMDocument();

        // create the xliff root element
        $xliff = $doc->createElement('xliff');
        // little hack to workaround the unusable php namespace handling
        $xliff->setAttribute('xmlns',
                             self::NS);
        // add the xliff version
        $xliff->setAttribute('version',
                             '1.2');
        $doc->appendChild($xliff);

        // create the file element
        $file = $doc->createElement('file');
        $file->setAttribute('original',
                            $strOriginal);
        $file->setAttribute('source-language',
                            $strSourceLanguage);
        $file->setAttribute('datatype',
                            $strDataType);
        $file->setAttribute('date',
                            date('c',
                                 $intDate));
        $file->setAttribute('target-language',
                            $strTargetLanguage);
        $xliff->appendChild($file);

        // create the body element
        $body = $doc->createElement('body');
        $file->appendChild($body);

        // append the translation units
        $this->generateXliffUnits($doc,
                                  $body,
                                  $strName,
                                  $strSourceLanguage,
                                  $arrSourceLanguage,
                                  $strTargetLanguage,
                                  $arrTargetLanguage);

        // return the document
        return $doc;
    }

    /**
     * Recursively add language items to the document.
     *
     * @param DOMDocument $doc
     * @param DOMElement  $body
     * @param             $strPath
     * @param array       $arrSourceLanguage
     * @param             $arrTargetLanguage
     */
    protected function generateXliffUnits(DOMDocument $doc,
                                          DOMElement $body,
                                          $strPath,
                                          $strSourceLanguage,
                                          array $arrSourceLanguage,
                                          $strTargetLanguage,
                                          $arrTargetLanguage)
    {
        foreach ($arrSourceLanguage as $strKey => $varSourceValue) {
            $varTargetValue = is_array($arrTargetLanguage) && isset($arrTargetLanguage[$strKey])
                ? $arrTargetLanguage[$strKey]
                : false;

            // build the path for the current item
            $strItemPath = ($strPath ? $strPath . '.' : '') . $strKey;

            // search recursively
            if (is_array($varSourceValue)) {
                $this->generateXliffUnits($doc,
                                          $body,
                                          $strItemPath,
                                          $strSourceLanguage,
                                          $varSourceValue,
                                          $strTargetLanguage,
                                          $varTargetValue);
            } // add the current item
            else {
                // create the trans-unit element
                $transUnit = $doc->createElement('trans-unit');
                $transUnit->setAttribute('id',
                                         $strItemPath);
                $body->appendChild($transUnit);

                // create the source element
                $source = $doc->createElement('source');
                $source->setAttribute('xml:lang',
                                      $strSourceLanguage);
                $source->appendChild($doc->createTextNode($varSourceValue));
                $transUnit->appendChild($source);

                // create the target element
                if ($strTargetLanguage != 'en') {
                    $target = $doc->createElement('target');
                    $target->setAttribute('xml:lang',
                                          $strTargetLanguage);
                    $target->appendChild($doc->createTextNode($varTargetValue ? $varTargetValue : $varSourceValue));
                    $transUnit->appendChild($target);
                }
            }
        }
    }

    /**
     * Export language file into an xliff document.
     *
     * @param string $strName
     * @param string $strSourceLanguage
     *
     * @return DOMDocument
     */
    public function updateXliffSource(DOMDocument $doc,
                                      $intDate,
                                      $strName,
                                      $strSourceLanguage,
                                      array $arrSourceLanguage)
    {
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

            $strTargetLanguage = $file->getAttribute('target-language');

            // update date timestamp
            $file->setAttribute('date',
                                date('c',
                                     $intDate));

            $bodies = $xpath->query('xliff:body',
                                        $file);

            for ($j = 0; $j < $bodies->length; $j++) {
                /** @var DOMElement $transUnit */
                $body = $bodies->item($j);

                $this->updateOrGenerateXliffUnits($doc, $body, $xpath, $strName, $strSourceLanguage, $arrSourceLanguage, $strTargetLanguage, null);
            }
        }

        return $doc;
    }

    /**
     * Recursively add language items to the document.
     *
     * @param DOMDocument $doc
     * @param DOMElement  $body
     * @param             $strPath
     * @param array       $arrSourceLanguage
     * @param             $arrTargetLanguage
     */
    protected function updateOrGenerateXliffUnits(DOMDocument $doc,
                                                  DOMElement $body,
                                                  DOMXPath $xpath,
                                                  $strPath,
                                                  $strSourceLanguage,
                                                  array $arrSourceLanguage,
                                                  $strTargetLanguage,
                                                  $arrTargetLanguage)
    {
        foreach ($arrSourceLanguage as $strKey => $varSourceValue) {
            $varTargetValue = is_array($arrTargetLanguage) && isset($arrTargetLanguage[$strKey])
                ? $arrTargetLanguage[$strKey]
                : '...';

            // build the path for the current item
            $strItemPath = $strPath . '.' . $strKey;

            // search recursively
            if (is_array($varSourceValue)) {
                $this->generateXliffUnits($doc,
                                          $body,
                                          $strItemPath,
                                          $strSourceLanguage,
                                          $varSourceValue,
                                          $strTargetLanguage,
                                          $varTargetValue);
                continue;
            }

            // search for existing trans-unit
            $transUnits = $xpath->query('xliff:trans-unit[@id=\'' . $strItemPath . '\']',
                                        $body);

            // update existing item
            if ($transUnits->length) {
                $transUnit = $transUnits->item(0);

                $sources = $xpath->query('xliff:source',
                                         $transUnit);

                $source = $sources->item(0);

                while ($source->childNodes->length) {
                    $source->removeChild($source->childNodes->item(0));
                }

                $source->appendChild($doc->createTextNode($varSourceValue));
            }

            // or add new item
            else {
                // create the trans-unit element
                $transUnit = $doc->createElement('trans-unit');
                $transUnit->setAttribute('id',
                                         $strItemPath);
                $body->appendChild($transUnit);

                // create the source element
                $source = $doc->createElement('source');
                $source->setAttribute('xml:lang',
                                      $strSourceLanguage);
                $source->appendChild($doc->createTextNode($varSourceValue));
                $transUnit->appendChild($source);

                // create the target element
                $target = $doc->createElement('target');
                $target->setAttribute('xml:lang',
                                      $strTargetLanguage);
                $target->appendChild($doc->createTextNode($varTargetValue));
                $transUnit->appendChild($target);
            }
        }
    }

    /**
     * Generate php languages file from an array.
     *
     * @param array $arrLanguages
     *
     * @return string
     */
    public function generatePhpFromArray(array $arrLanguages, $blnOpenTag = true)
    {
        if ($blnOpenTag) {
            $strBuffer = <<<EOF
<?php

EOF;
        }

        $this->generatePhpFromArrayItems('$GLOBALS[\'TL_LANG\']',
                                         $arrLanguages,
                                         $strBuffer);

        return $strBuffer;
    }

    /**
     * Recursively walk over the languages array and generate the php code.
     *
     * @param string $strVariableName
     * @param array  $arrLanguages
     * @param string $strBuffer
     * @param int    $intDepth
     */
    protected function generatePhpFromArrayItems($strVariableName,
                                                 array $arrLanguages,
                                                 &$strBuffer,
                                                 $intDepth = 0)
    {
        foreach ($arrLanguages as $strKey => $varItem) {
            if ($intDepth == 0) {
                $strBuffer .= <<<EOF

/**
 * $strKey
 */

EOF;
            }
            $strItemVariableName = $strVariableName . '[' . var_export($strKey,
                                                                       true) . ']';

            if (is_array($varItem) && array_keys($varItem) != array(0, 1)) {
                $this->generatePhpFromArrayItems($strItemVariableName,
                                                 $varItem,
                                                 $strBuffer,
                                                 $intDepth + 1);
            }
            else {
                if (is_array($varItem)) {
                    $a = var_export($varItem[0],
                                    true);
                    $b = var_export($varItem[1],
                                    true);
                    $strBuffer .= $strItemVariableName . ' = array(' . $a . ', ' . $b . ");\n";
                }
                else {
                    $varItem = var_export($varItem,
                                          true);
                    $strBuffer .= $strItemVariableName . ' = ' . $varItem . ";\n";
                }
            }
        }
    }
}