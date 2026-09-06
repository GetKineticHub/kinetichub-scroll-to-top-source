/**
 * Builds blueprint.json for the KineticHub Scroll to Top Playground demo.
 *
 * The demo is authored as ordinary .php / .css / .js files under src/ so it can
 * be read, linted and reviewed like normal code. This script inlines them into
 * the writeFile steps of a single blueprint.json, which is the only format the
 * WordPress.org "Live Preview" button accepts.
 *
 * Run:  node blueprint/build.js
 *
 * Nothing here ships inside the plugin - the whole blueprint/ directory is
 * excluded by .distignore.
 */
const fs   = require( 'fs' );
const path = require( 'path' );

const SRC = path.join( __dirname, 'src' );
const OUT = path.join( __dirname, 'blueprint.json' );

/** Reads one source file as a UTF-8 string with normalised line endings. */
function read( name ) {
	return fs.readFileSync( path.join( SRC, name ), 'utf8' ).replace( /\r\n/g, '\n' );
}

/** A writeFile step with literal file contents. */
function writeFile( target, contents ) {
	return { step: 'writeFile', path: target, data: contents };
}

const MU = '/wordpress/wp-content/mu-plugins';

const blueprint = {
	$schema: 'https://playground.wordpress.net/blueprint-schema.json',
	landingPage: '/',
	preferredVersions: {
		php: '8.2',
		wp: 'latest',
	},

	/*
	 * Nothing in this demo reaches the network, at any point. The plugin makes
	 * no remote request of its own, and unlike the Page Loader blueprint there
	 * is no image to fetch: every glyph on the page is an inline SVG literal
	 * and the control is drawn by the plugin itself. So the preview can run
	 * with networking off, which is also the honest setting to publish given
	 * the readme's "no remote requests" claim.
	 */
	features: {
		networking: false,
	},

	steps: [
		{
			step: 'login',
			username: 'admin',
			password: 'password',
		},
		{
			step: 'installPlugin',
			pluginData: {
				resource: 'wordpress.org/plugins',
				slug: 'kinetichub-scroll-to-top',
			},
		},
		{
			step: 'setSiteOptions',
			options: {
				blogname: 'KineticHub Scroll to Top',
				blogdescription: 'A smart scroll companion for WordPress',
			},
		},

		// The demo showcase, as a must-use plugin. mu-plugins only autoloads
		// top-level .php files, so the entry point sits at the root and its
		// assets live in the sibling directory.
		writeFile( MU + '/khstt-demo.php', read( 'demo.php' ) ),

		// writeFile does NOT create missing parent directories: without this the
		// three writes below fail with "the parent directory does not exist" and
		// the whole blueprint aborts. wp-content/mu-plugins itself already
		// exists, so only the asset subdirectory needs creating.
		{ step: 'mkdir', path: MU + '/khstt-demo' },

		writeFile( MU + '/khstt-demo/template.php', read( 'template.php' ) ),
		writeFile( MU + '/khstt-demo/demo.css', read( 'demo.css' ) ),
		writeFile( MU + '/khstt-demo/demo.js', read( 'demo.js' ) ),

		// Showcase settings and the demo page, which becomes the front page.
		{
			step: 'runPHP',
			code: read( 'setup.php' ),
		},
	],
};

fs.writeFileSync( OUT, JSON.stringify( blueprint, null, 4 ) + '\n', 'utf8' );

const raw = fs.readFileSync( OUT, 'utf8' );

// Re-parse what was written: a blueprint that is not valid JSON fails silently
// in Playground with an unhelpful error, so it is worth proving here.
const parsed = JSON.parse( raw );

/*
 * Leak scan.
 *
 * The sources are authored on a Windows machine inside a LocalWP site, so the
 * two things most likely to end up embedded by accident are a C:\ path and a
 * development host name. A blueprint carrying either is not merely untidy: the
 * demo would try to load something that does not exist in Playground, and the
 * file is published on WordPress.org. Failing the build is the only reliable
 * place to catch it, because nothing downstream reads this file until it is
 * already deployed.
 *
 * Only wordpress.org, playground.wordpress.net and getkinetichub.com are
 * allowed as absolute URLs; every other asset is written by the blueprint.
 */
const FORBIDDEN = [
	[ /[A-Za-z]:[\\/]{1,2}Users/i,        'a Windows user path' ],
	[ /Local Sites/i,                     'a LocalWP site path' ],
	[ /\blocalhost\b/i,                   'a localhost URL' ],
	[ /development-test/i,                'the development site name' ],
	[ /\.local\b/i,                       'a .local development host' ],
	[ /\bwp-content[\\/]plugins[\\/]kinetichub-scroll-to-top\b/i, 'a source-tree plugin path' ],
	[ /[\\/]tmp[\\/]/i,                   'a temp path' ],
	[ /\b(?:127\.0\.0\.1|0\.0\.0\.0)\b/,  'a loopback address' ],
	[ /\bfile:\/\//i,                     'a file:// URL' ],
];

const problems = [];

FORBIDDEN.forEach( function ( entry ) {
	const match = raw.match( entry[ 0 ] );
	if ( match ) {
		problems.push( entry[ 1 ] + ' -> ' + JSON.stringify( match[ 0 ] ) );
	}
} );

const ALLOWED_HOSTS = [
	'playground.wordpress.net',
	'wordpress.org',
	'getkinetichub.com',
	'www.gnu.org',
];

( raw.match( /https?:\/\/[^"'\\\s)]+/g ) || [] ).forEach( function ( url ) {
	const host = url.replace( /^https?:\/\//, '' ).split( '/' )[ 0 ].toLowerCase();
	const ok   = ALLOWED_HOSTS.some( function ( allowed ) {
		return host === allowed || host.endsWith( '.' + allowed );
	} );
	if ( ! ok ) {
		problems.push( 'an unexpected external host -> ' + url );
	}
} );

if ( problems.length ) {
	console.error( 'blueprint.json FAILED the leak scan:' );
	problems.forEach( function ( p ) {
		console.error( '  - ' + p );
	} );
	process.exit( 1 );
}

const bytes = fs.statSync( OUT ).size;

console.log( 'blueprint.json written: ' + bytes + ' bytes, ' + parsed.steps.length + ' steps' );
parsed.steps.forEach( function ( s, i ) {
	const label = s.path ? s.step + ' -> ' + s.path : s.step;
	console.log( '  ' + ( i + 1 ) + '. ' + label );
} );
console.log( 'leak scan: clean' );
