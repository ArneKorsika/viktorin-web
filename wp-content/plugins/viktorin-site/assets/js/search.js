/**
 * Viktorin Site – custom dropdowns, date range picker, search redirect,
 * booking card and gallery helpers.
 */
( function () {
	'use strict';

	/* ---------- Custom dropdown (button + listbox) ---------- */

	function initSelect( root ) {
		var button  = root.querySelector( '.vks-select__button' );
		var list    = root.querySelector( '.vks-select__list' );
		var valueEl = root.querySelector( '.vks-select__value' );
		var input   = root.querySelector( 'input[type="hidden"]' );
		var options = Array.prototype.slice.call( root.querySelectorAll( '.vks-select__option' ) );
		var active  = 0;

		options.forEach( function ( opt, i ) {
			opt.id = ( list.getAttribute( 'aria-labelledby' ) || 'vks' ) + '-opt-' + i;
		} );

		function isOpen() {
			return ! list.hidden;
		}

		function setActive( i ) {
			active = Math.max( 0, Math.min( options.length - 1, i ) );
			options.forEach( function ( o, j ) {
				o.classList.toggle( 'is-active', j === active );
			} );
			list.setAttribute( 'aria-activedescendant', options[ active ].id );
			options[ active ].scrollIntoView( { block: 'nearest' } );
		}

		function open() {
			closeAll( root );
			list.hidden = false;
			root.classList.add( 'is-open' );
			button.setAttribute( 'aria-expanded', 'true' );
			var selected = options.findIndex( function ( o ) {
				return o.getAttribute( 'aria-selected' ) === 'true';
			} );
			setActive( selected < 0 ? 0 : selected );
			list.focus( { preventScroll: true } );
		}

		function close( focusButton ) {
			if ( ! isOpen() ) {
				return;
			}
			list.hidden = true;
			root.classList.remove( 'is-open' );
			button.setAttribute( 'aria-expanded', 'false' );
			if ( focusButton ) {
				button.focus();
			}
		}

		function select( i ) {
			var opt = options[ i ];
			options.forEach( function ( o ) {
				o.setAttribute( 'aria-selected', o === opt ? 'true' : 'false' );
			} );
			input.value = opt.dataset.value;
			valueEl.textContent = opt.textContent;
			valueEl.classList.toggle( 'is-placeholder', opt.dataset.value === '' );
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			close( true );
		}

		root._vksClose = close;

		button.addEventListener( 'click', function () {
			isOpen() ? close( true ) : open();
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
				case ' ': e.preventDefault(); select( active ); break;
				case 'Escape': e.preventDefault(); close( true ); break;
				case 'Tab': close( false ); break;
			}
		} );

		options.forEach( function ( opt, i ) {
			opt.addEventListener( 'mousemove', function () {
				if ( active !== i ) {
					setActive( i );
				}
			} );
			opt.addEventListener( 'click', function () {
				select( i );
			} );
		} );
	}

	function closeAll( except ) {
		document.querySelectorAll( '[data-vks-select].is-open' ).forEach( function ( el ) {
			if ( el !== except && el._vksClose ) {
				el._vksClose( false );
			}
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		if ( ! e.target.closest( '[data-vks-select]' ) ) {
			closeAll( null );
		}
	} );

	/* ---------- Date range ---------- */

	function pad( n ) {
		return ( n < 10 ? '0' : '' ) + n;
	}

	function ymd( d ) {
		return d.getFullYear() + '-' + pad( d.getMonth() + 1 ) + '-' + pad( d.getDate() );
	}

	function initDates( form ) {
		var wrap     = form.querySelector( '.vks-dates' );
		var input    = form.querySelector( '.vks-dates__input' );
		var clear    = form.querySelector( '.vks-dates__clear' );
		var checkIn  = form.querySelector( 'input[name="check_in"]' );
		var checkOut = form.querySelector( 'input[name="check_out"]' );

		if ( ! input || typeof window.flatpickr !== 'function' ) {
			return;
		}

		var narrow = form.dataset.mode === 'booking' || ! window.matchMedia( '(min-width: 768px)' ).matches;

		var fp = window.flatpickr( input, {
			mode: 'range',
			minDate: 'today',
			dateFormat: 'j M Y',
			showMonths: narrow && ! window.matchMedia( '(min-width: 1025px)' ).matches ? 1 : 2,
			disableMobile: true,
			locale: { firstDayOfWeek: 1, rangeSeparator: '  →  ' },
			position: form.dataset.mode === 'booking' ? 'below right' : 'below left',
			appendTo: document.body,
			onOpen: function () {
				closeAll( null );
				wrap.classList.add( 'is-focused' );
			},
			onClose: function ( dates ) {
				wrap.classList.remove( 'is-focused' );
				// A single click picks only the check-in day – treat it as one night.
				if ( dates.length === 1 ) {
					var next = new Date( dates[ 0 ] );
					next.setDate( next.getDate() + 1 );
					fp.setDate( [ dates[ 0 ], next ], true );
				}
			},
			onChange: function ( dates ) {
				checkIn.value  = dates[ 0 ] ? ymd( dates[ 0 ] ) : '';
				checkOut.value = dates[ 1 ] ? ymd( dates[ 1 ] ) : '';
				clear.hidden   = ! dates.length;
				form.dispatchEvent( new CustomEvent( 'vks:dates', { detail: { dates: dates } } ) );
			},
		} );

		fp.calendarContainer.classList.add( 'vks-calendar' );
		form._vksPicker = fp;

		wrap.addEventListener( 'click', function ( e ) {
			if ( e.target !== clear ) {
				fp.open();
			}
		} );

		clear.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			fp.clear();
		} );
	}

	/* ---------- Search bar → JetSmartFilters URL ---------- */

	function initSearchSubmit( form ) {
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			var val = function ( name ) {
				var el = form.querySelector( 'input[name="' + name + '"]' );
				return el ? el.value : '';
			};
			var type = val( 'type' ), guests = val( 'guests' ), spa = val( 'spa' );
			var cin = val( 'check_in' ), cout = val( 'check_out' );

			var meta = [];
			if ( type ) {
				meta.push( form.dataset.fieldType + ':' + type );
			}
			if ( spa ) {
				meta.push( form.dataset.fieldSpa + ':' + spa );
			}
			if ( guests ) {
				// "!compare-greater" = meta value >= N (at least N guests).
				meta.push( form.dataset.fieldGuests + '!compare-greater:' + guests );
			}

			var params = [];
			if ( meta.length ) {
				params.push( 'jsf=' + form.dataset.provider + ':' + form.dataset.queryId );
				params.push( 'meta=' + meta.map( encodeURIComponent ).join( ';' ) );
			}
			if ( cin && cout ) {
				params.push( 'check_in=' + cin, 'check_out=' + cout );
			}

			var url = form.getAttribute( 'action' );
			window.location.href = url + ( params.length ? ( url.indexOf( '?' ) === -1 ? '?' : '&' ) + params.join( '&' ) : '' );
		} );
	}

	/* ---------- Booking card ---------- */

	function money( n ) {
		return Math.round( n ) + '€';
	}

	function initBooking( form ) {
		var price    = parseFloat( form.dataset.price ) || 0;
		var summary  = form.querySelector( '.vks-book__summary' );
		var nightsEl = form.querySelector( '[data-vks-nights]' );
		var subEl    = form.querySelector( '[data-vks-subtotal]' );
		var totalEl  = form.querySelector( '[data-vks-total]' );

		form.addEventListener( 'vks:dates', function ( e ) {
			var d = e.detail.dates;
			if ( d.length < 2 || ! price ) {
				summary.hidden = true;
				return;
			}
			var nights = Math.round( ( d[ 1 ] - d[ 0 ] ) / 86400000 );
			nightsEl.textContent = money( price ) + ' × ' + nights + ( nights === 1 ? ' night' : ' nights' );
			subEl.textContent    = money( price * nights );
			totalEl.textContent  = money( price * nights );
			summary.hidden = false;
		} );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var cin    = form.querySelector( 'input[name="check_in"]' ).value;
			var cout   = form.querySelector( 'input[name="check_out"]' ).value;
			var guests = form.querySelector( 'input[name="guests"]' ).value;

			if ( ! cin || ! cout ) {
				form.querySelector( '.vks-dates' ).classList.add( 'is-invalid' );
				if ( form._vksPicker ) {
					form._vksPicker.open();
				}
				return;
			}

			var params = [
				'stay=' + encodeURIComponent( form.dataset.stay ),
				'check_in=' + cin,
				'check_out=' + cout,
			];
			if ( guests ) {
				params.push( 'guests=' + guests );
			}
			var url = form.getAttribute( 'action' );
			window.location.href = url + ( url.indexOf( '?' ) === -1 ? '?' : '&' ) + params.join( '&' );
		} );

		form.addEventListener( 'vks:dates', function () {
			form.querySelector( '.vks-dates' ).classList.remove( 'is-invalid' );
		} );
	}

	/* ---------- Gallery ---------- */

	function initGallery() {
		document.querySelectorAll( '[data-vks-gallery-open]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var first = btn.closest( '.vks-gallery' ).querySelector( '.vks-gallery__item' );
				if ( first ) {
					first.click();
				}
			} );
		} );
	}

	/* ---------- Mobile price bar ---------- */

	function initMobileBar() {
		var bar  = document.querySelector( '.vks-mbar' );
		var card = document.getElementById( 'vks-book' );
		if ( ! bar || ! card || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}
		document.body.classList.add( 'has-vks-mbar' );
		new IntersectionObserver( function ( entries ) {
			bar.classList.toggle( 'is-hidden', entries[ 0 ].isIntersecting );
		} ).observe( card );
	}

	function init() {
		document.querySelectorAll( '.vks-search' ).forEach( function ( form ) {
			if ( form.dataset.vksReady ) {
				return;
			}
			form.dataset.vksReady = '1';
			form.querySelectorAll( '[data-vks-select]' ).forEach( initSelect );
			initDates( form );
			if ( form.dataset.mode === 'booking' ) {
				initBooking( form );
			} else {
				initSearchSubmit( form );
			}
		} );
		initGallery();
		initMobileBar();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
