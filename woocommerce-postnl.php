<?php
/*
Plugin Name: WooCommerce PostNL
Plugin URI: https://postnl.nl/
Description: Export your WooCommerce orders to PostNL (https://postnl.nl/) and print labels directly from the WooCommerce admin
Author: Richard Perdaan
Version: 4.0.0
Text Domain: woocommerce-postnl

License: GPLv3 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (! class_exists('WCPN')) :

    class WCPN
    {
        /**
         * Translations domain
         */
        const DOMAIN                  = 'woocommerce-postnl';
        const NONCE_ACTION            = 'wc_postnl';
        const MINIMUM_PHP_VERSION_5_4 = '5.4';
        const PHP_VERSION_7_1         = '7.1';

        public $version = '4.0.0';

        public $plugin_basename;

        protected static $_instance = null;

        /**
         * @var WPO\WC\PostNL\Collections\SettingsCollection
         */
        public $setting_collection;

        /**
         * @var string
         */
        public $includes;

        /**
         * @var WCPN_Export
         */
        public $export;

        /**
         * @var WCPN_Admin
         */
        public $admin;

        /**
         * Main Plugin Instance
         * Ensures only one instance of plugin is loaded or can be loaded.
         */
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        /**
         * Constructor
         */
        public function __construct()
        {
            $this->define('WC_POST_NL_VERSION', $this->version);
            $this->plugin_basename = plugin_basename(__FILE__);

            // load the localisation & classes
            add_action('plugins_loaded', [$this, 'translations']);
            add_action('init', [$this, 'load_classes'], 9999);

            // run lifecycle methods
            if (is_admin() && ! defined('DOING_AJAX')) {
                add_action('init', [$this, 'do_install']);
            }
        }

        /**
         * Define constant if not already set
         *
         * @param string      $name
         * @param string|bool $value
         */
        private function define($name, $value)
        {
            if (! defined($name)) {
                define($name, $value);
            }
        }

        /**
         * Load the translation / text-domain files
         * Note: the first-loaded translation file overrides any following ones if the same translation is present
         */
        public function translations()
        {
            $locale = apply_filters('plugin_locale', get_locale(), self::DOMAIN);
            $dir    = trailingslashit(WP_LANG_DIR);

            /**
             * Frontend/global Locale. Looks in:
             *        - WP_LANG_DIR/woocommerce-postnl/woocommerce-postnl-LOCALE.mo
             *        - WP_LANG_DIR/plugins/woocommerce-postnl-LOCALE.mo
             *        - woocommerce-postnl/languages/woocommerce-postnl-LOCALE.mo (which if not found falls back to:)
             *        - WP_LANG_DIR/plugins/woocommerce-postnl-LOCALE.mo
             */
            load_textdomain(
                self::DOMAIN,
                $dir . 'woocommerce-postnl/' . self::DOMAIN . '-' . $locale . '.mo'
            );
            load_textdomain(self::DOMAIN, $dir . 'plugins/' . self::DOMAIN . '-' . $locale . '.mo');
            load_plugin_textdomain(self::DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
        }

        /**
         * Load the main plugin classes and functions
         */
        public function includes()
        {
            $this->includes = $this->plugin_path() . '/includes';
            // Use minimum php version 7.1
            require_once($this->includes . "/vendor/autoload.php");

            // include compatibility classes
            require_once($this->includes . "/compatibility/abstract-wc-data-compatibility.php");
            require_once($this->includes . "/compatibility/class-wc-date-compatibility.php");
            require_once($this->includes . "/compatibility/class-wc-core-compatibility.php");
            require_once($this->includes . "/compatibility/class-wc-order-compatibility.php");
            require_once($this->includes . "/compatibility/class-wc-product-compatibility.php");
            require_once($this->includes . "/compatibility/class-ce-compatibility.php");
            require_once($this->includes . "/compatibility/class-wcpdf-compatibility.php");

            require_once($this->includes . "/class-WCPN-data.php");
            require_once($this->includes . "/collections/settings-collection.php");
            require_once($this->includes . "/entities/setting.php");
            require_once($this->includes . "/entities/settings-field-arguments.php");

            require_once($this->includes . "/class-WCPN-assets.php");
            require_once($this->includes . "/frontend/class-WCPN-cart-fees.php");
            require_once($this->includes . "/frontend/class-WCPN-frontend-track-trace.php");
            require_once($this->includes . "/frontend/class-WCPN-checkout.php");
            require_once($this->includes . "/frontend/class-WCPN-frontend.php");
            $this->admin = require_once($this->includes . "/admin/class-WCPN-admin.php");
            require_once($this->includes . "/admin/settings/class-WCPN-settings.php");
            require_once($this->includes . "/class-WCPN-log.php");
            require_once($this->includes . "/admin/class-WCPN-country-codes.php");
            $this->export = require_once($this->includes . "/admin/class-WCPN-export.php");
            require_once($this->includes . "/class-WCPN-postcode-fields.php");
            require_once($this->includes . "/adapter/delivery-options-from-order-adapter.php");
            require_once($this->includes . "/adapter/shipment-options-from-order-adapter.php");
            require_once($this->includes . "/admin/class-WCPN-export-consignments.php");
        }

        /**
         * Instantiate classes when WooCommerce is activated
         */
        public function load_classes()
        {
            if ($this->is_woocommerce_activated() === false) {
                add_action('admin_notices', [$this, 'need_woocommerce']);

                return;
            }

            if (! $this->phpVersionMeets(self::MINIMUM_PHP_VERSION_5_4)) {
                add_action('admin_notices', [$this, 'required_php_version']);

                return;
            }

            if (! $this->phpVersionMeets(\WCPN::PHP_VERSION_7_1)) {
                // php 5.6
                $this->initSettings();
                $this->includes();
            } else {
                // php 7.1
                $this->includes();
                $this->initSettings();
            }
        }

        /**
         * Check if woocommerce is activated
         */
        public function is_woocommerce_activated()
        {
            $blog_plugins = get_option('active_plugins', []);
            $site_plugins = get_site_option('active_sitewide_plugins', []);

            if (in_array('woocommerce/woocommerce.php', $blog_plugins)
                || isset($site_plugins['woocommerce/woocommerce.php'])) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * WooCommerce not active notice.
         */
        public function need_woocommerce()
        {
            $error = sprintf(
                __("WooCommerce PostNL requires %sWooCommerce%s to be installed & activated!",
                    "woocommerce-postnl"
                ),
                '<a href="http://wordpress.org/extend/plugins/woocommerce/">',
                '</a>'
            );

            $message = '<div class="error"><p>' . $error . '</p></div>';

            echo $message;
        }

        /**
         * PHP version requirement notice
         */

        public function required_php_version()
        {
            $error         = __("WooCommerce PostNL requires PHP 5.4 or higher (5.6 or later recommended).",
                "woocommerce-postnl"
            );
            $how_to_update = __("How to update your PHP version", "woocommerce-postnl");
            $message       = sprintf(
                '<div class="error"><p>%s</p><p><a href="%s">%s</a></p></div>',
                $error,
                'http://docs.wpovernight.com/general/how-to-update-your-php-version/',
                $how_to_update
            );

            echo $message;
        }

        /** Lifecycle methods *******************************************************
         * Because register_activation_hook only runs when the plugin is manually
         * activated by the user, we're checking the current version against the
         * version stored in the database
         ****************************************************************************/

        /**
         * Handles version checking
         */
        public function do_install()
        {
            $version_setting   = "woocommerce_postnl_version";
            $installed_version = get_option($version_setting);

            // installed version lower than plugin version?
            if (version_compare($installed_version, $this->version, '<')) {
                if (! $installed_version) {
                    $this->install();
                } else {
                    $this->upgrade($installed_version);
                }

                // new version number
                update_option($version_setting, $this->version);
            }
        }

        /**
         * Plugin install method. Perform any installation tasks here
         */
        protected function install()
        {
            // Pre 2.0.0
            if (! empty(get_option('wcpostnl_settings'))) {
                require_once('migration/WCPN-installation-migration-v2-0-0.php');
            }
            // todo: Pre 4.0.0?
        }

        /**
         * Plugin upgrade method. Perform any required upgrades here
         *
         * @param string $installed_version the currently installed ('old') version
         */
        protected function upgrade($installed_version)
        {
            if (version_compare($installed_version, '2.4.0-beta-4', '<')) {
                require_once('migration/WCPN-upgrade-migration-v2-4-0-beta-4.php');
            }

            if (version_compare($installed_version, '3.0.4', '<=')) {
                require_once('migration/WCPN-upgrade-migration-v3-0-4.php');
            }

            if ($this->phpVersionMeets(\WCPN::PHP_VERSION_7_1)) {
                // Import the migration class base
                require_once('migration/WCPN-upgrade-migration.php');

                // Migrate php 7.1+ only version settings
                if (version_compare($installed_version, '4.0.0', '<=')) {
                    require_once('migration/WCPN-upgrade-migration-v4-0-0.php');
                }
            }
        }

        /**
         * Get the plugin url.
         *
         * @return string
         */
        public function plugin_url()
        {
            return untrailingslashit(plugins_url('/', __FILE__));
        }

        /**
         * Get the plugin path.
         *
         * @return string
         */
        public function plugin_path()
        {
            return untrailingslashit(plugin_dir_path(__FILE__));
        }

        /**
         * Initialize the settings.
         * Legacy: Before PHP 7.1, use old settings structure.
         */
        public function initSettings()
        {
            if (! $this->phpVersionMeets(\WCPN::PHP_VERSION_7_1)) {
                $this->general_settings  = get_option('woocommerce_postnl_general_settings');
                $this->export_defaults   = get_option('woocommerce_postnl_export_defaults_settings');
                $this->checkout_settings = get_option('woocommerce_postnl_checkout_settings');

                return;
            }

            // Create the settings collection by importing this function, because we can't use the sdk
            // imports in the legacy version.
            require_once('includes/WCPN-initialize-settings-collection.php');
            if (empty($this->setting_collection)) {
                $this->setting_collection = (new WCPN_Initialize_Settings_Collection())->initialize();
            }
        }

        /**
         * @param string $version
         *
         * @return bool
         */
        private function phpVersionMeets($version)
        {
            return version_compare(PHP_VERSION, $version, '>=');
        }
    }

endif;

/**
 * Returns the main instance of the plugin class to prevent the need to use globals.
 *
 * @return WCPN
 * @since  2.0
 */
function WCPN()
{
    return WCPN::instance();
}

/**
 * For PHP < 7.1 support.
 *
 * @return WCPN
 */
function WooCommerce_PostNL()
{
    return WCPN();
}

WCPN(); // load plugin
