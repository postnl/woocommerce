<?php

use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use WPO\WC\PostNL\Entity\SettingsFieldArguments;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCPN_Settings_Data')) {
    return new WCPN_Settings_Data();
}

/**
 * This class contains all data for the admin settings screens created by the plugin.
 */
class WCPN_Settings_Data
{
    public const ENABLED  = "1";
    public const DISABLED = "0";

    public const DISPLAY_FOR_SELECTED_METHODS = "selected_methods";
    public const DISPLAY_FOR_ALL_METHODS      = "all_methods";


    /**
     * @var WCPN_Settings_Callbacks
     */
    private $callbacks;

    public function __construct()
    {
        $this->callbacks = require 'class-wcpn-settings-callbacks.php';

        // Create the PostNL settings with the admin_init hook.
        add_action("admin_init", [$this, "create_all_settings"]);
    }

    /**
     * Create all settings sections.
     */
    public function create_all_settings(): void
    {
        $this->generate_settings(
            $this->get_sections_general(),
            WCPN_Settings::SETTINGS_GENERAL
        );

        $this->generate_settings(
            $this->get_sections_export_defaults(),
            WCPN_Settings::SETTINGS_EXPORT_DEFAULTS
        );

        $this->generate_settings(
            $this->get_sections_checkout(),
            WCPN_Settings::SETTINGS_CHECKOUT
        );

        $this->generate_settings(
            $this->get_sections_carrier_postnl(),
            WCPN_Settings::SETTINGS_POSTNL,
            true
        );
    }

    public static function getTabs()
    {
        $array = [
            WCPN_Settings::SETTINGS_GENERAL         => __("General", "woocommerce-postnl"),
            WCPN_Settings::SETTINGS_EXPORT_DEFAULTS => __("Default export settings", "woocommerce-postnl"),
            WCPN_Settings::SETTINGS_CHECKOUT        => __("Checkout settings", "woocommerce-postnl"),
        ];

        $array[WCPN_Settings::SETTINGS_POSTNL] = __("PostNL", "woocommerce-postnl");

        return $array;
    }

    /**
     * Generate settings sections and fields by the given $settingsArray.
     *
     * @param array  $settingsArray - Array of settings to loop through.
     * @param string $optionName - Name to use in the identifier.
     * @param bool   $prefix - Add the key of the top level settings as prefix before every setting or not.
     */
    private function generate_settings(array $settingsArray, string $optionName, bool $prefix = false): void
    {
        $optionIdentifier = WCPN_Settings::getOptionId($optionName);
        $defaults         = [];

        // Register settings.
        register_setting($optionIdentifier, $optionIdentifier, [$this->callbacks, 'validate']);

        foreach ($settingsArray as $name => $array) {
            foreach ($array as $section) {
                $sectionName = "{$name}_{$section["name"]}";

                add_settings_section(
                    $sectionName,
                    $section["label"],
                    function() use ($section) {
                        // Allows a description to be shown with a section title.
                        /** @noinspection PhpVoidFunctionResultUsedInspection */
                        return $this->callbacks->renderSection($section);
                    },
                    $optionIdentifier
                );

                foreach ($section["settings"] as $setting) {
                    $setting["id"] = $prefix ? "{$name}_{$setting["name"]}" : $setting["name"];

                    // Add the prefix to the name in the condition array
                    if (isset($setting["condition"])) {
                        if (is_array($setting["condition"])) {
                            $related                      = $setting["condition"]["name"];
                            $related                      = $prefix ? "{$name}_{$related}" : $related;
                            $setting["condition"]["name"] = "{$optionIdentifier}[$related]";
                        } else {
                            $related              = $setting["condition"];
                            $related              = $prefix ? "{$name}_{$related}" : $related;
                            $setting["condition"] = "{$optionIdentifier}[$related]";
                        }
                    }

                    $class = new SettingsFieldArguments($setting);

                    // Add the setting's default value to the defaults array.
                    $defaults[$setting["id"]] = $class->getDefault();

                    $defaultCallback = function() use ($class, $optionIdentifier) {
                        $this->callbacks->renderField($class, $optionIdentifier);
                    };

                    $callback = $setting["callback"] ?? $defaultCallback;

                    add_settings_field(
                        $setting["id"],
                        $setting["label"],
                        $callback,
                        $optionIdentifier,
                        $sectionName,
                        // If a custom callback is used, send the $setting as arguments. Otherwise use the created
                        // arguments from the class.
                        isset($setting["callback"]) ? $setting : $class->getArguments()
                    );
                }
            }
        }

        // Create option in wp_options with default settings if the option doesn't exist yet.
        if (false === get_option($optionIdentifier)) {
            add_option($optionIdentifier, $defaults);
        }

        // Merge any missing values into the settings
        update_option(
            $optionIdentifier,
            array_replace_recursive(
                $defaults,
                get_option($optionIdentifier)
            )
        );
    }

