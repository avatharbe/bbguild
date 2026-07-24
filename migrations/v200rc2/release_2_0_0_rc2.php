<?php
/**
 * bbGuild Extension — 2.0.0-rc2 migration
 *
 * Grants u_charclaim/u_charadd/u_chardelete/u_charupdate directly to
 * ADMINISTRATORS, in addition to the u_bbguild view-only floor v200rc1
 * already granted to staff groups. Without this, an admin whose primary
 * group is ADMINISTRATORS (not REGISTERED) can see guild pages but the
 * entire UCP "bbGuild" tab stays hidden — phpBB hides the whole category
 * when none of its modes' auth strings resolve true, and both UCP modes
 * ('char', 'add') require u_charclaim/u_charadd specifically, not
 * u_bbguild. GLOBAL_MODERATORS intentionally stays view-only.
 *
 * Canonical version lives in ext::BBGUILD_VERSION; not in phpbb_config.
 *
 * @package   avathar\bbguild
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild\migrations\v200rc2;

class release_2_0_0_rc2 extends \phpbb\db\migration\container_aware_migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguild\migrations\v200rc1\release_2_0_0_rc1'];
	}

	public function effectively_installed()
	{
		$sql = 'SELECT ag.group_id
			FROM ' . $this->table_prefix . 'acl_groups ag
			JOIN ' . $this->table_prefix . 'groups g ON g.group_id = ag.group_id
			JOIN ' . $this->table_prefix . 'acl_options ao ON ao.auth_option_id = ag.auth_option_id
			WHERE g.group_name = \'ADMINISTRATORS\'
				AND ao.auth_option = \'u_charclaim\'
				AND ag.forum_id = 0
				AND ag.auth_role_id = 0
				AND ag.auth_setting = 1';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}

	public function update_data()
	{
		return [
			['permission.permission_set', ['ADMINISTRATORS', ['u_charclaim', 'u_charadd', 'u_chardelete', 'u_charupdate'], 'group']],
		];
	}

	public function revert_data()
	{
		// Note: as in v200rc1, phpBB core's permission_unset('group') deletes
		// the target auth_option's direct-grant row without scoping the
		// DELETE to a group_id (phpbb/db/migration/tool/permission.php
		// ~line 644), so reverting this migration in isolation also strips
		// REGISTERED's direct grant of these same four permissions from
		// v200rc1, since REGISTERED shares the same unscoped direct-grant
		// row type. A full uninstall doesn't hit this — v200b3's own revert
		// separately calls permission.remove, which removes the option
		// everywhere regardless.
		return [
			['permission.permission_unset', ['ADMINISTRATORS', ['u_charclaim', 'u_charadd', 'u_chardelete', 'u_charupdate'], 'group']],
		];
	}
}
