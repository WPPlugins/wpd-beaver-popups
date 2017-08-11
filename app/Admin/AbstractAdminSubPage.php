<?php

/**
 * Admin sub page abstract class
 *
 * @package     WPD\BeaverPopups\Admin
 * @since       1.0.0
 * @author      smarterdigitalltd
 * @link        https://www.smarter.uk.com
 * @license     GNU-2.0+
 */

namespace WPD\BeaverPopups\Admin;

/**
 * Class AbstractAdminSubPage is responsible for admin dashboard page functionality
 * @package WPD\BeaverPopups
 */
abstract class AbstractAdminSubPage extends AbstractAdminPage
{
    /**
     * Parent admin page slug
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $parentSlug = 'parent';

    /**
     * Register sub page
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public function register()
    {
        add_submenu_page(
            $this->parentSlug,
            $this->pageTitle,
            $this->menuTitle,
            $this->requiredUserCapability,
            $this->pageSlug,
            [$this, 'render']
        );
    }
}
