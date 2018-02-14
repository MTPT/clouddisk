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
	init: async function(test) {
		this._outputDirectory = `${config.outputDirectory}/${test.title}`;
		if (!fs.existsSync(config.outputDirectory)) fs.mkdirSync(config.outputDirectory);
		if (!fs.existsSync(this._outputDirectory)) fs.mkdirSync(this._outputDirectory);
		await this.resetBrowser();
	},
	exit: async function() {
		await this.browser.close();
	},
	resetBrowser: async function () {
		if (this.browser) {
			await this.browser.close();
		}
		this.browser = await puppeteer.launch({args: ['--no-sandbox', '--disable-setuid-sandbox'], headless: false});
		this.pageBase = await this.browser.newPage();
		this.pageCompare = await this.browser.newPage();
	},

	takeAndCompare: async function (test, route, action, options) {
		if (!options.waitUntil) {
			options.waitUntil = 'domcontentloaded';
		}

		let fileName = test.test.fullTitle();
		await this.takeScreenshot(this.pageBase, 'base', test, `${config.urlBase}${route}`, action, options);
		await this.takeScreenshot(this.pageCompare, 'change', test, `${config.urlChange}${route}`, action, options);
		return this.compareScreenshots(fileName);
	},

	takeScreenshot: async function(page, suffix, test, route, action, options) {
		let fileName = test.test.fullTitle();
		if (options.viewport) {
			await page.setViewport({
				width: options.viewport.w,
				height: options.viewport.h
			});
		}
		await page.goto(route, {waitUntil: options.waitUntil});
		await action(page);
		await page.screenshot({
			path: `${this._outputDirectory}/${fileName}.${suffix}.png`,
			fullPage: false
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
	}

};
