module.exports = function(grunt) {

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    sass: {
      options: {
        loadPath: ['bower_components/bootstrap-sass-official/assets/stylesheets']
      },
      dist: {
        options: {
            style: 'expanded',  // nested, compact, compressed, expanded
            sourcemap: 'none'     // auto, file, inline, none
        },
        files: {
		  // bower_components/bootstrap-sass-official/assets/stylesheets/bootstrap.scss
          'css/bootstrap-custom.min.css': [
          	'scss/bootstrap.scss'
          ],
          'css/wp_trksit_style-new.css': [
          	'scss/wp_trksit_style.scss'
          ]
        }
      }
    },

    uglify: {
      options: {
        beautify: true,  // minify file when set to false
        compress: false,  // renames variables and all that
        mangle: false
      },
      dist: {
        files: {
          'js/bootstrap-custom.min.js': [
            //'bower_components/bootstrap-sass-official/assets/javascripts/bootstrap.js',
            'bower_components/bootstrap-sass-official/assets/javascripts/bootstrap/transition.js',
            'bower_components/bootstrap-sass-official/assets/javascripts/bootstrap/tooltip.js',
            'bower_components/bootstrap-sass-official/assets/javascripts/bootstrap/popover.js',
            'bower_components/bootstrap-sass-official/assets/javascripts/bootstrap/modal.js',
            'bower_components/bootstrap-sass-official/assets/javascripts/bootstrap/button.js'
          ],
		  //'js/plugins.min.js': [],
          //'js/app.min.js': []
        }
      }
    },

    watch: {
      grunt: { files: ['Gruntfile.js'], tasks: ['build'] },
      options: {
        livereload: true
      },
      sass: {
        files: 'scss/*.scss',
        tasks: ['sass']
      },
      scripts: {
        files: ['js/app.js'],
        tasks: ['uglify']
      },
      theme: {
        files: ['**/*.php'],
        exclude: ['!**/node_modules/**', '!**/bower_components/**']
      }
    }

  });


  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-uglify');

  grunt.registerTask('build', ['sass','uglify']);
  grunt.registerTask('default', ['build','watch']);
}