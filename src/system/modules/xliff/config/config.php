<?php


/**
 * Back end modules
 */
$GLOBALS['BE_MOD']['system']['xliff'] = array(
    'callback' => 'ModuleXliff'
);

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['loadLanguageFile'][] = array('ContaoXliffHelper', 'hookLoadLanguageFile');
