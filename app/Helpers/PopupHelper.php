<?php

namespace WPD\BeaverPopups\Helpers;

use WPD\BeaverPopups\Plugin;


/**
 * Class PopupHelper is responsible for operations with popup entities and bindings
 *
 * @package WPD\BeaverPopups\Helpers
 */
class PopupHelper {

    /**
     * Site option that stores site wide popups setup
     */
    const OPTION_POPUPS_SITE = 'SitePopups';

    /**
     * Site option that stores custom post type related popups setup
     */
    const OPTION_POPUPS_CPT = 'CPTPopups';

    /**
     * Meta key that holds post specific popup setup
     */
    const POST_META_POPUPS = 'SinglePopups';

    /**
     * Meta key that holds term specific popup setup
     */
    const TERM_META_POPUPS = 'TermPopups';

    /**
     * The path to the template that is used to remove all page elements from
     * the page builder when editing a popup (no header, footer or sidebar)
     *
     * @since 1.0
     */
    const POPUP_CPT_TEMPLATE_PATH = 'app/Templates/PopupCptTemplate.php'; // WPD_BB_POPUP_BUILDER_PLUGIN_DIR

    /**
     * Custom post type for popup
     */
    const CUSTOM_POST_TYPE_POPUP = 'wpd-bb-popup';

    /**
     * An array of post IDs of popups active on the current page
     *
     * @since 1.0
     */
    public static $activePopupsOnThisPage = [];

    /**
     * Site popups setup
     *
     * @var object|null
     */
    protected static $siteSetup = null;

    /**
     * Custom post types setup
     *
     * @var null
     */
    protected static $cptSetup = null;

    /**
     * Register filters and actions hooks
     */
    public static function registerHooks()
    {
        add_action( 'template_redirect', [ __CLASS__, 'getActivePopupsOnCurrentPage' ] );
        add_action( 'init', [ __CLASS__, 'registerCustomPostTypePopup' ] );
        add_action( 'admin_menu', [ __CLASS__, 'removePageAttributesMetaBoxOnPopupCpt' ] );
        add_filter( 'single_template', [ __CLASS__, 'setTemplateForPopupCpt' ], 10, 1 );
        add_action( 'before_delete_post', [ __CLASS__, 'removePopupFromSetup' ], 10, 1 );
        add_action( 'trashed_post', [ __CLASS__, 'removePopupFromSetup' ], 10, 1 );
    }

    /**
     * Get site popups setup
     *
     * @param bool $refresh
     *
     * @return object
     */
    public static function getSitePopups( $refresh = false )
    {
        if ( ! self::$siteSetup || $refresh ) {
            $json = OptionsHelper::get( self::OPTION_POPUPS_SITE, '{}' );
            self::$siteSetup = json_decode( $json, false );

            if ( empty( self::$siteSetup ) ) {
                self::$siteSetup = (object)[];
            }

            if ( ! isset( self::$siteSetup->global ) ) {
                self::$siteSetup->global = (object)[
                    'title' => 'Site wide',
                    'rules' => (object)[],
                ];
            }
        }

        return self::$siteSetup;
    }

    /**
     * Set site popups setup
     *
     * @param $scopeSubject 'site'|'search'|'archive'|'not-found'
     * @param $popupId
     * @param $trigger
     * @param $triggerSetup
     *
     * @return mixed
     */
    public static function setSitePopup( $popupId, $scopeSubject, $trigger, $triggerSetup = [] )
    {
        $setup = self::getSitePopups();
        $scopeSetup = Util::getItem( $setup, $scopeSubject, (object)[ 'rules' => (object)[] ] );

        if ( $popupId ) {
            if ( empty( $triggerSetup ) ) {
                $triggerSetup = [];
            }

            $triggerSetup[ 'id' ] = $popupId;
            if ( empty( $scopeSetup->rules ) ) {
                $scopeSetup->rules = (object)[];
            }

            $scopeSetup->rules->$trigger = $triggerSetup;
        }
        elseif ( isset( $scopeSetup->rules->$trigger ) ) {
            unset( $scopeSetup->rules->$trigger );
        }

        $setup->$scopeSubject = $scopeSetup;
        $json = JsonHelper::encode( $setup, false );
        OptionsHelper::set( self::OPTION_POPUPS_SITE, $json );

        return $scopeSetup;
    }

