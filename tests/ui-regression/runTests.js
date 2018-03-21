const fs = require('fs')
const Mocha = require('mocha')

const testFolder = './test/'


var tests = [
	'install',
	'login',
	'files',
	'public',
	'settings'
]

var args = process.argv.slice(2);
if (args.length > 0) {
	tests = args
}

var config = {
	tests: tests,
	pr: process.env.DRONE_PULL_REQUEST,
	repoUrl: process.env.DRONE_REPO_LINK,
};

console.log('=> Write test config');
console.log(config);
fs.writeFile('out/config.json', JSON.stringify(config), 'utf8', () => {});

var mocha = new Mocha({
	timeout: 20000
});
let result = {};

tests.forEach(async function (test) {
	mocha.addFile('./test/' + test + 'Spec.js')
	result[test] = {
		failures: [],
		passes: [],
		tests: [],
		pending: [],
		stats: {}
	}

});

// fixme fail if installation failed
// write json to file

function clean (test) {
	return {
		title: test.title,
		fullTitle: test.fullTitle(),
		duration: test.duration,
		currentRetry: test.currentRetry(),
		err: errorJSON(test.err || {})
	};
}

function errorJSON (err) {
	var res = {};
	Object.getOwnPropertyNames(err).forEach(function (key) {
		res[key] = err[key];
	}, err);
	return res;
}

mocha.run()
	.on('test', function (test) {
	})
	.on('suite end', function(suite) {
		if (result[suite.title] === undefined)
			return;
		result[suite.title].stats = suite.stats;
	})
	.on('test end', function (test) {
		result[test.parent.title].tests.push(test);
	})
	.on('pass', function (test) {
		result[test.parent.title].passes.push(test);
	})
	.on('fail', function (test) {
		result[test.parent.title].failures.push(test);
	})
	.on('pending', function (test) {
		result[test.parent.title].pending.push(test);
	})
	.on('end', function () {
		tests.forEach(function (test) {
			var json = JSON.stringify({
				stats: result[test].stats,
				tests: result[test].tests.map(clean),
				pending: result[test].pending.map(clean),
				failures: result[test].failures.map(clean),
				passes: result[test].passes.map(clean)
			}, null, 2);
			fs.writeFile(`out/${test}.json`, json, 'utf8', function () {
				console.log(`Written test result to out/${test}.json`)
			});
		});

		var errorMessage = 'This PR introduces some UI differences, please check at {LINK}, if there are regressions based on the changes.'
		fs.writeFile('out/GITHUB_COMMENT', errorMessage, 'utf8', () => {});
	});

