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
			return helper.takeAndCompare(this, 'index.php/apps/files', async function (page) {
				let element = await page.$('[data-file="welcome.txt"] .action-share');
				await element.click('[data-file="welcome.txt"] .action-share');
				await page.waitForSelector('.shareWithField');
				await helper.delay(500);
				await page.$eval('body', e => { $('.shareWithField').blur() });
			}, {viewport: resolution, waitUntil: 'networkidle2'});
		});
		it('file-popover.' + resolution.title, async function () {
			return helper.takeAndCompare(this, 'index.php/apps/files', async function (page) {
				await page.click('[data-file=\'welcome.txt\'] .action-menu');
				await page.waitForSelector('.fileActionsMenu');
			}, {viewport: resolution, waitUntil: 'networkidle2'});
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
		it('file-popover.favorite.' + resolution.title, async function () {
			return helper.takeAndCompare(this, 'index.php/apps/files', async function (page) {
				await page.click('[data-file=\'welcome.txt\'] .action-menu');
				await page.waitForSelector('.fileActionsMenu')
				await page.click('[data-file=\'welcome.txt\'] .fileActionsMenu [data-action=\'Favorite\']');;
			}, {viewport: resolution, waitUntil: 'networkidle2'});
		});

		it('file-favorites.' + resolution.title, async function () {
			return helper.takeAndCompare(this, 'index.php/apps/files', async function (page) {
				try {
					await page.waitForSelector('#app-navigation-toggle', {
						visible: true,
						timeout: 1000,
					}).then((element) => element.click())
				} catch (err) {}
				await page.click('#app-navigation [data-id=\'favorites\'] a');
				await helper.delay(500); // wait for animation
			}, {viewport: resolution, waitUntil: 'networkidle2'});
		});


	});



});
