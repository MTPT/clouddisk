<?php
/**
 * @author Thomas Citharel <tcit@tcit.fr>
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

namespace OCA\DAV\CalDAV\Reminder;


use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {
	protected $factory;

	public function __construct(IFactory $factory) {
		$this->factory = $factory;
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 */
	public function prepare(INotification $notification, $languageCode) {
		if ($notification->getApp() !== 'dav') {
			throw new \InvalidArgumentException();
		}

		// Read the language from the notification
		$l = $this->factory->get('dav', $languageCode);

		if ($notification->getSubject() === 'calendar_reminder') {
			$subjectParams = $notification->getSubjectParameters();
			$notification->setParsedSubject((string)$l->t('Your event "%s" is in %s', [$subjectParams[0], date_format($subjectParams[1], 'Y-m-d H:i:s')]));
			$notification->setParsedMessage($notification->getMessageParameters()[0]);
		} else {
			// Unknown subject => Unknown notification => throw
			throw new \InvalidArgumentException();
		}
		return $notification;
	}
}