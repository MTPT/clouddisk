<?php
/**
 * @copyright 2017, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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

namespace OCA\DAV\AppleProvisioning;

use OCA\Theming\ThemingDefaults;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\UUIDUtil;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class AppleProvisioningPlugin extends ServerPlugin {

	/**
	 * @var Server
	 */
	protected $server;

	/**
	 * @var IURLGenerator
	 */
	protected $urlGenerator;

	/**
	 * @var IUserSession
	 */
	protected $userSession;

	/**
	 * @var ThemingDefaults
	 */
	protected $themingDefaults;

	/**
	 * @var IRequest
	 */
	protected $request;

	/**
	 * AppleProvisioningPlugin constructor.
	 *
	 * @param IUserSession $userSession
	 * @param IURLGenerator $urlGenerator
	 * @param ThemingDefaults $themingDefaults
	 * @param IRequest $request
	 */
	public function __construct(IUserSession $userSession, IURLGenerator $urlGenerator, ThemingDefaults $themingDefaults, IRequest $request) {
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
		$this->themingDefaults = $themingDefaults;
		$this->request = $request;
	}

	/**
	 * @param Server $server
	 */
	public function initialize(Server $server) {
		$this->server = $server;
		$this->server->on('method:GET', [$this, 'httpGet'], 90);
	}

	/**
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return boolean
	 */
	public function httpGet(RequestInterface $request, ResponseInterface $response) {
		if ($request->getPath() !== AppleProvisioningNode::gitFILENAME) {
			return true;
		}

		$user = $this->userSession->getUser();
		if (!$user) {
			return true;
		}

		$serverProtocol = $this->request->getServerProtocol();
		$USE_SSL = ($serverProtocol === 'https');

		$absoluteURL = $this->urlGenerator->getAbsoluteURL('');
		$parsedUrl = parse_url($absoluteURL);
		if (isset($parsedUrl['port'])) {
			$SERVER_PORT = $parsedUrl['port'];
		} else {
			$SERVER_PORT = $USE_SSL ? 443 : 80;
		}
		$SERVER_URL = $serverProtocol . '://' . $parsedUrl['host'] . '/';

		$DESCRIPTION = $this->themingDefaults->getName();
		$PRINCIPAL_URL = implode([
			$this->urlGenerator->linkTo('', 'remote.php'),
			'/dav/principals/users/',
			$user->getUID(),
		]);
		$USER_ID = $user->getUID();

		$reverseDomain = implode('.', array_reverse(explode('.', $parsedUrl['host'])));

		$CALDAV_UUID = UUIDUtil::getUUID();
		$CARDDAV_UUID = UUIDUtil::getUUID();
		$PROFILE_UUID = UUIDUtil::getUUID();

		$CALDAV_IDENTIFIER = $reverseDomain . '.' . $CALDAV_UUID;
		$CARDDAV_IDENTIFIER = $reverseDomain . '.' . $CARDDAV_UUID;
		$PROFILE_IDENTIFIER = $reverseDomain . '.' . $PROFILE_UUID;

		$USE_SSL = $USE_SSL ? '<true/>' : '<false/>';

		$xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
	<key>PayloadContent</key>
	<array>
		<dict>
			<key>CalDAVAccountDescription</key>
			<string>$DESCRIPTION</string>
			<key>CalDAVHostName</key>
			<string>$SERVER_URL</string>
			<key>CalDAVUsername</key>
			<string>$USER_ID</string>
			<key>CalDAVUseSSL</key>
			$USE_SSL
			<key>CalDAVPort</key>
			<integer>$SERVER_PORT</integer>
			<key>CalDAVPrincipalURL</key>
			<string>$PRINCIPAL_URL</string>
			<key>PayloadDescription</key>
			<string>Configures a CalDAV account</string>
			<key>PayloadDisplayName</key>
			<string>CalDAV</string>
			<key>PayloadIdentifier</key>
			<string>$CALDAV_IDENTIFIER</string>
			<key>PayloadType</key>
			<string>com.apple.caldav.account</string>
			<key>PayloadUUID</key>
			<string>$CALDAV_UUID</string>
			<key>PayloadVersion</key>
			<integer>1</integer>
		</dict>
		<dict>
			<key>CardDAVAccountDescription</key>
			<string>$DESCRIPTION</string>
			<key>CardDAVHostName</key>
			<string>$SERVER_URL</string>
			<key>CardDAVUsername</key>
			<string>$USER_ID</string>
			<key>CardDAVUseSSL</key>
			$USE_SSL
			<key>CardDAVPort</key>
			<integer>$SERVER_PORT</integer>
			<key>CardDAVPrincipalURL</key>
			<string>$PRINCIPAL_URL</string>
			<key>PayloadDescription</key>
			<string>Configures a CardDAV account</string>
			<key>PayloadDisplayName</key>
			<string>CardDAV</string>
			<key>PayloadIdentifier</key>
			<string>$CARDDAV_IDENTIFIER</string>
			<key>PayloadType</key>
			<string>com.apple.carddav.account</string>
			<key>PayloadUUID</key>
			<string>$CARDDAV_UUID</string>
			<key>PayloadVersion</key>
			<integer>1</integer>
		</dict>
	</array>
	<key>PayloadDisplayName</key>
	<string>$DESCRIPTION</string>
	<key>PayloadIdentifier</key>
	<string>$PROFILE_IDENTIFIER</string>
	<key>PayloadRemovalDisallowed</key>
	<false/>
	<key>PayloadType</key>
	<string>Configuration</string>
	<key>PayloadUUID</key>
	<string>$PROFILE_UUID</string>
	<key>PayloadVersion</key>
	<integer>1</integer>
</dict>
</plist>
EOF;

		$this->server->httpResponse->setStatus(207);
		$this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
		$this->server->httpResponse->setBody($xml);

		return false;
	}

}
