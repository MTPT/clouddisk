const puppeteer = require('puppeteer');
const helper = require('../helper.js');
const config = require('../config.js');

describe('public', function () {

	before(async () => await helper.init(this));
	after(() => helper.exit());

	/**
	 * Test invalid file share rendering
	 */
	config.resolutions.forEach(function (resolution) {
		it('file-share-invalid.' + resolution.title, async function () {
			return helper.takeAndCompare(this, '/index.php/s/invalid', async function () {
			}, { waitUntil: 'networkidle2', viewport: resolution});
		});
	});

	it('login', async function () {
		this.timeout(30000);
		await helper.resetBrowser();
		return helper.takeAndCompare(this, '/', async function (page) {
			const login = await page.$('#user');
			const password = await page.$('#password');
			await login.type('admin');
			await password.type('admin');
			const inputElement = await page.$('input[type=submit]');
			inputElement.click();
			return await page.waitForNavigation({waitUntil: 'load'});
			}, {viewport: {w: 1920, h: 1080}});
	});

	/**
	 * Share a file
	 */
	it('file-share', async function () {
		this.timeout(30000);
		return helper.takeAndCompare(this, '/index.php/apps/files', async function (page) {
			await page.click('[data-file=\'welcome.txt\'] .action-share');
			await page.waitForSelector('input.linkCheckbox');
			const link = await page.$('input.linkCheckbox');
			link.click();
			await page.waitForSelector('.linkText');
			return await helper.delay(500);
		}, { waitUntil: 'networkidle2', viewport: {w: 1920, h:1080}});
	});

});
