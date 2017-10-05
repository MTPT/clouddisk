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

namespace OC\Core\Command\Migrate;

use OC\Core\Command\Base;
use OC\Migrate\MigrateManager;
use OC\Remote\Api\NotFoundException;
use OC\Remote\Credentials;
use OCP\App\IAppManager;
use OCP\IContainer;
use OCP\Remote\Api\IApiFactory;
use OCP\Remote\IInstanceFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateBase extends Base {
	/** @var MigrateManager */
	protected $migrateManager;
	/** @var IAppManager */
	private $appManager;
	/** @var IContainer */
	private $container;
	/** @var IInstanceFactory */
	private $instanceFactory;
	/** @var IApiFactory */
	private $apiFactory;

	protected function configure() {
		parent::configure();

		$this
			->addArgument('target_user', InputArgument::REQUIRED, 'The user id of the local target user')
			->addArgument('remote', InputArgument::REQUIRED, 'The url of the remote server')
			->addArgument('remote_user', InputArgument::REQUIRED, 'The username of the remote user')
			->addArgument('remote_password', InputArgument::REQUIRED, 'The password of the remote user');
	}

	/**
	 * @param MigrateManager $migrateManager
	 * @param IAppManager $appManager
	 * @param IContainer $container
	 * @param IInstanceFactory $instanceFactory
	 * @param IApiFactory $apiFactory
	 */
	public function __construct(
		MigrateManager $migrateManager,
		IAppManager $appManager,
		IContainer $container,
		IInstanceFactory $instanceFactory,
		IApiFactory $apiFactory
	) {
		parent::__construct();
		$this->migrateManager = $migrateManager;
		$this->appManager = $appManager;
		$this->container = $container;
		$this->instanceFactory = $instanceFactory;
		$this->apiFactory = $apiFactory;
	}

	protected function loadPlugins() {
		$this->migrateManager->loadPluginsFromApps($this->appManager, $this->container);
	}

	protected function getInstance(InputInterface $input) {
		return $this->instanceFactory->getInstance($input->getArgument('remote'));
	}

	protected function getCredentials(InputInterface $input) {
		return new Credentials($input->getArgument('remote_user'), $input->getArgument('remote_password'));
	}

	protected function getTargetUser(InputInterface $input) {
		return $input->getArgument('target_user');
	}

	protected function verifyInput(InputInterface $input, OutputInterface $output) {
		$instance = $this->getInstance($input);
		$credentials = $this->getCredentials($input);

		try {
			if (version_compare($instance->getVersion(), '13.0.0', '<')) {
				$output->writeln("<error>Migration is only supported from Nextcloud 13 and higher</error>");
				return false;
			}
			if (!$instance->isActive()) {
				$output->writeln("<error>Remote Nextcloud instance is in maintenance mode</error>");
				return false;
			}
		} catch (NotFoundException $e) {
			$output->writeln("<error>No Nextcloud instance found at remote url</error>");
			return false;
		}

		$userApi = $this->apiFactory->getApiCollection($instance, $credentials)->getUserApi();
		try {
			$userApi->getUser($credentials->getUsername());
		} catch (\Exception $e) {
			$output->writeln("<error>Remote user not found or invalid password</error>");
			return false;
		}

		return true;
	}
}