    /**
     * Remove popup from site setup, used when popup itself is removed from db
     *
     * @param $popupId
     *
     * @return object
     */
    public static function removeSitePopup( $popupId )
    {
        $setup = self::getSitePopups();

        foreach ( $setup as $scopeSubject => $scopeSetup ) {
            foreach ( $scopeSetup->rules as $trigger => $triggerSetup ) {
                $id = Util::getItem( $triggerSetup, 'id' );
                if ( $id == $popupId ) {
                    unset( $setup->$scopeSubject->rules->$trigger );
                }
            }
        }

        $json = JsonHelper::encode( $setup, false );
        OptionsHelper::set( self::OPTION_POPUPS_SITE, $json );

        return self::$siteSetup = $setup;
    }

    /**
     * Get custom post types popups setup
     *
     * @param bool $refresh
     *
     * @return object
     */
    public static function getCustomPostTypePopups( $refresh = false )
    {
        if ( ! self::$cptSetup || $refresh ) {
            $json = OptionsHelper::get( self::OPTION_POPUPS_CPT, '{}' );
            self::$cptSetup = json_decode( $json, false );

            if ( empty( self::$cptSetup ) ) {
                self::$cptSetup = (object)[];
            }

            $types = get_post_types( [
                'public' => true,
//                '_builtin' => false,
            ], 'objects' );

            foreach ( $types as $type ) {
                $name = $type->name;
                if ( ! isset( self::$cptSetup->$name ) && $name !== 'attachment' ) {
                    self::$cptSetup->$name = (object)[
                        'title' => $type->label,
                        'rules' => (object)[],
                    ];
                }
            }
        }

        return self::$cptSetup;
    }

    /**
     * Set custom post types popups setup
     *
     * @param $postType 'page'|'post'|'news'|'gallery'
     * @param $popupId
     * @param $trigger
     * @param $triggerSetup
     *
     * @return mixed
     */
    public static function setCustomPostTypePopup( $popupId, $postType, $trigger, $triggerSetup = [] )
    {
        $setup = self::getCustomPostTypePopups();
        $scopeSetup = Util::getItem( $setup, $postType, (object)[ 'rules' => (object)[] ] );

        if ( $popupId ) {
            if ( empty( $triggerSetup ) ) {
                $triggerSetup = [];
            }
            $triggerSetup[ 'id' ] = $popupId;
            if ( empty( $scopeSetup->rules ) ) {
                $scopeSetup->rules = (object)[];
            }
            $scopeSetup->rules->$trigger = $triggerSetup;
        }
        else if ( isset( $scopeSetup->rules->$trigger ) ) {
            unset( $scopeSetup->rules->$trigger );
        }

        $setup->$postType = $scopeSetup;
        $json = JsonHelper::encode( $setup, false );
        OptionsHelper::set( self::OPTION_POPUPS_CPT, $json );

        return $scopeSetup;
    }

    /**
     * Remove popup from cpt setup, used when popup itself is removed from db
     *
     * @param $popupId
     *
     * @return object
     */
    public static function removeCustomPostTypePopup( $popupId )
    {
        $setup = self::getCustomPostTypePopups();

        foreach ( $setup as $scopeSubject => $scopeSetup ) {
            foreach ( $scopeSetup->rules as $trigger => $triggerSetup ) {
                $id = Util::getItem( $triggerSetup, 'id' );
                if ( $id == $popupId ) {
                    unset( $setup->$scopeSubject->rules->$trigger );
                }
            }
        }

        $json = JsonHelper::encode( $setup, false );
        OptionsHelper::set( self::OPTION_POPUPS_CPT, $json );

        return self::$cptSetup = $setup;
    }

    /**
     * @param \WP_Post $post
     *
     * @return array
     */
    public static function packIndividualPost( $post )
    {
        return [
            'id' => $post->ID,
            'name' => $post->post_name,
            'title' => $post->post_title,
            'type' => $post->post_type,
            'rules' => self::getIndividualPostPopups( $post->ID )
        ];
    }

    /**
     * Select all the posts with individual setup
     */
    public static function getIndividualPostsPopups()
    {
        $query = new \WP_Query( [
            'post_type' => 'any',
            'posts_per_page' => -1,
            'meta_key' => self::POST_META_POPUPS,
            'meta_value' => '',
            'meta_compare' => '>'
        ] );

        $posts = $query->get_posts();

        $data = array_map( function ( $post ) {
            return self::packIndividualPost( $post );
        }, $posts );

        $ids = array_column( $data, 'id' );

        return array_combine( $ids, $data );
    }

    /**
     * Get post popups setup
     *
     * @param int|string|\WP_Post $post
     *
     * @return object
     */
    public static function getIndividualPostPopups( $post )
    {
        $postId = 0;

        if ( is_numeric( $post ) ) {
            $postId = $post;
        }

        else if ( is_string( $post ) ) {
            $query = new \WP_Query( [
                'post_type' => 'any',
                'posts_per_page' => 1,
                'name' => $post,
            ] );
            $posts = $query->get_posts();
            $post = reset( $posts );
            $postId = $post->ID;
        }

        else if ( is_object( $post ) && $post instanceof \WP_Post ) {
            $postId = $post->ID;
        }

        $json = get_post_meta( $postId, self::POST_META_POPUPS, true );
        $setup = $json ? json_decode( $json ) : null;

        return $setup;
    }

