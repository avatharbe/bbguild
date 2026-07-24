<?php
/**
 * bbGuild Extension — 2.0.0-rc1 migration
 *
 * Fixes u_bbguild/u_char* defaulting to "No" for REGISTERED, ADMINISTRATORS,
 * and GLOBAL_MODERATORS on installs where those groups manage `u_` permissions
 * via direct per-group grants rather than the stock ROLE_USER_STANDARD/
 * ROLE_USER_FULL role templates (v200b3's role-based grant never reaches them
 * in that case — see GUESTS in v200b3 for the working direct-grant pattern
 * this migration extends to the other three groups).
 *
 * Also widens bb_language.language from CHAR:2 to VCHAR:10: this project's
 * own locale convention already uses codes longer than 2 characters
 * (es_x_tu, used across recenttopics/bbaccounts/bbpoints), and bbguildwow's
 * spec-seeding migration inserts exactly that value, which previously
 * overflowed the column with a hard SQL error ("Data too long for column
 * 'language'") — found via 2.0.0-rc1 smoke testing bbguildwow's pairing.
 * Any other game plugin shipping es_x_tu translations would hit the same
 * failure, so this belongs in bbguild core, not a bbguildwow-side fix.
 *
 * @package   avathar\bbguild
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild\migrations\v200rc1;

class release_2_0_0_rc1 extends \phpbb\db\migration\container_aware_migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguild\migrations\v200b4\release_2_0_0_b4'];
	}

	/**
	 * No single schema/column check distinguishes this migration (it only
	 * widens an already-existing column), so check for the concrete data
	 * artifact instead: REGISTERED's direct u_bbguild grant, added by
	 * update_data() below and not present before this migration ran.
	 */
	public function effectively_installed()
	{
		$sql = 'SELECT ag.group_id
			FROM ' . $this->table_prefix . 'acl_groups ag
			JOIN ' . $this->table_prefix . 'groups g ON g.group_id = ag.group_id
			JOIN ' . $this->table_prefix . 'acl_options ao ON ao.auth_option_id = ag.auth_option_id
			WHERE g.group_name = \'REGISTERED\'
				AND ao.auth_option = \'u_bbguild\'
				AND ag.forum_id = 0
				AND ag.auth_role_id = 0
				AND ag.auth_setting = 1';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}

	public function update_schema()
	{
		return [
			'change_columns' => [
				$this->table_prefix . 'bb_language' => [
					'language' => ['VCHAR:10', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'change_columns' => [
				$this->table_prefix . 'bb_language' => [
					'language' => ['CHAR:2', ''],
				],
			],
		];
	}

	public function update_data()
	{
		return [
			// REGISTERED: full set, matching what ROLE_USER_FULL was meant to
			// grant regular members (v200b3 set this on the role; REGISTERED
			// doesn't inherit from that role on installs using direct grants).
			['permission.permission_set', ['REGISTERED', ['u_bbguild', 'u_charclaim', 'u_charadd', 'u_chardelete', 'u_charupdate'], 'group']],

			// Staff: view-only floor, mirrors the avatharbe/recenttopics 3.0.0
			// precedent for groups whose primary role isn't REGISTERED.
			['permission.permission_set', ['ADMINISTRATORS', 'u_bbguild', 'group']],
			['permission.permission_set', ['GLOBAL_MODERATORS', 'u_bbguild', 'group']],

			// Version now lives in ext::BBGUILD_VERSION, not phpbb_config —
			// mirrors avatharbe/recenttopics (ext::RT_VERSION, no rt_version
			// row). Removes any leftover row from before this migration.
			['config.remove', ['bbguild_version']],
		];
	}

	public function revert_data()
	{
		// Note: phpBB core's permission_unset('group') deletes the target
		// auth_option's direct-grant row without scoping the DELETE to a
		// group_id (phpbb/db/migration/tool/permission.php ~line 644), so
		// reverting this migration in isolation (not as part of a full
		// extension uninstall) also strips GUESTS' u_bbguild grant from
		// v200b3, since GUESTS shares the same unscoped direct-grant row
		// type. A full uninstall doesn't hit this — v200b3's own revert
		// separately calls permission.remove, which removes the option
		// everywhere regardless — so this only matters for a standalone
		// rollback of just this migration.
		return [
			['permission.permission_unset', ['GLOBAL_MODERATORS', 'u_bbguild', 'group']],
			['permission.permission_unset', ['ADMINISTRATORS', 'u_bbguild', 'group']],
			['permission.permission_unset', ['REGISTERED', ['u_bbguild', 'u_charclaim', 'u_charadd', 'u_chardelete', 'u_charupdate'], 'group']],
		];
	}
}
