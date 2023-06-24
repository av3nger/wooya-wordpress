const path = require( 'path' );
const webpack = require( 'webpack' );

// Plugins
const ExtractTextPlugin = require( 'mini-css-extract-plugin' );
const TerserPlugin = require( 'terser-webpack-plugin' ); // Included with Webpack v5.

module.exports = {
	mode: 'production',

	entry: {
		'market-exporter': path.resolve( __dirname, '_src/app.js' ),
		'market-exporter-i18n': path.resolve( __dirname, '_src/i18n.js' ),
	},

	output: {
		clean: {
			keep: /images|index.php/,
		},
		filename: 'js/[name].min.js',
		path: path.resolve( __dirname, 'admin' ),
	},

	module: {
		rules: [
			{
				test: /\.(js|jsx)$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [ '@babel/preset-env', '@babel/preset-react' ],
					},
				},
			},
			{
				test: /\.scss$/,
				exclude: /node_modules/,
				use: [
					ExtractTextPlugin.loader,
					{
						loader: 'css-loader',
						options: {
							sourceMap: true,
						},
					},
					{
						loader: 'sass-loader',
						options: {
							sourceMap: true,
						},
					},
				],
			},
			{
				test: /\.(png|jpg|gif)$/,
				type: 'asset/resource',
				generator: {
					filename: 'images/[name][ext]',
				},
			},
			{
				test: /\.(woff|woff2|eot|ttf|otf|svg)$/,
				type: 'asset/resource',
				generator: {
					filename: 'fonts/[name][ext]',
				},
			},
		],
	},

	// This will allow us to import files without writing these extensions.
	// eg: import 'app', instead of import 'app.jsx'
	resolve: {
		extensions: [ '.js', '.jsx' ],
	},

	plugins: [
		new ExtractTextPlugin( {
			filename: 'css/[name].min.css',
		} ),
		new webpack.DefinePlugin( {
			'process.env.NODE_ENV': JSON.stringify( 'production' ),
		} ),
		require( 'autoprefixer' ),
	],

	devtool: 'source-map', // Generates source Maps for these files

	stats: {
		colors: true,
		entrypoints: true,
	},

	watchOptions: {
		ignored: /node_modules/,
		poll: 1000,
	},

	optimization: {
		minimize: true,
		minimizer: [
			new TerserPlugin( {
				terserOptions: {
					format: {
						comments: false,
					},
				},
				extractComments: false,
			} ),
		],
	},
};
