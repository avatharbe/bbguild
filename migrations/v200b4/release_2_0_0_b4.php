<?php
/**
 * bbGuild Extension — 2.0.0-b4 migration
 *
 * Adds specialization support (#331):
 *   - bb_specializations table
 *   - player_spec_id column on bb_players (nullable FK)
 *
 * The legacy free-text player_spec column is preserved for backward
 * compatibility; it's deprecated in favor of player_spec_id and will be
 * dropped in a later release once data is migrated.
 *
 * @package   avathar\bbguild
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild\migrations\v200b4;

class release_2_0_0_b4 extends \phpbb\db\migration\container_aware_migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguild\migrations\v200b3\release_2_0_0_b3'];
	}

	public function effectively_installed()
	{
		return isset($this->config['bbguild_version'])
			&& version_compare($this->config['bbguild_version'], '2.0.0-b4', '>=');
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'bb_specializations' => [
					'COLUMNS' => [
						'spec_id'   => ['UINT', null, 'auto_increment'],
						'game_id'   => ['VCHAR:10', ''],
						'class_id'  => ['USINT', 0],
						'role_id'   => ['USINT', 0],
						'spec_name' => ['VCHAR_UNI:100', ''],
						'spec_icon' => ['VCHAR:100', ''],
						'spec_order' => ['USINT', 0],
					],
					'PRIMARY_KEY' => 'spec_id',
					'KEYS' => [
						'game_class_idx' => ['INDEX', ['game_id', 'class_id']],
					],
				],
			],
			'add_columns' => [
				$this->table_prefix . 'bb_players' => [
					'player_spec_id' => ['UINT', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'bb_players' => ['player_spec_id'],
			],
			'drop_tables' => [
				$this->table_prefix . 'bb_specializations',
			],
		];
	}

	public function update_data()
	{
		return [
			['config.update', ['bbguild_version', '2.0.0-b4']],
		];
	}

	public function revert_data()
	{
		return [
			['config.update', ['bbguild_version', '2.0.0-b3']],
		];
	}
}
