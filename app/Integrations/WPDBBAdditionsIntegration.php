<?php

/**
 * WPD BB Additions Integration
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

class WPDBBAdditionsIntegration {

    /**
     * Register filters and actions hooks
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public static function registerHooks()
    {
        if ( defined( 'WPD_BB_ADDITIONS_PLUGIN_SLUG' ) ) {
            add_filter( 'fl_builder_render_module_settings', [ __CLASS__, 'enableAutoplayOptionOnWPDOptimisedVideo' ], 10, 2 );
        }
    }

    /**
     * Adds an 'autoplay' option on WPD Optimised Video
     *
     * @since   1.0.0
     *
     * @param   array $form Settings form
     * @param   string $module Module slug
     *
     * @return  array Settings form
     */
    public static function enableAutoplayOptionOnWPDOptimisedVideo( $form, $module )
    {
        if ( get_post_type() == PopupHelper::CUSTOM_POST_TYPE_POPUP ) {
            if ( 'wpd-optimised-video' == $module->slug ) {
                $form[ 'general' ][ 'sections' ][ 'integrations' ] = [
                    'title' => __( 'Integrations', BEAVER_POPUPS_TEXT_DOMAIN ),
                    'fields' => [
                        'trigger_autoplay' => [
                            'type' => 'select',
                            'label' => __( 'Autoplay when popup opens', BEAVER_POPUPS_TEXT_DOMAIN ),
                            'options' => [
                                'yes' => __( 'Yes', BEAVER_POPUPS_TEXT_DOMAIN ),
                                'no' => __( 'No', BEAVER_POPUPS_TEXT_DOMAIN ),
                            ],
                            'default' => 'no',
                        ]
                    ]
                ];
            }
        }

        return $form;
    }
}