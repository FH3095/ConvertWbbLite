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
			'CONVERT_WBB_LITE_POLL_POST_TEXT_1' => "Der vorherige Post enthält eine Umfrage: %s (%d Stimmen)\nDie Umfrage lief vom %s bis zum %s mit %d Stimmen.\nErgebnis\n[list=1]\n",
			'CONVERT_WBB_LITE_POLL_OPTION_TEXT' => "[*]%s: %d\n",
			'CONVERT_WBB_LITE_POLL_POST_TEXT_2' => '[/list]'
		));
