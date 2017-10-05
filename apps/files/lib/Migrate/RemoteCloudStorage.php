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

use Icewind\Streams\IteratorDirectory;
use OC\Files\Storage\DAV;

/**
 * DAV storage with incoming shares filtered out
 */
class RemoteCloudStorage extends DAV {
	public function opendir($path) {
		$this->init();
		$path = $this->cleanPath($path);
		try {
			$response = $this->client->propFind(
				$this->encodePath($path),
				['{http://owncloud.org/ns}permissions'],
				1
			);
			if ($response === false) {
				return false;
			}
			$content = [];
			$files = array_keys($response);
			array_shift($files); //the first entry is the current directory

			if (!$this->statCache->hasKey($path)) {
				$this->statCache->set($path, true);
			}
			foreach ($files as $file) {
				if (strpos($response[$file]['{http://owncloud.org/ns}permissions'], 'S') !== false) {
					continue;
				}
				$file = urldecode($file);
				// do not store the real entry, we might not have all properties
				if (!$this->statCache->hasKey($path)) {
					$this->statCache->set($file, true);
				}
				$file = basename($file);
				$content[] = $file;
			}
			return IteratorDirectory::wrap($content);
		} catch (\Exception $e) {
			$this->convertException($e, $path);
		}
		return false;
	}
}
