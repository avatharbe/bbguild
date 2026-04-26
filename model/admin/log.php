<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Logging class
 *
 * Follows phpBB log design pattern: stores a log type (language key)
 * and a JSON-encoded array of vsprintf substitution args.
 * The log viewer resolves the language key and fills in the args.
 */

namespace avathar\bbguild\model\admin;

use avathar\bbguild\model\admin\constants;

class log
{
	public $bb_logs_table;
	protected $db;
	protected $user;

	/** @var int */
	protected $total_logs;

	/**
	 * Map of numeric constant => string action type name.
	 *
	 * @var array
	 */
	public $valid_action_types = array(
		// Player management
		constants::PLAYER_ADDED => 'PLAYER_ADDED',
		constants::PLAYER_UPDATED => 'PLAYER_UPDATED',
		constants::PLAYER_DELETED => 'PLAYER_DELETED',
		constants::PLAYER_DEACTIVATED => 'PLAYER_DEACTIVATED',
		// Guild management
		constants::GUILD_ADDED => 'GUILD_ADDED',
		constants::GUILD_UPDATED => 'GUILD_UPDATED',
		constants::GUILD_DELETED => 'GUILD_DELETED',
		// Rank management
		constants::RANK_ADDED => 'RANK_ADDED',
		constants::RANK_UPDATED => 'RANK_UPDATED',
		constants::RANK_DELETED => 'RANK_DELETED',
		// News
		constants::NEWS_ADDED => 'NEWS_ADDED',
		constants::NEWS_UPDATED => 'NEWS_UPDATED',
		constants::NEWS_DELETED => 'NEWS_DELETED',
		// Game management
		constants::GAME_ADDED => 'GAME_ADDED',
		constants::GAME_DELETED => 'GAME_DELETED',
		constants::FACTION_ADDED => 'FACTION_ADDED',
		constants::FACTION_UPDATED => 'FACTION_UPDATED',
		constants::FACTION_DELETED => 'FACTION_DELETED',
		constants::RACE_ADDED => 'RACE_ADDED',
		constants::RACE_UPDATED => 'RACE_UPDATED',
		constants::RACE_DELETED => 'RACE_DELETED',
		constants::CLASS_ADDED => 'CLASS_ADDED',
		constants::CLASS_UPDATED => 'CLASS_UPDATED',
		constants::CLASS_DELETED => 'CLASS_DELETED',
		constants::ROLE_ADDED => 'ROLE_ADDED',
		constants::ROLE_UPDATED => 'ROLE_UPDATED',
		constants::ROLE_DELETED => 'ROLE_DELETED',
		// Admin
		constants::SETTINGS_CHANGED => 'SETTINGS_CHANGED',
		constants::PORTAL_CHANGED => 'PORTAL_CHANGED',
		constants::LOG_DELETED => 'LOG_DELETED',
		// Battle.net API
		constants::ARMORY_DOWN => 'ARMORY_DOWN',
		constants::BATTLENET_ACCOUNT_INACTIVE => 'BATTLENET_ACCOUNT_INACTIVE',
		constants::ROSTER_SYNCED => 'ROSTER_SYNCED',
		constants::SPECS_SYNCED => 'SPECS_SYNCED',
		constants::PORTRAITS_SYNCED => 'PORTRAITS_SYNCED',
	);

	public function __construct($bb_logs_table, \phpbb\db\driver\driver_interface $db, \phpbb\user $user)
	{
		$this->bb_logs_table = $bb_logs_table;
		$this->db = $db;
		$this->user = $user;
	}

	/**
	 * @return int
	 */
	public function getTotalLogs()
	{
		return $this->logcount();
	}

	/**
	 * Get a single log entry for detail view.
	 *
	 * @param  int $log_id
	 * @return array
	 */
	public function get_logentry($log_id)
	{
		$sql_array = array(
			'SELECT'    => 'l.*, u.username, u.user_id, u.user_colour',
			'FROM'      => array($this->bb_logs_table => 'l'),
			'LEFT_JOIN' => array(
				array(
					'FROM' => array(USERS_TABLE => 'u'),
					'ON'   => 'u.user_id=l.log_userid',
				),
			),
			'WHERE' => 'log_id=' . (int) $log_id,
		);
		$total_sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($total_sql);
		$log = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		$log['colouruser'] = get_username_string('full', $log['user_id'], $log['username'], $log['user_colour']);

		$log['log_type_clean'] = $this->strip_log_prefix((string) $log['log_type']);
		$log['log_result'] = str_replace('L_', '', (string) $log['log_result']);

		return $log;
	}

