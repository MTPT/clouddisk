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

namespace Test\Migrate;

use OC\Migrate\MigrateManager;
use OCP\App\IAppManager;
use OCP\IContainer;
use OCP\Migrate\IMigratePlugin;
use OCP\Remote\ICredentials;
use OCP\Remote\IInstance;
use Test\TestCase;

class MigrateManagerTest extends TestCase {
	/** @var IAppManager|\PHPUnit_Framework_MockObject_MockObject */
	private $appManager;

	/** @var IContainer|\PHPUnit_Framework_MockObject_MockObject */
	private $container;

	/** @var IMigratePlugin|\PHPUnit_Framework_MockObject_MockObject */
	private $dummyPlugin1;
	/** @var IMigratePlugin|\PHPUnit_Framework_MockObject_MockObject */
	private $dummyPlugin2;

	/** @var IInstance */
	private $instance;
	/** @var ICredentials */
	private $credentials;

	protected function setUp() {
		parent::setUp();

		$this->appManager = $this->createMock(IAppManager::class);
		$this->container = $this->createMock(IContainer::class);
		$this->dummyPlugin1 = $this->createMock(IMigratePlugin::class);
		$this->dummyPlugin2 = $this->createMock(IMigratePlugin::class);
		$this->instance = $this->createMock(IInstance::class);
		$this->credentials = $this->createMock(ICredentials::class);
		$invalidPlugin = new \stdClass();

		$this->container->expects($this->any())
			->method('query')
			->willReturnMap([
				['dummy1', $this->dummyPlugin1],
				['dummy2', $this->dummyPlugin2],
				['invalid', $invalidPlugin]
			]);
	}

	public function testLoadPluginsFromApps() {
		$this->appManager->expects($this->any())
			->method('getInstalledApps')
			->willReturn(['foo', 'bar', 'bar2']);

		$this->appManager->expects($this->any())
			->method('getAppInfo')
			->willReturnMap([
				['foo', ['migrate' => ['dummy1']]],
				['bar', ['migrate' => []]],
				['bar2', ['migrate' => ['dummy2']]]
			]);

		$manager = new MigrateManager();
		$manager->loadPluginsFromApps($this->appManager, $this->container);

		$this->assertEquals([$this->dummyPlugin1, $this->dummyPlugin2], $manager->getPlugins());
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testLoadPluginsInvalid() {
		$this->appManager->expects($this->any())
			->method('getInstalledApps')
			->willReturn(['foo']);

		$this->appManager->expects($this->any())
			->method('getAppInfo')
			->willReturnMap([
				['foo', ['migrate' => ['invalid']]]
			]);

		$manager = new MigrateManager();
		$manager->loadPluginsFromApps($this->appManager, $this->container);
	}

	public function testPreMigrate() {
		$this->dummyPlugin1->expects($this->any())
			->method('isDestructive')
			->willReturn(true);
		$this->dummyPlugin2->expects($this->any())
			->method('isDestructive')
			->willReturn(false);

		$this->dummyPlugin1->expects($this->never())
			->method('migrateFrom');
		$this->dummyPlugin2->expects($this->once())
			->method('migrateFrom');

		$manager = new MigrateManager();
		$manager->addPlugin($this->dummyPlugin1);
		$manager->addPlugin($this->dummyPlugin2);

		$manager->preMigrateFrom('', $this->instance, $this->credentials);
	}

	public function testMigrate() {
		$this->dummyPlugin1->expects($this->once())
			->method('migrateFrom');
		$this->dummyPlugin2->expects($this->once())
			->method('migrateFrom');

		$manager = new MigrateManager();
		$manager->addPlugin($this->dummyPlugin1);
		$manager->addPlugin($this->dummyPlugin2);

		$manager->preMigrateFrom('', $this->instance, $this->credentials);
	}
}
