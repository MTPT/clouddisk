const puppeteer = require('puppeteer');
const pixelmatch = require('pixelmatch');
const expect = require('chai').expect;
const PNG = require('pngjs2').PNG;
const fs = require('fs');
const config = require('./config.js');


module.exports = {
	browser: null,
	pageBase: null,
	pageCompare: null,
	init: async function (test) {
		this._outputDirectory = `${config.outputDirectory}/${test.title}`;
		if (!fs.existsSync(config.outputDirectory)) fs.mkdirSync(config.outputDirectory);
		if (!fs.existsSync(this._outputDirectory)) fs.mkdirSync(this._outputDirectory);
		await this.resetBrowser();
	},
	exit: async function () {
		await this.browser.close();
	},
	resetBrowser: async function () {
		if (this.browser) {
			await this.browser.close();
		}
		this.browser = await puppeteer.launch({
			args: ['--no-sandbox', '--disable-setuid-sandbox'],
			headless: true
		});
		this.pageBase = await this.browser.newPage();
		this.pageCompare = await this.browser.newPage();
		this.pageBase.setDefaultNavigationTimeout(60000);
		this.pageCompare.setDefaultNavigationTimeout(60000);
	},

	login: async function (test) {
		test.timeout(20000);
		await this.resetBrowser();
		await Promise.all([
			this.performLogin(this.pageBase, config.urlBase),
			this.performLogin(this.pageCompare, config.urlChange)
		]);
	},

	performLogin: async function (page, baseUrl) {
		await page.goto(baseUrl + '/index.php/login', {waitUntil: 'networkidle0'});
		await page.type('#user', 'admin');
		await page.type('#password', 'admin');
		const inputElement = await page.$('input[type=submit]');
		inputElement.click();
		return await page.waitForNavigation({waitUntil: 'networkidle0'});
	},

	takeAndCompare: async function (test, route, action, options) {
		// use Promise.all
		if (options === undefined)
			options = {};
		if (options.waitUntil === undefined) {
			options.waitUntil = 'networkidle0';
		}
		if (options.viewport) {
			await Promise.all([
				this.pageBase.setViewport({
					width: options.viewport.w,
					height: options.viewport.h
				}),
				this.pageCompare.setViewport({
					width: options.viewport.w,
					height: options.viewport.h
				})
			]);
		}
		let fileName = test.test.fullTitle();
		if (route !== undefined) {
			await Promise.all([
				this.pageBase.goto(`${config.urlBase}${route}`, {waitUntil: options.waitUntil}),
				this.pageCompare.goto(`${config.urlChange}${route}`, {waitUntil: options.waitUntil})
			]);
		}
		var failed = null;
		try {
			await Promise.all([
				action(this.pageBase),
				action(this.pageCompare)
			]);
		} catch (err) {
			failed = err;
		}
		await this.delay(500);
		await Promise.all([
			this.pageBase.screenshot({
				path: `${this._outputDirectory}/${fileName}.base.png`,
				fullPage: false,
			}),
			this.pageCompare.screenshot({
				path: `${this._outputDirectory}/${fileName}.change.png`,
				fullPage: false
			})
		]);

		return new Promise(async (resolve, reject) => {
			try {
				await this.compareScreenshots(fileName);
			} catch (err) {
				console.log(err);
				if (failed) {
					return reject(failed);
				}
				return reject(err);
			}
			if (failed) {
				return reject(failed);
			}
			return resolve();
		});
	},

	compareScreenshots: function (fileName) {
		let self = this;
		return new Promise((resolve, reject) => {
			const img1 = fs.createReadStream(`${self._outputDirectory}/${fileName}.base.png`).pipe(new PNG()).on('parsed', doneReading);
			const img2 = fs.createReadStream(`${self._outputDirectory}/${fileName}.change.png`).pipe(new PNG()).on('parsed', doneReading);

			let filesRead = 0;

			function doneReading () {
				// Wait until both files are read.
				if (++filesRead < 2) return;

				// The files should be the same size.
				expect(img1.width, 'image widths are the same').equal(img2.width);
				expect(img1.height, 'image heights are the same').equal(img2.height);

				// Do the visual diff.
				const diff = new PNG({width: img1.width, height: img2.height});
				const numDiffPixels = pixelmatch(
					img1.data, img2.data, diff.data, img1.width, img1.height,
					{threshold: 0.3});
				diff.pack().pipe(fs.createWriteStream(`${self._outputDirectory}/${fileName}.diff.png`));

				// The files should look the same.
				expect(numDiffPixels, 'number of different pixels').equal(0);
				resolve();
			}
		});
	},
	/**
	 * Helper function to wait
	 * to make sure that initial animations are done
	 */
	delay: async function (timeout) {
		return new Promise((resolve) => {
			setTimeout(resolve, timeout);
		});
	},

	childOfClassByText: async function (page, classname, text) {
		return page.$x('//*[contains(concat(" ", normalize-space(@class), " "), " ' + classname + ' ")]//text()[normalize-space() = \'' + text + '\']/..');
	},

	childOfIdByText: async function (page, classname, text) {
		return page.$x('//*[contains(concat(" ", normalize-space(@id), " "), " ' + classname + ' ")]//text()[normalize-space() = \'' + text + '\']/..');
	}
};
