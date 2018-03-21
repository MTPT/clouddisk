const helper = require('../helper.js');
const config = require('../config.js');

describe('apps', function () {

	before(async () => {
    await helper.init(this) 
    await helper.login(this) 
  });
	after(async () => await helper.exit());

	config.resolutions.forEach(function (resolution) {
		it('apps.' + resolution.title, async function () {
			return helper.takeAndCompare(this, 'index.php/settings/apps', async function (page) {
			}, {viewport: resolution});
		});

		['installed', 'enabled', 'disabled', 'app-bundles'].forEach(function(endpoint) {
			it('apps.' + endpoint + '.' + resolution.title, async function () {
				return helper.takeAndCompare(this, undefined, async function (page) {
					await page.waitForSelector('#app-navigation-toggle', {
						visible: true,
						timeout: 1000,
					}).then((element) => element.click())
					await helper.delay(500);
					await page.click('li#app-category-' + endpoint + ' a');
					await helper.delay(500);
					await page.waitForSelector('#app-content:not(.icon-loading)');
				}, {viewport: resolution});
			});
		});
	});

});
