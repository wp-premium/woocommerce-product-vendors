var gulp = require( 'gulp' ),
    minifycss = require( 'gulp-clean-css' ),
    uglifyjs = require( 'gulp-uglify' ),
    rename = require( 'gulp-rename' ),
    notify = require( 'gulp-notify' ),
    scss_src = ['assets/css/*.scss'],
    js_src = ['assets/js/*.js', '!assets/js/*.min.js'];

gulp.task( 'css', function() {
	return gulp.src( scss_src )
		.pipe( minifycss())
		.pipe( rename( function( path ) {
			path.dirname += '';
			path.extname = '.css';
		}))
		.pipe( gulp.dest( 'assets/css' ) )
		.pipe( notify( 'CSS has been minified' ) );
});

gulp.task( 'js', function() {
	return gulp.src( js_src )
		.pipe( uglifyjs())
		.pipe( rename( function( path ) {
			path.dirname += '';
			path.basename += '.min';
			path.extname = '.js';
		}))
		.pipe( gulp.dest( 'assets/js' ) )
		.pipe( notify( 'JS has been uglified' ));
});

gulp.task( 'default', function() {
	gulp.watch( scss_src, ['css'] );
	gulp.watch( js_src, ['js'] );
});
