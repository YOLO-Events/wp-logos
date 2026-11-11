/* eslint-env node */
const assert = require( 'node:assert/strict' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );
const vm = require( 'node:vm' );

test( 'Full Cards renders with the dedicated WordPress preview component', () => {
	const buildDir = path.join( __dirname, '../build/logo-full-cards/gutenberg' );
	const metadata = JSON.parse(
		readFileSync( path.join( buildDir, 'block.json' ), 'utf8' )
	);
	let settings;
	const ServerSideRender = () => null;
	const component = () => null;
	const element = ( type, props ) => {
		assert.notEqual( type, undefined, 'Element type must be defined' );
		return { type, props };
	};
	const context = {
		window: {
			ReactJSXRuntime: { jsx: element, jsxs: element },
			wp: {
				blocks: {
					registerBlockType( name, options ) {
						assert.equal( name, metadata.name );
						settings = options;
					},
				},
				i18n: { __: ( text ) => text },
				blockEditor: {
					InspectorControls: component,
					useBlockProps: () => ( {} ),
				},
				components: {
					ColorPicker: component,
					PanelBody: component,
					RangeControl: component,
					SelectControl: component,
					ToggleControl: component,
				},
				data: {
					useSelect: ( select ) =>
						select( () => ( {
							getEntityRecords: () => [],
						} ) ),
				},
				coreData: { store: {} },
				serverSideRender: ServerSideRender,
			},
		},
	};

	vm.runInNewContext(
		readFileSync( path.join( buildDir, 'index.js' ), 'utf8' ),
		context
	);
	const attributes = Object.fromEntries(
		Object.entries( metadata.attributes ).map( ( [ key, schema ] ) => [
			key,
			schema.default,
		] )
	);
	const tree = settings.edit( { attributes, setAttributes() {} } );
	const preview = tree.props.children.find(
		( child ) => child.type === ServerSideRender
	);

	assert.ok( preview, 'Preview must use wp.serverSideRender' );
	assert.equal( preview.props.block, metadata.name );
	assert.equal( preview.props.attributes, attributes );
	assert.equal( preview.props.httpMethod, 'POST' );
	assert.equal( settings.save(), null );
	assert.match(
		readFileSync( path.join( buildDir, 'index.asset.php' ), 'utf8' ),
		/'wp-server-side-render'/
	);
} );
