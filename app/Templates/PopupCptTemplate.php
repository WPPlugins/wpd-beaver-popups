<?php

use WPD\BeaverPopups\Helpers\BeaverBuilderHelper;
use WPD\BeaverPopups\Helpers\PopupHelper;

$cpt = PopupHelper::CUSTOM_POST_TYPE_POPUP;
$settings = BeaverBuilderHelper::getPopupStyleSettings(null, get_the_ID());
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <?php wp_head(); ?>
    </head>
    <body <?php body_class(); ?>>
        <div id="<?php echo $cpt . '-' . get_the_ID(); ?>__outer" class="<?php echo $cpt . '__outer'; ?>">
            <div id="<?php echo $cpt . '-' . get_the_ID(); ?>__content" class="<?php echo $cpt . '__content'; ?>">
                <div id="<?php echo $cpt . '-' . get_the_ID(); ?>__inner" class="<?php echo $cpt; ?>__inner">
                    <?php if ( method_exists( 'FLBuilder', 'render_content_by_id' ) ) :
                        \FLBuilder::render_content_by_id(get_the_ID());
                    else :
                        if ( have_posts() ) : while ( have_posts() ) : the_post();
                            the_content();
                        endwhile; endif;
                    endif; ?>
                </div>
                <i id="<?php echo $cpt . '-' . get_the_ID(); ?>__close-button" class="fa fa-close"></i>
            </div>
        </div>
    </body>

    <?php wp_footer(); ?>
</html>
