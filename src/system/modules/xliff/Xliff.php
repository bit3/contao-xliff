<?php

class Xliff
{
    /**
     * xliff Namespace
     */
    const NS = 'urn:oasis:names:tc:xliff:document:1.2';

    /**
     * @var XliffHelper
     */
    protected $helper = null;

    /**
     * Singleton constructor.
     */
    public function __construct(XliffHelper $helper = null)
    {
        $this->helper = $helper;
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
                /** @var DOMElement $target */
                $target = $xpath
                    ->query('xliff:target',
                            $transUnit)
                    ->item(0);

                // continue if target element not found
                if (!$target) {
                    continue;
                }

                // get the path
                $path = explode('/',
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
                            }
                            // key is an integer
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
                                                    $target);
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
    public function generateXliff($strName,
                                  $strSourceLanguage,
                                  $strTargetLanguage = false,
                                  XliffHelper $helper = null)
    {
        // set helper to instance helper, if none given
        if ($helper === null) {
            $helper = $this->helper;
        }

        // if no helper is availeable, throw an exception
        if ($helper === null) {
            throw new Exception('Helper object is required to generate xliff document!');
        }

        // get the source language array
        $arrSourceLanguage = $helper->getLanguageArray($strName,
                                                       $strSourceLanguage);

        // add the target language
        if ($strTargetLanguage) {
            // get the target language array
            $arrTargetLanguage = $helper->getLanguageArray($strName,
                                                           $strTargetLanguage);
        }
        else {
            // use a dummy array
            $arrTargetLanguage = array();
        }

        // create the new document
        $doc = new DOMDocument();

        // create the xliff root element
        $xliff = $doc->createElement('xliff');
        // little hack to workaround the unusable php namespace handling
        $xliff->setAttribute('xmlns',
                             self::NS);
        $xliff->setAttribute('version',
                             '1.2');
        $doc->appendChild($xliff);

        // create the file element
        $file = $doc->createElement('file');
        $file->setAttribute('original',
                            $strName . '.php');
        $file->setAttribute('source-language',
                            $strSourceLanguage);
        $file->setAttribute('datatype',
                            'php');
        $xliff->appendChild($file);

        // create the body element
        $body = $doc->createElement('body');
        $file->appendChild($body);

        // append the translation units
        $this->generateXliffUnits($doc,
                                  $body,
                                  $strName,
                                  $arrSourceLanguage,
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
                                          array $arrSourceLanguage,
                                          $arrTargetLanguage)
    {
        foreach ($arrSourceLanguage as $strKey => $varSourceValue) {
            $varTargetValue = is_array($arrTargetLanguage) && isset($arrTargetLanguage[$strKey])
                ? $arrTargetLanguage[$strKey]
                : '...';

            // build the path for the current item
            $strItemPath = $strPath . '/' . $strKey;

            // search recursively
            if (is_array($varSourceValue)) {
                $this->generateXliffUnits($doc,
                                          $body,
                                          $strItemPath,
                                          $varSourceValue,
                                          $varTargetValue);
            }

            // add the current item
            else {
                // create the trans-unit element
                $transUnit = $doc->createElement('trans-unit');
                $transUnit->setAttribute('id',
                                         $strItemPath);
                $body->appendChild($transUnit);

                // create the source element
                $source = $doc->createElement('source');
                $source->appendChild($doc->createTextNode($varSourceValue));
                $transUnit->appendChild($source);

                // create the target element
                $target = $doc->createElement('target');
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
    public function generatePhpFromArray(array $arrLanguages)
    {
        $strBuffer = <<<EOF
<?php

EOF;

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
            else if (is_array($varItem)) {
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