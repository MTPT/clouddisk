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

namespace OCP\Migrate;

use OC\Hooks\Emitter;
use OCP\Remote\ICredentials;
use OCP\Remote\IInstance;

/**
 * Plugin to migrate a app data from a remote instance
 *
 * @since 13.0.0
 */
interface IMigratePlugin extends Emitter {
	/**
	 * Get the description of the migration, i.e. "Outgoing shares"
	 *
	 * This is used to show the user a list of all data that can be migrated.
	 *
	 * Returned name should be translated
	 *
	 * @return string
	 *
	 * @since 13.0.0
	 */
	public function getName();

	/**
	 * @param string $targetUser
	 * @param IInstance $remote
	 * @param ICredentials $credentials
	 *
	 * To report migration progress the plugin should emit events withing the scope "Migrate"
	 *
	 * 2 progress event types are supported:
	 *  - "relativeProgress" with a progress value from 0 to 100
	 *  - "absoluteProgress" for when the total amount of work is unknown with the progress value being an incrementing integer
	 *       starting at 0
	 *
	 * @since 13.0.0
	 */
	public function migrateFrom($targetUser, IInstance $remote, ICredentials $credentials);

	/**
	 * Whether or not the migration operation is destructive for any remote server involved.
	 *
	 * A destructive migration makes changes to a remote server such as modifying a remote share
	 * Non destructive migrations don't make any remote changes and can thus safely be run in advance of the final
	 * migration, an example would be copying over remote files.
	 *
	 * @return bool
	 */
	public function isDestructive();
}
