module.exports = {
	env: {
		browser: true,
		es6: true,
	},
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended-with-formatting' ],
	globals: {
		Atomics: 'readonly',
		SharedArrayBuffer: 'readonly',
	},
	parserOptions: {
		ecmaFeatures: {
			jsx: true,
		},
		ecmaVersion: 2018,
		sourceType: 'module',
	},
	rules: {
	},
};
