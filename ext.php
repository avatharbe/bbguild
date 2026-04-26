<?php
/**
 *
 * @package bbGuild Extension
 * @copyright (c) 2018 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Extension entry point
 *
 */

namespace avathar\bbguild;

use phpbb\extension\base;

/**
 * Class ext
 *
 * @package avathar\bbguild
 */
class ext extends base
{
	const MIN_PHP_VERSION = '8.1.0';
	const MIN_PHPBB_VERSION = '3.3.0';

	/**
	 * Check whether or not the extension can be enabled.
	 *
	 * @return bool|array True if enableable, or an array of error messages otherwise
	 */
	public function is_enableable()
	{
		$errors = [];

		if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<'))
		{
			$errors[] = 'This extension requires PHP ' . self::MIN_PHP_VERSION . ' or higher. You are running PHP ' . PHP_VERSION . '.';
		}

		if (phpbb_version_compare(PHPBB_VERSION, self::MIN_PHPBB_VERSION, '<'))
		{
			$errors[] = 'This extension requires phpBB ' . self::MIN_PHPBB_VERSION . ' or higher. You are running phpBB ' . PHPBB_VERSION . '.';
		}

		if (!extension_loaded('gd'))
		{
			$errors[] = 'This extension requires the GD PHP extension to be loaded.';
		}

		if (!extension_loaded('curl'))
		{
			$errors[] = 'This extension requires the cURL PHP extension to be loaded.';
		}

		return empty($errors) ? true : $errors;
	}


	/**
	 * Auto-disable child game-plugin extensions before bbGuild core is disabled.
	 *
	 * Without this, disabling core while a child (e.g. bbguildwow) is still
	 * enabled crashes the DI container because the child's services.yml
	 * references core parameters that no longer exist.
	 *
	 * @param mixed $old_state
	 * @return mixed
	 */
	public function disable_step($old_state)
	{
		if ($old_state === false)
		{
			$ext_manager = $this->container->get('ext.manager');

			$child_extensions = array(
				'avathar/bbguildwow',
				'avathar/bbguildeq2',
			);

			foreach ($child_extensions as $child)
			{
				if ($ext_manager->is_enabled($child))
				{
					while ($ext_manager->disable_step($child))
					{
						// run all disable steps
					}
				}
			}

			return 'children_disabled';
		}

		return parent::disable_step($old_state);
	}

	/**
	 * override enable step
	 * enable_step is executed on enabling an extension until it returns false.
	 *
	 * Calls to this function can be made in subsequent requests, when the
	 * function is invoked through a webserver with a too low max_execution_time.
	 *
	 * @param	mixed	$old_state	The return value of the previous call
	 *								of this method, or false on the first call
	 * @return	mixed				Returns false after last step, otherwise
	 *								temporary state which is passed as an
	 *								argument to the next step
	 */
	public function enable_step($old_state)
	{
		ini_set('max_execution_time', 300);

		return parent::enable_step($old_state);
	}
}
