<?php

// Set background colour or image for modals

if ( 'modal' === $settings->popup_type ) :
    if ( 'color' == $settings->modal_overlay_background_type && isset( $settings->modal_overlay_background_color ) ) : ?>
        .single-wpd-bb-popup.<?php echo $cpt . '-' . $popup->ID; ?>--active,
        #<?php echo $cpt . '-' . $popup->ID; ?>-overlay {
            background-color: <?php echo false === strpos( $settings->modal_overlay_background_color, 'rgba' ) ? '#' . $settings->modal_overlay_background_color : $settings->modal_overlay_background_color; ?> !important;
        }
    <?php endif; ?>

    <?php if ( 'image' == $settings->modal_overlay_background_type && isset( $settings->modal_overlay_background_image ) ) : ?>
        .single-wpd-bb-popup.<?php echo $cpt . '-' . $popup->ID; ?>--active,
        #<?php echo $cpt . '-' . $popup->ID; ?>-overlay {
            background-color: transparent;
            background-image: url(<?php echo $settings->modal_overlay_background_image_src; ?>) !important;
            background-position: center;
            background-repeat: <?php echo isset( $settings->modal_overlay_background_image_repeat ) && 'repeat' == $settings->modal_overlay_background_image_repeat ? 'repeat' : 'no-repeat'; ?> !important;
            background-size: <?php echo isset( $settings->modal_overlay_background_image_size ) ? $settings->modal_overlay_background_image_size : 'cover'; ?> !important;
        }
    <?php endif; ?>
<?php endif; ?>
