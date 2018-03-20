module.exports = {

	/**
	 * Define resolutions to be tested when diffing screenshots
	 */
	resolutions: [
		{title: 'mobile', w: 360, h: 480},
		{title: 'narrow', w: 800, h: 600},
		{title: 'normal', w: 1024, h: 768},
		{title: 'wide', w: 1920, h: 1080}
	],

	/**
	 * URL that holds the base branch
	 */
	urlBase: 'http://acceptance-ui-php-master/',

	/**
	 * URL that holds the branch to be diffed
	 */
	urlChange: 'http://acceptance-ui-php:8081/',

	/**
	 * Path to output directory for screenshot files
	 */
	outputDirectory: 'out',

};
