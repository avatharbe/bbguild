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

	public function effectively_installed()
	{
		return isset($this->config['bbguild_version'])
			&& version_compare($this->config['bbguild_version'], '2.0.0-rc1', '>=');
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

			['config.update', ['bbguild_version', '2.0.0-rc1']],
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
			['config.update', ['bbguild_version', '2.0.0-b4']],
			['permission.permission_unset', ['GLOBAL_MODERATORS', 'u_bbguild', 'group']],
			['permission.permission_unset', ['ADMINISTRATORS', 'u_bbguild', 'group']],
			['permission.permission_unset', ['REGISTERED', ['u_bbguild', 'u_charclaim', 'u_charadd', 'u_chardelete', 'u_charupdate'], 'group']],
		];
	}
}
