<?php

/**
 * Popup API controller
 *
 * @package     WPD\BeaverPopups\Controllers
 * @since       1.0.0
 * @author      smarterdigitalltd
 * @link        https://www.smarter.uk.com
 * @license     GNU-2.0+
 */

namespace WPD\BeaverPopups\Controllers;

use WPD\BeaverPopups\Helpers\PopupHelper;

/**
 * Class PopupsController is responsible for CRUD operations with Beaver Popups dashboard
 *
 * @package WPD\BeaverPopups\Controllers
 */
class PopupsController extends AbstractController {

    /**
     * Constructor
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public function __construct()
    {
        parent::__construct( '/api/wpd/beaver-popups/', false );
    }

    /**
     * Controller init performs permission check on all actions
     */
    public function init()
    {
        $this->checkPermission();
    }

    /**
     * Load list of available popups
     */
    public function loadList()
    {
        $query = new \WP_Query( [
            'post_type' => PopupHelper::CUSTOM_POST_TYPE_POPUP,
            'posts_per_page' => -1,
        ] );

        $posts = $query->get_posts();

        $this->respond( array_map( function ( $post ) {
            /**
             * @var $post \WP_Post
             */
            return [
                'id' => $post->ID,
                'name' => $post->post_name,
                'title' => $post->post_title
            ];
        }, $posts ) );
    }

    /**
     * Load site setup
     */
    public function loadSiteSetup()
    {
        $setup = PopupHelper::getSitePopups();
        $this->respond( $setup );
    }

    /**
     * Load custom post type setup
     */
    public function loadCustomPostTypesSetup()
    {
        $setup = PopupHelper::getCustomPostTypePopups();
        $this->respond( $setup );
    }

    /**
     * Load individual post setup
     */
    public function loadIndividualPostsSetup()
    {
        $setup = PopupHelper::getIndividualPostsPopups();
        $this->respond( $setup ? $setup : (object)[] );
    }

    /**
     * Setup popup trigger for specified scope
     */
    public function setupPopupTrigger()
    {
        $scope = $this->getParam( 'scope', 'site' );
        $subject = $this->getParam( 'subject', '' );
        $trigger = $this->getParam( 'trigger', 'entrance' );
        $popupId = $this->getParam( 'id', 0 );
        $setup = $this->getParam( 'setup', null );
        $payload = null;
        switch ( $scope ) {
            case 'cpt':
                $payload = PopupHelper::setCustomPostTypePopup( $popupId, $subject, $trigger, $setup );
                break;
            case 'site':
                $payload = PopupHelper::setSitePopup( $popupId, $subject, $trigger, $setup );
                break;
            case 'post':
                $payload = PopupHelper::setIndividualPostPopup( $popupId, $subject, $trigger, $setup );
                break;
        }

        $this->respond( $payload );
    }

    /**
     * @param $postId
     */
    public function addIndividualPost( $postId )
    {
        PopupHelper::addIndividualPostSetup( $postId );
        $post = get_post( $postId );
        $this->respond( PopupHelper::packIndividualPost( $post ) );
    }

    /**
     * @param $postId
     */
    public function removeIndividualPost( $postId )
    {
        PopupHelper::removeIndividualPostSetup( $postId );
        $this->respond( null );
    }

    /**
     * Searching posts for drop-down selector
     */
    public function searchPosts()
    {
        $search = $this->getParam( 'q', '' );
        $limit = $this->getParam( 'limit', 20 );
        $offset = $this->getParam( 'offset', 0 );

        $query = new \WP_Query( [
            's' => $search,
            'post_type' => 'any',
            'posts_per_page' => $limit,
            'offset' => $offset,
            'orderby' => 'relevance'
        ] );

        $posts = $query->get_posts();

        $this->respond( array_map( function ( $post ) {
            /**
             * @var $post \WP_Post
             */
            return [
                'id' => $post->ID,
                'name' => $post->post_name,
                'title' => $post->post_title
            ];
        }, $posts ) );
    }

    /**
     * Remove popup bindings
     *
     * @param $id
     */
    public function removeBindings( $id )
    {
        PopupHelper::removePopupSetup( $id );
        $this->respond( [
            'site' => PopupHelper::getSitePopups( true ),
            'cpt' => PopupHelper::getCustomPostTypePopups( true ),
            'posts' => PopupHelper::getIndividualPostsPopups()
        ] );
    }

}
