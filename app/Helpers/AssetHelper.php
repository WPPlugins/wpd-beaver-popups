<?php

/**
 * Asset helper methods
 *
 * @package     WPD\BeaverPopups\Helpers
 * @since       1.0.0
 * @author      smarterdigitalltd
 * @link        https://www.smarter.uk.com
 * @license     GNU-2.0+
 */

namespace WPD\BeaverPopups\Helpers;

use WPD\BeaverPopups\Plugin;

/**
 * Class AssetHelper contains a set of handy methods for handling assets
 *
 * @package WPD\BeaverPopups\Helpers
 */
class AssetHelper {

    public static $manifest;
    public static $distUri;

    public static function init( $manifestPath = null, $distUri = null )
    {
        $manifestPath = isset( $manifestPath ) ? $manifestPath : Plugin::assetDistDir( 'manifest.json' );
        $distUri = isset( $distUri ) ? $distUri : Plugin::assetDistUri();
        self::$manifest = file_exists( $manifestPath ) ? json_decode( file_get_contents( $manifestPath ), true ) : [];
        self::$distUri = $distUri;
    }

    public static function getAssetFromManifest( $asset )
    {
        return isset( self::$manifest[ $asset ] ) ? self::$manifest[ $asset ] : null;
    }

    public static function getHashedAssetUri( $asset )
    {
        $assetHandle = strpos( $asset, '/' ) ? explode( '/', $asset )[1] : $asset;
        $distPath = self::$distUri;
        $assetPath = ! is_null( self::getAssetFromManifest( $assetHandle ) ) ? self::getAssetFromManifest( $assetHandle ) : $asset;

        return $distPath . $assetPath;
    }
}

AssetHelper::init();