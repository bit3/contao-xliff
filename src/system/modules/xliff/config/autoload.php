<?php

/**
 * Contao Open Source CMS
 * 
 * Copyright (C) 2005-2012 Leo Feyer
 * 
 * @package Xliff
 * @link    http://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	'ModuleXliff'       => 'system/modules/xliff/ModuleXliff.php',
	'ContaoXliffHelper' => 'system/modules/xliff/ContaoXliffHelper.php',
	'Xliff'             => 'system/modules/xliff/Xliff.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'be_xliff' => 'system/modules/xliff/templates',
));
