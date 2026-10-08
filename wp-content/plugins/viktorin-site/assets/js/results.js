/**
 * Viktorin Site – results page helpers:
 *  - live results counter for JetSmartFilters / JetEngine grids
 *  - JetSmartFilters sorting <select> replaced by the site's custom dropdown
 */
( function () {
	'use strict';

	var i18n = window.vksResults || {};
	var t = function ( key, fallback ) {
		return i18n[ key ] || fallback;
	};

	/* ---------- Live counter ---------- */

	function countLabel( n ) {
		if ( ! n ) {
			return t( 'none', 'No stays match your filters' );
		}
		return ( n === 1 ? t( 'one', '%d stay' ) : t( 'many', '%d stays' ) ).replace( '%d', n );
	}

	var watchers = [];

	function initCounters() {
		document.querySelectorAll( '[data-vks-count-for]' ).forEach( function ( el ) {
			var target = document.getElementById( el.getAttribute( 'data-vks-count-for' ) );
			if ( ! target ) {
				return;
			}
			var update = function () {
				var n = target.querySelectorAll( '.jet-listing-grid__item' ).length;
				el.textContent = countLabel( n );
				el.classList.toggle( 'is-empty', ! n );
				watchers.forEach( function ( fn ) {
					fn();
				} );
			};
			update();
			new MutationObserver( update ).observe( target, { childList: true, subtree: true } );
		} );
	}

	/* ---------- Sorting dropdown ---------- */

	var chevron = '<svg class="vks-chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="M5 7.5l5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	var uid = 0;

	function enhanceSort( select ) {
		if ( select.dataset.vksEnhanced ) {
			return;
		}
		select.dataset.vksEnhanced = '1';
		uid++;

		var prefix = t( 'sortPrefix', 'Sort by:' );
		var wrap = document.createElement( 'div' );
		wrap.className = 'vks-sortbox vks-select';
		wrap.innerHTML =
			'<button type="button" class="vks-control vks-select__button" aria-haspopup="listbox" aria-expanded="false" id="vks-sort-btn-' + uid + '">' +
				'<span class="vks-sortbox__prefix">' + prefix + '</span>' +
				'<span class="vks-select__value"></span>' + chevron +
			'</button>' +
			'<ul class="vks-select__list" role="listbox" tabindex="-1" hidden aria-labelledby="vks-sort-btn-' + uid + '"></ul>';

		var button = wrap.querySelector( 'button' );
		var valueEl = wrap.querySelector( '.vks-select__value' );
		var list = wrap.querySelector( 'ul' );
		var options = [];
		var active = 0;

		Array.prototype.forEach.call( select.options, function ( opt, i ) {
			var li = document.createElement( 'li' );
			li.className = 'vks-select__option';
			li.setAttribute( 'role', 'option' );
			li.id = 'vks-sort-' + uid + '-' + i;
			li.textContent = opt.value === '' ? t( 'sortDefault', 'Recommended' ) : opt.textContent.trim();
			li.addEventListener( 'mousemove', function () {
				setActive( i );
			} );
			li.addEventListener( 'click', function () {
				choose( i );
			} );
			list.appendChild( li );
			options.push( li );
		} );

		function sync() {
			var i = Math.max( 0, select.selectedIndex );
			valueEl.textContent = options[ i ] ? options[ i ].textContent : '';
			options.forEach( function ( li, j ) {
				li.setAttribute( 'aria-selected', j === i ? 'true' : 'false' );
			} );
		}

		function setActive( i ) {
			active = Math.max( 0, Math.min( options.length - 1, i ) );
			options.forEach( function ( li, j ) {
				li.classList.toggle( 'is-active', j === active );
			} );
			list.setAttribute( 'aria-activedescendant', options[ active ].id );
		}

		function open() {
			list.hidden = false;
			wrap.classList.add( 'is-open' );
			button.setAttribute( 'aria-expanded', 'true' );
			setActive( Math.max( 0, select.selectedIndex ) );
			list.focus( { preventScroll: true } );
		}

		function close( focus ) {
			if ( list.hidden ) {
				return;
			}
			list.hidden = true;
			wrap.classList.remove( 'is-open' );
			button.setAttribute( 'aria-expanded', 'false' );
			if ( focus ) {
				button.focus();
			}
		}

		function choose( i ) {
			if ( select.selectedIndex !== i ) {
				select.selectedIndex = i;
				select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			}
			sync();
			close( true );
		}

		button.addEventListener( 'click', function () {
			list.hidden ? open() : close( true );
		} );
		button.addEventListener( 'keydown', function ( e ) {
			if ( [ 'ArrowDown', 'ArrowUp', 'Enter', ' ' ].indexOf( e.key ) !== -1 ) {
				e.preventDefault();
				open();
			}
		} );
		list.addEventListener( 'keydown', function ( e ) {
			switch ( e.key ) {
				case 'ArrowDown': e.preventDefault(); setActive( active + 1 ); break;
				case 'ArrowUp': e.preventDefault(); setActive( active - 1 ); break;
				case 'Home': e.preventDefault(); setActive( 0 ); break;
				case 'End': e.preventDefault(); setActive( options.length - 1 ); break;
				case 'Enter':
				case ' ': e.preventDefault(); choose( active ); break;
				case 'Escape': e.preventDefault(); close( true ); break;
				case 'Tab': close( false ); break;
			}
		} );
		document.addEventListener( 'click', function ( e ) {
			if ( ! wrap.contains( e.target ) ) {
				close( false );
			}
		} );
		select.addEventListener( 'change', sync );

		select.classList.add( 'vks-native-hidden' );
		select.setAttribute( 'tabindex', '-1' );
		select.setAttribute( 'aria-hidden', 'true' );
		select.parentNode.insertBefore( wrap, select );
		sync();
		// "Clear all" resets the select without a change event – re-sync when results change.
		watchers.push( sync );
	}

	function initSorts() {
		document.querySelectorAll( '.vk-sort select.jet-sorting-select' ).forEach( enhanceSort );
	}

	function init() {
		initSorts();
		initCounters();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
