/**
 * Builds the distributable plugin ZIP for KineticHub Scroll to Top.
 *
 * The plugin has no compilation step - it ships the same PHP, CSS and vanilla
 * JS that live in the repository - so packaging is the whole job: select the
 * files .distignore does not exclude, and write them into one archive under a
 * single `kinetichub-scroll-to-top/` root.
 *
 * Two deliberate properties:
 *
 *   No dependencies. The archive is written directly against the ZIP spec
 *   using Node's own zlib. `npm install` is not part of cutting a release, and
 *   there is no build system to keep alive between releases.
 *
 *   Deterministic output. Every entry is stored with a fixed timestamp, in
 *   sorted order, with no extra fields and no platform permission bits, so two
 *   builds from unchanged sources produce a byte-identical file. That turns
 *   "did the artifact change?" into a hash comparison instead of an argument
 *   about archive metadata.
 *
 * Usage:
 *   node scripts/build-zip.js <output-directory>
 *
 * The output directory must be outside the plugin directory. That is not
 * fussiness: writing the archive inside the tree being archived is how a
 * release ends up containing a copy of itself.
 *
 * @package KineticHub_Scroll_To_Top
 */

'use strict';

const fs   = require( 'fs' );
const path = require( 'path' );
const zlib = require( 'zlib' );

const ROOT = path.resolve( __dirname, '..' );
const SLUG = 'kinetichub-scroll-to-top';

/*
 * A fixed DOS timestamp: 2020-01-01 00:00:00. The value itself is arbitrary and
 * never read by WordPress; what matters is that it does not change between
 * builds, because a real mtime would make every archive unique.
 */
const DOS_TIME = 0;
const DOS_DATE = ( ( 2020 - 1980 ) << 9 ) | ( 1 << 5 ) | 1;

// ---------------------------------------------------------------------------
// .distignore
// ---------------------------------------------------------------------------

/**
 * Parses .distignore into a list of patterns.
 *
 * @return {Array<Object>} Each { anchored, glob, value }.
 */
function readPatterns() {
	const file = path.join( ROOT, '.distignore' );

	if ( ! fs.existsSync( file ) ) {
		throw new Error( '.distignore is missing - refusing to guess what should ship' );
	}

	return fs.readFileSync( file, 'utf8' )
		.split( /\r?\n/ )
		.map( ( line ) => line.trim() )
		.filter( ( line ) => line && ! line.startsWith( '#' ) )
		.map( function ( line ) {
			const anchored = line.startsWith( '/' );
			let value = anchored ? line.slice( 1 ) : line;

			if ( value.endsWith( '/' ) ) {
				value = value.slice( 0, -1 );
			}

			return { anchored, glob: /[*?]/.test( value ), value };
		} );
}

/** Turns one glob into an anchored regular expression. */
function globToRegExp( glob ) {
	const escaped = glob.replace( /[.+^${}()|[\]\\]/g, '\\$&' )
		.split( '*' ).join( '[^/]*' )
		.split( '?' ).join( '[^/]' );

	return new RegExp( '^' + escaped + '$' );
}

/**
 * Whether a repository-relative path is excluded from the package.
 *
 * @param {string} rel Posix-style path relative to the plugin root.
 * @return {boolean}
 */
function isExcluded( rel, patterns ) {
	const segments = rel.split( '/' );

	return patterns.some( function ( p ) {
		if ( p.glob ) {
			const re = globToRegExp( p.value );
			return p.anchored ? re.test( rel ) : segments.some( ( s ) => re.test( s ) );
		}

		if ( p.anchored ) {
			return rel === p.value || rel.startsWith( p.value + '/' );
		}

		return segments.includes( p.value );
	} );
}

// ---------------------------------------------------------------------------
// File collection
// ---------------------------------------------------------------------------

/**
 * Every shippable file, sorted, as posix-style relative paths.
 *
 * Directories are never emitted as entries of their own: the paths carry the
 * structure, unzip recreates it, and an explicit directory entry is one more
 * thing that could differ between two builds.
 */
function collect( patterns ) {
	const out = [];

	( function walk( dir ) {
		for ( const entry of fs.readdirSync( dir, { withFileTypes: true } ).sort( ( a, b ) => a.name < b.name ? -1 : 1 ) ) {
			const abs = path.join( dir, entry.name );
			const rel = path.relative( ROOT, abs ).split( path.sep ).join( '/' );

			if ( isExcluded( rel, patterns ) ) {
				continue;
			}

			if ( entry.isDirectory() ) {
				walk( abs );
			} else if ( entry.isFile() ) {
				out.push( rel );
			}
			// Symlinks and anything else are skipped: a plugin ZIP has no use
			// for them and they do not survive the round trip predictably.
		}
	} )( ROOT );

	return out.sort();
}

// ---------------------------------------------------------------------------
// ZIP writing
// ---------------------------------------------------------------------------

const CRC_TABLE = ( function () {
	const table = new Int32Array( 256 );

	for ( let n = 0; n < 256; n++ ) {
		let c = n;
		for ( let k = 0; k < 8; k++ ) {
			c = ( c & 1 ) ? ( 0xEDB88320 ^ ( c >>> 1 ) ) : ( c >>> 1 );
		}
		table[ n ] = c;
	}

	return table;
} )();

function crc32( buf ) {
	let c = -1;

	for ( let i = 0; i < buf.length; i++ ) {
		c = CRC_TABLE[ ( c ^ buf[ i ] ) & 0xFF ] ^ ( c >>> 8 );
	}

	return ( c ^ -1 ) >>> 0;
}

