<?php

/**
 * Admin page abstract class
 *
 * @package     WPD\BeaverPopups\Admin
 * @since       1.0.0
 * @author      smarterdigitalltd
 * @link        https://www.smarter.uk.com
 * @license     GNU-2.0+
 */

namespace WPD\BeaverPopups\Admin;

use WPD\BeaverPopups\Helpers\OptionsHelper;

/**
 * Class AbstractAdminPage is responsible for admin dashboard page functionality
 *
 * @package WPD\BeaverPopups
 */
abstract class AbstractAdminPage {

    /**
     * Admin page slug that is a part of it's url
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $pageSlug = 'PLUGIN_SLUG';

    /**
     * Admin page title
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $pageTitle = 'Plugin OptionsHelper';

    /**
     * Admin page menu title
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $menuTitle = 'Plugin OptionsHelper';

    /**
     * Admin page menu icon url or dashicons alias
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $menuIcon = 'dashicons-options';

    /**
     * Admin page menu position
     *
     * @since   1.0.0
     *
     * @var     int
     */
    protected $menuPosition = 80;

    /**
     * User capability required to access page
     *
     * @since   1.0.0
     *
     * @var     string
     */
    protected $requiredUserCapability = 'manage_options';
    /**
     * A list of default options
     *
     * @var array
     */
    protected $defaultOptions = [];

    /**
     * Constructor.
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public function __construct()
    {
        add_action( 'admin_menu', [ $this, 'register' ] );
    }

    /**
     * Register admin page with it's renderer
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public function register()
    {
        add_menu_page(
            $this->pageTitle,
            $this->menuTitle,
            $this->requiredUserCapability,
            $this->pageSlug,
            [ $this, 'render' ],
            $this->menuIcon,
            $this->menuPosition
        );
    }

    /**
     * Render dashboard page
     *
     * @since   1.0.0
     *
     * @return  void
     */
    public function render()
    {
        $isError = false;

        try {
            $message = $this->onSubmit();
        } catch ( \Exception $e ) {
            $message = $e->getMessage();
            $isError = true;
        }

        $options = [];

        foreach ( $this->getDefaultOptions() as $name => $defaultVal ) {
            $options[ $name ] = OptionsHelper::get( $name, $defaultVal );
        }

        $this->renderPage( $options, $message, $isError );
    }

    /**
     * Submit handler. Can be overridden
     *
     * @since   1.0.0
     *
     * @throws  \Exception
     *
     * @return  string Should return success message, or throw exception with error message.
     */
    protected function onSubmit()
    {
        $message = '';
        if ( isset( $_POST[ 'action' ] ) ) {
            switch ( $_POST[ 'action' ] ) {
                /**
                 * Save options
                 */
                case 'save':
                    foreach ( $this->getDefaultOptions() as $name => $defaultVal ) {
                        if ( isset( $_POST[ $name ] ) ) {
                            $value = trim( stripslashes( $_POST[ $name ] ) );
                            OptionsHelper::set( $name, $value ? $value : $defaultVal );
                        }
                    }

                    $message = 'Your settings were saved!';
                    break;
            }
        }

        return $message;
    }

    /**
     * Get a list of options with their default values
     *
     * @since   1.0.0
     *
     * @return  array Default options
     */
    protected function getDefaultOptions()
    {
        return $this->defaultOptions;
    }

    /**
     * Page render. This function should be overridden
     *
     * @todo    Perhaps add to interface
     *
     * @since   1.0.0
     *
     * @param   array $options Array of inputs to render
     * @param   string $message Message to display
     * @param   bool $isError Whether it is an error or not
     *
     * @return  void
     */
    protected function renderPage( $options, $message, $isError )
    {
        ?>
        <h1>Options</h1>
        <?php if ( $message ) : ?>
            <div class="message <?php echo $isError ? 'error' : '' ?>">
                <?php echo $message ?>
            </div>
        <?php endif; ?>
        <form action="">
            <input type="hidden" name="action" value="save"/>
            <?php foreach ( $options as $option => $value ) : ?>
                <label><?php echo $option; ?></label>
                <input type="text" name="<?php echo $option; ?>" value="<?php echo $value; ?>"
                       title="<?php echo $option; ?>"/><br/>
            <?php endforeach; ?>
            <button>Save</button>
        </form>
        <?php
    }
}
