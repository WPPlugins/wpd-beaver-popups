<?php

/**
 * Beaver Builder management
 *
 * @package     WPD\BeaverPopups\Helpers
 * @since       1.0.0
 * @author      smarterdigitalltd
 * @link        https://www.smarter.uk.com
 * @license     GNU-2.0+
 */

namespace WPD\BeaverPopups\Helpers;

use FLBuilder;
use FLBuilderAJAX;
use FLBuilderIcons;
use FLBuilderModel;
use WPD\BeaverPopups\Plugin;

class BeaverBuilderHelper {

    /**
     * The path to the template part that contains the popup content once
     * it's loaded on the front end
     *
     * @since   1.0.0
     */
    const POPUP_TEMPLATE_CONTENT_PART_PATH = 'app/Templates/PopupContent.php';

    /**
     * Register filters and actions hooks
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public static function registerHooks()
    {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueueAssets' ] );
        add_action( 'init', [ __CLASS__, 'registerPopupStyleSettingsForm' ] );
        add_action( 'wp', [ __CLASS__, 'addPopupSettingsAjaxHandler' ], 5 );
        add_action( 'wp_footer', [ __CLASS__, 'renderPopupsInDom' ], 10 );
        add_action( 'wp_footer', [ __CLASS__, 'outputPopupJsConfigToFooter' ], 11 );
        add_action( 'add_meta_boxes_' . PopupHelper::CUSTOM_POST_TYPE_POPUP, [ __CLASS__, 'forcePageBuilderOnPopup' ], 11 );
        add_filter( 'body_class', [ __CLASS__, 'addBodyClassToPopup' ], 10, 1 );
        add_action( 'fl_builder_before_save_layout', [ __CLASS__, 'saveDraftPopupStyleSettingsIntoPublished' ], 11, 4 );
        add_filter( 'fl_builder_post_types', [ __CLASS__, 'enablePageBuilderOnPopupCpt' ], 10, 1 );
        add_filter( 'fl_builder_render_module_settings', [ __CLASS__, 'enablePopupLinkOptionOnBbButton' ], 10, 2 );
        add_filter( 'fl_builder_module_attributes', [ __CLASS__, 'setButtonAttributes' ], 10, 2 );
        add_filter( 'fl_builder_register_settings_form', [ __CLASS__, 'addRowCSSField' ], 10, 2 );
        add_filter( 'fl_builder_render_css', [ __CLASS__, 'renderCustomRowCSS' ], 10, 4 );
        add_filter( 'fl_builder_ui_bar_buttons', [ __CLASS__, 'addPopupOptionsButtonToBuilderBar' ], 10, 1 );
        add_filter( 'fl_builder_render_css', [ __CLASS__, 'saveCustomPopupCssIntoStylesheet' ], 10, 4 );
    }

    /**
     * Enables the page builder on Popup CPT
     *
     * @since   1.0.0
     *
     * @param   string $post_types Post types the builder is enabled for
     *
     * @return  string Updated array of post types the builder is enabled for
     */
    public static function enablePageBuilderOnPopupCpt( $post_types )
    {
        $post_types[] = PopupHelper::CUSTOM_POST_TYPE_POPUP;

        return $post_types;
    }

    /**
     * Sets the page builder as 'enabled' on CPT
     *
     * @since   1.0.0
     *
     * @param   $post Post object
     *
     * @return  void
     */
    public static function forcePageBuilderOnPopup( $post )
    {
        FLBuilderModel::enable();
    }

    /**
     * Adds a body class to single popup to mimic the style when editing/previewing
     *
     * @since   1.0.0
     *
     * @param   string $body_classes Existing body classes
     *
     * @return  string Updated array of body classes
     */
    public static function addBodyClassToPopup( $body_classes )
    {
        if ( is_singular( $cpt = PopupHelper::CUSTOM_POST_TYPE_POPUP ) ) {
            $popup_type_class = isset( self::getPopupStyleSettings( null, get_the_ID() )->popup_type ) ? self::getPopupStyleSettings( null, get_the_ID() )->popup_type : 'modal';

            $body_classes[] = 'fl-builder-panel--open';
            $body_classes[] = $cpt . '--active';
            $body_classes[] = $cpt . '__' . $popup_type_class . '--active';
            $body_classes[] = $cpt . '-' . get_the_id() . '--active';
        }

        return $body_classes;
    }

