<?php
/**
 * Single accommodation components, placed in the Elementor single template
 * (template 267) as shortcodes:
 *
 *   [viktorin_stay_gallery]      photo grid + "Show all photos" (Elementor lightbox)
 *   [viktorin_stay_header]       type badge, title, city, key facts
 *   [viktorin_stay_description]  "About this place"
 *   [viktorin_stay_amenities]    "What this place offers" (Amenities checkbox field)
 *   [viktorin_stay_booking]      sticky booking card + mobile price bar
 *   [viktorin_stay_related]      "Other stays you may like"
 *
 * All read the current accommodation; pass id="123" to render another one.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function vks_stay_id( $atts = [] ) {
	if ( ! empty( $atts['id'] ) ) {
		return (int) $atts['id'];
	}
	if ( is_singular( VKS_POST_TYPE ) ) {
		return (int) get_queried_object_id();
	}
	return (int) get_the_ID();
}

function vks_stay_meta( $id, $key ) {
	$value = get_post_meta( $id, $key, true );
	return is_string( $value ) ? trim( $value ) : $value;
}

function vks_stay_enqueue() {
	wp_enqueue_style( 'vks-stay' );
	wp_enqueue_script( 'vks-search' );
}

/**
 * Gallery attachment IDs: Gallery field, falling back to featured image + Slider 1–3.
 */
function vks_stay_gallery_ids( $id ) {
	$raw = get_post_meta( $id, VKS_FIELD_GALLERY, true );
	$ids = is_array( $raw ) ? $raw : array_filter( array_map( 'trim', explode( ',', (string) $raw ) ) );

	if ( ! $ids ) {
		$ids = [ get_post_thumbnail_id( $id ) ];
		foreach ( [ 'slider1', 'slider2', 'slider3' ] as $key ) {
			$ids[] = get_post_meta( $id, $key, true );
		}
	}

	$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	return array_values( array_filter( $ids, 'wp_attachment_is_image' ) );
}

/* ------------------------------------------------------------------ */
/* Gallery                                                             */
/* ------------------------------------------------------------------ */

