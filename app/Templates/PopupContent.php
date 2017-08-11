<?php
use WPD\BeaverPopups\Helpers\PopupHelper;

$cpt = PopupHelper::CUSTOM_POST_TYPE_POPUP;
?>

<div id="<?php echo $cpt . '-' . $popupId; ?>__outer" class="<?php echo $cpt; ?>__outer">
    <div id="<?php echo $cpt . '-' . $popupId; ?>__content" class="<?php echo $cpt; ?>__content">
        <div id="<?php echo $cpt . '-' . $popupId; ?>__inner" class="<?php echo $cpt; ?>__inner">
            <?php if ( method_exists( '\FLBuilder', 'render_content_by_id' ) ) {
                \FLBuilder::render_content_by_id( $popupId );
            }
            else {
                \FLBuilder::render_query( [
                    'post_type' => $cpt,
                    'p' => $popupId,
                    'no_found_posts' => true
                ] );
            } ?>
        </div>
    </div>
</div>
