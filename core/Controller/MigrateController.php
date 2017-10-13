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

namespace OC\Core\Controller;

use OC\Migrate\MigrateManager;
use OC\Remote\Credentials;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\DataResponse;
use OCP\IContainer;
use OCP\IRequest;
use OCP\Remote\Api\IApiFactory;
use OCP\Remote\IInstanceFactory;

class MigrateController extends \OCP\AppFramework\OCSController {
	/** @var MigrateManager */
	private $migrateManager;
	/** @var IAppManager */
	private $appManager;
	/** @var IContainer */
	private $container;
	/** @var IInstanceFactory */
	private $instanceFactory;
	/** @var IApiFactory */
	private $apiFactory;

	/**
	 * MigrateController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param MigrateManager $migrateManager
	 * @param IAppManager $appManager
	 * @param IContainer $container
	 * @param IInstanceFactory $instanceFactory
	 * @param IApiFactory $apiFactory
	 */
	public function __construct(
		$appName,
		IRequest $request,
		MigrateManager $migrateManager,
		IAppManager $appManager,
		IContainer $container,
		IInstanceFactory $instanceFactory,
		IApiFactory $apiFactory
	) {
		parent::__construct($appName, $request);
		$this->migrateManager = $migrateManager;
		$this->appManager = $appManager;
		$this->container = $container;
		$this->instanceFactory = $instanceFactory;
		$this->apiFactory = $apiFactory;
	}

	public function migrateUser($targetUser, $remoteUrl, $remoteUser, $remotePassword) {
		$instance = $this->instanceFactory->getInstance($remoteUrl);
		$this->migrateManager->loadPluginsFromApps($this->appManager, $this->container);
		$this->migrateManager->migrateFrom($targetUser, $instance, new Credentials($remoteUser, $remotePassword));
		return new DataResponse(true);
	}
}