/**
 * Writes the archive.
 *
 * @param {string} outFile Absolute path of the ZIP to create.
 * @param {Array}  files   Repository-relative paths to include.
 */
function writeZip( outFile, files ) {
	const locals  = [];
	const centrals = [];
	let offset = 0;

	for ( const rel of files ) {
		const name = Buffer.from( SLUG + '/' + rel, 'utf8' );
		const raw  = fs.readFileSync( path.join( ROOT, rel ) );

		// Level 9 so the result depends only on the input, not on a default
		// that could shift under us between Node versions.
		const deflated = zlib.deflateRawSync( raw, { level: 9 } );

		// Storing is used when compression does not help, which also keeps
		// tiny files from growing.
		const stored = deflated.length >= raw.length;
		const body   = stored ? raw : deflated;
		const method = stored ? 0 : 8;
		const crc    = crc32( raw );

		const local = Buffer.alloc( 30 );
		local.writeUInt32LE( 0x04034B50, 0 );
		local.writeUInt16LE( 20, 4 );          // version needed
		local.writeUInt16LE( 0, 6 );           // flags
		local.writeUInt16LE( method, 8 );
		local.writeUInt16LE( DOS_TIME, 10 );
		local.writeUInt16LE( DOS_DATE, 12 );
		local.writeUInt32LE( crc, 14 );
		local.writeUInt32LE( body.length, 18 );
		local.writeUInt32LE( raw.length, 22 );
		local.writeUInt16LE( name.length, 26 );
		local.writeUInt16LE( 0, 28 );          // no extra field

		locals.push( local, name, body );

		const central = Buffer.alloc( 46 );
		central.writeUInt32LE( 0x02014B50, 0 );
		// Version made by 0x0014: MS-DOS. A Unix value would carry permission
		// bits, which vary by checkout and would break reproducibility.
		central.writeUInt16LE( 0x0014, 4 );
		central.writeUInt16LE( 20, 6 );
		central.writeUInt16LE( 0, 8 );
		central.writeUInt16LE( method, 10 );
		central.writeUInt16LE( DOS_TIME, 12 );
		central.writeUInt16LE( DOS_DATE, 14 );
		central.writeUInt32LE( crc, 16 );
		central.writeUInt32LE( body.length, 20 );
		central.writeUInt32LE( raw.length, 24 );
		central.writeUInt16LE( name.length, 28 );
		central.writeUInt16LE( 0, 30 );        // extra
		central.writeUInt16LE( 0, 32 );        // comment
		central.writeUInt16LE( 0, 34 );        // disk
		central.writeUInt16LE( 0, 36 );        // internal attrs
		central.writeUInt32LE( 0, 38 );        // external attrs
		central.writeUInt32LE( offset, 42 );

		centrals.push( central, name );

		offset += local.length + name.length + body.length;
	}

	const centralBuf = Buffer.concat( centrals );

	const end = Buffer.alloc( 22 );
	end.writeUInt32LE( 0x06054B50, 0 );
	end.writeUInt16LE( 0, 4 );
	end.writeUInt16LE( 0, 6 );
	end.writeUInt16LE( files.length, 8 );
	end.writeUInt16LE( files.length, 10 );
	end.writeUInt32LE( centralBuf.length, 12 );
	end.writeUInt32LE( offset, 16 );
	end.writeUInt16LE( 0, 20 );

	fs.writeFileSync( outFile, Buffer.concat( [ ...locals, centralBuf, end ] ) );
}

// ---------------------------------------------------------------------------
// Entry point
// ---------------------------------------------------------------------------

function version() {
	const main = fs.readFileSync( path.join( ROOT, SLUG + '.php' ), 'utf8' );
	const header = main.match( /^\s*\*\s*Version:\s*(.+)$/m );
	const constant = main.match( /KHSTT_VERSION',\s*'([^']+)'/ );

	if ( ! header || ! constant ) {
		throw new Error( 'could not read the plugin version' );
	}

	const a = header[ 1 ].trim();

	// A ZIP named for one version containing another is the kind of mistake
	// that is only ever found by the person who installed it.
	if ( a !== constant[ 1 ] ) {
		throw new Error( 'version mismatch: header says ' + a + ', KHSTT_VERSION says ' + constant[ 1 ] );
	}

	return a;
}

function main() {
	const outDir = process.argv[ 2 ];

	if ( ! outDir ) {
		console.error( 'Usage: node scripts/build-zip.js <output-directory>' );
		process.exit( 1 );
	}

	const resolved = path.resolve( outDir );

	if ( resolved === ROOT || resolved.startsWith( ROOT + path.sep ) ) {
		console.error( 'Refusing to write the archive inside the plugin directory: ' + resolved );
		process.exit( 1 );
	}

	fs.mkdirSync( resolved, { recursive: true } );

	const v        = version();
	const patterns = readPatterns();
	const files    = collect( patterns );

	if ( ! files.includes( SLUG + '.php' ) ) {
		throw new Error( 'the main plugin file is not in the package' );
	}

	const outFile = path.join( resolved, SLUG + '.' + v + '.zip' );
	writeZip( outFile, files );

	const bytes = fs.statSync( outFile ).size;
	const raw   = files.reduce( ( n, f ) => n + fs.statSync( path.join( ROOT, f ) ).size, 0 );

	console.log( 'built  : ' + outFile );
	console.log( 'version: ' + v );
	console.log( 'files  : ' + files.length );
	console.log( 'raw    : ' + raw + ' bytes' );
	console.log( 'zip    : ' + bytes + ' bytes' );
}

main();
