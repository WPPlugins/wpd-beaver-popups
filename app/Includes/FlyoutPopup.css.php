<?php if ( isset( $settings->popup_type ) && 'fly_out' === $settings->popup_type ) : ?>
    .<?php echo $cpt; ?>__fly_out--active #<?php echo $cpt . '-' . $popup->ID; ?>__outer {
        position: fixed;

        <?php // X position ?>

        <?php if ( isset( $settings->fly_out_x_position ) ) {
            if ( 'center' === $settings->fly_out_x_position ) : ?>
                left: 50%;
                right: auto;
                -webkit-transform: translateX(-50%);
                -moz-transform: translateX(-50%);
                -ms-transform: translateX(-50%);
                -o-transform: translateX(-50%);
                transform: translateX(-50%);
            <?php else : $x_direction = $settings->fly_out_x_position; ?>
                <?php echo $x_direction; ?>: 0;
            <?php endif; ?>
        <?php } ?>

        <?php // Y position ?>

        <?php if ( isset( $settings->fly_out_y_position ) ) :
            if ( $y_direction = $settings->fly_out_y_position ) : ?>
                <?php echo $y_direction; ?>: 0;
            <?php endif; ?>
        <?php endif; ?>
    }

    <?php // WP Admin overrides ?>

    <?php if ( isset( $settings->fly_out_y_position ) && 'top' === $settings->fly_out_y_position ) : ?>
        .admin-bar.<?php echo $cpt; ?>__fly_out--active:not(.fl-builder-edit) #<?php echo $cpt . '-' . $popup->ID; ?>__outer {
            margin-top: 32px;
        }
    <?php endif; ?>

    <?php // Builder overrides ?>

    <?php if ( FLBuilderModel::is_builder_active() ) : ?>
        .fl-builder-edit.<?php echo $cpt; ?>__fly_out--active #<?php echo $cpt . '-' . $popup->ID; ?>__outer {
            transition: all 0.3s linear;

            <?php if ( isset( $settings->fly_out_y_position ) && 'top' === $settings->fly_out_y_position ) : ?>
                margin-top: 43px;
            <?php endif; ?>
        }

        .fl-builder-panel--open:not(.<?php echo $cpt; ?>__modal--active) #<?php echo $cpt . '-' . $popup->ID; ?>__outer {
            <?php if ( isset( $settings->fly_out_x_position ) ) :
                if ( 'right' === $settings->fly_out_x_position ) : ?>
                    right: 300px;
                <?php elseif ( 'center' === $settings->fly_out_x_position ) : ?>
                    left: calc(50% - 150px);
                <?php endif; ?>
            <?php endif; ?>
        }
    <?php endif; ?>
<?php endif; ?>
