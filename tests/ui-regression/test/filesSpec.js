const puppeteer = require('puppeteer');
const helper = require('../helper.js');
const config = require('../config.js');

describe('files', function () {

	before(async () => {
		await helper.init(this)
		await helper.login(this)
	});
	after(async () => await helper.exit());

	config.resolutions.forEach(function (resolution) {

		it('file-sidebar-share.' + resolution.title, async function () {
			return helper.takeAndCompare(this, '/index.php/apps/files', async function (page) {
				await page.click('[data-file=\'welcome.txt\'] .action-share');
				await page.waitForSelector('input.shareWithField');
				await helper.delay(500); // wait for animation
				await page.$eval('body', e => { $('.shareWithField').blur() });
			}, {waitUntil: 'networkidle2', viewport: resolution});
		});
		it('file-sidebar.popover.' + resolution.title, async function () {
			return helper.takeAndCompare(this, '/index.php/apps/files', async function (page) {
				await page.click('[data-file=\'welcome.txt\'] .action-menu');
				await page.waitForSelector('.fileActionsMenu');
			}, {waitUntil: 'networkidle2', viewport: resolution});
		});
		it('file-sidebar-details.' + resolution.title, async function() {
			return helper.takeAndCompare(this, undefined, async function (page) {
				await page.click('[data-file=\'welcome.txt\'] .fileActionsMenu [data-action=\'Details\']');
				await page.waitForSelector('#commentsTabView');
				await helper.delay(500); // wait for animation
			});
		});
		it('file-sidebar-details-sharing.' + resolution.title, async function() {
			return helper.takeAndCompare(this, undefined, async function (page) {
				let tab = await helper.childOfClassByText(page, 'tabHeaders', 'Sharing');
				tab[0].click();
				await page.waitForSelector('input.shareWithField');
				await helper.delay(500); // wait for animation
				await page.$eval('body', e => { $('.shareWithField').blur() });
			});
		});
		it('file-sidebar-details-versions.' + resolution.title, async function() {
			return helper.takeAndCompare(this, undefined, async function (page) {
				let tab = await helper.childOfClassByText(page, 'tabHeaders', 'Versions');
				tab[0].click();
				await helper.delay(100); // wait for animation
			});
		});


	});



});
