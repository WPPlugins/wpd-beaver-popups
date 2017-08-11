<?php

/**
 * Metabox abstract class
 *
 * @package     WPD\BeaverPopups\Admin
 * @since       1.0.0
 * @author      smarterdigitalltd
 * @link        https://www.smarter.uk.com
 * @license     GNU-2.0+
 */

namespace WPD\BeaverPopups\Admin;

use WPD\BeaverPopups\Helpers\InputHelper;
use WPD\BeaverPopups\Helpers\Util;

/**
 * Class AbstractMetabox is a base to extend for easy implementation of entry editor metaboxes.
 *
 * To extend this abstract class one needs to override render and save methods.
 * Keep in mind that if you create form inputs with metabox-some_key then post's
 * meta with key 'some_key' will be updated automatically.
 *
 * @package WPD\BeaverPopups\Admin
 */
abstract class AbstractMetabox {

    /**
     * Constructor.
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public function __construct( $metaboxId, $title, $screens = null, $contextNormalSideAdvanced = 'normal', $priorityDefaultHighLow = 'default' )
    {
        $box = $this;

        add_action( 'add_meta_boxes', function () use ( $box, $metaboxId, $title, $contextNormalSideAdvanced, $priorityDefaultHighLow, $screens ) {
            if ( is_array( $screens ) ) {
                foreach ( $screens as $screen ) {
                    add_meta_box( $metaboxId, __( $title, __NAMESPACE__ ), [ $box, 'render' ], $screen, $contextNormalSideAdvanced, $priorityDefaultHighLow );
                }
            }
            else {
                add_meta_box( $metaboxId, __( $title, __NAMESPACE__ ), [ $box, 'render' ], $screens, $contextNormalSideAdvanced, $priorityDefaultHighLow );
            }
        } );

        /**
         * Add hook on save data
         *
         * @since   1.0.0
         *
         * @return  void
         */
        add_action( 'save_post', function ( $postId, $post ) use ( $box, $metaboxId ) {

            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            if ( ! current_user_can( 'edit_post', $postId ) ) {
                return;
            }

            $nonce = Util::getItem( $_POST, $metaboxId . '_nonce' );

            if ( wp_verify_nonce( $nonce, $metaboxId ) ) {
                $params = InputHelper::getParams();
                foreach ( $params as $key => $value ) {
                    $match = array();

                    if ( preg_match( '%^metabox-([\w\d_]+)$%i', $key, $match ) ) {
                        if ( $value ) {
                            update_post_meta( $postId, $match[ 1 ], $value );
                        }
                        else {
                            delete_post_meta( $postId, $match[ 1 ] );
                        }
                    }
                }
                call_user_func( [ $box, 'save' ], $postId, $post );
            }
        }, 50, 2 );
    }

    /**
     * Meta box display callback.
     *
     * @since   1.0.0
     *
     * @param   \WP_Post $post Current post object.
     *
     * @return  void
     */
    public function render( $post )
    {
        ?>
        <div>Please implement render($post) method to render this metabox. Below is a dump of the current post</div>
        <?php
        var_dump( $post );
    }

    /**
     * Save meta box content.
     *
     * @since   1.0.0
     *
     * @param   int $postId Post ID
     *
     * @return  void
     */
    public function save( $postId )
    {
    }
}