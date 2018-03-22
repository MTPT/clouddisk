const puppeteer = require('puppeteer');
const helper = require('../helper.js');
const config = require('../config.js');

describe('public', function () {

	before(async () => { 
    await helper.init(this)
    await helper.login(this)
  });
	after(async () => await helper.exit());

	/**
	 * Test invalid file share rendering
	 */
	config.resolutions.forEach(function (resolution) {
		it('file-share-invalid.' + resolution.title, async function () {
			return helper.takeAndCompare(this, '/index.php/s/invalid', async function () {
			}, { waitUntil: 'networkidle2', viewport: resolution});
		});
	});

	let shareLink = {};
	/**
	 * Share a file
	 */
	it('file-share-link', async function () {
		return helper.takeAndCompare(this, '/index.php/apps/files', async function (page) {
			const element = await page.$('[data-file="welcome.txt"] .action-share');
			await element.click('[data-file="welcome.txt"] .action-share');
			await page.waitForSelector('input.linkCheckbox');
			const linkCheckbox = await page.$('.linkShareView label');
			await linkCheckbox.click();
			await helper.delay(500);
			const text = await page.waitForSelector('.linkText');
			const link = await (await text.getProperty('value')).jsonValue();
			shareLink[page.url()] = link;
			return await helper.delay(500);
		}, { runOnly: true, waitUntil: 'networkidle2', viewport: {w: 1920, h:1080}});
	});

	it('file-share-valid', async function () {
		return helper.takeAndCompare(this, '/index.php/apps/files', async function (page) {
			await page.goto(shareLink[page.url()]);
			await helper.delay(500);
		}, { waitUntil: 'networkidle2', viewport: {w: 1920, h:1080}});
	});
	it('file-share-valid-actions', async function () {
		return helper.takeAndCompare(this, undefined, async function (page) {
			const moreButton = await page.waitForSelector('#header-secondary-action');
			await moreButton.click();
			await page.evaluate((data) => {
				return document.querySelector('#directLink').value = 'http://nextcloud.example.com/';
			});
			await helper.delay(500);
		}, { waitUntil: 'networkidle2', viewport: {w: 1920, h:1080}});
	});

	it('file-unshare', async function () {
		return helper.takeAndCompare(this, '/index.php/apps/files', async function (page) {
			const element = await page.$('[data-file="welcome.txt"] .action-share');
			await element.click('[data-file="welcome.txt"] .action-share');
			await page.waitForSelector('input.linkCheckbox');
			const linkCheckbox = await page.$('.linkShareView label');
			await linkCheckbox.click();
			await helper.delay(500);
		}, { waitUntil: 'networkidle2', viewport: {w: 1920, h:1080}});
	});

});