    /**
     * Set individual post popups setup
     *
     * @param $popupId
     * @param $postId
     * @param $trigger
     * @param $triggerSetup
     *
     * @return mixed
     */
    public static function setIndividualPostPopup( $popupId, $postId, $trigger, $triggerSetup = [] )
    {
        $scopeSetup = self::getIndividualPostPopups( $postId );

        if ( $popupId ) {
            if ( empty( $triggerSetup ) ) {
                $triggerSetup = [];
            }
            $triggerSetup[ 'id' ] = $popupId;
            if ( empty( $scopeSetup ) ) {
                $scopeSetup = (object)[];
            }
            $scopeSetup->$trigger = $triggerSetup;
        }
        else if ( isset( $scopeSetup->$trigger ) ) {
            unset( $scopeSetup->$trigger );
        }

        if ( ! empty( $scopeSetup ) ) {
            $json = JsonHelper::encode( $scopeSetup, false );
            update_post_meta( $postId, self::POST_META_POPUPS, $json );
        }
        else {
            delete_post_meta( $postId, self::POST_META_POPUPS );
        }

        $post = get_post( $postId );

        return self::packIndividualPost( $post );
    }

    /**
     * Remove popup from individual posts setup, used when popup itself is removed from db
     *
     * @param $popupId
     *
     * @return object
     */
    public static function removeIndividualPostsPopup( $popupId )
    {
        $setup = self::getIndividualPostsPopups();

        foreach ( $setup as $postId => $postSetup ) {
            foreach ( $postSetup[ 'rules' ] as $trigger => $triggerSetup ) {
                $id = Util::getItem( $triggerSetup, 'id' );
                if ( $id == $popupId ) {
                    unset( $postSetup[ 'rules' ]->$trigger );
                    $json = JsonHelper::encode( $postSetup[ 'rules' ], false );
                    update_post_meta( $postId, self::POST_META_POPUPS, $json );
                }
            }
        }

        return $setup;
    }

    /**
     * Remove popup setup
     *
     * @param $popupId
     */
    public static function removePopupSetup( $popupId )
    {
        self::removeSitePopup( $popupId );
        self::removeCustomPostTypePopup( $popupId );
        self::removeIndividualPostsPopup( $popupId );
    }

    /**
     * On post (popup remove)
     *
     * @param $postId
     */
    public static function removePopupFromSetup( $postId )
    {
        $post = get_post( $postId );

        if ( $post->post_type === self::CUSTOM_POST_TYPE_POPUP ) {
            self::removePopupSetup( $postId );
        }
    }

    /**
     * Add empty popup setup to post, so that it will be requested by list query
     *
     * @param $postId
     */
    public static function addIndividualPostSetup( $postId )
    {
        update_post_meta( $postId, self::POST_META_POPUPS, '{}' );
    }

    /**
     * Remove individual post setup
     *
     * @param $postId
     */
    public static function removeIndividualPostSetup( $postId )
    {
        delete_post_meta( $postId, self::POST_META_POPUPS );
    }

    /**
     * Register custom post type 'wpd-bb-popup'
     */
    public static function registerCustomPostTypePopup()
    {
        $labels = [
            'name' => _x( 'Beaver Popups', 'Post Type General Name', 'wpd' ),
            'singular_name' => _x( 'Popup', 'Post Type Singular Name', 'wpd' ),
            'menu_name' => __( 'Beaver Popups', 'wpd' ),
            'name_admin_bar' => __( 'Beaver Popup', 'wpd' ),
            'archives' => __( 'Popup Archives', 'wpd' ),
            'parent_item_colon' => __( 'Parent Popup:', 'wpd' ),
            'all_items' => __( 'All Popups', 'wpd' ),
            'add_new_item' => __( 'Add New Popup', 'wpd' ),
            'add_new' => __( 'Add New', 'wpd' ),
            'new_item' => __( 'New Popup', 'wpd' ),
            'edit_item' => __( 'Edit Popup', 'wpd' ),
            'update_item' => __( 'Update Popup', 'wpd' ),
            'view_item' => __( 'View Popup', 'wpd' ),
            'search_items' => __( 'Search Popup', 'wpd' ),
            'not_found' => __( 'Not found', 'wpd' ),
            'not_found_in_trash' => __( 'Not found in Trash', 'wpd' ),
            'featured_image' => __( 'Featured Image', 'wpd' ),
            'set_featured_image' => __( 'Set featured image', 'wpd' ),
            'remove_featured_image' => __( 'Remove featured image', 'wpd' ),
            'use_featured_image' => __( 'Use as featured image', 'wpd' ),
            'insert_into_item' => __( 'Insert into popup', 'wpd' ),
            'uploaded_to_this_item' => __( 'Uploaded to this popup', 'wpd' ),
            'items_list' => __( 'Popups list', 'wpd' ),
            'items_list_navigation' => __( 'Popups list navigation', 'wpd' ),
            'filter_items_list' => __( 'Filter popups list', 'wpd' ),
        ];

        $args = [
            'label' => __( 'Popup', 'wpd' ),
            'description' => __( 'Popup Description', 'wpd' ),
            'labels' => $labels,
            'supports' => [ 'title', 'editor', 'page-attributes' ],
            'hierarchical' => false,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-editor-expand',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => false,
            'can_export' => true,
            'has_archive' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => true,
            'rewrite' => [
                'with_front' => true,
                'rewrite_base' => self::CUSTOM_POST_TYPE_POPUP
            ],
            'capability_type' => 'page',
        ];

        register_post_type( self::CUSTOM_POST_TYPE_POPUP, $args );
    }

