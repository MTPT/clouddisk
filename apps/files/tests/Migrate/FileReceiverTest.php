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

use OC\Files\Cache\Cache;
use OC\Files\Storage\Temporary;
use OCA\Files\Migrate\FileReceiver;
use OCP\Files\Storage\IStorage;
use Test\TestCase;

/**
 * @group DB
 */
class FileReceiverTest extends TestCase {
	/** @var  IStorage|\PHPUnit_Framework_MockObject_MockObject */
	private $sourceStorage;
	/** @var  IStorage */
	private $targetStorage;
	/** @var  FileReceiver */
	private $fileReceiver;

	protected function setUp() {
		parent::setUp();
		$this->sourceStorage = $this->getMockBuilder(Temporary::class)
			->setMethods(['getETag', 'filemtime'])
			->getMock();
		$this->targetStorage = new Temporary();

		// make sure we have persistent etags and modified dates as a remote storage would have
		$this->sourceStorage->expects($this->any())
			->method('getETag')
			->willReturnCallback(function ($path) {
				return md5(trim($path, '/'));
			});
		$this->sourceStorage->expects($this->any())
			->method('filemtime')
			->willReturnCallback(function ($path) {
				return 100;
			});

		$this->fileReceiver = new FileReceiver($this->sourceStorage, $this->targetStorage);
	}

	public function tearDown() {
		/** @var Cache $sourceCache */
		$sourceCache = $this->sourceStorage->getCache();
		/** @var Cache $targetCache */
		$targetCache = $this->targetStorage->getCache();
		$sourceCache->clear();
		$targetCache->clear();
		return parent::tearDown();
	}

	public function testCopySingleFolder() {
		$this->sourceStorage->file_put_contents('foo.txt', 'bar');
		$this->sourceStorage->file_put_contents('bar.txt', 'foo');
		$this->fileReceiver->copyFiles();

		$this->assertEquals('bar', $this->targetStorage->file_get_contents('foo.txt'));
		$this->assertEquals('foo', $this->targetStorage->file_get_contents('bar.txt'));

		$targetCache = $this->targetStorage->getCache();
		$this->assertEquals(md5('foo.txt'), $targetCache->get('foo.txt')->getEtag());
		$this->assertEquals(md5('bar.txt'), $targetCache->get('bar.txt')->getEtag());
		$this->assertEquals(100, $targetCache->get('foo.txt')->getMTime());
		$this->assertEquals(100, $targetCache->get('bar.txt')->getMTime());
	}

	public function testCopyExistingDataOtherEtag() {
		$this->sourceStorage->file_put_contents('foo.txt', 'bar');
		$this->sourceStorage->file_put_contents('bar.txt', 'foo');

		$this->targetStorage->file_put_contents('bar.txt', 'foobar');
		$this->targetStorage->getScanner()->scan('');

		$this->fileReceiver->copyFiles();

		$this->assertEquals('bar', $this->targetStorage->file_get_contents('foo.txt'));
		$this->assertEquals('foo', $this->targetStorage->file_get_contents('bar.txt'));

		$targetCache = $this->targetStorage->getCache();
		$this->assertEquals(md5('foo.txt'), $targetCache->get('foo.txt')->getEtag());
		$this->assertEquals(md5('bar.txt'), $targetCache->get('bar.txt')->getEtag());
	}

	public function testCopyExistingDataSameEtag() {
		$this->sourceStorage->file_put_contents('foo.txt', 'bar');
		$this->sourceStorage->file_put_contents('bar.txt', 'foo');

		$this->targetStorage->file_put_contents('bar.txt', 'foobar');
		$this->targetStorage->getScanner()->scan('');
		$this->targetStorage->getCache()->put('bar.txt', [
			'etag' => md5('bar.txt')
		]);

		$this->fileReceiver->copyFiles();

		$this->assertEquals('bar', $this->targetStorage->file_get_contents('foo.txt'));
		$this->assertEquals('foobar', $this->targetStorage->file_get_contents('bar.txt'));

		$targetCache = $this->targetStorage->getCache();
		$this->assertEquals(md5('foo.txt'), $targetCache->get('foo.txt')->getEtag());
		$this->assertEquals(md5('bar.txt'), $targetCache->get('bar.txt')->getEtag());
	}

	public function testRecurse() {
		$this->sourceStorage->file_put_contents('foo.txt', 'bar');
		$this->sourceStorage->mkdir('bar');
		$this->sourceStorage->mkdir('bar/asd');
		$this->sourceStorage->file_put_contents('bar/bar.txt', 'foo');
		$this->fileReceiver->copyFiles();

		$this->assertEquals('bar', $this->targetStorage->file_get_contents('foo.txt'));
		$this->assertEquals('foo', $this->targetStorage->file_get_contents('bar/bar.txt'));
		$this->assertTrue($this->targetStorage->is_dir('bar/asd'));

		$targetCache = $this->targetStorage->getCache();
		$this->assertEquals(md5('foo.txt'), $targetCache->get('foo.txt')->getEtag());
		$this->assertEquals(md5('bar/bar.txt'), $targetCache->get('bar/bar.txt')->getEtag());
		$this->assertEquals(md5('bar'), $targetCache->get('bar')->getEtag());
	}

	public function testDontRecurseSameEtag() {
		$this->sourceStorage->file_put_contents('foo.txt', 'bar');
		$this->sourceStorage->mkdir('bar');
		$this->sourceStorage->mkdir('bar/asd');
		$this->sourceStorage->file_put_contents('bar/bar.txt', 'foo');

		$this->targetStorage->mkdir('bar');
		$this->targetStorage->getScanner()->scan('');
		$this->targetStorage->getCache()->put('bar', [
			'etag' => md5('bar')
		]);

		$this->fileReceiver->copyFiles();

		$this->assertEquals('bar', $this->targetStorage->file_get_contents('foo.txt'));
		$this->assertFalse($this->targetStorage->file_exists('/bar/bar.txt'));
		$this->assertFalse($this->targetStorage->is_dir('bar/asd'));
	}

	public function testRecurseDifferentEtag() {
		$this->sourceStorage->file_put_contents('foo.txt', 'bar');
		$this->sourceStorage->mkdir('bar');
		$this->sourceStorage->mkdir('bar/asd');
		$this->sourceStorage->file_put_contents('bar/bar.txt', 'foo');

		$this->targetStorage->mkdir('bar');
		$this->targetStorage->getScanner()->scan('');

		$this->fileReceiver->copyFiles();

		$this->assertEquals('bar', $this->targetStorage->file_get_contents('foo.txt'));
		$this->assertTrue($this->targetStorage->file_exists('/bar/bar.txt'));
		$this->assertTrue($this->targetStorage->is_dir('bar/asd'));
	}
}
