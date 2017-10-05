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

use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files\Storage\IStorage;

/**
 * Storage wrapper that reads etags and mtimes from a different storage
 *
 * This way we can trigger a scanner on the target storage while using the etags from the source storage
 */
class MetaStorageWrapper extends Wrapper {
	/** @var  IStorage */
	private $etagStorage;

	/**
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		parent::__construct($parameters);
		$this->etagStorage = $parameters['etag_storage'];
	}

	public function getETag($path) {
		return $this->etagStorage->getETag($path);
	}

	public function filemtime($path) {
		return $this->etagStorage->filemtime($path);
	}

	/**
	 * @param string $path
	 * @return array
	 */
	public function getMetaData($path) {
		$meta = parent::getMetaData($path);
		$meta['etag'] = $this->etagStorage->getETag($path);
		$meta['mtime'] = $this->etagStorage->filemtime($path);
		return $meta;
	}
}