	/**
	 * Insert a log entry.
	 *
	 * log_action is a flat indexed array of vsprintf args (after username).
	 * These args match the VLOG_* language format string placeholders.
	 *
	 * Example:
	 *   $this->log->log_insert(array(
	 *       'log_type'   => 'L_ACTION_GUILD_ADDED',
	 *       'log_action' => [$realm . '-' . $name],
	 *   ));
	 *
	 * @param  array $values
	 * @return boolean
	 */
	public function log_insert(array $values)
	{
		$log_fields = array('log_date', 'log_type', 'log_action', 'log_ipaddress', 'log_sid', 'log_result', 'log_userid');

		$defaultlog = array(
			'log_date'      => time(),
			'log_type'      => '',
			'log_action'    => '',
			'log_ipaddress' => $this->user->ip,
			'log_sid'       => $this->user->session_id,
			'log_result'    => 'L_SUCCESS',
			'log_userid'    => $this->user->data['user_id'],
		);

		if (count($values) == 0)
		{
			return false;
		}

		foreach ($log_fields as $field)
		{
			$values[$field] = isset($values[$field]) ? $values[$field] : $defaultlog[$field];
		}

		// Validate log_type
		$log_type = $this->strip_log_prefix((string) $values['log_type']);
		if (!in_array($log_type, $this->valid_action_types))
		{
			return false;
		}

		if (is_array($values['log_action']))
		{
			$values['log_action'] = (string) json_encode($values['log_action']);
		}

		$query = $this->db->sql_build_array('INSERT', $values);
		$sql = 'INSERT INTO ' . $this->bb_logs_table . $query;
		$this->db->sql_query($sql);
		return true;
	}


	/**
	 * Delete logs from database.
	 *
	 * @param  array $marked Array of log IDs to delete
	 * @return array The deleted log IDs
	 */
	public function delete_log($marked)
	{
		$sql_in = array();
		foreach ($marked as $mark)
		{
			$sql_in[] = $mark;
		}
		$sql = 'DELETE FROM ' . $this->bb_logs_table . ' WHERE ' . $this->db->sql_in_set('log_id', $sql_in);
		$this->db->sql_query($sql);

		return $sql_in;
	}


