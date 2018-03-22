const helper = require('../helper.js');
const config = require('../config.js');

describe('settings', function () {

	before(async () => {
    await helper.init(this) 
    await helper.login(this) 
  });
	after(async () => await helper.exit());

	config.resolutions.forEach(function (resolution) {
		it('personal.' + resolution.title, async function () {
			return helper.takeAndCompare(this, '/index.php/settings/user', async function (page) {
			}, {viewport: resolution});
		});

		it('admin.' + resolution.title, async function () {
			return helper.takeAndCompare(this, '/index.php/settings/admin', async function (page) {
			}, {viewport: resolution});
		});

		['sharing', 'security', 'theming', 'encryption', 'additional', 'tips-tricks'].forEach(function(endpoint) {
			it('admin.' + endpoint + '.' + resolution.title, async function () {
				return helper.takeAndCompare(this, '/index.php/settings/admin/' + endpoint, async function (page) {
				}, {viewport: resolution, waitUntil: 'networkidle2'});
			});
		});

		it('usermanagement.' + resolution.title, async function () {
			return helper.takeAndCompare(this, '/index.php/settings/users', async function (page) {
			}, {viewport: resolution});
		});

		it('usermanagement.add.' + resolution.title, async function () {
			return helper.takeAndCompare(this, undefined, async function (page) {
				try {
					await page.waitForSelector('#app-navigation-toggle', {
						visible: true,
						timeout: 1000,
					}).then((element) => element.click())
				} catch (err) {}
				let newUserButton = await page.waitForSelector('#new-user-button');
				await newUserButton.click();
				await helper.delay(200);
				await page.$eval('body', function (e) {
					$('#newusername').blur();
				})
				await helper.delay(100);
			}, {viewport: resolution});
		});

	});
});
