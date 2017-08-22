<?php

/**
 *
 * Convert WBB Lite to phpBB. An extension for the phpBB Forum Software package.
 * For post migration the option "Enable dotted topics" and "Enable server-side topic marking" must be deactivated temporarily.
 *
 * @copyright (c) 2017, FH3095
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */
namespace FH3095\ConvertWbbLite;

class service
{
	protected $user;
	protected $lang;
	protected $db;
	protected $config;
	protected $userLoader;
	protected $mimeGuesser;
	protected $uploadTargetPath;
	protected $wbbDb;
	protected $usersTable;
	protected $topicsTable;
	protected $postsTable;
	protected $attachmentsTable;
	protected $rootPath;
	protected $userIds;
	protected $regGroupId;

	public function __construct(\phpbb\user $user, 
			\phpbb\language\language $lang, 
			\phpbb\db\driver\driver_interface $db, \phpbb\config\config $config, 
			\phpbb\user_loader $userLoader, \phpbb\mimetype\guesser $guesser, 
			$usersTable, $groupsTable, $topicsTable, $postsTable, 
			$attachmentsTable, $phpbb_root_path, $php_ext)
	
	{
		require_once ('fhmysqli.inc.php');
		require_once ('convertconfig.inc.php');
		if (! function_exists('user_add'))
		{
			require_once ($phpbb_root_path . 'includes/functions_user.' .
					 $php_ext);
		}
		if (! function_exists('generate_text_for_storage'))
		{
			require_once ($phpbb_root_path . 'includes/functions_content.' .
					 $php_ext);
		}
		if (! function_exists('submit_post'))
		{
			require_once ($phpbb_root_path . 'includes/functions_posting.' .
					 $php_ext);
		}
		if (! function_exists('sync'))
		{
			require_once ($phpbb_root_path . 'includes/functions_admin.' .
					 $php_ext);
		}
		if (! function_exists('unique_id'))
		{
			require_once ($phpbb_root_path . 'includes/functions.' . $php_ext);
		}
		$this->rootPath = $phpbb_root_path;
		$this->wbbDb = new \FH_mysqli(CONVERT_WBB_HOST, CONVERT_WBB_USER, 
				CONVERT_WBB_PASS, CONVERT_WBB_DB);
		$this->wbbDb->set_charset('utf8');
		$this->db = $db;
		$this->user = $user;
		$this->lang = $lang;
		$this->config = $config;
		$this->userLoader = $userLoader;
		$this->mimeGuesser = $guesser;
		$this->uploadTargetPath = $phpbb_root_path . $this->config['upload_path'] .
				 '/';
		$this->usersTable = $usersTable;
		$this->topicsTable = $topicsTable;
		$this->postsTable = $postsTable;
		$this->attachmentsTable = $attachmentsTable;
		$this->userIds = array();
		$res = $db->sql_query(
				'SELECT group_id FROM ' . $groupsTable .
						 ' WHERE group_name = \'REGISTERED\'');
		if ($row = $db->sql_fetchrow($res))
		{
			$this->regGroupId = (int) $row['group_id'];
		}
		$db->sql_freeresult($res);
	}

