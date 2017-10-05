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

use OC\Files\Storage\Local;
use OC\Files\Storage\Temporary;
use OC\Remote\Credentials;
use OCA\Files\Migrate\MigratePlugin;
use OCA\Files\Migrate\RemoteStorageFactory;
use OCP\Files\Folder;
use OCP\Files\IHomeStorage;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IStorage;
use OCP\Remote\IInstance;
use Test\TestCase;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * @group DB
 */
class MigratePluginTest extends TestCase {
	use UserTrait;
	use MountProviderTrait;

	/** @var  IInstance|\PHPUnit_Framework_MockObject_MockObject */
	private $instance;
	/** @var RemoteStorageFactory|\PHPUnit_Framework_MockObject_MockObject */
	private $remoteStorageFactory;
	/** @var  IStorage */
	private $remoteStorage;
	/** @var  IStorage */
	private $homeStorage;
	/** @var  Folder|\PHPUnit_Framework_MockObject_MockObject */
	private $userFolder;
	/** @var  IRootFolder|\PHPUnit_Framework_MockObject_MockObject */
	private $rootFolder;

	public function setUp() {
		parent::setUp();

		$this->remoteStorage = $this->getMockBuilder(Temporary::class)
			->setMethods(['getETag'])
			->getMock();
		// make sure we have persistent etags as a remote storage would have
		$this->remoteStorage->expects($this->any())
			->method('getETag')
			->willReturnCallback(function ($path) {
				return md5(trim($path, '/'));
			});

		$this->remoteStorageFactory = $this->createMock(RemoteStorageFactory::class);
		$this->remoteStorageFactory->expects($this->any())
			->method('getRemoteStorage')
			->willReturn($this->remoteStorage);

		$this->instance = $this->createMock(IInstance::class);
		$this->instance->expects($this->any())
			->method('getFullUrl')
			->willReturn('http://example.com');
		$this->instance->expects($this->any())
			->method('getProtocol')
			->willReturn('http');

		$this->homeStorage = $this->getMockBuilder(Temporary::class)
			->setMethods(['instanceOfStorage'])
			->getMock();
		$this->homeStorage->expects($this->any())
			->method('instanceOfStorage')
			->willReturnCallback(function ($class) {
				return $class === IHomeStorage::class || $class === Local::class;
			});
		$this->homeStorage->mkdir('files');

		$this->createUser('test', 'test');
		$this->registerMount('test', $this->homeStorage, '/test');

		$this->userFolder = $this->createMock(Folder::class);
		$this->userFolder->expects($this->any())
			->method('getStorage')
			->willReturn($this->homeStorage);

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->rootFolder->expects($this->any())
			->method('getUserFolder')
			->with('foo')
			->willReturn($this->userFolder);
	}

	public function testMigrate() {
		$this->remoteStorage->mkdir('sub');
		$this->remoteStorage->file_put_contents('foo.txt', 'foo');
		$this->remoteStorage->file_put_contents('sub/bar.txt', 'foo');
		$this->remoteStorage->file_put_contents('sub/other.txt', 'foobar');


		$migrator = new MigratePlugin($this->getL10NMock(), $this->rootFolder, $this->remoteStorageFactory);

		$migrator->migrateFrom('foo', $this->instance, new Credentials('foo', 'bar'));

		$homeCache = $this->homeStorage->getCache();
		$this->assertTrue($this->homeStorage->file_exists('files/foo.txt'));
		$this->assertTrue($this->homeStorage->file_exists('files/sub'));
		$this->assertTrue($this->homeStorage->file_exists('files/sub/bar.txt'));
		$this->assertTrue($homeCache->inCache('files/sub/bar.txt'));
		$this->assertEquals(9, $homeCache->get('files/sub')->getSize());
	}
}
