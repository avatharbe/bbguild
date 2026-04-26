<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Specialization (subclass) class — issue #331
 *
 */
namespace avathar\bbguild\model\games\rpg;

use phpbb\cache\driver\driver_interface as cache_interface;
use phpbb\db\driver\driver_interface;

/**
 * A specialization is a subclass within a class that pins the player to
 * a specific role. Examples: Frost Mage (DPS), Restoration Druid (Healer).
 *
 * Specs are seeded by game plugins via the optional
 * `specialization_provider_interface`. Plugins that don't seed specs
 * (e.g. the Custom game) simply have no rows in bb_specializations for
 * their game_id, and the feature stays inert.
 */
class specialization
{
	public $bb_specializations_table;

	/** @var driver_interface */
	protected $db;
	/** @var cache_interface */
	protected $cache;

	public int $spec_id = 0;
	public string $game_id = '';
	public int $class_id = 0;
	public int $role_id = 0;
	public string $spec_name = '';
	public string $spec_icon = '';
	public int $spec_order = 0;

	public function __construct(driver_interface $db, cache_interface $cache, string $bb_specializations_table)
	{
		$this->db = $db;
		$this->cache = $cache;
		$this->bb_specializations_table = $bb_specializations_table;
	}

	/**
	 * Load a single specialization by primary key. Returns false when not found.
	 */
	public function load(int $spec_id): bool
	{
		$sql = 'SELECT * FROM ' . $this->bb_specializations_table . ' WHERE spec_id = ' . (int) $spec_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			return false;
		}

		$this->spec_id = (int) $row['spec_id'];
		$this->game_id = (string) $row['game_id'];
		$this->class_id = (int) $row['class_id'];
		$this->role_id = (int) $row['role_id'];
		$this->spec_name = (string) $row['spec_name'];
		$this->spec_icon = (string) $row['spec_icon'];
		$this->spec_order = (int) $row['spec_order'];
		return true;
	}

	/**
	 * Insert or update the current specialization. Sets spec_id on insert.
	 */
	public function save(): void
	{
		$data = [
			'game_id'    => $this->game_id,
			'class_id'   => $this->class_id,
			'role_id'    => $this->role_id,
			'spec_name'  => $this->spec_name,
			'spec_icon'  => $this->spec_icon,
			'spec_order' => $this->spec_order,
		];

		if ($this->spec_id > 0)
		{
			$sql = 'UPDATE ' . $this->bb_specializations_table
				. ' SET ' . $this->db->sql_build_array('UPDATE', $data)
				. ' WHERE spec_id = ' . (int) $this->spec_id;
			$this->db->sql_query($sql);
		}
		else
		{
			$sql = 'INSERT INTO ' . $this->bb_specializations_table . ' '
				. $this->db->sql_build_array('INSERT', $data);
			$this->db->sql_query($sql);
			$this->spec_id = (int) $this->db->sql_nextid();
		}

		$this->cache->destroy('sql', $this->bb_specializations_table);
	}

	/**
	 * Delete this specialization. Caller is responsible for clearing
	 * any referencing player_spec_id values.
	 */
	public function delete(): void
	{
		if ($this->spec_id <= 0)
		{
			return;
		}

		$sql = 'DELETE FROM ' . $this->bb_specializations_table
			. ' WHERE spec_id = ' . (int) $this->spec_id;
		$this->db->sql_query($sql);
		$this->cache->destroy('sql', $this->bb_specializations_table);
	}

	/**
	 * Return all specs for a class (or for a whole game if class_id is null),
	 * ordered by spec_order then spec_name.
	 *
	 * @return array<int, array{spec_id:int,game_id:string,class_id:int,role_id:int,spec_name:string,spec_icon:string,spec_order:int}>
	 */
	public function get_for_class(string $game_id, ?int $class_id = null): array
	{
		$where = "game_id = '" . $this->db->sql_escape($game_id) . "'";
		if ($class_id !== null)
		{
			$where .= ' AND class_id = ' . (int) $class_id;
		}

		$sql = 'SELECT * FROM ' . $this->bb_specializations_table
			. ' WHERE ' . $where
			. ' ORDER BY spec_order ASC, spec_name ASC';

		$result = $this->db->sql_query($sql);
		$rows = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rows[] = [
				'spec_id'    => (int) $row['spec_id'],
				'game_id'    => (string) $row['game_id'],
				'class_id'   => (int) $row['class_id'],
				'role_id'    => (int) $row['role_id'],
				'spec_name'  => (string) $row['spec_name'],
				'spec_icon'  => (string) $row['spec_icon'],
				'spec_order' => (int) $row['spec_order'],
			];
		}
		$this->db->sql_freeresult($result);
		return $rows;
	}
}
