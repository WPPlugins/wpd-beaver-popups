<?php

use WPD\BeaverPopups\Helpers\Util;

?>

<?php // Popup width ?>

<?php if ( isset( $settings->width ) && ! empty( $settings->width ) ) : ?>
    .single-wpd-bb-popup #<?php echo $cpt . '-' . $popup->ID; ?>__content .fl-builder-content {
        max-width: 100%;
        width: <?php echo $settings->width; ?>px;
    }
<?php endif; ?>