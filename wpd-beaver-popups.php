<?php

/**
 * Beaver Popups entry point file
 *
 * @package         WPD\BeaverPopups
 * @author          smarterdigitalltd
 * @license         GPL-2.0+
 * @link            https://smarter.uk.com
 *
 * Plugin Name:     WPD Beaver Popups
 * Plugin URI:      https://beaverpopups.com
 * Description:     Take full control over your popups. Design and build with Beaver Builder
 * Version:         1.0.3
 * Author:          Doug Belchamber
 * Author URI:      https://wpdevelopers.co.uk
 * Text Domain:     beaverpopups
 * Requires WP:     4.7
 * Requires PHP:    5.4
 * Requires BB:     1.9.5
 */

namespace WPD\BeaverPopups;

use WPD\BeaverPopups\Helpers\RequirementsHelper;

/**
 * Require dependencies
 *
 * @since 1.0.0
 */
require_once __DIR__ . '/vendor/autoload.php';

/**
 * If this file is called directly, abort.
 *
 * @since 1.0.0
 */
if ( ! defined( 'WPINC' ) ) {
    die( 'No entry' );
}

/**
 * Define constants
 *
 * @since 1.0.0
 */
define( 'BEAVER_POPUPS_TEXT_DOMAIN', 'wpdbeaverpopups' );

/**
 * Plugin Init
 *
 * @since 1.0.0
 */
add_action( 'plugins_loaded', function() {
    // Load text domain
    load_plugin_textdomain( 'wpd', false, basename( dirname( __FILE__ ) ) . '/languages' );

    if ( ! RequirementsHelper::isCompatible() ) {
        RequirementsHelper::addAdminNotices();
        return;
    }

    Plugin::getInstance(__FILE__);
} );

/**
 * Run actions when plugin is activated
 *
 * @since 1.0.0
 *
 * @return void
 */
function registerHooks()
{
    register_activation_hook( __FILE__, __NAMESPACE__ . '\flushRewriteRules' );
    register_deactivation_hook( __FILE__, __NAMESPACE__ . '\flushRewriteRules' );
    register_uninstall_hook( __FILE__, __NAMESPACE__ . '\flushRewriteRules' );
}

/**
 * Flush rewrite rules
 *
 * @since 1.0.0
 *
 * @return void
 */
function flushRewriteRules()
{
    delete_option( 'rewrite_rules' );
}

registerHooks();