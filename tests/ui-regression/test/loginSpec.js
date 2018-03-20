const helper = require('../helper.js');
const config = require('../config.js');

describe('login', function () {

	before(async () => await helper.init(this));
	after(async () => await helper.exit());

	/**
	 * Test login page rendering
	 */
	config.resolutions.forEach(function (resolution) {
		it('login-page.' + resolution.title, async function () {
			return helper.takeAndCompare(this, '/', async (page) => {
				// make sure the cursor is not blinking in the login field
				await page.$eval('body', function (e) {
					$('#user').blur();
				});
				return await helper.delay(100);
			}, {viewport: resolution});
		});
	});

	/**
	 * Perform login
	 */
	config.resolutions.forEach(function (resolution) {
		it('login-success.' + resolution.title, async function () {
			this.timeout(20000);
			await helper.resetBrowser();
			return helper.takeAndCompare(this, '/', async function (page) {
				await page.type('#user', 'admin');
				await page.type('#password', 'admin');
				const inputElement = await page.$('input[type=submit]');
				await inputElement.click();
				await page.waitForNavigation({waitUntil: 'networkidle0'});
				return await helper.delay(100);
			}, {viewport: resolution});
		})
	});

	/**
	 * Load settings page
	 */
	config.resolutions.forEach(function (resolution) {
		it('settings.' + resolution.title, async function () {
			this.timeout(20000);
			return helper.takeAndCompare(this, '/index.php/settings/user', async function (page) {
				return await helper.delay(500);
			}, {viewport: resolution, waitUntil: 'networkidle2'});
		});

	});
});
