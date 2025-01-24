module.exports = function( grunt ) {
	require( 'load-grunt-tasks' )( grunt );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		clean: {
			main: [ 'build/' ],
		},

		checktextdomain: {
			options: {
				text_domain: 'market-exporter',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d',
				],
			},
			files: {
				src: [
					'_src/**/*.*',
					'languages/market-exporter.php',
					'includes/**/*.php',
					'market-exporter.php',
				],
				expand: true,
			},
		},

		makepot: {
			options: {
				domainPath: 'languages',
				mainFile: 'market-exporter.php',
				potFilename: 'market-exporter.pot',
				potHeaders: {
					'report-msgid-bugs-to': 'https://wordpress.org/support/plugin/market-exporter/',
					'language-team': 'RUSSIAN LANGUAGE <INFO@VCORE.RU>',
					'last-translator': 'ANTON VANYUKOV <A.VANYUKOV@TESTOR.RU>',
				},
				type: 'wp-plugin',
				updateTimestamp: false,
			},
			main: {
				options: {
					cwd: '',
				},
			},
			release: {
				options: {
					cwd: 'build/market-exporter',
				},
			},
		},

		copy: {
			main: {
				src: [
					'admin/**',
					'includes/**',
					'languages/**',
					'!languages/react.php',
					'!languages/react.pot',
					'readme.txt',
					'index.php',
					'market-exporter.php',
				],
				dest: 'build/market-exporter/',
				options: {
					noProcess: [ '**/*.{png,gif,jpg,ico,svg,eot,ttf,woff,woff2}' ],
					process( content, srcpath ) {
						const pkg = grunt.file.readJSON( 'package.json' );
						return content.replace( /\%\%VERSION\%\%/g, pkg.version );
					},
				},
			},
		},

		compress: {
			main: {
				options: {
					archive: './build/market-exporter-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'build/market-exporter/',
				src: [ '**/*' ],
				dest: 'market-exporter/',
			},
		},
	} );

	grunt.registerTask( 'prepare', [ 'checktextdomain' ] );

	grunt.registerTask( 'translate', [ 'makepot:main' ] );

	grunt.registerTask( 'build', [
		'copy',
		'makepot:release',
		'compress',
	] );
};
