<?php

/**
 * Plugin bootstrap file
 *
 * @package     WPD\BeaverPopups\Plugin
 * @since       1.0.0
 * @author      smarterdigitalltd
 * @link        https://www.smarter.uk.com
 * @license     GNU-2.0+
 */

namespace WPD\BeaverPopups;

use WPD\BeaverPopups\Helpers\BeaverBuilderHelper;
use WPD\BeaverPopups\Helpers\PopupHelper;
use WPD\BeaverPopups\Integrations\PowerpackIntegration;
use WPD\BeaverPopups\Integrations\UABBIntegration;
use WPD\BeaverPopups\Integrations\WPDBBAdditionsIntegration;

class Plugin {

    /**
     * Plugin version
     *
     * @since   1.0.0
     */
    const VERSION = '1.0.0';

    /**
     * Plugin name extracted from the path
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected static $name = '';

    /**
     * Base plugin root dir
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected static $rootDir = '';

    /**
     * Main plugin file
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected static $rootFile = '';

    /**
     * Singleton instance
     *
     * @since   1.0.0
     *
     * @var     self null
     */
    protected static $instance = null;

    /**
     * Plugin constructor.
     *
     * @since   1.0.0
     *
     * @param   $rootFile The entry point file
     *
     * @return  void
     */
    public function __construct( $rootFile )
    {
        self::$rootFile = $rootFile;
        self::$rootDir = realpath( dirname( $rootFile ) );
        self::$name = plugin_basename( $rootFile );

        $this->registerResources();
        $this->registerHooks();

        new Admin\PopupsAdminPage();

        new Controllers\PopupsController();
    }

    /**
     * Get singleton instance
     *
     * @param   string $rootFile The entry point file
     *
     * @return  Plugin Instance of the plugin
     */
    public static function getInstance( $rootFile = '' )
    {
        if ( ! self::$instance ) {
            self::$instance = new self( $rootFile );
        }

        return self::$instance;
    }

    /**
     * Get main plugin filename
     *
     * @since   1.0.0
     *
     * @return  string The filename
     */
    public static function filename()
    {
        return self::$rootFile;
    }

    /**
     * Returns root dir path
     *
     * @since   1.0.0
     *
     * @param   string $relPath Directory path to item, assuming the
     *                          root is the current plugin dir
     *
     * @return  string The complete directory path
     */
    public static function path( $relPath = '' )
    {
        return self::$rootDir . '/' . $relPath;
    }

    /**
     * Returns root dir url
     *
     * @since   1.0.0
     *
     * @param   string $relPath Directory path to item, assuming the
     *                          root is the current plugin dir
     *
     * @return  string The URL to the item
     */
    public static function url( $relPath = '' )
    {
        return plugins_url( $relPath, dirname( __FILE__ ) );
    }

    /**
     * Returns root dir of dist directory
     *
     * @since   1.0.0
     *
     * @param   string $path Directory path to item
     *
     * @return  string The path to a dist item
     */
    public static function assetDistDir( $path = null )
    {
        return self::path( 'res/dist/' . $path );
    }

    /**
     * Returns root dir of dist directory URL
     *
     * @since   1.0.0
     *
     * @param   string $path Directory path to item
     *
     * @return  string The URL to the dist path item
     */
    public static function assetDistUri( $path = null )
    {
        return self::url( 'res/dist/' . $path );
    }

    /**
     * Output notification in admin area
     *
     * @since   1.0.0
     *
     * @param   string $message The message text
     * @param   string $type Allowed types are 'info', 'warning', 'error'
     *
     * @return  void
     */
    public static function addAdminNotice( $message, $type = 'info' )
    {
        add_action( 'admin_notices', function () use ( $message, $type ) {
            ?>
            <div class="notice notice-<?php echo $type ?>">
                <p><?php echo $message; ?></p>
            </div>
            <?php
        } );
    }

    /**
     * Register scripts and styles
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public function registerResources()
    {

    }

    /**
     * Register filters and actions hooks
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public function registerHooks()
    {
        PopupHelper::registerHooks();
        BeaverBuilderHelper::registerHooks();
        PowerpackIntegration::registerHooks();
        UABBIntegration::registerHooks();
        WPDBBAdditionsIntegration::registerHooks();
    }
}