add_shortcode( 'viktorin_stay_gallery', 'vks_stay_gallery' );
function vks_stay_gallery( $atts ) {
	$id  = vks_stay_id( $atts );
	$ids = vks_stay_gallery_ids( $id );
	if ( ! $ids ) {
		return '';
	}
	vks_stay_enqueue();

	$title     = get_the_title( $id );
	$slideshow = 'vks-gallery-' . $id;
	$visible   = min( 5, count( $ids ) );

	ob_start();
	?>
	<div class="vks-gallery vks-gallery--<?php echo (int) $visible; ?>">
		<?php foreach ( $ids as $i => $att_id ) :
			$full = wp_get_attachment_image_url( $att_id, 'full' );
			$alt  = get_post_meta( $att_id, '_wp_attachment_image_alt', true ) ?: sprintf( '%s – %d', $title, $i + 1 );
			?>
			<a class="vks-gallery__item<?php echo $i >= $visible ? ' is-extra' : ''; ?>" href="<?php echo esc_url( $full ); ?>"
				data-elementor-open-lightbox="yes"
				data-elementor-lightbox-slideshow="<?php echo esc_attr( $slideshow ); ?>"
				data-elementor-lightbox-title="<?php echo esc_attr( $title ); ?>">
				<?php
				if ( $i < $visible ) {
					echo wp_get_attachment_image(
						$att_id,
						0 === $i ? 'full' : 'large',
						false,
						[
							'alt'     => $alt,
							'loading' => 0 === $i ? 'eager' : 'lazy',
							'sizes'   => 0 === $i ? '(max-width: 767px) 100vw, 60vw' : '(max-width: 767px) 100vw, 25vw',
						]
					);
				}
				?>
			</a>
		<?php endforeach; ?>

		<?php if ( count( $ids ) > 1 ) : ?>
			<button type="button" class="vks-gallery__all" data-vks-gallery-open>
				<?php echo vks_icon( 'photos' ); // phpcs:ignore ?>
				<?php
				/* translators: %d: number of photos */
				printf( esc_html__( 'Show all photos (%d)', 'viktorin-site' ), count( $ids ) );
				?>
			</button>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/* ------------------------------------------------------------------ */
/* Header                                                              */
/* ------------------------------------------------------------------ */

function vks_stay_facts( $id ) {
	$facts  = [];
	$guests = (int) vks_stay_meta( $id, VKS_FIELD_GUESTS );
	$beds   = (int) vks_stay_meta( $id, VKS_FIELD_BEDS );
	$baths  = (int) vks_stay_meta( $id, VKS_FIELD_BATHROOMS );
	$spa    = (string) vks_stay_meta( $id, VKS_FIELD_SPA );

	if ( $guests ) {
		/* translators: %d: guests */
		$facts[] = [ 'guests', sprintf( _n( 'Up to %d guest', 'Up to %d guests', $guests, 'viktorin-site' ), $guests ) ];
	}
	if ( $beds ) {
		/* translators: %d: beds */
		$facts[] = [ 'bed', sprintf( _n( '%d bed', '%d beds', $beds, 'viktorin-site' ), $beds ) ];
	}
	if ( $baths ) {
		/* translators: %d: bathrooms */
		$facts[] = [ 'bath', sprintf( _n( '%d bathroom', '%d bathrooms', $baths, 'viktorin-site' ), $baths ) ];
	}
	if ( '' !== $spa ) {
		$facts[] = [ 'spa', $spa ];
	}
	return $facts;
}

add_shortcode( 'viktorin_stay_header', 'vks_stay_header' );
function vks_stay_header( $atts ) {
	$id   = vks_stay_id( $atts );
	$type = (string) vks_stay_meta( $id, VKS_FIELD_TYPE );
	$city = (string) vks_stay_meta( $id, VKS_FIELD_CITY );
	vks_stay_enqueue();

	ob_start();
	?>
	<header class="vks-stay-head">
		<div class="vks-stay-head__meta">
			<?php if ( $type ) : ?>
				<span class="vks-badge"><?php echo esc_html( $type ); ?></span>
			<?php endif; ?>
			<?php if ( $city ) : ?>
				<span class="vks-stay-head__city"><?php echo vks_icon( 'pin' ); // phpcs:ignore ?><?php echo esc_html( $city ); ?></span>
			<?php endif; ?>
		</div>
		<h1 class="vks-stay-head__title"><?php echo esc_html( get_the_title( $id ) ); ?></h1>
		<?php $facts = vks_stay_facts( $id ); ?>
		<?php if ( $facts ) : ?>
			<ul class="vks-facts">
				<?php foreach ( $facts as $fact ) : ?>
					<li class="vks-facts__item"><?php echo vks_icon( $fact[0] ); // phpcs:ignore ?><span><?php echo esc_html( $fact[1] ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</header>
	<?php
	return ob_get_clean();
}

/* ------------------------------------------------------------------ */
/* Description                                                         */
/* ------------------------------------------------------------------ */

add_shortcode( 'viktorin_stay_description', 'vks_stay_description' );
function vks_stay_description( $atts ) {
	$id   = vks_stay_id( $atts );
	$text = (string) get_post_meta( $id, VKS_FIELD_DESC, true );
	if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
		return '';
	}
	vks_stay_enqueue();

	return '<section class="vks-stay-section">'
		. '<h2 class="vks-stay-section__title">' . esc_html__( 'About this place', 'viktorin-site' ) . '</h2>'
		. '<div class="vks-prose">' . wp_kses_post( wpautop( $text ) ) . '</div>'
		. '</section>';
}

/* ------------------------------------------------------------------ */
/* Amenities                                                           */
/* ------------------------------------------------------------------ */

