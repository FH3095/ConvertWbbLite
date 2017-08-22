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

// Include files
/*
 * $phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../';
 * $phpEx = substr(strrchr(__FILE__, '.'), 1);
 * require($phpbb_root_path . 'common.' . $phpEx);
 * require($phpbb_root_path . 'includes/functions_acp.' . $phpEx);
 * require($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
 * require($phpbb_root_path . 'includes/functions_module.' . $phpEx);
 */

class main_module
{
	public $page_title;
	public $tpl_name;
	public $u_action;

	public function main($id, $mode)
	{
		global $request, $template, $user;
		global $phpbb_container;
		
		$user->add_lang_ext('FH3095/ConvertWbbLite', 'common');
		$this->tpl_name = 'acp_convert_wbb_lite_body';
		$this->page_title = $user->lang('ACP_CONVERT_WBB_LITE');
		add_form_key('FH3095/ConvertWbbLite');
		
		if ($request->is_set_post('submit'))
		{
			if (! check_form_key('FH3095/ConvertWbbLite'))
			{
				trigger_error('FORM_INVALID', E_USER_WARNING);
			}
			
			if ($request->variable('ConvertWbbLite_doConversion', 0) == 1)
			{
				$service = $phpbb_container->get('FH3095.ConvertWbbLite.service');
				$service->doConvertUsers();
				$service->doConvertPosts();
				trigger_error(
						$user->lang('ACP_CONVERT_WBB_LITE_DONE') .
								 adm_back_link($this->u_action));
			}
			else
			{
				trigger_error(
						$user->lang('ACP_CONVERT_WBB_LITE_NOTHING_DONE') .
								 adm_back_link($this->u_action));
			}
		}
		
		$template->assign_vars(
				array(
					'U_ACTION' => $this->u_action
				));
	}
}
