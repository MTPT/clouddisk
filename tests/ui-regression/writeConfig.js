var fs = require('fs');
var Mocha = require('mocha')
    Suite = Mocha.Suite,
    Runner = Mocha.Runner,
    Test = Mocha.Test;

var tests = [
  'install',
  'login',
  'public',
  'settings'
]

var config = {
  tests: tests,
  pr: process.env.DRONE_PULL_REQUEST,
  repoUrl: process.env.DRONE_REPO_LINK,
};

var json = JSON.stringify(config);
var callback = function() {};
fs.writeFile('out/config.json', json, 'utf8', callback);

var errorMessage = 'This PR introduces some UI differences, please check at {LINK}, if there are regressions based on the changes.'

