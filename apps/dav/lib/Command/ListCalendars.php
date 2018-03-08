<?php
/**
 * @copyright Copyright (c) 2018, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license AGPL-3.0
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
namespace OCA\DAV\Command;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCalendars extends Command {

	/** @var IUserManager */
	protected $userManager;

	/** @var IGroupManager $groupManager */
	private $groupManager;

	/** @var \OCP\IDBConnection */
	protected $dbConnection;

	/**
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param IDBConnection $dbConnection
	 */
	function __construct(IUserManager $userManager, IGroupManager $groupManager, IDBConnection $dbConnection) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->dbConnection = $dbConnection;
	}

	protected function configure() {
		$this
			->setName('dav:list-calendars')
			->setDescription('List all calendars of a user')
			->addArgument('user',
				InputArgument::REQUIRED,
				'User for whom all calendars will be listed');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$user = $input->getArgument('user');
		if (!$this->userManager->userExists($user)) {
			throw new \InvalidArgumentException("User <$user> in unknown.");
		}

		$principalBackend = new Principal(
			$this->userManager,
			$this->groupManager,
			\OC::$server->getShareManager(),
			\OC::$server->getUserSession()
		);
		$random = \OC::$server->getSecureRandom();
		$logger = \OC::$server->getLogger();
		$dispatcher = \OC::$server->getEventDispatcher();

		$caldav = new CalDavBackend($this->dbConnection, $principalBackend,
			$this->userManager, $this->groupManager, $random, $logger,
			$dispatcher);

		$calendars = $caldav->getCalendarsForUser("principals/users/$user");

		$calendarTableData = [];
		foreach($calendars as $calendar) {
			// skip birthday calendar
			if ($calendar['uri'] === BirthdayService::BIRTHDAY_CALENDAR_URI) {
				continue;
			}

			$readOnly = false;
			$readOnlyIndex = '{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}read-only';
			if (isset($calendar[$readOnlyIndex])) {
				$readOnly = $calendar[$readOnlyIndex];
			}

			$calendarTableData[] = [
				$calendar['uri'],
				$calendar['{DAV:}displayname'],
				$calendar['{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal'],
				$calendar['{' . \OCA\DAV\DAV\Sharing\Plugin::NS_NEXTCLOUD . '}owner-displayname'],
				$readOnly ? ' x ' : ' ✓ ',
			];
		}

		$table = new Table($output);
		$table->setHeaders(['uri', 'displayname', 'owner\'s userid', 'owner\'s displayname', 'writable'])
			->setRows($calendarTableData);

		$table->render();
	}

}
