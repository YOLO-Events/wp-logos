/**
 * WP Logos – Public JavaScript
 * Handles carousel, ticker, and any interactive showcase types.
 */
( function () {
	'use strict';

	/* -----------------------------------------------------------------------
	 * Helpers
	 * --------------------------------------------------------------------- */

	/**
	 * Parse the data-wp-logos attribute from a showcase element.
	 *
	 * @param {HTMLElement} el Showcase root element.
	 * @returns {Object} Parsed configuration.
	 */
	function parseConfig( el ) {
		try {
			return JSON.parse( el.dataset.wpLogos || '{}' );
		} catch ( e ) {
			return {};
		}
	}

	/**
	 * Get the current breakpoint columns from computed CSS custom properties.
	 * We read the CSS variable that was set by the PHP render callback.
	 *
	 * @param {HTMLElement} el Showcase element.
	 * @returns {number} Number of visible slides.
	 */
	function getVisibleSlides( el ) {
		var style = getComputedStyle( el );
		var width = el.offsetWidth;

		// Match same breakpoints as CSS.
		var colsMobile  = parseInt( style.getPropertyValue( '--wp-logos-cols-mobile' ).trim(), 10 )  || 2;
		var colsTablet  = parseInt( style.getPropertyValue( '--wp-logos-cols-tablet' ).trim(), 10 )  || 3;
		var colsLaptop  = parseInt( style.getPropertyValue( '--wp-logos-cols-laptop' ).trim(), 10 )  || 4;
		var colsDesktop = parseInt( style.getPropertyValue( '--wp-logos-cols-desktop' ).trim(), 10 ) || 5;

		if ( width >= 1200 ) { return colsDesktop; }
		if ( width >= 900 )  { return colsLaptop; }
		if ( width >= 600 )  { return colsTablet; }
		return colsMobile;
	}

	/* -----------------------------------------------------------------------
	 * Carousel
	 * --------------------------------------------------------------------- */

	/**
	 * Initialise a single carousel showcase.
	 *
	 * @param {HTMLElement} el Root showcase element.
	 * @param {Object}      cfg Configuration object.
	 */
	function initCarousel( el, cfg ) {

		// Ticker mode – delegate to separate init.
		if ( cfg.ticker ) {
			initTicker( el, cfg );
			return;
		}

		var wrapper  = el.querySelector( '.wp-logos-carousel-wrapper' );
		var track    = el.querySelector( '.wp-logos-carousel-track' );
		var items    = Array.from( track.querySelectorAll( '.wp-logos-item' ) );
		var btnPrev  = el.querySelector( '.wp-logos-arrow-prev' );
		var btnNext  = el.querySelector( '.wp-logos-arrow-next' );
		var dotsEl   = el.querySelector( '.wp-logos-dots' );

		if ( ! track || items.length === 0 ) { return; }

		var totalItems  = items.length;
		var currentIdx  = 0;
		var autoplayTimer = null;
		var isDragging   = false;
		var dragStartX   = 0;
		var dragCurrentX = 0;
		var dragOffset   = 0;
		var resizeTimer  = null;

		/* --- Clone items for infinite loop --- */
		if ( cfg.infinite ) {
			items.forEach( function ( item ) {
				var clone = item.cloneNode( true );
				clone.setAttribute( 'aria-hidden', 'true' );
				clone.classList.add( 'wp-logos-clone' );
				track.appendChild( clone );
			} );
		}

		/* --- Dot creation --- */
		var dots = [];
		if ( dotsEl ) {
			for ( var i = 0; i < totalItems; i++ ) {
				var dot = document.createElement( 'button' );
				dot.className = 'wp-logos-dot' + ( i === 0 ? ' wp-logos-dot-active' : '' );
				dot.setAttribute( 'role', 'tab' );
				dot.setAttribute( 'aria-label', 'Slide ' + ( i + 1 ) );
				( function ( idx ) {
					dot.addEventListener( 'click', function () { goTo( idx, true ); } );
				} )( i );
				dotsEl.appendChild( dot );
				dots.push( dot );
			}
		}

		/* --- Core move logic --- */
		function getSlideWidth() {
			var visible = getVisibleSlides( el );
			var gap = parseFloat( getComputedStyle( el ).getPropertyValue( '--wp-logos-gap' ) ) || 0;
			return ( wrapper.offsetWidth - gap * ( visible - 1 ) ) / visible;
		}

		function setTransform( offset, animated ) {
			track.style.transition = animated ? '' : 'none';
			track.style.transform  = 'translateX(' + offset + 'px)';
		}

		function getOffset( idx ) {
			var slideWidth = getSlideWidth();
			var gap = parseFloat( getComputedStyle( el ).getPropertyValue( '--wp-logos-gap' ) ) || 0;
			return -idx * ( slideWidth + gap );
		}

		function updateDots() {
			dots.forEach( function ( d, i ) {
				d.classList.toggle( 'wp-logos-dot-active', i === currentIdx );
			} );
		}

		function updateArrows() {
			if ( ! cfg.infinite ) {
				if ( btnPrev ) { btnPrev.disabled = currentIdx === 0; }
				if ( btnNext ) { btnNext.disabled = currentIdx >= totalItems - getVisibleSlides( el ); }
			}
		}

		function goTo( idx, stopAutoplay ) {
			if ( stopAutoplay ) { clearAutoplay(); }

			var maxIdx = cfg.infinite ? totalItems - 1 : Math.max( 0, totalItems - getVisibleSlides( el ) );
			currentIdx = Math.max( 0, Math.min( idx, maxIdx ) );

			setTransform( getOffset( currentIdx ), true );
			updateDots();
			updateArrows();

			if ( cfg.infinite ) {
				// After transition, if we are at a clone, snap back silently.
				setTimeout( function () {
					if ( currentIdx >= totalItems ) {
						currentIdx = currentIdx - totalItems;
						setTransform( getOffset( currentIdx ), false );
					}
				}, 400 );
			}
		}

		function next() { goTo( currentIdx + 1, false ); }
		function prev() { goTo( currentIdx - 1, false ); }

		/* --- Autoplay --- */
		function startAutoplay() {
			if ( ! cfg.autoplay ) { return; }
			autoplayTimer = setInterval( next, cfg.autoplaySpeed || 3000 );
		}

		function clearAutoplay() {
			if ( autoplayTimer ) {
				clearInterval( autoplayTimer );
				autoplayTimer = null;
			}
		}

		/* --- Arrow buttons --- */
		if ( btnPrev ) {
			btnPrev.addEventListener( 'click', function () { prev(); startAutoplay(); } );
		}
		if ( btnNext ) {
			btnNext.addEventListener( 'click', function () { next(); startAutoplay(); } );
		}

		/* --- Touch / pointer drag --- */
		wrapper.addEventListener( 'pointerdown', function ( e ) {
			isDragging  = true;
			dragStartX  = e.clientX;
			dragOffset  = getOffset( currentIdx );
			clearAutoplay();
			track.style.transition = 'none';
			wrapper.setPointerCapture( e.pointerId );
		} );

		wrapper.addEventListener( 'pointermove', function ( e ) {
			if ( ! isDragging ) { return; }
			dragCurrentX = e.clientX;
			var delta = dragCurrentX - dragStartX;
			track.style.transform = 'translateX(' + ( dragOffset + delta ) + 'px)';
		} );

		function endDrag() {
			if ( ! isDragging ) { return; }
			isDragging = false;
			var delta = dragCurrentX - dragStartX;
			var threshold = getSlideWidth() * 0.25;
			if ( Math.abs( delta ) > threshold ) {
				delta < 0 ? next() : prev();
			} else {
				setTransform( getOffset( currentIdx ), true );
			}
			startAutoplay();
		}

		wrapper.addEventListener( 'pointerup',     endDrag );
		wrapper.addEventListener( 'pointercancel', endDrag );

		/* --- Keyboard accessibility --- */
		el.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'ArrowLeft' )  { prev(); startAutoplay(); }
			if ( e.key === 'ArrowRight' ) { next(); startAutoplay(); }
		} );
		el.setAttribute( 'tabindex', '0' );

		/* --- Pause on hover --- */
		if ( cfg.autoplay ) {
			wrapper.addEventListener( 'mouseenter', clearAutoplay );
			wrapper.addEventListener( 'mouseleave', startAutoplay );
		}

		/* --- Resize --- */
		window.addEventListener( 'resize', function () {
			clearTimeout( resizeTimer );
			resizeTimer = setTimeout( function () {
				setTransform( getOffset( currentIdx ), false );
				updateArrows();
			}, 150 );
		} );

		/* --- Initialise --- */
		setTransform( getOffset( 0 ), false );
		updateDots();
		updateArrows();
		startAutoplay();
	}

	/* -----------------------------------------------------------------------
	 * Ticker (continuous scrolling marquee)
	 * --------------------------------------------------------------------- */

	/**
	 * Initialise a ticker showcase.
	 *
	 * @param {HTMLElement} el  Root showcase element.
	 * @param {Object}      cfg Configuration object.
	 */
	function initTicker( el, cfg ) {
		var track = el.querySelector( '.wp-logos-carousel-track' );
		if ( ! track ) { return; }

		// Duplicate items so the track is seamlessly looping.
		var origItems = Array.from( track.children );
		origItems.forEach( function ( item ) {
			var clone = item.cloneNode( true );
			clone.setAttribute( 'aria-hidden', 'true' );
			track.appendChild( clone );
		} );

		// Calculate duration based on speed factor and number of items.
		var speedFactor = parseFloat( cfg.tickerSpeed ) || 1;
		var numItems    = origItems.length;
		// Base: each item takes ~4 s to scroll past at speed 1.
		var duration    = ( numItems * 4 ) / speedFactor;

		el.style.setProperty( '--wp-logos-ticker-speed', duration + 's' );
		el.classList.add( 'wp-logos-ticker-active' );

		// Pause on hover (accessibility / user control).
		el.addEventListener( 'mouseenter', function () {
			track.style.animationPlayState = 'paused';
		} );
		el.addEventListener( 'mouseleave', function () {
			track.style.animationPlayState = 'running';
		} );

		// Respect prefers-reduced-motion.
		if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			track.style.animation = 'none';
		}
	}

	/* -----------------------------------------------------------------------
	 * Initialisation
	 * --------------------------------------------------------------------- */

	/**
	 * Initialise all showcase elements on the page.
	 */
	function initAll() {
		var showcases = document.querySelectorAll( '.wp-logos-showcase[data-wp-logos]' );
		showcases.forEach( function ( el ) {
			var cfg = parseConfig( el );
			if ( cfg.type === 'carousel' ) {
				initCarousel( el, cfg );
			}
			// Grid and Flexbox are CSS-only; no JS needed.
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}
} )();
