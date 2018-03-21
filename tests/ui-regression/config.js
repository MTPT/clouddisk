module.exports = {

	/**
	 * Define resolutions to be tested when diffing screenshots
	 */
	resolutions: [
		{title: 'mobile', w: 360, h: 480},
		{title: 'narrow', w: 800, h: 600},
		{title: 'normal', w: 1024, h: 768},
		{title: 'wide', w: 1920, h: 1080},
		{title: 'qhd', w: 2560, h: 1440, scale: 2},
		{title: 'uhd', w: 3840, h: 2160, scale: 2},
	],

	/**
	 * URL that holds the base branch
	 */
	urlBase: 'http://acceptance-ui-php-master/',

	/**
	 * URL that holds the branch to be diffed
	 */
	urlChange: 'http://acceptance-ui-php/',

	/**
	 * Path to output directory for screenshot files
	 */
	outputDirectory: 'out',

	/**
	 * Run in headless mode (useful for debugging)
	 */
	headless: true,

};
