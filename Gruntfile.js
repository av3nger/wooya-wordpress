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
					'uninstall.php',
					'market-exporter.php',
				],
				expand: true,
			},
		},

		makepot: {
			options: {
				domainPath: 'languages',
				exclude: [
					'freemius/.*',
				],
				mainFile: 'market-exporter.php',
				potFilename: 'market-exporter.pot',
				potHeaders: {
					'report-msgid-bugs-to': 'https://wordpress.org/support/plugin/market-exporter/',
					'language-team': 'LANGUAGE <EMAIL@ADDRESS>',
				},
				type: 'wp-plugin',
				updateTimestamp: false, // Update POT-Creation-Date header if no other changes are detected
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
			pro: {
				src: [
					'admin/**',
					'freemius/**',
					'includes/**',
					'languages/**',
					'!languages/react.php',
					'!languages/react.pot',
					'readme.txt',
					'index.php',
					'uninstall.php',
					'market-exporter.php',
				],
				dest: 'build/market-exporter/',
				options: {
					noProcess: [ '**/*.{png,gif,jpg,ico,svg,eot,ttf,woff,woff2}' ],
				},
			},
		},
	} );

	grunt.registerTask( 'prepare', [ 'checktextdomain' ] );

	grunt.registerTask( 'translate', [ 'makepot:main' ] );

	grunt.registerTask( 'build', [
		'copy',
		'makepot:release',
	] );
};
