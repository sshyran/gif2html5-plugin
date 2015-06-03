module.exports = function( grunt ) {

	'use strict';
	var banner = '/**\n * <%= pkg.homepage %>\n * Copyright (c) <%= grunt.template.today("yyyy") %>\n * This file is generated automatically. Do not edit.\n */\n';
	// Project configuration
	grunt.initConfig( {

		pkg:    grunt.file.readJSON( 'package.json' ),

		addtextdomain: {
			options: {
				textdomain: 'gif2html5',
			},
			target: {
				files: {
					src: [ '*.php', '**/*.php', '!node_modules/**', '!php-tests/**', '!bin/**' ]
				}
			}
		},

		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.md': 'readme.txt'
				}
			},
		},

		phpcs: {
			plugin: {
				src: './'
			},
			options: {
				bin: "vendor/bin/phpcs --extensions=php --ignore=\"*/vendor/*,*/node_modules/*\"",
				standard: "phpcs.ruleset.xml"
			}
		},
		
		jasmine: {
			dev: {
				src: ['js/src/**/*.js'],
				options: {
					specs: 'js-tests/specs/**/*.js',
					outfile: '_SpecRunner.html',
					display: 'short',
					summary: true,
					vendor: [
						'js-tests/vendors/jquery/jquery-1.11.3.min.js',
						'js-tests/vendors/jasmine-jquery/jasmine-jquery.js'
					]
				}
			}
		},


		sass: {
			compile: {
				files: {
					'css/gif2html5.css' : 'css/src/gif2html5.scss',
				}
			}

		},

		watch: {
			scripts: {
				files: ['js/**/*.js', 'js-tests/**/*.js'],
				tasks: ['test'],
				options: {
					debounceDelay: 500
				}
			},
			styles: {
				files: ['css/src/*.scss'],
				tasks: ['sass'],
				options: {
					debounceDelay: 500,
				}
			},
		},

		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					mainFile: 'gif2html5.php',
					potFilename: 'gif2html5.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: true
				}
			}
		},
	} );

	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.loadNpmTasks( 'grunt-phpcs' );
	grunt.loadNpmTasks( 'grunt-contrib-jasmine' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-sass' );
	grunt.registerTask( 'i18n', ['addtextdomain', 'makepot'] );
	grunt.registerTask( 'test', ['jasmine'] );
	grunt.registerTask( 'readme', ['wp_readme_to_markdown']);

	grunt.util.linefeed = '\n';

};
