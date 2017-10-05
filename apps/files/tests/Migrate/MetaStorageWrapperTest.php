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

namespace OCA\Files\Tests\Migrate;

use OC\Files\Storage\Storage;
use OCA\Files\Migrate\MetaStorageWrapper;
use OCP\Files\Storage\IStorage;
use Test\TestCase;

class MetaStorageWrapperTest extends TestCase {
	/** @var  IStorage|\PHPUnit_Framework_MockObject_MockObject */
	private $sourceStorage;
	/** @var  IStorage|\PHPUnit_Framework_MockObject_MockObject */
	private $etagStorage;

	/** @var  MetaStorageWrapper */
	private $storage;

	public function setUp() {
		parent::setUp();
		$this->sourceStorage = $this->createMock(Storage::class);
		$this->etagStorage = $this->createMock(Storage::class);
		$this->storage = new MetaStorageWrapper([
			'storage' => $this->sourceStorage,
			'etag_storage' => $this->etagStorage
		]);
	}

	public function testGetEtag() {
		$this->sourceStorage->expects($this->never())
			->method('getETag');
		$this->etagStorage->expects($this->once())
			->method('getETag')
			->willReturn('foo');

		$this->assertEquals('foo', $this->storage->getETag(''));
	}

	public function testGetMTime() {
		$this->sourceStorage->expects($this->never())
			->method('filemtime');
		$this->etagStorage->expects($this->once())
			->method('filemtime')
			->willReturn(10);

		$this->assertEquals(10, $this->storage->filemtime(''));
	}

	public function testGetMetadata() {
		$this->sourceStorage->expects($this->once())
			->method('getMetaData')
			->willReturn([
				'etag' => 'random',
				'size' => 100,
				'mtime' => 150
			]);
		$this->etagStorage->expects($this->once())
			->method('filemtime')
			->willReturn(50);
		$this->etagStorage->expects($this->once())
			->method('getETag')
			->willReturn('foo');

		$this->assertEquals([
			'etag' => 'foo',
			'size' => 100,
			'mtime' => 50
		], $this->storage->getMetaData(''));
	}
}