    /**
     * Calculate which popups are active on this page
     *
     * @since 1.0
     * @return array Array of popup IDs
     */
    public static function getActivePopupsOnCurrentPage()
    {
        $post = null;

        /**
         * Our future popup setup for all events
         */
        $popups = [
            'entrance' => null,
            'exit' => null,
            'scroll' => null,
        ];

        /**
         * Our scope setup
         */
        $setup = [
            'site' => null,
            'cpt' => null,
            'post' => null,
        ];

        if ( is_single() || is_page() ) {
            /**
             * If we are on the single post entry get individual setup
             */
            $post = get_post();
            $postSetup = self::getIndividualPostPopups( $post );

            if ( $postSetup ) {
                $setup[ 'post' ] = $postSetup;
            }
        }
        else if ( is_archive() ) {
            /**
             * If we are on archive page simply fetch one post to get post_type
             */
            $post = get_post();
        }

        /**
         * We have a post and can fetch post_type popup bindings
         */
        if ( $post ) {
            $cptSetup = Util::getItem( self::getCustomPostTypePopups(), $post->post_type, null );

            if ( $cptSetup ) {
                $setup[ 'cpt' ] = $cptSetup->rules;
            }
        }

        /**
         * Fetch global popup bindings
         */
        $setup[ 'site' ] = Util::getItem( self::getSitePopups(), 'global' )->rules;

        /**
         * Loop through all scopes beginning from the lowest priority and set trigger setups.
         * In the end we'll have $popups split by trigger events with
         * highest prioritized popup setup available for each trigger event
         */
        foreach ( $setup as $scopeSetup ) {
            $onEntrance = Util::getItem( $scopeSetup, 'entrance' );

            if ( $onEntrance ) {
                $popups[ 'entrance' ] = $onEntrance;
            }

            $onExit = Util::getItem( $scopeSetup, 'exit' );

            if ( $onExit ) {
                $popups[ 'exit' ] = $onExit;
            }

            $onScroll = Util::getItem( $scopeSetup, 'scroll' );

            if ( $onScroll ) {
                $popups[ 'scroll' ] = $onScroll;
            }
        }

        /**
         * Rearrange $popups by popup id
         */
        foreach ( $popups as $trigger => $triggerSetup ) {
            if ( $triggerSetup ) {
                $triggerSetup->trigger = $trigger;
                self::$activePopupsOnThisPage[] = $triggerSetup;
            }
        }

        return self::$activePopupsOnThisPage;
    }

    /**
     * Forces the wpd-bb-popup CPT to use a specific template that removes
     * all unnecessary page elements, such as page header, footer & sidebar
     *
     * @since 1.0
     *
     * @param $single
     *
     * @return string Template path
     */
    public static function setTemplateForPopupCpt( $single )
    {
        global $post;
        $path = Plugin::path( self::POPUP_CPT_TEMPLATE_PATH );

        if ( $post->post_type == self::CUSTOM_POST_TYPE_POPUP ) {
            if ( file_exists( $path ) ) {
                return $path;
            }
        }

        return $single;
    }

    /**
     * Removes unnecessary meta boxes from Popup CPT
     *
     * @since 1.0
     * @return void
     */
    public static function removePageAttributesMetaBoxOnPopupCpt()
    {
        remove_meta_box( 'pageparentdiv', self::CUSTOM_POST_TYPE_POPUP, 'side' );
    }
}