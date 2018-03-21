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
			}, {viewport: resolution, waitUntil: 'networkidle2'});
		});

		it('admin.' + resolution.title, async function () {
			return helper.takeAndCompare(this, '/index.php/settings/admin', async function (page) {
			}, {viewport: resolution, waitUntil: 'networkidle0'});
		});

		['sharing', 'security', 'theming', 'encryption', 'additional', 'tips-tricks'].forEach(function(endpoint) {
			it('admin.' + endpoint + '.' + resolution.title, async function () {
				return helper.takeAndCompare(this, '/index.php/settings/admin/' + endpoint, async function (page) {
				}, {viewport: resolution, waitUntil: 'networkidle2'});
			});
		});

	});
});
