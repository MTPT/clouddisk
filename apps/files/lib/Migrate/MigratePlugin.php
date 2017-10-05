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

namespace OCA\Files\Migrate;

use OC\Hooks\BasicEmitter;
use OC\Files\Storage\Wrapper\Jail;
use OCP\Files\IHomeStorage;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\Migrate\IMigratePlugin;
use OCP\Remote\ICredentials;
use OCP\Remote\IInstance;

class MigratePlugin extends BasicEmitter implements IMigratePlugin {
	/** @var IL10N */
	private $l10n;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var RemoteStorageFactory */
	private $storageFactory;

	/**
	 * MigratePlugin constructor.
	 *
	 * @param IL10N $l10n
	 * @param IRootFolder $rootFolder
	 */
	public function __construct(IL10N $l10n, IRootFolder $rootFolder, RemoteStorageFactory $storageFactory) {
		$this->l10n = $l10n;
		$this->rootFolder = $rootFolder;
		$this->storageFactory = $storageFactory;
	}

	public function getName() {
		return $this->l10n->t('Files and Folders');
	}

	public function migrateFrom($targetUser, IInstance $remote, ICredentials $credentials) {
		$userStorage = $this->rootFolder->getUserFolder($targetUser)->getStorage();
		if (!$userStorage->instanceOfStorage(IHomeStorage::class)) {
			throw new \Exception('expected home storage');
		}
		$targetStorage = new Jail([
			'storage' => $userStorage,
			'root' => 'files'
		]);

		$remoteStorage = $this->storageFactory->getRemoteStorage($remote, $credentials);
		$fileMigrator = new FileReceiver($remoteStorage, $targetStorage);

		$fileCount = 0;
		$fileMigrator->listen('File', 'copied', function () use (&$fileCount) {
			$fileCount++;
			// limit the number of events to once in every 10 files
			if (($fileCount % 10) === 0) {
				$this->emit('Migrate', 'absoluteProgress', [$fileCount]);
			}
		});

		$fileMigrator->copyFiles();
	}

	public function isDestructive() {
		return false;
	}
}
