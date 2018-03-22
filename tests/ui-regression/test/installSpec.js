const helper = require('../helper.js');
const config = require('../config.js');

describe('install', function () {

	before(async () => await helper.init(this));
	after(async () => await helper.exit());

	config.resolutions.forEach(function (resolution) {
		it('show-page.' + resolution.title, async function () {
			// (test, route, prepare, action, options
			return helper.takeAndCompare(this, '/index.php', async (page) => {
				await helper.delay(100);
				await page.$eval('body', function (e) {
					$('#adminlogin').blur();
				});
				await helper.delay(100);
			}, { waitUntil: 'networkidle0', viewport: resolution});
		});

		it('show-advanced.' + resolution.title, async function () {
			// (test, route, prepare, action, options
			return helper.takeAndCompare(this, undefined, async (page) => {
				await page.click('#showAdvanced');
				await helper.delay(500);
			});
		});
		it('show-advanced-mysql.' + resolution.title, async function () {
			// (test, route, prepare, action, options
			return helper.takeAndCompare(this, undefined, async (page) => {
				await page.click('label.mysql');
				await helper.delay(500);
			});
		});
	});

	it('runs', async function () {
		this.timeout(5*60*1000);
		helper.pageBase.setDefaultNavigationTimeout(5*60*1000);
		helper.pageCompare.setDefaultNavigationTimeout(5*60*1000);
		// just run for one resolution since we can only install once
		return helper.takeAndCompare(this, '/index.php',  async function (page) {
			const login = await page.type('#adminlogin', 'admin');
			const password = await page.type('#adminpass', 'admin');
			const inputElement = await page.$('input[type=submit]');
			await inputElement.click();
			await page.waitForNavigation({waitUntil: 'networkidle0'});
			helper.pageBase.setDefaultNavigationTimeout(60000);
			helper.pageCompare.setDefaultNavigationTimeout(60000);
		}, { waitUntil: 'networkidle0', viewport: {w: 1920, h: 1080}});
	});

});
