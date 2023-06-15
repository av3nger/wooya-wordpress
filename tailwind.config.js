module.exports = {
	presets: [ require( '@yoast/tailwindcss-preset' ) ],
	content: [
		// Include all JS files inside the UI library in your content.
		'./node_modules/@yoast/ui-library/**/*.js',
		'./_src/**/*.js',
		'./_src/**/*.jsx',
	],
	theme: {
		extend: {},
	},
	plugins: [],
};
