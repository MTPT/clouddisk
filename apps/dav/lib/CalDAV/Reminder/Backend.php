<?php
/**
 * @copyright Copyright (c) 2017 Thomas Citharel <tcit@tcit.fr>
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

namespace OCA\DAV\CalDAV\Reminder;


use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUserSession;
use Sabre\VObject;
use Sabre\VObject\Component\VAlarm;
use Sabre\VObject\Reader;

/**
 * Class Backend
 *
 * @package OCA\DAV\CalDAV\Reminder
 */
class Backend {

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IUserSession */
	protected $userSession;

	/** @var IDBConnection */
	protected $db;

	/** @var CalDavBackend */
	protected $calDavBackend;

	const ALARM_TYPES = ['AUDIO', 'EMAIL', 'DISPLAY'];

	/**
	 * @param IDBConnection $db
	 * @param CalDavBackend $calDavBackend
	 * @param IGroupManager $groupManager
	 * @param IUserSession $userSession
	 */
	public function __construct(IDBConnection $db, CalDavBackend $calDavBackend, IGroupManager $groupManager, IUserSession $userSession) {
		$this->db = $db;
		$this->calDavBackend = $calDavBackend;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
	}

	/**
	 * Saves reminders when a calendar object with some alarms was created/updated/deleted
	 *
	 * @param string $action
	 * @param array $calendarData
	 * @param array $shares
	 * @param array $objectData
	 */
	public function onTouchCalendarObject($action, array $calendarData, array $shares, array $objectData) {
		if (!isset($calendarData['principaluri'])) {
			return;
		}

		if ($action === '\OCA\DAV\CalDAV\CalDavBackend::deleteCalendarObject') {
			$this->cleanRemindersForEvent($calendarData['id'], $objectData['uri']);
			return;
		}

		$principal = explode('/', $calendarData['principaluri']);
		$owner = array_pop($principal);

		$object = $this->getObjectNameAndType($objectData);

		$users = $this->getUsersForShares($shares);
		$users[] = $owner;

		$this->cleanRemindersForEvent($objectData['calendarid'], $objectData['uri']);

		$vobject = VObject\Reader::read($objectData['calendardata']);

		foreach ($vobject->VEVENT->VALARM as $alarm) {
			if ($alarm instanceof VAlarm) {
				$type = strtoupper($alarm->ACTION->getValue());
				if (in_array($type, self::ALARM_TYPES, true)) {
					$time = $alarm->getEffectiveTriggerTime();

					foreach ($users as $user) {
						$query = $this->db->getQueryBuilder();
						$query->insert('calendar_reminders')
							->values([
								'user' => $query->createNamedParameter($user),
								'calendarid' => $query->createNamedParameter($objectData['calendarid']),
								'objecturi' => $query->createNamedParameter($objectData['uri']),
								'type' => $query->createNamedParameter($type),
								'notificationDate' => $query->createNamedParameter($time->getTimestamp()),
							])->execute();
					}
				}
			}
		}
	}

	/**
	 * @param array $objectData
	 * @return string[]|bool
	 */
	protected function getObjectNameAndType(array $objectData) {
		$vObject = Reader::read($objectData['calendardata']);
		$component = $componentType = null;
		foreach($vObject->getComponents() as $component) {
			if (in_array($component->name, ['VEVENT', 'VTODO'], true)) {
				$componentType = $component->name;
				break;
			}
		}

		if (!$componentType) {
			// Calendar objects must have a VEVENT or VTODO component
			return false;
		}

		if ($componentType === 'VEVENT') {
			return ['id' => (string) $component->UID, 'name' => (string) $component->SUMMARY, 'type' => 'event'];
		}
		return ['id' => (string) $component->UID, 'name' => (string) $component->SUMMARY, 'type' => 'todo', 'status' => (string) $component->STATUS];
	}

	/**
	 * Get all users that have access to a given calendar
	 *
	 * @param array $shares
	 * @return string[]
	 */
	protected function getUsersForShares(array $shares)
	{
		$users = $groups = [];
		foreach ($shares as $share) {
			$prinical = explode('/', $share['{http://owncloud.org/ns}principal']);
			if ($prinical[1] === 'users') {
				$users[] = $prinical[2];
			} else if ($prinical[1] === 'groups') {
				$groups[] = $prinical[2];
			}
		}

		if (!empty($groups)) {
			foreach ($groups as $gid) {
				$group = $this->groupManager->get($gid);
				if ($group instanceof IGroup) {
					foreach ($group->getUsers() as $user) {
						$users[] = $user->getUID();
					}
				}
			}
		}

		return array_unique($users);
	}

	/**
	 * Cleans reminders in database
	 *
	 * @param string $calendarId
	 * @param string $objectUri
	 */
	public function cleanRemindersForEvent($calendarId, $objectUri)
	{
		$query = $this->db->getQueryBuilder();

		$query->delete('calendar_reminders')
			->where($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)))
			->andWhere($query->expr()->eq('objecturi', $query->createNamedParameter($objectUri)))
			->execute();
	}

	public function cleanRemindersForCalendar($calendarId)
	{
		$query = $this->db->getQueryBuilder();

		$query->delete('calendar_reminders')
			->where($query->expr()->eq('calendarid', $query->createNamedParameter($calendarId)))
			->execute();
	}

	public function removeReminder($reminderId)
	{
		$query = $this->db->getQueryBuilder();

		$query->delete('calendar_reminders')
			->where($query->expr()->eq('id', $query->createNamedParameter($reminderId)))
			->execute();
	}

	/**
	 * Get reminders
	 *
	 * @return array
	 */
	public function getReminders()
	{
		$query = $this->db->getQueryBuilder();
		$fields = ['id', 'calendarid', 'objecturi', 'type', 'notificationDate', 'user'];
		$result = $query->select($fields)
			->from('calendar_reminders')
			->execute();

		$reminders = [];
		while($row = $result->fetch(\PDO::FETCH_ASSOC)) {
			$reminder = [
				'id' => $row['id'],
				'user' => $row['user'],
				'calendarId' => $row['calendarid'],
				'objecturi' => $row['objecturi'],
				'type' => $row['type'],
				'notificationDate' => $row['notificationDate']
			];

			$reminder['event'] = $this->getCalendarObject($reminder['calendarId'], $reminder['objecturi']);

			$reminder['calendar'] = $this->getCalendarById($reminder['calendarId']);

			$reminders[] = $reminder;

		}
		return $reminders;
	}

	public function getCalendarById($id)
	{
		return $this->calDavBackend->getCalendarById($id);
	}

	public function getCalendarObject($calendarId, $objectUri)
	{
		return $this->calDavBackend->getCalendarObject($calendarId, $objectUri);
	}
}
