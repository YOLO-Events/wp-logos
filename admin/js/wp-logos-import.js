/* global wpLogosImport */
( function () {
	'use strict';

	const form = document.getElementById( 'wp-logos-import-form' );
	if ( ! form || typeof wpLogosImport === 'undefined' ) {
		return;
	}

	const config = wpLogosImport;
	const text = config.text;
	const preview = document.getElementById( 'wp-logos-import-preview' );
	const execute = document.getElementById( 'wp-logos-import-execute' );
	const retry = document.getElementById( 'wp-logos-import-retry' );
	const progress = document.getElementById( 'wp-logos-import-progress' );
	const status = document.getElementById( 'wp-logos-import-status' );
	const report = document.getElementById( 'wp-logos-import-report' );
	const selectors = Array.from( form.querySelectorAll( 'select' ) );
	let approved = '';
	let state = null;
	let busy = false;

	function settings() {
		return {
			source: form.elements.source.value,
			mode: form.elements.mode.value,
			collision: form.elements.collision.value,
		};
	}

	function signature() {
		return JSON.stringify( settings() );
	}

	function controls() {
		selectors.forEach( function ( select ) {
			select.disabled = busy;
		} );
		preview.disabled = busy;
		execute.disabled = busy || approved !== signature();
		retry.hidden = ! state || ! state.failed;
		retry.disabled = busy;
	}

	function stringify( value ) {
		return typeof value === 'string' ? value : JSON.stringify( value );
	}

	function target( value ) {
		if ( value === null || value === undefined || Number( value ) === 0 ) {
			return text.unmapped;
		}
		return Number( value ) < 0 ? text.newCategory : String( value );
	}

	function section( title, lines ) {
		const heading = document.createElement( 'h3' );
		heading.textContent = title;
		report.appendChild( heading );
		const output = document.createElement( 'pre' );
		output.textContent = lines.join( '\n' );
		report.appendChild( output );
	}

	function render() {
		report.replaceChildren();
		const counters = document.createElement( 'p' );
		counters.textContent =
			text.created +
			': ' +
			state.created +
			' · ' +
			text.updated +
			': ' +
			state.updated +
			' · ' +
			text.skipped +
			': ' +
			state.skipped;
		report.appendChild( counters );
		section(
			text.missingImages + ' (' + state.missing_images.length + ')',
			state.missing_images.map( stringify )
		);
		section(
			text.errors + ' (' + state.errors.length + ')',
			state.errors.map( stringify )
		);
		section(
			text.collisions + ' (' + state.slug_collisions.length + ')',
			state.slug_collisions.map( stringify )
		);
		section(
			text.termMap,
			Object.keys( state.term_map ).map( function ( id ) {
				return id + ' → ' + target( state.term_map[ id ] );
			} )
		);
		section(
			text.legacy,
			state.legacy_references.map( function ( reference ) {
				return (
					[ reference.type, reference.post_id, reference.title ]
						.map( stringify )
						.join( ' · ' ) +
					' · ' +
					String( reference.category_source_id ) +
					' → ' +
					target( reference.target_term_id )
				);
			} )
		);
	}

	function validResult( result ) {
		return (
			result &&
			[ 'created', 'updated', 'skipped', 'next_offset', 'total' ].every(
				function ( key ) {
					return (
						Number.isSafeInteger( result[ key ] ) &&
						result[ key ] >= 0
					);
				}
			) &&
			[
				'errors',
				'missing_images',
				'slug_collisions',
				'legacy_references',
			].every( function ( key ) {
				return Array.isArray( result[ key ] );
			} ) &&
			result.term_map !== null &&
			typeof result.term_map === 'object' &&
			typeof result.done === 'boolean' &&
			result.dry_run === state.dry
		);
	}

	async function batches() {
		busy = true;
		state.failed = false;
		controls();
		status.textContent = text.running;
		try {
			while ( true ) {
				const body = new URLSearchParams( state.settings );
				body.set( 'action', 'wp_logos_import_batch' );
				body.set( '_ajax_nonce', config.nonce );
				body.set( 'dry_run', state.dry ? '1' : '0' );
				body.set( 'offset', String( state.offset ) );
				body.set( 'limit', '50' );
				const response = await fetch( config.url, {
					method: 'POST',
					credentials: 'same-origin',
					body,
				} );
				const payload = await response.json();
				if ( ! response.ok || ! payload.success ) {
					throw new Error(
						payload.data && payload.data.message
							? payload.data.message
							: text.invalidReply
					);
				}
				const result = payload.data;
				if ( ! validResult( result ) ) {
					throw new Error( text.invalidReply );
				}
				if ( ! result.done && result.next_offset <= state.offset ) {
					throw new Error( text.noProgress );
				}
				[ 'created', 'updated', 'skipped' ].forEach( function ( key ) {
					state[ key ] += result[ key ];
				} );
				[ 'missing_images', 'errors', 'slug_collisions' ].forEach(
					function ( key ) {
						state[ key ] = state[ key ].concat( result[ key ] );
					}
				);
				state.term_map = result.term_map;
				state.legacy_references = result.legacy_references;
				state.offset = result.next_offset;
				progress.max = Math.max( 1, result.total );
				progress.value = Math.min( result.next_offset, progress.max );
				status.textContent =
					text.running +
					' ' +
					result.next_offset +
					' / ' +
					result.total;
				render();
				if ( result.done ) {
					if ( state.errors.length ) {
						status.textContent = text.withErrors;
						approved = '';
					} else {
						status.textContent = state.dry
							? text.previewDone
							: text.importDone;
						approved = state.dry
							? JSON.stringify( state.settings )
							: '';
					}
					break;
				}
			}
		} catch ( error ) {
			state.failed = true;
			approved = '';
			status.textContent = text.failed + ' ' + error.message;
		} finally {
			busy = false;
			controls();
		}
	}

	function start( dry ) {
		if ( busy || ( ! dry && approved !== signature() ) ) {
			return;
		}
		approved = '';
		state = {
			settings: settings(),
			dry,
			offset: 0,
			failed: false,
			created: 0,
			updated: 0,
			skipped: 0,
			missing_images: [],
			errors: [],
			slug_collisions: [],
			term_map: {},
			legacy_references: [],
		};
		progress.value = 0;
		progress.max = 1;
		render();
		batches();
	}

	form.addEventListener( 'submit', function ( event ) {
		event.preventDefault();
	} );
	selectors.forEach( function ( select ) {
		select.addEventListener( 'change', function () {
			approved = '';
			state = null;
			controls();
		} );
	} );
	preview.addEventListener( 'click', function () {
		start( true );
	} );
	execute.addEventListener( 'click', function () {
		start( false );
	} );
	retry.addEventListener( 'click', function () {
		if (
			! busy &&
			state &&
			state.failed &&
			JSON.stringify( state.settings ) === signature()
		) {
			batches();
		}
	} );
	controls();
} )();