function vks_amenity_icon( $key ) {
	$map = [
		'spa'              => 'spa',
		'wifi'             => 'wifi',
		'parking'          => 'parking',
		'air-conditioning' => 'snow',
		'kitchen'          => 'kitchen',
		'tv'               => 'tv',
		'washing-machine'  => 'washer',
		'terrace'          => 'terrace',
		'sauna'            => 'sauna',
		'jacuzzi'          => 'jacuzzi',
		'pool'             => 'pool',
		'pets'             => 'pets',
		'non-smoking'      => 'nosmoke',
	];
	return $map[ $key ] ?? 'check';
}

add_shortcode( 'viktorin_stay_amenities', 'vks_stay_amenities' );
function vks_stay_amenities( $atts ) {
	$id    = vks_stay_id( $atts );
	$value = get_post_meta( $id, VKS_FIELD_AMENITIES, true );

	// JetEngine checkbox: list of keys (is_array) or [ key => 'true'|'false' ].
	$keys = [];
	foreach ( (array) $value as $k => $v ) {
		if ( is_int( $k ) ) {
			$keys[] = (string) $v;
		} elseif ( filter_var( $v, FILTER_VALIDATE_BOOLEAN ) ) {
			$keys[] = (string) $k;
		}
	}
	$labels = vks_get_field_options( VKS_FIELD_AMENITIES );

	// Ticked amenities, in the order defined in the field.
	$items = [];
	foreach ( $labels ?: array_combine( $keys, $keys ) as $key => $label ) {
		if ( in_array( (string) $key, $keys, true ) ) {
			$items[ (string) $key ] = $label;
		}
	}

	// Always include the spa type from the Spa field (Spa / Sauna / Jacuzzi / Pool) first.
	$spa = trim( (string) vks_stay_meta( $id, VKS_FIELD_SPA ) );
	if ( '' !== $spa ) {
		$spa_key = sanitize_title( $spa );
		unset( $items[ $spa_key ] );
		$items = [ $spa_key => $spa ] + $items;
	}

	if ( ! $items ) {
		return '';
	}
	vks_stay_enqueue();

	ob_start();
	?>
	<section class="vks-stay-section">
		<h2 class="vks-stay-section__title"><?php esc_html_e( 'What this place offers', 'viktorin-site' ); ?></h2>
		<ul class="vks-amenities">
			<?php foreach ( $items as $key => $label ) : ?>
				<li class="vks-amenities__item"><?php echo vks_icon( vks_amenity_icon( $key ) ); // phpcs:ignore ?><span><?php echo esc_html( $label ); ?></span></li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
	return ob_get_clean();
}

/* ------------------------------------------------------------------ */
/* Booking card                                                        */
/* ------------------------------------------------------------------ */