	public function doConvertUsers()
	{
		$newUsers = array();
		$res = $this->wbbDb->queryElseDie(
				"SELECT userID,username,email,avatarID,signature FROM " .
						 CONVERT_WBB_TABLE_USERS . " ORDER BY userID");
		$this->db->sql_transaction('begin');
		while ($row = $res->fetch_assoc())
		{
			$newId = null;
			// Check E-Mail
			$res2 = $this->db->sql_query(
					'SELECT user_id FROM ' . $this->usersTable .
							 ' WHERE user_email = \'' .
							 strtolower($this->db->sql_escape($row['email'])) .
							 '\'');
			if ($row2 = $this->db->sql_fetchrow($res2))
			{
				$newId = (int) $row2['user_id'];
			}
			$this->db->sql_freeresult($res2);
			if (empty($newId))
			{
				// Check username
				$existingUsernameId = $this->userLoader->load_user_by_username(
						$row['username']);
				if ($existingUsernameId != ANONYMOUS)
				{
					$newId = (int) $existingUsernameId;
				}
				else
				{
					$sig = $row['signature'];
					$sigUid = $sigBitfield = null;
					$this->convertBBcode($sig, $sigUid, $sigBitfield, true);
					$userRow = array(
						'username' => $row['username'],
						'user_email' => $row['email'],
						'group_id' => $this->regGroupId,
						'user_type' => USER_NORMAL,
						'user_sig' => $sig,
						'user_sig_bbcode_uid' => $sigUid,
						'user_sig_bbcode_bitfield' => $sigBitfield
					);
					$newId = (int) user_add($userRow);
					$this->convertAvatar($row['avatarID'], $newId);
					$newUsers[] = $newId;
				}
			}
			$this->userIds[$row['userID']] = $newId;
		}
		$this->db->sql_transaction('commit');
		$res->free();
		$this->addNewUsersToGroup($newUsers);
	}

	protected function convertAvatar($avatarId, $userId)
	{
		$avatarId = (int) $avatarId;
		$userId = (int) $userId;
		$avaRes = $this->wbbDb->queryElseDie(
				'SELECT width,height,avatarExtension FROM ' .
						 CONVERT_WBB_TABLE_AVATARS . ' WHERE avatarID = ' .
						 $avatarId);
		$row = $avaRes->fetch_assoc();
		if (! $row)
		{
			return;
		}
		
		copy(
				CONVERT_WBB_AVATAR_FOLDER . 'avatar-' . $avatarId . '.' .
						 $row['avatarExtension'], 
						$this->rootPath . '/' . $this->config['avatar_path'] .
						 '/' . $this->config['avatar_salt'] . '_' . $userId . '.' .
						 $row['avatarExtension']);
		$this->db->sql_query(
				'UPDATE ' . $this->usersTable . ' SET user_avatar = \'' . $userId .
						 '_1.' . $row['avatarExtension'] .
						 '\', user_avatar_type = \'avatar.driver.upload\', user_avatar_width = ' .
						 $row['width'] . ', user_avatar_height = ' .
						 $row['height'] . ' WHERE user_id = ' . $userId);
		$avaRes->free();
	}

	protected function addNewUsersToGroup($userIds)
	{
		if (CONVERT_WBB_OLD_USERS_GROUP == 0)
		{
			return;
		}
		group_user_add(CONVERT_WBB_OLD_USERS_GROUP, $userIds, false, false, 
				true);
	}

	protected function convertBBcode(&$text, &$uid, &$bitfield, $forSignature)
	{
		$flags = null;
		$text = preg_replace(
				array(
					'@\\[font=[^\\]]+\\]@uX',
					'@\\[\\/font\\]@uX',
					'@\\[/?video\\]@uX'
				), '', $text);
		$text = preg_replace('@\\[quote=\'([^\']+)\'[^\\]]*\\]@uX', 
				"[quote=$1]", $text);
		$text = preg_replace_callback('@\\[size=(\\d+)\\]@uX', 
				function ($match)
				{
					$size = (int) $match[1];
					$newSize = - 32.39928 + 11.78431 * $size -
							 0.1480601 * pow($size, 2);
					$newSize = (int) round($newSize);
					return '[size=' . $newSize . ']';
				}, $text);
		generate_text_for_storage($text, $uid, $bitfield, $flags, true, true, 
				true, true, false, true, true, $forSignature ? 'sig' : 'post');
	}