    /**
     * @return array
     */
    private function get_sections_general()
    {
        return [
            WCPN_Settings::SETTINGS_GENERAL => [
                [
                    "name"     => "api",
                    "label"    => __("API settings", "woocommerce-postnl"),
                    "settings" => $this->get_section_general_api(),
                ],
                [
                    "name"     => "general",
                    "label"    => __("General settings", "woocommerce-postnl"),
                    "settings" => $this->get_section_general_general(),
                ],
                [
                    "name"     => "diagnostics",
                    "label"    => __("Diagnostic tools", "woocommerce-postnl"),
                    "settings" => $this->get_section_general_diagnostics(),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function get_sections_export_defaults()
    {
        return [
            WCPN_Settings::SETTINGS_EXPORT_DEFAULTS => [
                [
                    "name"     => "main",
                    "label"    => __("Default export settings", "woocommerce-postnl"),
                    "settings" => $this->get_section_export_defaults_main(),
                ],
            ],
        ];
    }

    private function get_sections_checkout()
    {
        return [
            WCPN_Settings::SETTINGS_CHECKOUT => [
                [
                    "name"     => "main",
                    "label"    => __("Checkout settings", "woocommerce-postnl"),
                    "settings" => $this->get_section_checkout_main(),
                ],
                [
                    "name"      => "strings",
                    "label"     => __("Titles", "woocommerce-postnl"),
                    "condition" => WCPN_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                    "settings"  => $this->get_section_checkout_strings(),
                ],
            ],
        ];
    }

    /**
     * Get the array of PostNL sections and their settings to be added to WordPress.
     *
     * @return array
     */
    private function get_sections_carrier_postnl()
    {
        return [
            PostNLConsignment::CARRIER_NAME => [
                [
                    "name"        => "export_defaults",
                    "label"       => __("Default export settings", "woocommerce-postnl"),
                    "description" => __(
                        "These settings will be applied to PostNL shipments you create in the backend.",
                        "woocommerce-postnl"
                    ),
                    "settings"    => $this->get_section_carrier_postnl_export_defaults(),
                ],
                [
                    "name"     => "delivery_options",
                    "label"    => __("PostNL delivery options", "woocommerce-postnl"),
                    "settings" => $this->get_section_carrier_postnl_delivery_options(),
                ],
                [
                    "name"     => "pickup_options",
                    "label"    => __("PostNL pickup options", "woocommerce-postnl"),
                    "settings" => $this->get_section_carrier_postnl_pickup_options(),
                ],
            ],
        ];
    }


    /**
     * @return array
     */
    private function get_section_general_api(): array
    {
        return [
            [
                "name"      => WCPN_Settings::SETTING_API_KEY,
                "label"     => __("Key", "woocommerce-postnl"),
                "help_text" => __("api key", "woocommerce-postnl"),
            ],
        ];
    }

    /**
     * @return array
     */
    private function get_section_general_general(): array
    {
        return [
            [
                "name"    => WCPN_Settings::SETTING_DOWNLOAD_DISPLAY,
                "label"   => __("Label display", "woocommerce-postnl"),
                "type"    => "select",
                "options" => [
                    "download" => __("Download PDF", "woocommerce-postnl"),
                    "display"  => __("Open the PDF in a new tab", "woocommerce-postnl"),
                ],
            ],
            [
                "name"    => WCPN_Settings::SETTING_LABEL_FORMAT,
                "label"   => __("Label format", "woocommerce-postnl"),
                "type"    => "select",
                "options" => [
                    "A4" => __("Standard printer (A4)", "woocommerce-postnl"),
                    "A6" => __("Label Printer (A6)", "woocommerce-postnl"),
                ],
            ],
            [
                "name"      => WCPN_Settings::SETTING_ASK_FOR_PRINT_POSITION,
                "label"     => __("Ask for print start position", "woocommerce-postnl"),
                "condition" => [
                    "name"         => WCPN_Settings::SETTING_LABEL_FORMAT,
                    "type"         => "disable",
                    "parent_value" => "A4",
                    "set_value"    => self::DISABLED,
                ],
                "type"      => "toggle",
                "help_text" => __(
                    "This option enables you to continue printing where you left off last time",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_TRACK_TRACE_EMAIL,
                "label"     => __("Track & Trace in email", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __(
                    "Add the Track & Trace code to emails to the customer.", "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_TRACK_TRACE_MY_ACCOUNT,
                "label"     => __("Track & Trace in My Account", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __("Show Track & Trace trace code and link in My Account.", "woocommerce-postnl"),
            ],
            [
                "name"      => WCPN_Settings::SETTING_PROCESS_DIRECTLY,
                "label"     => __("Process shipments directly", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __(
                    "When you enable this option, shipments will be directly processed when sent to PostNL.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_ORDER_STATUS_AUTOMATION,
                "label"     => __("Order status automation", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __(
                    "Automatically set order status to a predefined status after successful PostNL export.<br/>Make sure <strong>Process shipments directly</strong> is enabled when you use this option together with the <strong>Track & Trace in email</strong> option, otherwise the Track & Trace code will not be included in the customer email.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_AUTOMATIC_ORDER_STATUS,
                "condition" => WCPN_Settings::SETTING_ORDER_STATUS_AUTOMATION,
                "class"     => ["wcpn__child"],
                "label"     => __("Automatic order status", "woocommerce-postnl"),
                "type"      => "select",
                "options"   => $this->callbacks->get_order_status_options(),
            ],
            [
                "name"      => WCPN_Settings::SETTING_BARCODE_IN_NOTE,
                "label"     => __("Place barcode inside note", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __("Place the barcode inside a note of the order", "woocommerce-postnl"),
            ],
            [
                "name"      => WCPN_Settings::SETTING_BARCODE_IN_NOTE_TITLE,
                "condition" => WCPN_Settings::SETTING_BARCODE_IN_NOTE,
                "class"     => ["wcpn__child"],
                "label"     => __("Title before the barcode", "woocommerce-postnl"),
                "default"   => __("Track & trace code:", "woocommerce-postnl"),
                "help_text" => __(
                    "You can change the text before the barcode inside an note",
                    "woocommerce-postnl"
                ),
            ],
        ];
    }

    /**
     * @return array
     */
    private function get_section_general_diagnostics(): array
    {
        return [
            [
                "name"        => WCPN_Settings::SETTING_ERROR_LOGGING,
                "label"       => __("Log API communication", "woocommerce-postnl"),
                "type"        => "toggle",
                "description" => '<a href="' . esc_url_raw(
                        admin_url("admin.php?page=wc-status&tab=logs")
                    ) . '" target="_blank">' . __("View logs", "woocommerce-postnl") . "</a> (wc-postnl)",
            ],
        ];
    }

    /**
     * Export defaults specifically for postnl.
     *
     * @return array
     */
    private function get_section_carrier_postnl_export_defaults(): array
    {
        return [
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT,
                "label"     => __("Home address only", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __(
                    "If you don't want the parcel to be delivered at the neighbours, choose this option.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE,
                "label"     => __("Signature on delivery", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __(
                    "The parcel will be offered at the delivery address. If the recipient is not at home, the parcel will be delivered to the neighbours. In both cases, a signature will be required.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_DEFAULT_EXPORT_AGE_CHECK,
                "label"     => __("Age check 18+", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __(
                    "The age check is intended for parcel shipments for which the recipient must show 18+ by means of a proof of identity. With this shipping option Signature for receipt and Delivery only at recipient are included. The age 18+ is further excluded from the delivery options morning and evening delivery.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_DEFAULT_EXPORT_RETURN,
                "label"     => __("Return if no answer", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __(
                    "By default, a parcel will be offered twice. After two unsuccessful delivery attempts, the parcel will be available at the nearest pickup point for two weeks. There it can be picked up by the recipient with the note that was left by the courier. If you want to receive the parcel back directly and NOT forward it to the pickup point, enable this option.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
                "label"     => __("Insured shipment", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __(
                    "By default, there is no insurance on the shipments. If you still want to insure the shipment, you can do that. We insure the purchase value of the shipment, with a maximum insured value of € 5.000. Insured parcels always contain the options 'Home address only' en 'Signature for delivery'",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT,
                "condition" => WCPN_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED,
                "label"     => __("Insured amount", "woocommerce-postnl"),
                "type"      => "select",
                "options"   => WCPN_Data::getInsuranceAmount(),
            ],
        ];
    }

    /**
     * These are the unprefixed settings for postnl.
     * After the settings are generated every name will be prefixed with "postnl_"
     * Example: delivery_enabled => postnl_delivery_enabled
     *
     * @return array
     */
    private function get_section_carrier_postnl_delivery_options(): array
    {
        return [
            [
                "name"  => WCPN_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label" => __("Enable PostNL delivery", "woocommerce-postnl"),
                "type"  => "toggle",
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_DROP_OFF_DAYS,
                "condition" => WCPN_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Drop-off days", "woocommerce-postnl"),
                "callback"  => [$this->callbacks, "enhanced_select"],
                "options"   => $this->getWeekdays(),
                "default"   => [1, 2, 3, 4, 5],
                "help_text" => __("Days of the week on which you hand over parcels to PostNL", "woocommerce-postnl"),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_CUTOFF_TIME,
                "condition" => WCPN_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Cut-off time", "woocommerce-postnl"),
                "help_text" => __(
                    "Time at which you stop processing orders for the day (format: hh:mm)",
                    "woocommerce-postnl"
                ),
                "default"   => "17:00",
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_DROP_OFF_DELAY,
                "condition" => WCPN_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Drop-off delay", "woocommerce-postnl"),
                "type"      => "number",
                "max"       => 14,
                "help_text" => __("Number of days you need to process an order.", "woocommerce-postnl"),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW,
                "condition" => WCPN_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Show delivery date", "woocommerce-postnl"),
                "type"      => "number",
                "max"       => 14,
                "default"   => self::ENABLED,
                "help_text" => __("Show the delivery date inside the delivery options.", "woocommerce-postnl"),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_DELIVERY_MORNING_ENABLED,
                "condition" => WCPN_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Morning delivery", "woocommerce-postnl"),
                "type"      => "toggle",
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_DELIVERY_MORNING_FEE,
                "condition" => WCPN_Settings::SETTING_CARRIER_DELIVERY_MORNING_ENABLED,
                "class"     => ["wcpn__child"],
                "label"     => __("Fee (optional)", "woocommerce-postnl"),
                "type"      => "currency",
                "help_text" => __(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_DELIVERY_EVENING_ENABLED,
                "condition" => WCPN_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Evening delivery", "woocommerce-postnl"),
                "type"      => "toggle",
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_DELIVERY_EVENING_FEE,
                "condition" => WCPN_Settings::SETTING_CARRIER_DELIVERY_EVENING_ENABLED,
                "class"     => ["wcpn__child"],
                "label"     => __("Fee (optional)", "woocommerce-postnl"),
                "type"      => "currency",
                "help_text" => __(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_ONLY_RECIPIENT_ENABLED,
                "condition" => WCPN_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Home address only", "woocommerce-postnl"),
                "type"      => "toggle",
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_ONLY_RECIPIENT_FEE,
                "condition" => WCPN_Settings::SETTING_CARRIER_ONLY_RECIPIENT_ENABLED,
                "class"     => ["wcpn__child"],
                "label"     => __("Fee (optional)", "woocommerce-postnl"),
                "type"      => "currency",
                "help_text" => __(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
                "condition" => WCPN_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Signature on delivery", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_SIGNATURE_FEE,
                "condition" => WCPN_Settings::SETTING_CARRIER_SIGNATURE_ENABLED,
                "class"     => ["wcpn__child"],
                "label"     => __("Fee (optional)", "woocommerce-postnl"),
                "type"      => "currency",
                "help_text" => __(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_MONDAY_DELIVERY_ENABLED,
                "condition" => WCPN_Settings::SETTING_CARRIER_DELIVERY_ENABLED,
                "label"     => __("Monday delivery", "woocommerce-postnl"),
                "type"      => "toggle",
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_MONDAY_CUTOFF_TIME,
                "condition" => WCPN_Settings::SETTING_CARRIER_MONDAY_DELIVERY_ENABLED,
                "class"     => ["wcpn__child"],
                "label"     => __("Fee (optional)", "woocommerce-postnl"),
                "placeholder" => "14:30",
                "default"     => "15:00",
                "help_text" => __(
                    "Your drop-off days must include Saturday and cut-off time on Saturday must be before 15:00 (14:30 recommended).",
                    "woocommerce-postnl"
                ),
            ],
        ];
    }

    /**
     * @return array
     */
    private function get_section_carrier_postnl_pickup_options(): array
    {
        return [
            [
                "name"  => WCPN_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                "label" => __("Enable PostNL pickup", "woocommerce-postnl"),
                "type"  => "toggle",
            ],
            [
                "name"      => WCPN_Settings::SETTING_CARRIER_PICKUP_FEE,
                "condition" => WCPN_Settings::SETTING_CARRIER_PICKUP_ENABLED,
                "class"     => ["wcpn__child"],
                "label"     => __("Fee (optional)", "woocommerce-postnl"),
                "type"      => "currency",
                "help_text" => __(
                    "Enter an amount that is either positive or negative. For example, do you want to give a discount for using this function or do you want to charge extra for this delivery option.",
                    "woocommerce-postnl"
                ),
            ],
        ];
    }

    /**
     * @return array
     */
    private function get_section_export_defaults_main()
    {
        return [
            [
                "name"      => WCPN_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES,
                "label"     => __("Package types", "woocommerce-postnl"),
                "callback"  => [$this->callbacks, "enhanced_select"],
                "loop"      => WCPN_Data::getPackageTypesHuman(),
                "options"   => WCPN_Settings_Callbacks::getShippingMethods(),
                "default"   => [],
                "help_text" => __(
                    "Select one or more shipping methods for each PostNL package type",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CONNECT_EMAIL,
                "label"     => __("Connect customer email", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __(
                    "When you connect the customer email, PostNL can send a Track & Trace email to this address.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_CONNECT_PHONE,
                "label"     => __("Connect customer phone", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __(
                    "When you connect the customer's phone number, the courier can use this for the delivery of the parcel. This greatly increases the delivery success rate for foreign shipments.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_LABEL_DESCRIPTION,
                "label"     => __("Label description", "woocommerce-postnl"),
                "help_text" => __(
                    "With this option, you can add a description to the shipment. This will be printed on the top left of the label, and you can use this to search or sort shipments in your backoffice. Use [ORDER_NR] to include the order number, [DELIVERY_DATE] to include the delivery date.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_EMPTY_PARCEL_WEIGHT,
                "label"     => __("Empty parcel weight (grams)", "woocommerce-postnl"),
                "help_text" => __(
                    "Default weight of your empty parcel, rounded to grams.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_HS_CODE,
                "label"     => __("Default HS Code", "woocommerce-postnl"),
                "help_text" => __(
                    "HS Codes are used for PostNL world shipments, you can find the appropriate code on the site of the Dutch Customs.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"    => WCPN_Settings::SETTING_PACKAGE_CONTENT,
                "label"   => __("Customs shipment type", "woocommerce-postnl"),
                "type"    => "select",
                "options" => [
                    1 => __("Commercial goods", "woocommerce-postnl"),
                    2 => __("Commercial samples", "woocommerce-postnl"),
                    3 => __("Documents", "woocommerce-postnl"),
                    4 => __("Gifts", "woocommerce-postnl"),
                    5 => __("Return shipment", "woocommerce-postnl"),
                ],
            ],
            [
              "name"      => WCPN_Settings::SETTING_COUNTRY_OF_ORIGIN,
              "label"     => __("Default country of origin", "woocommerce-postnl"),
              "help-text" => __(
                  "Country of origin is required for world shipments. Defaults to shop base or NL. Example: 'NL', 'BE', 'DE'", "woocommerce-postnl"
              ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_AUTOMATIC_EXPORT,
                "label"     => __("Automatic export", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __(
                    "With this setting enabled, orders are exported to PostNL automatically after payment.",
                    "woocommerce-postnl"
                ),
            ],
        ];
    }

    /**
     * @return array
     */
    private function get_section_checkout_main(): array
    {
        return [
            [
                "name"      => WCPN_Settings::SETTING_USE_SPLIT_ADDRESS_FIELDS,
                "label"     => __("PostNL address fields", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __(
                    "When enabled the checkout will use the PostNLPostNL address fields. This means there will be three separate fields for street name, number and suffix. Want to use the WooCommerce default fields? Leave this option unchecked.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_SHOW_DELIVERY_DAY,
                "label"     => __("Show delivery day", "woocommerce-postnlbe"),
                "type"      => "toggle",
                "help_text" => __(
                    "Show delivery day options allow your customers to see the delivery day in order confirmation and My Account.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Enable PostNL delivery options", "woocommerce-postnl"),
                "type"      => "toggle",
                "help_text" => __(
                    "The PostNL delivery options allow your customers to select whether they want their parcel delivered at home or to a pickup point. Depending on the settings you can allow them to select a date, time and even options like requiring a signature on delivery.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY,
                "condition" => WCPN_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Display for", "woocommerce-postnl"),
                "type"      => "select",
                "help_text" => __(
                    "You can link the delivery options to specific shipping methods by adding them to the package types under \"Standard export settings\". The delivery options are not visible at foreign addresses.",
                    "woocommerce-postnl"
                ),
                "options"   => [
                    self::DISPLAY_FOR_SELECTED_METHODS => __(
                        "Shipping methods associated with Parcels",
                        "woocommerce-postnl"
                    ),
                    self::DISPLAY_FOR_ALL_METHODS      => __("All shipping methods", "woocommerce-postnl"),
                ],
            ],
            [
                "name"      => WCPN_Settings::SETTING_DELIVERY_OPTIONS_POSITION,
                "condition" => WCPN_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Checkout position", "woocommerce-postnl"),
                "type"      => "select",
                "default"   => "woocommerce_after_checkout_billing_form",
                "options"   => [
                    "woocommerce_after_checkout_billing_form"     => __(
                        "Show after billing details",
                        "woocommerce-postnl"
                    ),
                    "woocommerce_after_checkout_shipping_form"    => __(
                        "Show after shipping details",
                        "woocommerce-postnl"
                    ),
                    "woocommerce_checkout_after_customer_details" => __(
                        "Show after customer details",
                        "woocommerce-postnl"
                    ),
                    "woocommerce_after_order_notes"               => __(
                        "Show after notes",
                        "woocommerce-postnl"
                    ),
                ],
                "help_text" => __(
                    "You can change the place of the delivery options on the checkout page. By default it will be placed after shipping details.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"              => WCPN_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS,
                "condition"         => WCPN_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"             => __("Custom styles", "woocommerce-postnl"),
                "type"              => "textarea",
                "append"            => $this->getCustomCssAddition(),
                "custom_attributes" => [
                    "style" => "font-family: monospace;",
                    "rows"  => "8",
                    "cols"  => "12",
                ],
            ],
        ];
    }

    /**
     * Get the weekdays from WP_Locale and remove any entries. Sunday is removed by default unless `null` is passed.
     *
     * @param int|null ...$remove
     *
     * @return array
     */
    private function getWeekdays(...$remove): array
    {
        $weekdays = (new WP_Locale())->weekday;

        if ($remove !== null) {
            $remove = count($remove) ? $remove : [0];
            foreach ($remove as $index) {
                unset($weekdays[$index]);
            }
        }

        return $weekdays;
    }

    private function get_section_checkout_strings(): array
    {
        return [
            [
                "name"      => WCPN_Settings::SETTING_HEADER_DELIVERY_OPTIONS_TITLE,
                "condition" => WCPN_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Delivery options title", "woocommerce-postnl"),
                "title"     => "Delivery options title",
                "help_text" => __(
                    "You can place a delivery title above the PostNL options. When there is no title, it will not be visible.",
                    "woocommerce-postnl"
                ),
            ],
            [
                "name"      => WCPN_Settings::SETTING_DELIVERY_TITLE,
                "condition" => WCPN_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Delivery title", "woocommerce-postnl"),
                "default"   => __("Delivered at home or at work", "woocommerce-postnl"),
            ],
            [
                "name"      => WCPN_Settings::SETTING_MORNING_DELIVERY_TITLE,
                "condition" => WCPN_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Morning delivery title", "woocommerce-postnl"),
                "default"   => __("Morning delivery", "woocommerce-postnl"),
            ],
            [
                "name"      => WCPN_Settings::SETTING_STANDARD_TITLE,
                "condition" => WCPN_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Standard delivery title", "woocommerce-postnl"),
                "help_text" => __(
                    "When there is no title, the delivery time will automatically be visible.",
                    "woocommerce-postnl"
                ),
                "default"   => __("Standard delivery", "woocommerce-postnl"),
            ],
            [
                "name"      => WCPN_Settings::SETTING_EVENING_DELIVERY_TITLE,
                "condition" => WCPN_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Evening delivery title", "woocommerce-postnl"),
                "default"   => __("Evening delivery", "woocommerce-postnl"),
            ],
            [
                "name"      => WCPN_Settings::SETTING_ONLY_RECIPIENT_TITLE,
                "condition" => WCPN_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Home address only title", "woocommerce-postnl"),
                "default"   => __("Home address only", "woocommerce-postnl"),
            ],
            [
                "name"      => WCPN_Settings::SETTING_SIGNATURE_TITLE,
                "condition" => WCPN_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Signature on delivery title", "woocommerce-postnl"),
                "default"   => __("Signature on delivery", "woocommerce-postnl"),
            ],
            [
                "name"      => WCPN_Settings::SETTING_PICKUP_TITLE,
                "condition" => WCPN_Settings::SETTING_DELIVERY_OPTIONS_ENABLED,
                "label"     => __("Pickup title", "woocommerce-postnl"),
                "default"   => __("Pickup", "woocommerce-postnl"),
            ],
        ];
    }

    /**
     * Get the html string to render after the custom css select.
     *
     * @return string
     */
    private function getCustomCssAddition(): string
    {
        $currentTheme = wp_get_theme();

        $preset  = sanitize_title($currentTheme);
        $cssPath = WCPN()->plugin_path() . "/assets/css/delivery-options/delivery-options-preset-$preset.css";

        if (! file_exists($cssPath)) {
            return "";
        }

        return sprintf(
            '<p>%s <a class="" href="#" onclick="document.querySelector(`#delivery_options_custom_css`).value = `%s`">%s</a></p>',
            sprintf(__("Theme \"%s\" detected.", "woocommerce-postnl"), $currentTheme),
            file_get_contents($cssPath),
            __("Apply preset.", "woocommerce-postnl")
        );
    }
}

new WCPN_Settings_Data();