    /**
     * Gets the name of a icon pack based on the class name used. The icon font will then be enqueued
     *
     * @since   1.0.0
     *
     * @param   string $icon_css_class CSS class
     *
     * @return  string Icon pack name
     */
    public static function getIconPackName( $icon_css_class )
    {
        if ( stristr( $icon_css_class, 'fa-' ) ) {
            return 'font-awesome';
        }
        else if ( stristr( $icon_css_class, 'fi-' ) ) {
            return 'foundation-icons';
        }
        else if ( stristr( $icon_css_class, 'dashicon' ) ) {
            return 'dashicons';
        }

        return null;
    }

    /**
     * Enqueues either the font pack specified or enqueues all custom sets
     *
     * @since   1.0.0
     *
     * @param   string $icon_css_class CSS class
     *
     * @return  void
     */
    public static function enqueueIconPackFromCssClass( $icon_css_class )
    {
        if ( $icon_pack = self::getIconPackName( $icon_css_class ) ) {
            wp_enqueue_style( $icon_pack );
        }
        else {
            FLBuilderIcons::enqueue_all_custom_icons_styles();
        }
    }

    /**
     * Adds 'Popup Options' to page builder bar
     *
     * @todo 'use' the FLBuilderUserAccess Class when BB 1.10 is more widely adopted
     *
     * @since   1.0.0
     *
     * @return  array Buttons in row
     */
    public static function addPopupOptionsButtonToBuilderBar( $buttons )
    {
        $show = false;

        if ( class_exists( '\FLBuilderUserAccess' ) && method_exists( '\FLBuilderUserAccess', 'current_user_can' ) ) {
            $show = \FLBuilderUserAccess::current_user_can( 'unrestricted_editing' ) && PopupHelper::CUSTOM_POST_TYPE_POPUP == get_post_type();
        }
        else {
            $show = FLBuilderModel::current_user_has_editing_capability() && PopupHelper::CUSTOM_POST_TYPE_POPUP == get_post_type();
        }

        $buttons[ 'popup-options' ] = [
            'label' => __( 'Popup Options', 'wpd' ),
            'show' => $show
        ];

        return $buttons;
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
    public static function enablePopupLinkOptionOnBbButton( $form, $module )
    {
        if ( get_post_type() != PopupHelper::CUSTOM_POST_TYPE_POPUP ) {
            if ( 'button' == $module->slug ) {
                $form[ 'general' ][ 'sections' ][ 'general' ][ 'fields' ][ 'click_action' ][ 'options' ][ 'popup' ] = __( 'Popup', 'fuelled' );
                $form[ 'general' ][ 'sections' ][ 'general' ][ 'fields' ][ 'click_action' ][ 'toggle' ][ 'popup' ] = [
                    'sections' => [
                        'popup'
                    ]
                ];

                $form[ 'general' ][ 'sections' ][ 'popup' ] = [
                    'title' => __( 'Popup', 'wpd' ),
                    'fields' => [
                        'popup' => [
                            'type' => 'suggest',
                            'label' => __( 'Select Popup', BEAVER_POPUPS_TEXT_DOMAIN ),
                            'action' => 'fl_as_posts',
                            'data' => PopupHelper::CUSTOM_POST_TYPE_POPUP,
                            'limit' => 1,
                        ]
                    ]
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
        if ( 'button' == $module->slug ) {
            if ( 'popup' == $module->settings->click_action && $module->settings->popup ) {
                if ( PopupHelper::CUSTOM_POST_TYPE_POPUP == get_post( $module->settings->popup )->post_type && 'publish' == get_post( $module->settings->popup )->post_status ) {
                    $attrs[ 'data-wpd-bb-popup-id' ] = $module->settings->popup;
                    $attrs[ 'class' ][] = 'wpd-bb-popup__button--enabled';

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
     * Adds a CSS field to rows inside the popup builder
     *
     * @since   1.0.2
     *
     * @param   array $form The form
     * @param   string $id The ID (type) of the form
     *
     * @return  array New form
     */
    public static function addRowCSSField( $form, $id )
    {
        if ( isset( $_REQUEST['fl_builder_data']['post_id'] ) && PopupHelper::CUSTOM_POST_TYPE_POPUP == get_post_type( $_REQUEST['fl_builder_data']['post_id'] ) && 'row' == $id ) {
            $form[ 'tabs' ][ 'advanced' ][ 'sections' ][ 'row_css' ] = [
                'title' => __( 'Row CSS', 'wpd' ),
                'fields' => [
                    'row_css' => [
                        'label' => __( 'Row CSS', 'wpd' ),
                        'help' => __( 'Add CSS here to automatically apply it to the row, without using the node ID' ),
                        'type' => 'code',
                        'editor' => 'html',
                        'rows' => '18'
                    ]
                ]
            ];
        }

        return $form;
    }

    /**
     * Renders the custom CSS added to rows
     *
     * @since   1.0.2
     *
     * @param   mixed $css The compiled CSS as part of render_css process
     * @param   array $nodes The nodes on the page
     * @param   object $global_settings Beaver Builder global settings
     *
     * @return  mixed New CSS
     */
    public static function renderCustomRowCSS( $css, $nodes, $global_settings, $global )
    {
        ob_start();

        foreach ( $nodes as $node_group ) {
            foreach( $node_group as $node => $node_object ) {
                if ( 'row' === $node_object->type && isset( $node_object->settings->row_css ) && ! empty( $node_object->settings->row_css ) ) : ?>
                    .fl-node-<?php echo $node_object->node; ?> .fl-row-content-wrap {
                        <?php echo $node_object->settings->row_css; ?>
                    }
                <?php endif;
            }
        }

        $css .= ob_get_clean();

        return $css;
    }

    /**
     * Add AJAX handlers to display and save settings forms
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public static function addPopupSettingsAjaxHandler()
    {
        FLBuilderAJAX::add_action( 'wpd_render_bb_popup_styles_settings_form', [ __CLASS__, 'renderPopupStylesSettingsForm' ] );
        FLBuilderAJAX::add_action( 'wpd_save_bb_popup_styles_settings', [ __CLASS__, 'savePopupStyleSettings' ], [ 'settings' ] );
    }

    /**
     * Register settings forms for per-popup settings. These are accessed via the header bar in the builder
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public static function registerPopupStyleSettingsForm()
    {
        FLBuilder::register_settings_form( 'wpd-bb-popup-styles-settings-form', [
            'title' => __( 'Popup Styles', 'wpd' ),
            'tabs' => [
                'general' => [
                    'title' => __( 'General', 'wpd' ),
                    'sections' => [
                        'popup_type' => [
                            'title' => __( 'Popup Type', 'wpd' ),
                            'fields' => [
                                'popup_type' => [
                                    'type' => 'select',
                                    'label' => __( 'Popup Type', 'wpd' ),
                                    'default' => 'modal',
                                    'options' => [
                                        'modal' => __( 'Modal', 'wpd' ),
                                        'fly_out' => __( 'Fly out', 'wpd' )
                                    ],
                                    'toggle' => [
                                        'modal' => [
                                            'sections' => [ 'modal_style' ],
                                            'fields' => [ 'modal_close_icon_position' ]
                                        ],
                                        'fly_out' => [
                                            'sections' => [ 'fly_out_style' ],
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'modal_style' => [
                            'title' => __( 'Popup Modal Style', 'wpd' ),
                            'fields' => [
                                'modal_overlay_background_type' => [
                                    'type' => 'select',
                                    'label' => __( 'Overlay Type', 'wpd' ),
                                    'options' => [
                                        'color' => __( 'Color', 'wpd' ),
                                        'image' => __( 'Image', 'wpd' ),
                                    ],
                                    'toggle' => [
                                        'color' => [
                                            'fields' => [ 'modal_overlay_background_color' ]
                                        ],
                                        'image' => [
                                            'fields' => [
                                                'modal_overlay_background_image',
                                                'modal_overlay_background_image_repeat',
                                                'modal_overlay_background_image_size',
                                                'modal_overlay_background_image_position'
                                            ]
                                        ],
                                    ],
                                    'default' => 'color'
                                ],
                                'modal_overlay_background_color' => [
                                    'type' => 'color',
                                    'label' => __( 'Popup Overlay Color', 'wpd' ),
                                    'show_alpha' => true,
                                ],
                                'modal_overlay_background_image' => [
                                    'type' => 'photo',
                                    'label' => __( 'Popup Overlay Image', 'wpd' ),
                                ],
                                'modal_overlay_background_image_repeat' => [
                                    'type' => 'select',
                                    'label' => __( 'Popup Overlay Image Repeat', 'wpd' ),
                                    'options' => [
                                        'no_repeat' => __( 'No Repeat', 'wpd' ),
                                        'repeat' => __( 'Repeat', 'wpd' ),
                                    ],
                                    'default' => 'no_repeat'
                                ],
                                'modal_overlay_background_image_size' => [
                                    'type' => 'select',
                                    'label' => __( 'Popup Overlay Image Size', 'wpd' ),
                                    'options' => [
                                        'cover' => __( 'Cover', 'wpd' ),
                                        'contain' => __( 'Contain', 'wpd' ),
                                        'initial' => __( 'Image size', 'wpd' ),
                                    ],
                                    'default' => 'cover'
                                ],
                                'modal_overlay_background_image_position' => [
                                    'type' => 'select',
                                    'label' => __( 'Popup Overlay Image Position', 'wpd' ),
                                    'options' => [
                                        'center' => __( 'Center', 'wpd' ),
                                    ],
                                    'default' => 'center'
                                ],
                            ]
                        ],
                        'fly_out_style' => [
                            'title' => __( 'Fly Out Popup Style', 'wpd' ),
                            'fields' => [
                                'fly_out_x_position' => [
                                    'label' => __( 'Fly Out X Position', 'wpd' ),
                                    'type' => 'select',
                                    'options' => [
                                        'right' => __( 'Right' ),
                                        'center' => __( 'Center' ),
                                        'left' => __( 'Left' ),
                                    ],
                                    'default' => 'right'
                                ],
                                'fly_out_y_position' => [
                                    'label' => __( 'Fly Out Y Position', 'wpd' ),
                                    'type' => 'select',
                                    'options' => [
                                        'top' => __( 'Top' ),
                                        'bottom' => __( 'Bottom' ),
                                    ],
                                    'default' => 'bottom'
                                ],
                            ]
                        ],
                        'close_icon_style' => [
                            'title' => __( 'Close Icon Style', 'wpd' ),
                            'fields' => [
//                                'close_icon' => [
//                                    'type' => 'icon',
//                                    'label' => __( 'Icon', 'wpd' ),
//                                    'default' => 'fa fa-close'
//                                ],
                                'close_icon_size' => [
                                    'type' => 'unit',
                                    'label' => __( 'Icon Size', 'wpd' ),
                                    'description' => 'px',
                                    'responsive' => true,
                                    'default' => '32'
                                ],
                                'close_icon_color' => [
                                    'type' => 'color',
                                    'label' => __( 'Icon Color', 'wpd' ),
                                    'default' => '#000'
                                ],
                                'modal_close_icon_position' => [
                                    'label' => __( 'Icon Position', 'wpd' ),
                                    'type' => 'select',
                                    'options' => [
                                        'overlay' => __( 'Relative to Overlay' ),
                                        'box' => __( 'Relative to Popup' ),
                                    ],
                                    'default' => 'overlay'
                                ],
//                                'close_icon_vertical_position' => [
//                                    'label' => __( 'Vertical Position', 'wpd' ),
//                                    'type' => 'select',
//                                    'options' => [
//                                        'top' => __( 'Top' ),
//                                        'bottom' => __( 'Bottom' ),
//                                    ],
//                                    'default' => 'top'
//                                ],
                                'close_icon_vertical_distance' => [
                                    'label' => __( 'Distance from Top', 'wpd' ),
                                    'type' => 'unit',
                                    'description' => 'px',
                                    'default' => '0'
                                ],
//                                'close_icon_horizontal_position' => [
//                                    'label' => __( 'Horizontal Position', 'wpd' ),
//                                    'type' => 'select',
//                                    'options' => [
//                                        'top' => __( 'Left' ),
//                                        'bottom' => __( 'Right' ),
//                                    ],
//                                    'default' => 'top'
//                                ],
                                'close_icon_horizontal_distance' => [
                                    'label' => __( 'Distance from Right', 'wpd' ),
                                    'type' => 'unit',
                                    'description' => 'px',
                                    'default' => '0'
                                ],
                            ]
                        ],
                        'popup_structure' => [
                            'title' => __( 'Popup Structure', 'wpd' ),
                            'fields' => [
                                'width' => [
                                    'type' => 'unit',
                                    'label' => __( 'Width', 'wpd' ),
                                    'description' => 'px',
                                    'responsive' => false,
                                    'default' => '600'
                                ],
                                'border_radius' => [
                                    'type' =>'unit',
                                    'label' => __( 'Border Radius', 'wpd' ),
                                    'description' => 'px',
                                    'default' => '0'
                                ],
                            ]
                        ],
                        'popup_box_shadow' => [
                            'title' => __( 'Box Shadow', 'wpd' ),
                            'fields' => [
                                'add_box_shadow' => [
                                    'type' => 'select',
                                    'label' => __( 'Add Box Shadow?', 'wpd' ),
                                    'options' => [
                                        'no' => __( 'No', 'wpd' ),
                                        'yes' => __( 'Yes', 'wpd' ),
                                    ],
                                    'toggle' => [
                                        'yes' => [
                                            'fields' => [
                                                'box_shadow_color',
                                                'box_shadow_horizontal_length',
                                                'box_shadow_vertical_length',
                                                'box_shadow_spread_radius',
                                                'box_shadow_blur_radius',
                                                'box_shadow_color_opacity',
                                            ]
                                        ]
                                    ],
                                ],
                                'box_shadow_color' => [
                                    'type' => 'color',
                                    'label' => __( 'Box Shadow Color', 'wpd' ),
                                    'show_reset' => true,
                                    'default' => '000',
                                ],
                                'box_shadow_horizontal_length' => [
                                    'type' => 'unit',
                                    'label' => __( 'Box Shadow Horizontal Length', 'wpd' ),
                                    'description' => __( 'px', 'wpd' ),
                                    'default' => '0',
                                ],
                                'box_shadow_vertical_length' => [
                                    'type' => 'unit',
                                    'label' => __( 'Box Shadow Vertical Length', 'wpd' ),
                                    'description' => __( 'px', 'wpd' ),
                                    'default' => '0',
                                ],
                                'box_shadow_spread_radius' => [
                                    'type' => 'unit',
                                    'label' => __( 'Box Shadow Spread', 'wpd' ),
                                    'description' => __( 'px', 'wpd' ),
                                    'default' => '5',
                                ],
                                'box_shadow_blur_radius' => [
                                    'type' => 'unit',
                                    'label' => __( 'Box Shadow Blur', 'wpd' ),
                                    'description' => __( 'px', 'wpd' ),
                                    'default' => '0',
                                ],
                                'box_shadow_color_opacity' => [
                                    'type' => 'unit',
                                    'label' => __( 'Box Shadow Opacity', 'wpd' ),
                                    'help' => __( 'Between 0 and 1', 'wpd' ),
                                    'default' => '0.5',
                                ],
                            ]
                        ],
                        'popup_animations' => [
                            'title' => __( 'Popup Animations', 'wpd' ),
                            'fields' => [
                                'open_animation' => [
                                    'type' => 'select',
                                    'label' => __( 'Open Animation' ),
                                    'options' => [
                                        'none' => __( 'Select Animation', 'wpd' ),
                                        'zoomIn' => 'Zoom In',
                                        'zoomOut' => 'Zoom Out',
                                        'pulse' => 'Pulse',
                                        'slide' => 'Slide',
                                        'move' => 'Move',
                                        'flip' => 'Flip',
                                        'tada' => 'Tada',
                                    ],
                                    'toggle' => [
                                        'slide' => [
                                            'fields' => ['open_animation_direction' ],
                                        ],
                                        'move' => [
                                            'fields' => ['open_animation_direction' ],
                                        ]
                                    ]
                                ],
                                'open_animation_direction' => [
                                    'type' => 'select',
                                    'label' => __( 'Open Animation Direction', 'wpd' ),
                                    'options' => [
                                        'top' => __( 'To Top', 'wpd' ),
                                        'right' => __( 'To Right', 'wpd' ),
                                        'bottom' => __( 'To Bottom', 'wpd' ),
                                        'left' => __( 'To Left', 'wpd' ),
                                    ]
                                ],
                                'close_animation' => [
                                    'type' => 'select',
                                    'label' => __( 'Close Animation' ),
                                    'options' => [
                                        'none' => __( 'Select Animation', 'wpd' ),
                                        'zoomIn' => 'Zoom In',
                                        'zoomOut' => 'Zoom Out',
                                        'pulse' => 'Pulse',
                                        'slide' => 'Slide',
                                        'move' => 'Move',
                                        'flip' => 'Flip',
                                        'tada' => 'Tada',
                                    ],
                                    'toggle' => [
                                        'slide' => [
                                            'fields' => ['close_animation_direction' ],
                                        ],
                                        'move' => [
                                            'fields' => ['close_animation_direction' ],
                                        ]
                                    ]
                                ],
                                'close_animation_direction' => [
                                    'type' => 'select',
                                    'label' => __( 'Close Animation Direction', 'wpd' ),
                                    'options' => [
                                        'top' => __( 'To Top', 'wpd' ),
                                        'right' => __( 'To Right', 'wpd' ),
                                        'bottom' => __( 'To Bottom', 'wpd' ),
                                        'left' => __( 'To Left', 'wpd' ),
                                    ]
                                ],
                            ]
                        ],
                    ],
                ],
            ]
        ] );
    }

    /**
     * Called via AJAX to render styles settings form for popup.
     *
     * @since   1.0.0
     *
     * @return  array Form settings
     */
    public static function renderPopupStylesSettingsForm()
    {
        $settings = self::getPopupStyleSettings();
        $form = FLBuilderModel::$settings_forms[ 'wpd-bb-popup-styles-settings-form' ];

        return FLBuilder::render_settings( array(
            'class' => 'wpd-bb-popup-styles-settings-form',
            'title' => $form[ 'title' ],
            'tabs' => $form[ 'tabs' ],
            'resizable' => true
        ), $settings );
    }

    /**
     * Called via AJAX to save the popup styles settings.
     *
     * @since   1.0.0
     *
     * @param   array $settings The new layout settings.
     * @param   string $status Either published or draft.
     * @param   int $post_id The ID of the post to update.
     *
     * @return  object Updated settings
     */
    static public function savePopupStyleSettings( $settings = array(), $status = null, $post_id = null )
    {
        return self::updatePopupStyleSettings( $settings, $status, $post_id );
    }

    /**
     * Updates popup settings.
     *
     * @since   1.0.0
     *
     * @param   array $settings The new popup settings.
     * @param   string $status Either published or draft.
     * @param   int $post_id The ID of the popup to update.
     *
     * @return  object Settings object
     */
    public static function updatePopupStyleSettings( $settings = array(), $status = null, $post_id = null )
    {
        $status         = ! $status ? FLBuilderModel::get_node_status() : $status;
        $post_id        = ! $post_id ? FLBuilderModel::get_post_id() : $post_id;
        $key            = 'published' == $status ? '_wpd_bb_popup_style_settings' : '_wpd_bb_popup_style_draft_settings';
        $raw_settings   = get_metadata( 'post', $post_id, $key );
        $old_settings   = self::getPopupStyleSettings( $status, $post_id );
        $new_settings   = ( object )array_merge( ( array )$old_settings, ( array )$settings );

        if ( 0 === count( $raw_settings ) ) {
            add_metadata( 'post', $post_id, $key, FLBuilderModel::slash_settings( $new_settings ) );
        }
        else {
            update_metadata( 'post', $post_id, $key, FLBuilderModel::slash_settings( $new_settings ) );
        }

        return $new_settings;
    }

    /**
     * Get the popup settings
     *
     * @since   1.0.0
     *
     * @param   string $status Either published or draft.
     * @param   int $post_id The ID of the popup to get settings for.
     *
     * @return  object
     */
    public static function getPopupStyleSettings( $status = null, $post_id = null )
    {
        $status     = ! $status ? FLBuilderModel::get_node_status() : $status;
        $post_id    = ! $post_id ? FLBuilderModel::get_post_id() : $post_id;
        $key        = 'published' == $status ? '_wpd_bb_popup_style_settings' : '_wpd_bb_popup_style_draft_settings';
        $settings   = get_metadata( 'post', $post_id, $key, true );
        $defaults   = FLBuilderModel::get_settings_form_defaults( 'wpd-bb-popup-styles-settings-form' );

        if ( ! $settings ) {
            $settings = new \stdClass();
        }

        $settings = ( object )array_merge( ( array )$defaults, ( array )$settings );

        return apply_filters( 'wpd_bb_popup_style_settings', $settings, $status, $post_id );
    }

    /**
     * Delete the settings for a popup.
     *
     * @since   1.0.0
     *
     * @param   string $status Either published or draft.
     * @param   int $post_id The ID of a popup whose settings to delete.
     *
     * @return  void
     */
    public static function deletePopupStyleSettings( $status = null, $post_id = null )
    {
        $status = ! $status ? FLBuilderModel::get_node_status() : $status;
        $post_id = ! $post_id ? FLBuilderModel::get_post_id() : $post_id;
        $key = 'published' == $status ? '_wpd_bb_popup_style_settings' : '_wpd_bb_popup_style_draft_settings';

        update_metadata( 'post', $post_id, $key, array() );
    }

    /**
     * When a layout is saved, we switch the 'draft'/unpublished settings into
     * the 'published' settings
     *
     * @since   1.0.0
     *
     * @param   integer $post_id The post ID of the popup
     * @param   boolean $publish Publish this layout?
     * @param   array $data Layout data
     * @param   array $settings Layout settings
     *
     * @return  void
     */
    public static function saveDraftPopupStyleSettingsIntoPublished( $post_id, $publish, $data, $settings )
    {
        if ( PopupHelper::CUSTOM_POST_TYPE_POPUP == get_post_type() ) {
            $popup_settings = self::getPopupStyleSettings( 'draft', $post_id );

            // Delete old popup settings
            self::deletePopupStyleSettings( 'published', $post_id );

            // Save new popup settings
            self::updatePopupStyleSettings( $popup_settings, 'published', $post_id );
        }
    }

    /**
     * Hook into the fl_builder_render_css filter and merge the custom popup styles into it
     *
     * @since   1.0.0
     * @updated 1.0.2 Refactor for Themer compatibility. Include 4th param of $global
     *
     * @param   string $css
     * @param   array $nodes
     * @param   array $global_settings
     * @param   bool  $global
     *
     * @return  string CSS
     */
    public static function saveCustomPopupCssIntoStylesheet( $css, $nodes, $global_settings, $global )
    {
        if ( isset( $global ) && ! $global ) {
            return $css;
        }

        $cpt        = PopupHelper::CUSTOM_POST_TYPE_POPUP;
        $cssFiles   = [
            'Common--Width',
            'Common--BorderRadius',
            'Common--BoxShadow',
            'Common--CloseButton',
            'ModalPopup',
            'FlyoutPopup'
        ];

        if ( FLBuilderModel::is_builder_active() && 'published' != FLBuilderModel::get_node_status() ) {

            $popup      = new \stdClass();
            $popup->ID  = get_the_ID();
            $settings   = self::getPopupStyleSettings( 'draft' );

            if ( ! empty( $settings ) ) {
                ob_start();

                foreach( $cssFiles as $file ) {
                    include( Plugin::path( "app/Includes/{$file}.css.php" ) );
                }

                $css .= ob_get_clean();
            }
        }
        else {
            // Get Popup settings CSS and render with other CSS
            $all_published_popups = get_posts( [
                'post_type'         => PopupHelper::CUSTOM_POST_TYPE_POPUP,
                'post_status'       => 'publish',
                'posts_per_page'    => -1
            ] );

            foreach ( $all_published_popups as $popup ) {
                $settings = self::getPopupStyleSettings( 'published', $popup->ID );

                if ( ! empty( $settings ) ) {
                    ob_start();

                    foreach( $cssFiles as $file ) {
                        include( Plugin::path( "app/Includes/{$file}.css.php" ) );
                    }

                    $css .= ob_get_clean();
                }
            }
        }

        return $css;
    }

    /**
     * Adds popup to the DOM in wp_footer, if we're not in the builder
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public static function renderPopupsInDom()
    {
        if ( PopupHelper::CUSTOM_POST_TYPE_POPUP != get_post_type() && ! FLBuilderModel::is_builder_active() ) {
            /**
             * To output into DOM, we need a unique array to avoid duplicate popups.
             * As the array is of objects, we need to just get the ID
             */
            $popups = [];
            foreach ( PopupHelper::$activePopupsOnThisPage as $popup ) {
                $popups[] = $popup->id;
            }

            /**
             * Loop through
             */
            foreach( array_unique( $popups ) as $popupId ) {
                /**
                 * Problem: plugins filtering the_content to add stuff before, to and after the_content
                 * such as share buttons. Provisionally removing content filters.
                 *
                 * Seems to have removed the share buttons without impacting the page that the popup
                 * was triggered on. That is to say, share buttons on the underlying page (not the popup)
                 * still have share buttons, capital_p_dangit still works, but share buttons removed from
                 * the popup
                 */
                remove_all_filters( 'the_content' );

                /**
                 * Enqueue by post ID. Previously this was handled by FLBuilder::render_query in the popup template
                 * but there is a new method available in Beaver Builder 1.10 - FLBuilder::render_content_by_id()
                 * which doesn't enqueue styles, hence using this new method here
                 *
                 * @since 1.0.3
                 */
                if ( method_exists( '\FLBuilder', 'enqueue_layout_styles_scripts_by_id' ) ) {
                    \FLBuilder::enqueue_layout_styles_scripts_by_id( $popupId );
                }

                // Include the template file (therefore passing all variables through
                include( Plugin::path( self::POPUP_TEMPLATE_CONTENT_PART_PATH ) );

                // Dequeue this individual popup's layout script, as we'll inject in via popup open callback in JS
                wp_dequeue_script( 'fl-builder-layout-' . $popupId );
            }
        }
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
        // Popup Builder only scripts
        if ( FLBuilderModel::is_builder_active() && PopupHelper::CUSTOM_POST_TYPE_POPUP == get_post_type() ) {

            // Ascertain which script handle to use as a dependency
            $fl_builder_handle = defined( 'WP_DEBUG' ) && WP_DEBUG ? 'fl-builder' : 'fl-builder-min';

            wp_enqueue_script( 'wpd-bb-popups-page-builder', AssetHelper::getHashedAssetUri( 'js/popupBuilder.js' ), [
                'jquery',
                'jquery-validate',
                $fl_builder_handle
            ], false, true );
        }

        // Page Builder only scripts
        // Only registered - enqueued elsewhere, when necessary
        wp_register_script( 'wpd-bb-popups-page-builder', AssetHelper::getHashedAssetUri( 'js/pageBuilder.js', [ 'jquery' ] ) );

        // Front end scripts
        if ( ! FLBuilderModel::is_builder_active() && get_post_type() != PopupHelper::CUSTOM_POST_TYPE_POPUP ) {
            wp_enqueue_script( 'wpd-bb-popups-front-end', AssetHelper::getHashedAssetUri( 'js/frontend.js' ), [
                'jquery',
            ], false, true );
        }

        /**
         * Styles
         */
        // Front end styles
        wp_enqueue_style( 'wpd-bb-popups-front-end', AssetHelper::getHashedAssetUri( 'css/frontend.css' ) );
    }

    /**
     * Output popup config into JS object in footer
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public static function outputPopupJsConfigToFooter()
    {
        $wpd_popup_config = [
            'wpdPopupCpt' => PopupHelper::CUSTOM_POST_TYPE_POPUP,
            'pageID' => get_the_ID()
        ];

        foreach ( PopupHelper::$activePopupsOnThisPage as $popup ) {
            $wpd_popup_config[ 'activePopups' ][] = array_merge( get_object_vars( $popup ), [
                'settings' => BeaverBuilderHelper::getPopupStyleSettings( 'published', $popup->id ),
                'script' => [
                    // Full script URL
                    'source' => isset( wp_scripts()->registered[ 'fl-builder-layout-' . $popup->id ]->src ) ? wp_scripts()->registered[ 'fl-builder-layout-' . $popup->id ]->src : null,
                    // Cache-buster
                    'version' => isset( wp_scripts()->registered[ 'fl-builder-layout-' . $popup->id ]->ver ) ? wp_scripts()->registered[ 'fl-builder-layout-' . $popup->id ]->ver : null
                ]
            ] );
        }

        ?>

        <script>
            /* <![CDATA[ */
            WPDPopupConfig = <?= json_encode( $wpd_popup_config ); ?>;
            /* ]]> */
        </script>

        <?php
    }

}