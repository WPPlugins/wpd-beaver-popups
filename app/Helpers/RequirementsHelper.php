<?php

namespace WPD\BeaverPopups\Helpers;

use WPD\BeaverPopups\Plugin;

class RequirementsHelper
{

    const WP_MINIMUM_VERSION = '4.7';
    const BB_MINIMUM_VERSION = '1.9.5';
    const PHP_MINIMUM_VERSION = '5.4';

    /**
     * Check a specified version against the Beaver Builder plugin version
     *
     * @param  string $specified_minimum_version version string
     * @return bool Is the current plugin version a supported minimum
     */
    public static function specify_minimum_beaver_builder_plugin_version($specified_minimum_version)
    {
        if(version_compare(floatval(FL_BUILDER_VERSION), floatval($specified_minimum_version), '<')){
            return false;
        }

        return true;
    }

    /**
     * Add admin notices
     */
    public static function addAdminNotices()
    {
        // WP version notice
        if(version_compare(get_bloginfo('version'), self::WP_MINIMUM_VERSION, '<')){
            Plugin::addAdminNotice(wpautop(sprintf(__('WPD BB Popup Builder plugin requires at least WordPress 4.7+. You are running WordPress %s. Please upgrade and try again.', 'beaver-popups'), get_bloginfo('version'))), 'error');
        }

        // PHP version notice
        if(version_compare(PHP_VERSION, self::PHP_MINIMUM_VERSION, '<')){
            Plugin::addAdminNotice(wpautop(sprintf(__('WPD BB Popup Builder plugin requires at least PHP 5.3+. You are running PHP %s. Please upgrade and try again.', 'beaver-popups'), PHP_VERSION)), 'error');
        }

        // BeaverBuilder availability check
        if(!class_exists('FLBuilder')){
            // Beaver Builder not active
            Plugin::addAdminNotice(wpautop(__('WPD BB Popup Builder plugin requires <a href="https://www.wpbeaverbuilder.com/" target="_blank">Beaver Builder Plugin</a>. Please install and activate Beaver Builder Plugin to use Beaver Popups.', 'beaver-popups')), 'error');
        } else if(version_compare(FL_BUILDER_VERSION, self::BB_MINIMUM_VERSION, '<')){
            // BB Plugin Active
            Plugin::addAdminNotice(wpautop(sprintf(__('WPD BB Popup Builder plugin requires at least beaver Builder v.1.9.5+. You are running Beaver Builder %s. Please upgrade and try again.', 'beaver-popups'), FL_BUILDER_VERSION)), 'error');
        }
    }

    /**
     * Check if installation have min requirements.
     *
     * @return bool true if server have min requirement
     */
    public static function isCompatible()
    {
        $pass = true;

        // WP Version Check
        if(version_compare(get_bloginfo('version'), self::WP_MINIMUM_VERSION, '<')){
            $pass = false;
        }// PHP Check
        else if(version_compare(PHP_VERSION, self::PHP_MINIMUM_VERSION, '<')){
            $pass = false;
        }// Beaver Builder Check
        else if(!class_exists('FLBuilder') || class_exists('FLBuilder') && version_compare(FL_BUILDER_VERSION, self::BB_MINIMUM_VERSION, '<')){
            $pass = false;
        }

        return $pass;
    }
}