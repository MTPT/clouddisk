<?php
/**
 * @copyright Copyright (c) 2017, Robin Appelman <robin@icewind.nl>
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files\Migrate;

use OC\Files\Cache\Cache;
use OC\Hooks\BasicEmitter;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Cache\IScanner;
use OCP\Files\Storage\IStorage;

class FileReceiver extends BasicEmitter {
	/** @var IStorage */
	private $sourceStorage;

	/** @var IStorage */
	private $targetStorage;

	/** @var MetaStorageWrapper */
	private $reuseMetaStorage;

	public function __construct(IStorage $sourceStorage, IStorage $targetStorage) {
		$this->sourceStorage = $sourceStorage;
		$this->targetStorage = $targetStorage;
		$this->reuseMetaStorage = new MetaStorageWrapper([
			'storage' => $this->targetStorage,
			'etag_storage' => $this->sourceStorage
		]);
	}

	/**
	 * Copy all files from the source to the target storage that don't exist on the target storage yet
	 *
	 * While copying the target files will be added to the cache with the etag from the source storage
	 */
	public function copyFiles() {
		$this->copyFolder('');
	}

	/**
	 * @param string $path
	 */
	private function copyFolder($path) {
		$this->targetStorage->getScanner()->scanFile($path, IScanner::REUSE_NONE);
		$subPaths = $this->copyChildren($path);
		foreach ($subPaths as $subPath) {
			$this->copyFolder($subPath);
		}

		/** @var Cache $cache */
		$cache = $this->targetStorage->getCache();
		$cache->calculateFolderSize($path);
		$cache->put($path, [
			'etag' => $this->sourceStorage->getETag($path),
			'mtime' => $this->sourceStorage->filemtime($path)
		]);
	}

	/**
	 * @param $path
	 * @return string[] a list of sub folders that should be recursed into
	 */
	private function copyChildren($path) {
		$subFolders = [];
		$files = $this->getFilesToCopy($path);
		foreach ($files as $file) {
			$fullPath = trim($path . '/' . $file, '/');
			if ($this->sourceStorage->is_dir($fullPath)) {
				if (!$this->targetStorage->is_dir($fullPath)) {
					$this->targetStorage->mkdir($fullPath);
				}
				$subFolders[] = $fullPath;
			} else {
				$this->targetStorage->copyFromStorage($this->sourceStorage, $fullPath, $fullPath);
				// scan files with the source etag
				$this->reuseMetaStorage->getScanner()->scanFile($fullPath, IScanner::REUSE_NONE);
				$this->emit('File', 'copied');
			}
		}
		return $subFolders;
	}

	/**
	 * Get the list of files to copy to the target storage
	 *
	 * @param string $path
	 * @return string[]
	 */
	private function getFilesToCopy($path) {
		$sourceFiles = $this->getFolderContent($this->sourceStorage, $path);
		$targetFiles = $this->getFolderContent($this->targetStorage, $path);
		$targetCacheFolderData = $this->targetStorage->getCache()->getFolderContents($path);
		return array_filter($sourceFiles, function ($file) use ($targetFiles, $path, $targetCacheFolderData) {
			$fullPath = $path . '/' . $file;
			$targetCacheData = $this->getFileInfoForFile($targetCacheFolderData, $file);
			if ($targetCacheData) {
				return $targetCacheData->getEtag() !== $this->sourceStorage->getETag($fullPath);
			} else {
				return !in_array($file, $targetFiles) || $this->sourceStorage->is_dir($fullPath);
			}
		});
	}

	private function getFolderContent(IStorage $storage, $path) {
		$handle = $storage->opendir($path);
		$files = [];
		while ($file = readdir($handle)) {
			if ($file !== '.' && $file !== '..') {
				$files[] = $file;
			}
		}
		return $files;
	}


	/**
	 * @param ICacheEntry[] $fileInfoArray
	 * @param $file
	 * @return ICacheEntry|null
	 */
	private function getFileInfoForFile(array $fileInfoArray, $file) {
		foreach ($fileInfoArray as $fileInfo) {
			if ($fileInfo->getName() === $file) {
				return $fileInfo;
			}
		}
		return null;
	}
}