add_shortcode( 'viktorin_stay_booking', 'vks_stay_booking' );
function vks_stay_booking( $atts ) {
	$id     = vks_stay_id( $atts );
	$price  = (string) vks_stay_meta( $id, VKS_FIELD_PRICE );
	$max    = (int) vks_stay_meta( $id, VKS_FIELD_GUESTS ) ?: vks_max_guests();
	$action = vks_page_url( 'contact' );
	vks_stay_enqueue();

	$price_num = is_numeric( str_replace( ',', '.', $price ) ) ? (float) str_replace( ',', '.', $price ) : 0;

	ob_start();
	?>
	<aside class="vks-book" id="vks-book">
		<?php if ( '' !== $price ) : ?>
			<div class="vks-book__price">
				<strong><?php echo esc_html( vks_format_price( $price ) ); ?></strong>
				<span><?php esc_html_e( '/ night', 'viktorin-site' ); ?></span>
			</div>
		<?php endif; ?>

		<form class="vks-search vks-book__form" method="get" data-mode="booking"
			action="<?php echo esc_url( $action ); ?>"
			data-stay="<?php echo esc_attr( get_the_title( $id ) ); ?>"
			data-price="<?php echo esc_attr( $price_num ); ?>">

			<?php echo vks_render_dates_field( __( 'Check in – Check out', 'viktorin-site' ) ); // phpcs:ignore ?>

			<?php echo vks_render_dropdown( 'guests', __( 'Guests', 'viktorin-site' ), '', vks_guest_options( $max ), vks_icon( 'guests' ), min( 2, $max ) ); // phpcs:ignore ?>

			<div class="vks-book__summary" hidden>
				<div class="vks-book__line"><span data-vks-nights></span><span data-vks-subtotal></span></div>
				<div class="vks-book__line vks-book__line--total"><span><?php esc_html_e( 'Total', 'viktorin-site' ); ?></span><span data-vks-total></span></div>
			</div>

			<div class="vks-field vks-field--submit">
				<button type="submit" class="vks-submit"><?php esc_html_e( 'Request booking', 'viktorin-site' ); ?></button>
			</div>
			<p class="vks-book__note"><?php esc_html_e( 'No payment now – we’ll confirm availability by email.', 'viktorin-site' ); ?></p>
		</form>
	</aside>

	<div class="vks-mbar" aria-hidden="false">
		<div class="vks-mbar__price">
			<?php if ( '' !== $price ) : ?>
				<strong><?php echo esc_html( vks_format_price( $price ) ); ?></strong> <span><?php esc_html_e( '/ night', 'viktorin-site' ); ?></span>
			<?php endif; ?>
		</div>
		<a class="vks-submit vks-mbar__cta" href="#vks-book"><?php esc_html_e( 'Check availability', 'viktorin-site' ); ?></a>
	</div>
	<?php
	return ob_get_clean();
}

/* ------------------------------------------------------------------ */
/* Related stays                                                       */
/* ------------------------------------------------------------------ */

