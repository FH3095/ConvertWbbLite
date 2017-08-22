<?php

/**
 *
 * Convert WBB Lite to phpBB. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, FH3095, https://github.com/FH3095/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */
namespace FH3095\ConvertWbbLite\migrations;

class install_acp_module extends \phpbb\db\migration\migration
{

	public function effectively_installed()
	{
		return $this->config['convert_wbb_lite_installed'] == 2;
	}

	static public function depends_on()
	{
		return array(
			'\phpbb\db\migration\data\v31x\v314'
		);
	}

	public function update_data()
	{
		return array(
			array(
				'config.add',
				array(
					'convert_wbb_lite_installed',
					2
				)
			),
			array(
				'module.add',
				array(
					'acp',
					'ACP_CAT_DOT_MODS',
					'ACP_CONVERT_WBB_LITE'
				)
			),
			array(
				'module.add',
				array(
					'acp',
					'ACP_CONVERT_WBB_LITE',
					array(
						'module_basename' => '\FH3095\ConvertWbbLite\acp\main_module',
						'modes' => array(
							'settings'
						)
					)
				)
			)
		);
	}
}
