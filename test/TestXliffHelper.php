<?php

class TestXliffHelper
    implements XliffHelper
{
    public function getLanguageArray($strName,
                                     $strLanguage)
    {
        switch ($strLanguage) {
            case 'en':
                return array(
                    'hello' => 'Hello World!'
                );

            case 'de':
                return array(
                    'hello' => 'Hallo Welt!'
                );
        }
    }
}
