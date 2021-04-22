<?php

if (! defined("ABSPATH")) {
    exit;
} // Exit if accessed directly

if (class_exists("WCPN_Assets")) {
    return new WCPN_Assets();
}

class WCPN_Assets
{
    public function __construct()
    {
        add_action("admin_enqueue_scripts", [$this, "enqueueScripts"], 9999);
    }

    public function enqueueScripts(): void
    {
        global $post_type;
        $screen = get_current_screen();

        if ($post_type === "shop_order" || (is_object($screen) && strpos($screen->id, "wcpn") !== false)) {
            self::enqueue_admin_scripts_and_styles();
        }
    }

    /**
     * Load styles & scripts
     */
    public static function enqueue_admin_scripts_and_styles(): void
    {
        // WC2.3+ load all WC scripts for shipping_method search!
        if (version_compare(WOOCOMMERCE_VERSION, "2.3", ">=")) {
            wp_enqueue_script("woocommerce_admin");
            wp_enqueue_script("iris");

            if (! wp_script_is("wc-enhanced-select", "registered")) {
                $suffix = defined("SCRIPT_DEBUG") && SCRIPT_DEBUG ? "" : ".min";
                wp_register_script(
                    "wc-enhanced-select",
                    WC()->plugin_url() . "/assets/js/admin/wc-enhanced-select" . $suffix . ".js",
                    ["jquery", version_compare(WC()->version, "3.2.0", ">=") ? "selectWoo" : "select2"],
                    WC_VERSION
                );
            }
            wp_enqueue_script("wc-enhanced-select");
            wp_enqueue_script("jquery-ui-sortable");
            wp_enqueue_script("jquery-ui-autocomplete");
            wp_enqueue_style(
                "woocommerce_admin_styles",
                WC()->plugin_url() . "/assets/css/admin.css",
                [],
                WC_VERSION
            );
        }

        wp_enqueue_script("thickbox");
        wp_enqueue_style("thickbox");
        wp_enqueue_script(
            "wcpn-admin",
            WCPOST()->plugin_url() . "/assets/js/wcpn-admin.js",
            ["jquery", "thickbox"],
            WC_POSTNL_VERSION
        );

        wp_localize_script(
            "wcpn-admin",
            "wcpn",
            [
                "api_url"                => WCPN_Data::API_URL,
                "actions"                => [
                    "export"        => WCPN_Export::EXPORT,
                    "add_return"    => WCPN_Export::ADD_RETURN,
                    "add_shipments" => WCPN_Export::ADD_SHIPMENTS,
                    "get_labels"    => WCPN_Export::GET_LABELS,
                    "modal_dialog"  => WCPN_Export::MODAL_DIALOG,
                ],
                "bulk_actions"           => [
                    "export"       => WCPOST_Admin::BULK_ACTION_EXPORT,
                    "print"        => WCPOST_Admin::BULK_ACTION_PRINT,
                    "export_print" => WCPOST_Admin::BULK_ACTION_EXPORT_PRINT,
                ],
                "ajax_url"               => admin_url("admin-ajax.php"),
                "nonce"                  => wp_create_nonce(WCPOST::NONCE_ACTION),
                "download_display"       => WCPOST()->setting_collection->getByName(
                    WCPOST_Settings::SETTING_DOWNLOAD_DISPLAY
                ),
                "ask_for_print_position" => WCPOST()->setting_collection->isEnabled(
                    WCPOST_Settings::SETTING_ASK_FOR_PRINT_POSITION
                ),
                "strings"                => [
                    "no_orders_selected" => __("You have not selected any orders!", "woocommerce-postnl"),
                    "dialog" => [
                        "return" => __("Export options", "woocommerce-postnl")
                    ],
                ],
            ]
        );

        wp_enqueue_style(
            "wcpn-admin-styles",
            WCPOST()->plugin_url() . "/assets/css/wcpn-admin-styles.css",
            [],
            WC_POSTNL_VERSION,
            "all"
        );

        // Legacy styles (WC 2.1+ introduced MP6 style with larger buttons)
        if (version_compare(WOOCOMMERCE_VERSION, "2.1", "<=")) {
            wp_enqueue_style(
                "wcpn-admin-styles-legacy",
                WCPOST()->plugin_url() . "/assets/css/wcpn-admin-styles-legacy.css",
                [],
                WC_POSTNL_VERSION,
                "all"
            );
        }
    }
}

return new WCPN_Assets();