	public function doConvertPosts()
	{
		global $CONVERT_WBB_FORUM_MAPPING;
		$wbbThreadId = 0;
		$phpbbThreadId = 0;
		$this->db->sql_transaction('begin');
		$res = $this->wbbDb->queryElseDie(
				"SELECT t.threadID,t.boardID,t.topic,t.isSticky,t.isClosed,p.postID,p.userID,p.username,p.subject,p.message,p.time,p.pollID" .
						 " FROM " . CONVERT_WBB_TABLE_POSTS . " AS p INNER JOIN " .
						 CONVERT_WBB_TABLE_THREADS .
						 " AS t ON p.threadID=t.threadID " .
						// " WHERE p.threadID
						// IN(435,438,1140,1013,1969,1557,2114) " .
						" ORDER BY p.threadID,p.time");
		while ($row = $res->fetch_assoc())
		{
			if ($wbbThreadId != (int) $row['threadID'])
			{
				$phpbbThreadId = 0;
				$wbbThreadId = (int) $row['threadID'];
			}
			$mode = "reply";
			$topicType = POST_NORMAL;
			$subject = empty($row['subject']) ? 'Re: ' . $row['topic'] : $row['subject'];
			$username = $row['username'];
			$message = $row['message'];
			$msgBitfield = null;
			$msgUid = null;
			$this->convertBBcode($message, $msgUid, $msgBitfield, false);
			$data = array(
				'icon_id' => false,
				'enable_bbcode' => true,
				'enable_smilies' => true,
				'enable_urls' => true,
				'enable_sig' => true,
				'post_edit_locked' => 0,
				'forum_id' => $CONVERT_WBB_FORUM_MAPPING[(int) $row['boardID']],
				'topic_id' => $phpbbThreadId,
				'message' => $message,
				'message_md5' => md5($message),
				'bbcode_bitfield' => $msgBitfield,
				'bbcode_uid' => $msgUid,
				'post_time' => $row['time'],
				'force_approved_state' => true,
				'force_visibility' => true
			);
			if ($data['forum_id'] == null)
			{
				continue;
			}
			if ($phpbbThreadId == 0)
			{
				$mode = "post";
				$data['topic_title'] = $row['topic'];
				if ($row['isSticky'] == 1)
				{
					$topicType = POST_STICKY;
				}
				if ($row['isClosed'] == 1)
				{
					$data['topic_status'] = ITEM_LOCKED;
				}
			}
			submit_post($mode, $subject, $username, $topicType, $_NullRet, 
					$data);
			if (! empty($this->db->get_sql_error_returned()))
			{
				trigger_error(
						"SQL ERROR: " . print_r(
								$this->db->get_sql_error_returned(), true), 
						E_USER_ERROR);
				return;
			}
			if ($phpbbThreadId == 0)
			{
				$phpbbThreadId = $data['topic_id'];
			}
			$updateValues = array();
			if (! isset($this->userIds[(int) $row['userID']]))
			{
				$updateValues['poster_id'] = 1;
				$updateValues['post_username'] = $row['username'];
			}
			else
			{
				$updateValues['post_username'] = '';
				$updateValues['poster_id'] = $this->userIds[(int) $row['userID']];
			}
			$this->db->sql_query(
					'UPDATE ' . $this->postsTable . ' SET ' .
							 $this->db->sql_build_array('UPDATE', $updateValues) .
							 ' WHERE post_id = ' . (int) $data['post_id']);
			if (! empty($row['pollID']) && $row['pollID'] != 0)
			{
				$this->createPostForPoll($data['forum_id'], $phpbbThreadId, 
						$data['post_time'], (int) $row['pollID']);
			}
			$this->convertPostAttachments((int) $row['postID'], 
					(int) $data['post_id'], (int) $data['forum_id'], 
					(int) $updateValues['poster_id']);
		}
		$res->free();
		$this->db->sql_transaction('commit');
		$forumsToSync = array();
		foreach ($CONVERT_WBB_FORUM_MAPPING as $forumId)
		{
			$forumsToSync[] = $forumId;
		}
		sync('topic', 'forum_id', $forumsToSync);
		sync('forum', 'forum_id', $forumsToSync);
	}