	/**
	 * Read log entries.
	 *
	 * @param  string $order
	 * @param  bool   $search
	 * @param  bool   $verbose
	 * @param  string $search_term
	 * @param  string $start
	 * @return array
	 */
	public function read_log($order = '', $search = false, $verbose = false, $search_term = '', $start = '')
	{
		$sql_array = array(
			'SELECT' => 'l.*, u.username, u.user_colour',
			'FROM'   => array(
				$this->bb_logs_table => 'l',
				USERS_TABLE          => 'u',
			),
			'WHERE' => 'u.user_id=l.log_userid',
		);

		if ($search)
		{
			if (in_array($search_term, $this->valid_action_types))
			{
				$sql_array['WHERE'] = " u.user_id=l.log_userid
                    AND ( l.log_type='" . $this->db->sql_escape('L_ACTION_' . $search_term) . "'
                    OR  l.log_type='" . $this->db->sql_escape('L_ERROR_' . $search_term) . "')";
			}
			else if (preg_match("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/", $search_term))
			{
				$sql_array['WHERE'] = "u.user_id=l.log_userid AND l.log_ipaddress='" . $this->db->sql_escape($search_term) . "'";
			}
			else if ($search_term != '')
			{
				$sql_array['WHERE'] = "u.user_id=l.log_userid AND u.user_id='" . $this->db->sql_escape($search_term) . "'";
			}
		}

		if ($verbose == false)
		{
			$sql_array['ORDER_BY'] = $order;
			$sql = $this->db->sql_build_query('SELECT', $sql_array);
			$result = $this->db->sql_query_limit($sql, 30);
		}
		else
		{
			$sql_array['ORDER_BY'] = 'log_id DESC';
			$sql = $this->db->sql_build_query('SELECT', $sql_array);
			$result = $this->db->sql_query_limit($sql, constants::USER_LLIMIT, $start);
		}

		$outlog = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$log_type = $this->strip_log_prefix((string) $row['log_type']);
			$logline = $this->get_logmessage($log_type, $row['log_action'], $row['log_userid'], $row['username'], $row['user_colour'], $verbose);
			$outlog[] = array(
				'log_id'         => $row['log_id'],
				'datestamp'      => date('d.m.y - H:i:s', $row['log_date']),
				'log_line'       => $logline,
				'log_type'       => $log_type,
				'username'       => get_username_string('full', $row['log_userid'], $row['username'], $row['user_colour']),
				'log_ipaddress'  => $row['log_ipaddress'],
				'log_result'     => $this->user->lang[str_replace('L_', '', (string) $row['log_result'])] ?? str_replace('L_', '', (string) $row['log_result']),
				'cssresult'      => (str_replace('L_', '', (string) $row['log_result']) == 'SUCCESS') ? 'positive' : 'negative',
				'encoded_type'   => urlencode($row['log_type']),
				'encoded_user'   => urlencode($row['username']),
				'encoded_ip'     => urlencode($row['log_ipaddress']),
			);
		}

		return $outlog;
	}


	/**
	 * Get the language string for a log action type.
	 *
	 * @param  string  $value   The log type (e.g. 'GUILD_ADDED')
	 * @param  boolean $verbose If true, return verbose format string (VLOG_*)
	 * @return string
	 */
	public function getLogMessage($value, $verbose = false)
	{
		$key = ($verbose ? 'VLOG_' : 'ACTION_') . $value;
		return $this->user->lang[$key] ?? $value;
	}


	/**
	 * Get number of logs.
	 *
	 * @return int
	 */
	private function logcount()
	{
		$sql = 'SELECT count(*) as log_count FROM ' . $this->bb_logs_table;
		$result = $this->db->sql_query($sql);
		$this->total_logs = (int) $this->db->sql_fetchfield('log_count');
		$this->db->sql_freeresult($result);
		return $this->total_logs;
	}


	/**
	 * Format a log message for display.
	 * Decodes stored args and applies them to the VLOG_ format string via vsprintf.
	 *
	 * @param  string $log_type    The clean log type (e.g. 'GUILD_ADDED')
	 * @param  string $log_action  The stored log_action data (JSON-encoded array)
	 * @param  int    $log_userid  The user ID who performed the action
	 * @param  string $username    The username
	 * @param  string $user_colour The user colour
	 * @param  bool   $verbose     Whether to use verbose format
	 * @return string
	 */
	private function get_logmessage($log_type, $log_action, $log_userid, $username, $user_colour, $verbose = false)
	{
		$format = $this->getLogMessage($log_type, $verbose);

		if (!$verbose)
		{
			return $format;
		}

		$userstring = get_username_string('full', $log_userid, $username, $user_colour);
		$args = json_decode((string) $log_action, true);

		if (!is_array($args))
		{
			$args = [];
		}

		$result = @vsprintf($format, array_merge([$userstring], $args));
		return ($result !== false) ? $result : $format;
	}


	/**
	 * Parse log_action data for the detail view.
	 *
	 * @param  string $log_action The raw log_action from database
	 * @return array
	 */
	public function parse_log_action($log_action)
	{
		$data = json_decode((string) $log_action, true);
		return is_array($data) ? $data : [];
	}


	/**
	 * Strip L_ACTION_ / L_ERROR_ prefixes from log_type.
	 *
	 * @param  string $log_type
	 * @return string
	 */
	private function strip_log_prefix($log_type)
	{
		$log_type = str_replace('L_ACTION_', '', $log_type);
		$log_type = str_replace('L_ERROR_', '', $log_type);
		return $log_type;
	}
}
