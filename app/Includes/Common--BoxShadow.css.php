<?php

use WPD\BeaverPopups\Helpers\Util;

?>

<?php // Popup box shadow ?>

<?php if ( isset( $settings->add_box_shadow ) && 'yes' === $settings->add_box_shadow ) : ?>
    body:not(.single-wpd-bb-popup) #<?php echo $cpt . '-' . $popup->ID; ?> .jBox-content,
    .single-wpd-bb-popup #<?php echo $cpt . '-' . $popup->ID; ?>__content .fl-builder-content {
        box-shadow: <?php echo Util::createBoxShadow(
            $settings->box_shadow_horizontal_length,
            $settings->box_shadow_vertical_length,
            $settings->box_shadow_spread_radius,
            $settings->box_shadow_blur_radius,
            $settings->box_shadow_color,
            $settings->box_shadow_color_opacity
        ); ?>;
    }
<?php endif; ?>