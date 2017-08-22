<?php
/**
 *
 * Convert WBB Lite to phpBB. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, FH3095
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace FH3095\ConvertWbbLite\acp;

class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\FH3095\ConvertWbbLite\acp\main_module',
			'title'		=> 'ACP_CONVERT_WBB_LITE',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_CONVERT_WBB_LITE_TITLE',
					'auth'	=> 'ext_FH3095/ConvertWbbLite && acl_a_board',
					'cat'	=> array('ACP_CONVERT_WBB_LITE')
				),
			),
		);
	}
}
