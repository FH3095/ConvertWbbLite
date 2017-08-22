<?php
/**
 *
 * ConvertWbbToPhpbb3. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, FH3095
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */
if (! defined('IN_PHPBB'))
{
	exit();
}

if (empty($lang) || ! is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, 
		array(
			'ACP_CONVERT_WBB_LITE' => 'Convert from Wbb Lite',
			'ACP_CONVERT_WBB_LITE_TITLE' => 'Convert',
			'CONVERT_WBB_LITE_DO_CONVERSION' => 'Start conversion'
		));
