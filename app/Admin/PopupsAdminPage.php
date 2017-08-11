<?php

/**
 * Render main dashboard page
 *
 * @package     WPD\BeaverPopups\Admin
 * @since       1.0.0
 * @author      smarterdigitalltd
 * @link        https://www.smarter.uk.com
 * @license     GNU-2.0+
 */

namespace WPD\BeaverPopups\Admin;

use WPD\BeaverPopups\Helpers\AssetHelper;

/**
 * Class PopupsAdminPage is responsible for rendering of WPD Experiments WP Dashboard page
 *
 * @package WPD\BeaverPopups\Admin
 */
class PopupsAdminPage extends AbstractAdminSubPage
{
    /**
     * Page slug
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $parentSlug = 'edit.php?post_type=wpd-bb-popup';

    /**
     * Admin page slug that is a part of it's url
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $pageSlug = 'beaver-popups-manager';

    /**
     * Admin page title
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $pageTitle = 'Beaver Popups Manager';

    /**
     * Admin page menu title
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $menuTitle = 'Popup Manager';

    /**
     * Admin page menu icon url or dashicons alias
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $menuIcon = 'dashicons-forms';

    /**
     * Admin page menu position
     *
     * @since   1.0.0
     *
     * @var     int
     */
    protected $menuPosition = 50;

    /**
     * User capability required to access page
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $requiredUserCapability = 'manage_options';

    /**
     * Render page
     *
     * @since   1.0.0
     *
     * @param   array $options Array of options to display
     * @param   string $message Message to display
     * @param   bool $isError Whether the message is an error or not
     *
     * @return  void
     */
    protected function renderPage($options, $message, $isError)
    {
        wp_enqueue_script( 'beaver-popups-manager', AssetHelper::getHashedAssetUri( 'js/build.js' ) );
        ?>
        <script>
            window.WPD = {
                siteUrl: '<?php echo get_site_url();?>'
            };
        </script>

        <div id="beaver-popups-manager-app"></div>
        <?php
    }
}