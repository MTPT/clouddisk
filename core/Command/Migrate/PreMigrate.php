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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PreMigrate extends MigrateBase {
	protected function configure() {
		parent::configure();

		$this
			->setName('user-migrate:pre-migrate')
			->setDescription('Run pre-migration for a user from a remote server');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if ($this->verifyInput($input, $output)) {
			$this->loadPlugins();
			$this->migrateManager->preMigrateFrom(
				$this->getTargetUser($input),
				$this->getInstance($input),
				$this->getCredentials($input)
			);
		}
	}
}
