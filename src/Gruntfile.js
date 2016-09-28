module.exports = function (grunt) {
    'use strict';

    var env = grunt.option('env') || 'prod';
    grunt.config('env', env);
    console.log('Environment: ' + env);

    grunt.initConfig({
        jshint: {
            files: [],
            options: {
                loopfunc: true,
                globals: {
                    jQuery: true,
                    console: true,
                    module: true
                }
            }
        },
        less: {
            typeahead: {
                files: {
                    "Resources/public/css/typeahead.css": [
                        "Resources/assets/css/typeahead.less"
                    ]
                }
            }
        },
        jade: {
            components: {
                options: {
                    client: true,
                    debug: grunt.config('env') !== 'prod',
                    compileDebug: grunt.config('env') !== 'prod',
                    processName: function(filename) {
                        var path = require('path');
                        return path.basename(filename, '.jade');
                    }
                },
                files: {
                    "Resources/public/js/components.jade.js": [
                        "Resources/assets/components/tasksPage/*.jade",
                        "Resources/assets/components/taskList/*.jade",
                        "Resources/assets/components/taskCategorySelect/*.jade",
                        "Resources/assets/components/taskCategoriesPage/*.jade",
                        "Resources/assets/components/taskCategoryList/*.jade",
                        "Resources/assets/components/taskCategoryEditorPopup/*.jade",
                        "Resources/assets/components/taskProjectsPage/*.jade",
                        "Resources/assets/components/taskProject/*.jade",
                        "Resources/assets/components/taskProjectList/*.jade",
                        "Resources/assets/components/taskProjectParameters/*.jade",
                        "Resources/assets/components/taskProjectPermissions/*.jade",
                        "Resources/assets/components/taskProjectPermissionEditorPopup/*.jade",
                        "Resources/assets/components/taskProjectEditorPopup/*.jade",
                        "Resources/assets/components/taskEditor/*.jade",
                        "Resources/assets/components/task/*.jade",
                        "Resources/assets/components/taskComments/*.jade",
                        "Resources/assets/components/taskCommentForm/*.jade",
                        "Resources/assets/components/taskAttachments/*.jade"
                    ]
                }
            }
        },
        uglify: {
            vendors: {
                options: {
                    compress: grunt.config('env') === 'prod',
                    beautify: grunt.config('env') !== 'prod',
                    mangle: grunt.config('env') === 'prod',
                },
                files: {
                    'Resources/public/js/vendor.js': [
                        "bower_components/url-mutator/src/UrlMutator.js"
                    ]
                }
            },
            upload: {
                options: {
                    compress: grunt.config('env') === 'prod',
                    beautify: grunt.config('env') !== 'prod',
                    mangle: grunt.config('env') === 'prod',
                },
                files: {
                    "Resources/public/js/upload.js": [
                        'bower_components/upload.js/dist/upload.min.js'
                    ]
                }
            }
        },
        copy: {
            moment: {
                expand: true,
                flatten: true,
                cwd: 'bower_components/moment/',
                src: [
                    'min/moment.min.js',
                    'locale/*'
                ],
                dest: 'Resources/public/js/moment'
            },
            tinymce: {
                expand: true,
                cwd: 'bower_components/tinymce/',
                src: [
                    '**'
                ],
                dest: 'Resources/public/js/tinymce'
            },
            typeahead: {
                expand: true,
                cwd: 'bower_components/typeahead.js/dist/',
                src: [
                    'typeahead.jquery.min.js',
                    'bloodhound.min.js'
                ],
                dest: 'Resources/public/js/typeahead'
            }
        },
        cssmin: {
            vendors: {
                files: {
                    'Resources/public/css/vendor.css': [

                    ]
                }
            }
        },
        watch: {
            project: {
                files: [
                    'Resources/assets/**/*'
                ],
                tasks: ['build'],
                options: {},
            },
        }
    });

    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-jade');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-newer');
    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.registerTask('build', [
        'newer:less',
        'newer:jade',
        'newer:copy',
        'newer:uglify',
        'newer:cssmin'
    ]);

    grunt.registerTask('listen', [
        'watch'
    ]);

    grunt.registerTask('default', [
        'build'
    ]);
};