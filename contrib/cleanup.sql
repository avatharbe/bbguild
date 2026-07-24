-- ============================================================================
-- bbGuild Database Cleanup Script
-- ============================================================================
--
-- WARNING: This script DROPS ALL bbGuild tables and removes all
--          bbGuild data from the database.
--          It is intended for development/testing purposes only.
--          Back up your database before running this script!
--
-- Assumptions:
--   - phpBB table prefix is "phpbb_"
--   - bbguildwow plugin tables exist (skip errors if not installed)
--
-- After running this script, disable and re-enable the extensions in
-- phpBB ACP to re-run migrations and restore default config/permissions.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Drop bbguildwow tables (child tables first)
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS phpbb_bb_criteria_track;
DROP TABLE IF EXISTS phpbb_bb_achievement_track;
DROP TABLE IF EXISTS phpbb_bb_relations_table;
DROP TABLE IF EXISTS phpbb_bb_achievement_rewards;
DROP TABLE IF EXISTS phpbb_bb_achievement_criteria;
DROP TABLE IF EXISTS phpbb_bb_achievement_category;
DROP TABLE IF EXISTS phpbb_bb_achievement;
DROP TABLE IF EXISTS phpbb_bb_player_equipment;
DROP TABLE IF EXISTS phpbb_bb_guild_wow;

-- ----------------------------------------------------------------------------
-- 2. Drop bbguild portal tables
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS phpbb_bb_portal_config;
DROP TABLE IF EXISTS phpbb_bb_portal_modules;

-- ----------------------------------------------------------------------------
-- 3. Drop bbguild core tables (child tables first)
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS phpbb_bb_players;
DROP TABLE IF EXISTS phpbb_bb_specializations;
DROP TABLE IF EXISTS phpbb_bb_ranks;
DROP TABLE IF EXISTS phpbb_bb_recruit;
DROP TABLE IF EXISTS phpbb_bb_motd;
DROP TABLE IF EXISTS phpbb_bb_news;
DROP TABLE IF EXISTS phpbb_bb_logs;
DROP TABLE IF EXISTS phpbb_bb_language;
DROP TABLE IF EXISTS phpbb_bb_classes;
DROP TABLE IF EXISTS phpbb_bb_races;
DROP TABLE IF EXISTS phpbb_bb_factions;
DROP TABLE IF EXISTS phpbb_bb_gameroles;
DROP TABLE IF EXISTS phpbb_bb_guild;
DROP TABLE IF EXISTS phpbb_bb_games;

-- ----------------------------------------------------------------------------
-- 4. phpBB config entries
-- ----------------------------------------------------------------------------

DELETE FROM phpbb_config WHERE config_name LIKE 'bbguild\_%';

-- ----------------------------------------------------------------------------
-- 5. phpBB extension registrations
-- ----------------------------------------------------------------------------

DELETE FROM phpbb_ext WHERE ext_name IN ('avathar/bbguild', 'avathar/bbguildwow');

-- ----------------------------------------------------------------------------
-- 6. phpBB migration tracking
-- ----------------------------------------------------------------------------

DELETE FROM phpbb_migrations WHERE migration_name LIKE '%avathar\\\\bbguild%';

-- ----------------------------------------------------------------------------
-- 7. phpBB modules (ACP/UCP menu entries)
-- ----------------------------------------------------------------------------

DELETE FROM phpbb_modules WHERE module_langname LIKE 'ACP_BBGUILD%';
DELETE FROM phpbb_modules WHERE module_langname LIKE 'ACP_CAT_BBGUILD%';
DELETE FROM phpbb_modules WHERE module_langname LIKE 'UCP_BBGUILD%';
DELETE FROM phpbb_modules WHERE module_basename LIKE '%avathar%bbguild%';

-- ----------------------------------------------------------------------------
-- 8. phpBB permissions
-- ----------------------------------------------------------------------------

DELETE FROM phpbb_acl_roles_data WHERE auth_option_id IN (
    SELECT auth_option_id FROM phpbb_acl_options WHERE auth_option LIKE 'a_bbguild%' OR auth_option LIKE 'u_char%' OR auth_option = 'u_bbguild'
);
DELETE FROM phpbb_acl_groups WHERE auth_option_id IN (
    SELECT auth_option_id FROM phpbb_acl_options WHERE auth_option LIKE 'a_bbguild%' OR auth_option LIKE 'u_char%' OR auth_option = 'u_bbguild'
);
DELETE FROM phpbb_acl_users WHERE auth_option_id IN (
    SELECT auth_option_id FROM phpbb_acl_options WHERE auth_option LIKE 'a_bbguild%' OR auth_option LIKE 'u_char%' OR auth_option = 'u_bbguild'
);
DELETE FROM phpbb_acl_options WHERE auth_option LIKE 'a_bbguild%' OR auth_option LIKE 'u_char%' OR auth_option = 'u_bbguild';

-- ============================================================================
-- Done. Now purge the phpBB cache and re-enable the extensions from ACP.
-- ============================================================================
