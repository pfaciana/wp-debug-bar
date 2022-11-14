const fs = require('fs'),
	gulp = require('gulp'),
	through2 = require('through2'),
	lodash = require('lodash'),
	babel = require('gulp-babel'),
	sass = require('gulp-sass'),
	postcss = require('gulp-postcss'),
	cssnano = require('cssnano'),
	concat = require('gulp-concat'),
	rename = require("gulp-rename"),
	terser = require('gulp-terser'),
	gulpif = require('gulp-if'),
	ceol = require('gulp-conditional-eol'),
	debug = require('gulp-debug'),
	size = require('gulp-size'),
	presetenv = require('postcss-preset-env'),
	flexbugsfixes = require('postcss-flexbugs-fixes'),
	doiuse = require('doiuse'),
	brotli = require('gulp-brotli'),
	createTasks = require('gulp-create-tasks'),
	tags2Files = require('gulp-tags-to-files'),
	browsers = 'last 2 years';

const options = {
	browsers,
	postcss: {
		all: [
			flexbugsfixes(),
			//presetenv({overrideBrowserslist: browsers, grid: 'autoplace'}),
		],
		min: [
			cssnano({preset: 'default'}),
		],
	},
	babelrc: {
		presets: [
			["@babel/preset-env", {targets: {browsers}}],
		],
	},
	exclude: ['./**/*', '!./css/**/*', '!./fonts/**/*', '!./node_modules/**/*', '!./vendor/**/*',],
	watchTasks: true,
};

const builds = {
	css: {
		configs: [{
			id: ['styles', 'kint-dark-theme'],
			src: ['./css/src/styles.scss', './css/src/kint-dark-theme.scss'],
			dep: [],
			dest: './css/dist',
			watch: ['./css/src/**/*.scss'],
			//post: ['clean'],
		}],
		cb(_) {
			return gulp.src(_.src)
				.pipe(gulpif(_.match.sass, gulpif(_.sass, sass(_.sass).on('error', sass.logError))))
				.pipe(concat(_.filename))
				.pipe(gulpif(_.debug, debug(_.debug)))
				.pipe(gulpif(!!_.postcss.all, postcss(_.postcss.all)))
				.pipe(gulp.dest(_.dest))
				.pipe(gulpif(!!_.postcss.min, postcss(_.postcss.min)))
				.pipe(rename({suffix: '.min'}))
				.pipe(gulpif(_.size, size(_.size)))
				.pipe(gulp.dest(_.dest))
				.pipe(brotli.compress({extension: 'br', quality: 11}))
				.pipe(gulpif(_.sizeNgz, size(_.sizeNgz)))
				.pipe(gulp.dest(_.dest));
		},
	},
	js: {
		configs: [{
			id: 'scripts',
			src: './js/src/**/*.js',
			dest: './js/dist',
			alsoMin: true,
			watch: true,
			//post: ['clean'],
		}],
		cb(_) {
			return gulp.src(_.src)
				.pipe(gulpif(_.babelrc, babel(_.babelrc)))
				.pipe(gulpif(_.minify, gulpif(_.match.min, terser())))
				.pipe(concat(_.filename))
				.pipe(gulpif(_.minify, rename({suffix: '.min'})))
				.pipe(gulpif(_.minify, gulpif(_.debug, debug(_.debug))))
				.pipe(gulpif(_.minify, gulpif(_.size, size(_.size))))
				.pipe(gulp.dest(_.dest))
				.pipe(gulpif(_.minify, brotli.compress({extension: 'br', quality: 11})))
				.pipe(gulpif(_.sizeNgz, size(_.sizeNgz)))
				.pipe(gulpif(_.minify, gulp.dest(_.dest)));
		}
	},
	clean: {
		configs: [{
			id: 'all',
			debug: {title: 'clean:all'},
		}],
		cb(_) {
			return gulp.src(_.exclude, {base: (_.base || './'), since: gulp.lastRun(_.cb)})
				.pipe(gulpif(_.ceol, ceol(_.ceol)))
				.pipe(gulpif(buffer => !buffer.isDirectory(), gulpif(_.debug, debug(_.debug))))
				.pipe(gulp.dest((_.dest || './')));
		},
	},
	doiuse: {
		configs: [{
			id: 'css',
			src: './css/src/styles.scss',
		}],
		cb(_) {
			return gulp.src(_.src)
				.pipe(gulpif(_.match.sass, gulpif(_.sass, sass(_.sass).on('error', sass.logError))))
				.pipe(gulpif(!!_.postcss.all, postcss(_.postcss.all)))
				.pipe(postcss([doiuse({
					browsers, ignore: ['css-initial-value', 'flexbox', 'viewport-units', 'css3-cursors', 'calc', 'css-gradients'],
				})]))
		}
	},
};

createTasks(builds, options);