	protected function createPostForPoll($forumId, $threadId, $postTime, $pollId)
	{
		$res1 = $this->wbbDb->queryElseDie(
				'SELECT question,time,endTime,choiceCount,votes,sortByResult FROM ' .
						 CONVERT_WBB_TABLE_POLLS . ' WHERE pollID = ' .
						 (int) $pollId);
		$poll = $res1->fetch_assoc();
		
		$res2 = $this->wbbDb->queryElseDie(
				'SELECT pollOption, votes FROM ' . CONVERT_WBB_TABLE_POLL_OPTIONS .
						 ' WHERE pollID = ' . $pollId . ' ORDER BY votes DESC');
		
		$resultMsg = '';
		$this->lang->add_lang('common', 'FH3095/ConvertWbbLite');
		$startTimeStr = $this->user->format_date($poll['time']);
		$endTimeStr = $this->user->format_date(
				$poll['endTime'] == 0 ? time() : $poll['endTime']);
		$resultMsg = $this->lang->lang('CONVERT_WBB_LITE_POLL_POST_TEXT_1', 
				$poll['question'], $poll['choiceCount'], $startTimeStr, 
				$endTimeStr, $poll['votes']);
		while ($pollOption = $res2->fetch_assoc())
		{
			$resultMsg .= $this->lang->lang('CONVERT_WBB_LITE_POLL_OPTION_TEXT', 
					$pollOption['pollOption'], $pollOption['votes']);
		}
		$resultMsg .= $this->lang->lang('CONVERT_WBB_LITE_POLL_POST_TEXT_2');
		
		$message = $resultMsg;
		$msgBitfield = null;
		$msgUid = null;
		$this->convertBBcode($message, $msgUid, $msgBitfield, false);
		
		$data = array(
			'icon_id' => false,
			'enable_bbcode' => true,
			'enable_smilies' => true,
			'enable_urls' => true,
			'enable_sig' => true,
			'post_edit_locked' => 0,
			'forum_id' => $forumId,
			'topic_id' => $threadId,
			'message' => $message,
			'message_md5' => md5($message),
			'bbcode_bitfield' => $msgBitfield,
			'bbcode_uid' => $msgUid,
			'post_time' => $postTime + 1,
			'force_approved_state' => true,
			'force_visibility' => true
		);
		submit_post('reply', 'Migration: Umfrage', '', POST_NORMAL, 
				$_nullRetValue, $data);
	}

	protected function convertPostAttachments($wbbPostId, $postId, $topicId, 
			$userId)
	{
		$res = $this->wbbDb->queryElseDie(
				'SELECT attachmentID, attachmentName FROM ' .
						 CONVERT_WBB_TABLE_ATTACHMENTS . ' WHERE containerID = ' .
						 (int) $wbbPostId .
						 ' AND containerType = \'post\' ORDER BY showOrder');
		while ($row = $res->fetch_assoc())
		{
			$targetFilename = '' . (int) $userId . '_' . md5(unique_id());
			$srcFilename = CONVERT_WBB_ATTACHMENTS_FOLDER . 'attachment-' .
					 (int) $row['attachmentID'];
			$sqlAry = array(
				'post_msg_id' => $postId,
				'topic_id' => $topicId,
				'poster_id' => $userId,
				'physical_filename' => $targetFilename,
				'real_filename' => $row['attachmentName'],
				'extension' => pathinfo($row['attachmentName'], 
						PATHINFO_EXTENSION),
				'mimetype' => $this->mimeGuesser->guess($srcFilename, 
						$row['attachmentName']),
				'is_orphan' => 0,
				'attach_comment' => '',
				'filetime' => time(),
				'filesize' => filesize($srcFilename)
			);
			$this->db->sql_query(
					'INSERT INTO ' . $this->attachmentsTable .
							 $this->db->sql_build_array('INSERT', $sqlAry));
			copy($srcFilename, $this->uploadTargetPath . $targetFilename);
			$this->db->sql_query(
					'UPDATE ' . $this->postsTable .
							 ' SET post_attachment = 1 WHERE post_id = ' .
							 (int) $postId);
			$this->db->sql_query(
					'UPDATE ' . $this->topicsTable .
							 ' SET topic_attachment = 1 WHERE topic_id = ' .
							 (int) $topicId);
		}
		$res->free();
	}
}
