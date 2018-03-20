const helper = require('../helper.js');
const config = require('../config.js');

describe('install', function () {

	before(async () => await helper.init(this));
	after(async () => await helper.exit());

	config.resolutions.forEach(function (resolution) {
		it('show-page.' + resolution.title, async function () {
			// (test, route, prepare, action, options
			return helper.takeAndCompare(this, '/index.php', async () => {}, { waitUntil: 'networkidle0', viewport: resolution});
		});
	});

	it('runs', async function () {
		this.timeout(50000);
		// just run for one resolution since we can only install once
		return helper.takeAndCompare(this, '/index.php',  async function (page) {
			const login = await page.type('#adminlogin', 'admin');
			const password = await page.type('#adminpass', 'admin');
			const inputElement = await page.$('input[type=submit]');
			await inputElement.click();
			return await page.waitForNavigation({waitUntil: 'networkidle0'});
		}, { waitUntil: 'networkidle0', viewport: {w: 1920, h: 1080}});
	});

});