add_shortcode( 'viktorin_stay_related', 'vks_stay_related' );
function vks_stay_related( $atts ) {
	$atts = shortcode_atts( [ 'id' => 0, 'count' => 3 ], $atts, 'viktorin_stay_related' );
	$id   = vks_stay_id( $atts );

	$posts = get_posts(
		[
			'post_type'      => VKS_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atts['count'],
			'post__not_in'   => [ $id ],
			'orderby'        => 'menu_order date',
			'order'          => 'DESC',
		]
	);
	if ( ! $posts ) {
		return '';
	}
	vks_stay_enqueue();

	ob_start();
	?>
	<section class="vks-related">
		<div class="vks-related__head">
			<span class="vks-eyebrow"><?php esc_html_e( 'Keep exploring', 'viktorin-site' ); ?></span>
			<h2 class="vks-related__title"><?php esc_html_e( 'Other stays you may like', 'viktorin-site' ); ?></h2>
		</div>
		<div class="vks-related__grid">
			<?php
			foreach ( $posts as $p ) {
				echo vks_render_stay_card( $p->ID ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside
			}
			?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* ------------------------------------------------------------------ */
/* Accommodation card (shared by related stays and JetEngine listings) */
/* ------------------------------------------------------------------ */

/**
 * Card markup for one accommodation.
 */
function vks_render_stay_card( $post_id, $sizes = '(max-width: 767px) 85vw, (max-width: 1024px) 50vw, 33vw' ) {
	$post_id = (int) $post_id;
	$ids     = vks_stay_gallery_ids( $post_id );
	$type    = (string) vks_stay_meta( $post_id, VKS_FIELD_TYPE );
	$city    = (string) vks_stay_meta( $post_id, VKS_FIELD_CITY );
	$price   = (string) vks_stay_meta( $post_id, VKS_FIELD_PRICE );
	$facts   = vks_stay_facts( $post_id );
	$thumb   = get_post_thumbnail_id( $post_id ) ?: ( $ids[0] ?? 0 );
	vks_stay_enqueue();

	// Compact fact labels for cards: "2 guests · 2 beds · 1 bath · Spa".
	$short = [
		'/^Up to /'          => '',
		'/ bathrooms?$/'     => ' bath',
	];

	ob_start();
	?>
	<a class="vks-card" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
		<span class="vks-card__media">
			<?php
			if ( $thumb ) {
				echo wp_get_attachment_image( $thumb, 'large', false, [ 'loading' => 'lazy', 'sizes' => $sizes ] );
			}
			?>
			<?php if ( $type ) : ?><span class="vks-badge vks-badge--overlay"><?php echo esc_html( $type ); ?></span><?php endif; ?>
		</span>
		<span class="vks-card__body">
			<span class="vks-card__top">
				<span class="vks-card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></span>
				<?php if ( '' !== $price ) : ?>
					<span class="vks-card__price"><strong><?php echo esc_html( vks_format_price( $price ) ); ?></strong> <?php esc_html_e( '/ night', 'viktorin-site' ); ?></span>
				<?php endif; ?>
			</span>
			<?php if ( $city ) : ?>
				<span class="vks-card__city"><?php echo vks_icon( 'pin' ); // phpcs:ignore ?><?php echo esc_html( $city ); ?></span>
			<?php endif; ?>
			<?php if ( $facts ) : ?>
				<span class="vks-card__facts">
					<?php foreach ( $facts as $fact ) : ?>
						<span><?php echo vks_icon( $fact[0] ); // phpcs:ignore ?><?php echo esc_html( preg_replace( array_keys( $short ), array_values( $short ), $fact[1] ) ); ?></span>
					<?php endforeach; ?>
				</span>
			<?php endif; ?>
		</span>
	</a>
	<?php
	return ob_get_clean();
}

/**
 * [viktorin_stay_card] – card for the current post in a loop
 * (used inside the JetEngine listing templates). id="123" for a specific one.
 */
add_shortcode( 'viktorin_stay_card', 'vks_stay_card_shortcode' );
function vks_stay_card_shortcode( $atts ) {
	$atts = shortcode_atts( [ 'id' => 0 ], $atts, 'viktorin_stay_card' );
	$id   = $atts['id'] ? (int) $atts['id'] : (int) get_the_ID();
	if ( ! $id || VKS_POST_TYPE !== get_post_type( $id ) ) {
		return '';
	}
	return vks_render_stay_card( $id );
}

/* ------------------------------------------------------------------ */
/* Contact page: prefill the message from a booking request            */
/* ------------------------------------------------------------------ */

add_action( 'wp_footer', 'vks_prefill_contact_message', 50 );
function vks_prefill_contact_message() {
	if ( empty( $_GET['stay'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$stay   = sanitize_text_field( wp_unslash( $_GET['stay'] ) );            // phpcs:ignore
	$in     = sanitize_text_field( wp_unslash( $_GET['check_in'] ?? '' ) );  // phpcs:ignore
	$out    = sanitize_text_field( wp_unslash( $_GET['check_out'] ?? '' ) ); // phpcs:ignore
	$guests = absint( $_GET['guests'] ?? 0 );                                // phpcs:ignore

	$fmt = function ( $d ) {
		$t = strtotime( $d );
		return $t ? date_i18n( 'j M Y', $t ) : '';
	};

	$msg = sprintf( __( 'Hello, I would like to book %s', 'viktorin-site' ), $stay );
	if ( $fmt( $in ) && $fmt( $out ) ) {
		$msg .= sprintf( __( ' from %1$s to %2$s', 'viktorin-site' ), $fmt( $in ), $fmt( $out ) );
	}
	if ( $guests ) {
		/* translators: %d: guests */
		$msg .= sprintf( _n( ' for %d guest', ' for %d guests', $guests, 'viktorin-site' ), $guests );
	}
	$msg .= '.';
	?>
	<script>
	( function () {
		var msg = <?php echo wp_json_encode( $msg ); ?>;
		var field = document.querySelector( 'form textarea' );
		if ( field && ! field.value ) {
			field.value = msg;
			field.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			var form = field.closest( 'form' );
			if ( form ) {
				setTimeout( function () { form.scrollIntoView( { behavior: 'smooth', block: 'center' } ); }, 300 );
			}
		}
	} )();
	</script>
	<?php
}
