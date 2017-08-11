<?php

/**
 * UABB Integration
 *
 * @package     WPD\BeaverPopups\Integrations
 * @since       1.0.1
 * @author      smarterdigitalltd
 * @link        https://www.smarter.uk.com
 * @license     GNU-2.0+
 */

namespace WPD\BeaverPopups\Integrations;

use FLBuilderModel;
use WPD\BeaverPopups\Helpers\PopupHelper;
use WPD\BeaverPopups\Helpers\AssetHelper;

class UABBIntegration {

    /**
     * Register filters and actions hooks
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public static function registerHooks()
    {
        if ( class_exists( 'BB_Ultimate_Addon' ) ) {
            add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueueAssets' ] );
            add_filter( 'fl_builder_render_module_settings', [ __CLASS__, 'enablePopupLinkOptionOnButton' ], 10, 2 );
            add_filter( 'fl_builder_module_attributes', [ __CLASS__, 'setButtonAttributes' ], 10, 2 );
        }
    }

    /**
     * Adds 'popup' to normal BB button click actions. This is disabled on
     * buttons within popups
     *
     * @since   1.0.0
     *
     * @param   array $form Settings form
     * @param   string $module Module slug
     *
     * @return  array Settings form
     */
    public static function enablePopupLinkOptionOnButton( $form, $module )
    {
        if ( get_post_type() != PopupHelper::CUSTOM_POST_TYPE_POPUP ) {
            if ( 'uabb-button' == $module->slug ) {
                $linkFields = $form[ 'general' ][ 'sections' ][ 'link' ][ 'fields' ];

                $form[ 'general' ][ 'sections' ][ 'link' ][ 'fields' ] = [
                    'click_action' => [
                        'type' => 'select',
                        'label' => __( 'Click Action', BEAVER_POPUPS_TEXT_DOMAIN ),
                        'options' => [
                            'link' => __( 'Link', BEAVER_POPUPS_TEXT_DOMAIN ),
                            'popup' => __( 'Popup', BEAVER_POPUPS_TEXT_DOMAIN ),
                        ],
                        'default' => 'link',
                        'toggle' => [
                            'link' => [
                                'fields' => [ 'link', 'link_target' ]
                            ],
                            'popup' => [
                                'fields' => [ 'popup' ]
                            ]
                        ]
                    ]
                ] + $linkFields;

                $form[ 'general' ][ 'sections' ][ 'link' ][ 'fields' ][ 'popup' ] = [
                    'type' => 'suggest',
                    'label' => __( 'Select Popup', BEAVER_POPUPS_TEXT_DOMAIN ),
                    'action' => 'fl_as_posts',
                    'data' => PopupHelper::CUSTOM_POST_TYPE_POPUP,
                    'limit' => 1,
                ];
            }
        }

        return $form;
    }

    /**
     * Updates button attributes if popup is selected as the click action
     *
     * @since   1.0.0
     *
     * @param   array $attrs Existing array of attributes for a module
     * @param   object $module Module object
     *
     * @return  array button attributes
     */
    public static function setButtonAttributes( $attrs, $module )
    {
        if ( 'uabb-button' == $module->slug ) {
            if ( 'popup' == $module->settings->click_action && $module->settings->popup ) {
                if ( PopupHelper::CUSTOM_POST_TYPE_POPUP == get_post( $module->settings->popup )->post_type && 'publish' == get_post( $module->settings->popup )->post_status ) {
                    $attrs[ 'data-wpd-bb-popup-id' ] = $module->settings->popup;
                    $attrs[ 'class' ][] = PopupHelper::CUSTOM_POST_TYPE_POPUP . '__button--enabled';

                    /**
                     * Add the popup to the global variable for future use
                     */
                    $popup = new \stdClass();
                    $popup->id = (int) $module->settings->popup;
                    PopupHelper::$activePopupsOnThisPage[] = $popup;
                }
                else {
                    $attrs[ 'class' ][] = 'wpd-orphaned-popup-button';
                }
            }
        }

        return $attrs;
    }

    /**
     * Enqueues styles and scripts
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public static function enqueueAssets()
    {
        /**
         * Scripts
         */
        // Builder only scripts
        if ( FLBuilderModel::is_builder_active() ) {

            // Ascertain which script handle to use as a dependency
            $fl_builder_handle = defined( 'WP_DEBUG' ) && WP_DEBUG ? 'fl-builder' : 'fl-builder-min';

            wp_enqueue_script( 'wpd-bb-popups-page-builder' );
            wp_enqueue_script( 'wpd-bb-popups-uabb-page-builder', AssetHelper::getHashedAssetUri( 'js/uabb.js' ), [
                'jquery',
                $fl_builder_handle,
                'wpd-bb-popups-page-builder'
            ], false, true );
        }
    }

}