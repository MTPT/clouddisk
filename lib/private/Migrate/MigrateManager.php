<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Migrate;

use OCP\App\IAppManager;
use OCP\IContainer;
use OCP\Migrate\IMigratePlugin;
use OCP\Remote\ICredentials;
use OCP\Remote\IInstance;

class MigrateManager {
	/** @var IMigratePlugin[] */
	private $plugins = [];

	public function loadPluginsFromApps(IAppManager $appManager, IContainer $container) {
		$apps = $appManager->getInstalledApps();
		$appInfos = array_map(function ($appId) use ($appManager) {
			return $appManager->getAppInfo($appId);
		}, $apps);
		$pluginClasses = array_reduce($appInfos, function (array $plugins, array $appInfo) {
			return array_merge($plugins, isset($appInfo['migrate']) ? $appInfo['migrate'] : []);
		}, []);
		$plugins = array_map(function ($class) use ($container) {
			$plugin = $container->query($class);
			if (!$plugin instanceof IMigratePlugin) {
				throw new \InvalidArgumentException("'$class' is not a migrate plugin");
			}
			return $plugin;
		}, $pluginClasses);
		array_walk($plugins, [$this, 'addPlugin']);
	}

	public function addPlugin(IMigratePlugin $plugin) {
		$this->plugins[] = $plugin;
	}

	/**
	 * @return IMigratePlugin[]
	 */
	public function getPlugins() {
		return $this->plugins;
	}

	/**
	 * Pre-migration allows us to run non-destructive migration steps in advance of the "real" migration.
	 *
	 * @param string $targetUser
	 * @param IInstance $remote
	 * @param ICredentials $credentials
	 */
	public function preMigrateFrom($targetUser, IInstance $remote, ICredentials $credentials) {
		/** @var IMigratePlugin[] $plugins */
		$plugins = array_filter($this->plugins, function (IMigratePlugin $plugin) {
			return !$plugin->isDestructive();
		});

		foreach ($plugins as $plugin) {
			$plugin->migrateFrom($targetUser, $remote, $credentials);
		}
	}

	/**
	 * @param string $targetUser
	 * @param IInstance $remote
	 * @param ICredentials $credentials
	 */
	public function migrateFrom($targetUser, IInstance $remote, ICredentials $credentials) {
		foreach ($this->plugins as $plugin) {
			$plugin->migrateFrom($targetUser, $remote, $credentials);
		}
	}
}
