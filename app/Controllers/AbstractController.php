<?php

/**
 * Abstract controller
 *
 * @package     WPD\BeaverPopups\Controllers
 * @since       1.0.0
 * @author      smarterdigitalltd
 * @link        https://www.smarter.uk.com
 * @license     GNU-2.0+
 */

namespace WPD\BeaverPopups\Controllers;

use WPD\BeaverPopups\Helpers\InputHelper;
use WPD\BeaverPopups\Helpers\JsonHelper;

/**
 * Class Api is a simple backend handler
 *
 * @package WPD\WPPluginAnalytics
 */
abstract class AbstractController {

    /**
     * Flag that denotes that controller serves REST requests
     *
     * @since   1.0.0
     *
     * @var     bool
     */
    protected $isRest = false;

    /**
     * Api constructor.
     * Adds hook that passes http request to a static public method of this class.
     *
     * E.g. /api/beaver-popups/get-stats/foo/bar
     * calls Api::getStats('foo', 'bar')
     *
     * @since   1.0.0
     *
     * @param   string $prefix API endpoint prefix
     * @param   bool $isRest Handle REST requests or not
     *
     * @return  void
     */
    public function __construct( $prefix, $isRest = false )
    {
        $this->isRest = $isRest;

        if ( $prefix ) {
            /**
             * Adding hook on parse_request to handle backend calls.
             */
            add_action( 'parse_request', function () use ( $prefix ) {

                $uri = preg_replace( '/\?.*$/', '', $_SERVER[ 'REQUEST_URI' ] );
                $prefixLength = strlen( $prefix );

                /**
                 * When site is located in a sub-folder, say /blog/ we need to strip it down.
                 */
                $siteUrl = get_site_url();
                $sitePrefix = preg_replace( '/http[s]?\:\/\/[^\/]*/', '', $siteUrl );
                if ( $sitePrefix && strpos( $uri, $sitePrefix ) === 0 ) {
                    $uri = substr( $uri, strlen( $sitePrefix ) );
                }

                /**
                 * Requested uri should start with provided prefix.
                 * Prefix should end with slash (e.g. '/api/surge/')
                 */
                if ( $prefix === substr( $uri, 0, $prefixLength ) || untrailingslashit( $prefix ) === $uri ) {
                    $uri = substr( $uri, $prefixLength );
                    $params = explode( '/', $uri );
                    foreach ( $params as $i => $param ) {
                        $params[ $i ] = urldecode( $param );
                    }

                    /**
                     * Capture filtered input into $this->input
                     */
                    InputHelper::captureInput();

                    /**
                     * First element is callable method name
                     */
                    $method = array_shift( $params );

                    /**
                     * Camel-casing possible dashes in method name
                     */
                    $method = preg_replace_callback( '/-(\w)/', function ( $m ) {
                        return strtoupper( $m[ 1 ] );
                    }, $method );

                    /**
                     * If method not set or not exists, and REST is enable than route to REST methods
                     */
                    if ( ( ! $method || ! method_exists( $this, $method ) ) && $this->isRest ) {
                        if ( $method ) {
                            array_unshift( $params, $method );
                        }
                        $method = strtoupper( $_SERVER[ 'REQUEST_METHOD' ] );
                        if ( $method === 'GET' && ! count( $params ) ) {
                            $method = 'QUERY';
                        }
                    }

                    if ( $method ) {
                        /**
                         * Calling common initializer.
                         */
                        $this->init();

                        /**
                         * Calling public method with params that left
                         */
                        call_user_func_array( [ $this, $method ], $params );

                        /**
                         * We don't want any WP logic to run after this API call.
                         */
                        die();
                    }
                    else {
                        if ( ! headers_sent() ) {
                            header( $_SERVER[ 'SERVER_PROTOCOL' ] . ' 404 Not Found', true, 404 );
                        }
                        die( 'Method not found' );
                    }
                }
            } );
        }
    }

    /**
     * Define input params that are allowed to contain HTML
     *
     * @since   1.0.0
     *
     * @param   string /array $keys 'post_content, comment_content' or
     *                 ['post_content', 'comment_content']
     *
     * @return  void
     */
    protected function allowHtml( $keys )
    {
        InputHelper::permitHtml( $keys );
    }

    /**
     * Get request param
     *
     * @since   1.0.0
     *
     * @param   string $param Parameter from request
     * @param   string $defaultValue Value to return if param not found
     *
     * @return  string Filtered/sanitized param
     */
    protected function getParam( $param, $defaultValue = '' )
    {
        return InputHelper::getParam( $param, $defaultValue );
    }

    /**
     * Respond payload wrapped into {payload, code, message} envelope.
     *
     * @since   1.0.0
     *
     * @param   mixed $payload Payload of request
     * @param   int $code Response code
     * @param   string $message Response message
     *
     * @return  void
     */
    protected function respond( $payload, $code = 0, $message = '' )
    {
        JsonHelper::respond( $payload, $code, $message );
    }

    /**
     * Respond success
     *
     * @since   1.0.0
     *
     * @param   string $message Response message
     * @param   null $payload Payload
     *
     * @return  void
     */
    protected function respondSuccess( $message, $payload = null )
    {
        $this->respond( $payload, 0, $message );
    }

    /**
     * Respond error
     *
     * @since   1.0.0
     *
     * @param   string $message The plugin message
     * @param   int $code
     * @param   null $payload
     *
     * @return  void
     */
    protected function respondError( $message, $code = 1, $payload = null )
    {
        $this->respond( $payload, $code, $message );
    }

    /**
     * Little helper to check user permissions
     *
     * @since   1.0.0
     *
     * @param   string $capability User cap to check
     * @param   string $message Error message
     *
     * @return  void
     */
    protected function checkPermission( $capability = 'administrator', $message = 'Not enough permissions to access this endpoint' )
    {
        if ( ! current_user_can( $capability ) ) {
            $this->respondError( $message, 'permissions' );
        }
    }

    /**
     * Init function that will be called on any API call.
     *
     * @since   1.0.0
     *
     * @return  void
     */
    protected function init()
    {
    }

    /**
     * Method called when no id param set. (no entity id set)
     *
     * @since   1.0.0
     *
     * @return  void
     */
    protected function QUERY()
    {
        $this->respondError( "Need to implement method. This method should return all queried entities" );
    }

    /**
     * Method called on POST request (insert)
     *
     * @since   1.0.0
     *
     * @return  void
     */
    protected function POST()
    {
        $this->respondError( "Need to implement method. This method should create new entity." );
    }

    /**
     * Method called on GET request when id param set (get single entity by id)
     *
     * @since   1.0.0
     *
     * @param   $id
     *
     * @return  void
     */
    protected function GET( $id )
    {
        $this->respondError( "Need to implement method. This method should return entity #{$id}." );
    }

    /**
     * Method called on PUT request (update)
     *
     * @since   1.0.0
     *
     * @param   $id
     *
     * @return  void
     */
    protected function PUT( $id )
    {
        $this->respondError( "Need to implement method. This method should update entity #{$id}." );
    }

    /**
     * Method called on PATCH request (partial update)
     *
     * @since   1.0.0
     *
     * @param   $id
     *
     * @return  void
     */
    protected function PATCH( $id )
    {
        $this->respondError( "Need to implement method. This method should patch entity #{$id}." );
    }

    /**
     * Method called on DELETE request (update)
     *
     * @since   1.0.0
     *
     * @param   $id
     *
     * @return  void
     */
    protected function DELETE( $id )
    {
        $this->respondError( "Need to implement method. This method should delete entity #{$id}." );
    }
}
