<?php

use MyParcelNL\Sdk\src\Factory\DeliveryOptionsAdapterFactory;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\DPDConsignment;
use MyParcelNL\Sdk\src\Support\Arr;
use WPO\WC\PostNL\Compatibility\Order as WCX_Order;
use WPO\WC\PostNL\Compatibility\WC_Core as WCX;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCPN_Checkout')) {
    return new WCPN_Checkout();
}

/**
 * Frontend views
 */
class WCPN_Checkout
{
    /**
     * WCPN_Checkout constructor.
     */
    public function __construct()
    {
        add_action("wp_enqueue_scripts", [$this, "enqueue_frontend_scripts"], 100);

        // Save delivery options data
        add_action("woocommerce_checkout_update_order_meta", [$this, "save_delivery_options"], 10, 2);
    }

    /**
     * Load styles & scripts
     */
    public function enqueue_frontend_scripts()
    {
        // return if not checkout or order received page
        if (! is_checkout() && ! is_order_received_page()) {
            return;
        }

        // if using split address fields
        $useSplitAddressFields = WCPN()->setting_collection->isEnabled(WCPN_Settings::SETTING_USE_SPLIT_ADDRESS_FIELDS);
        if ($useSplitAddressFields) {
            wp_enqueue_script(
                "wcpn-checkout-fields",
                WCPN()->plugin_url() . "/assets/js/wcpn-checkout-fields.js",
                ["wc-checkout"],
                WC_POST_NL_VERSION,
                true
            );
        }

        // Don"t load the delivery options scripts if it"s disabled
        if (! WCPN()->setting_collection->isEnabled(WCPN_Settings::SETTING_DELIVERY_OPTIONS_ENABLED)) {
            return;
        }

        /**
         * JS dependencies array
         */
        $deps = ["wc-checkout"];

        /**
         * If split address fields are enabled add the checkout fields script as an additional dependency.
         */
        if ($useSplitAddressFields) {
            array_push($deps, "wcpn-checkout-fields");
        }

        wp_enqueue_script(
            "wc-postnl",
            WCPN()->plugin_url() . "/assets/js/postnl.js",
            $deps,
            WC_POST_NL_VERSION,
            true
        );

        wp_enqueue_script(
            "wc-postnl-frontend",
            WCPN()->plugin_url() . "/assets/js/wcpn-frontend.js",
            array_merge($deps, ["wc-postnl", "jquery"]),
            WC_POST_NL_VERSION,
            true
        );

        $this->inject_delivery_options_variables();
    }

    /**
     * Localize variables into the delivery options scripts.
     */
    public function inject_delivery_options_variables()
    {
        wp_localize_script(
            "wc-postnl-frontend",
            "PostNLDisplaySettings",
            [
                // Convert true/false to int for JavaScript
                "isUsingSplitAddressFields" => (int) WCPN()->setting_collection->isEnabled(
                    WCPN_Settings::SETTING_USE_SPLIT_ADDRESS_FIELDS
                ),
            ]
        );

        wp_localize_script(
            "wc-postnl",
            "PostNLDeliveryOptions",
            [
                "allowedShippingMethods"    => json_encode($this->getShippingMethodsForDeliveryOptions()),
                "disallowedShippingMethods" => json_encode(["local_pickup"]),
                "alwaysShow"                => $this->alwaysDisplayDeliveryOptions(),
                "hiddenInputName"           => WCPN_Admin::META_DELIVERY_OPTIONS,
            ]
        );

        wp_localize_script(
            'wc-postnl',
            'MyParcelConfig',
            $this->get_delivery_options_config()
        );

        // Load the checkout template.
        add_action(
            apply_filters(
                'wc_wcpn_delivery_options_location',
                WCPN()->setting_collection->getByName(WCPN_Settings::SETTING_DELIVERY_OPTIONS_POSITION)
            ),
            [$this, 'output_delivery_options'],
            10
        );
    }

    /**
     * @return string
     */
    public function get_delivery_options_shipping_methods()
    {
        $packageTypes = WCPN()->setting_collection->getByName(WCPN_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES);

        if (! is_array($packageTypes)) {
            $packageTypes = [];
        }

        $shipping_methods = [];

        if (array_key_exists(WCPN_Export::PACKAGE, $packageTypes ?? [])) {
            // Shipping methods associated with parcels = enable delivery options
            $shipping_methods = $packageTypes[WCPN_Export::PACKAGE];
        }

        return json_encode($shipping_methods);
    }

    /**
     * Get the delivery options config in JSON for passing to JavaScript.
     *
     * @return false|mixed|string|void
     */
    public function get_delivery_options_config()
    {
        $settings = WCPN()->setting_collection;

        $carriers = $this->get_carriers();

        $MyParcelConfig = [
            "config"  => [
                "carriers" => $carriers,
                "platform" => "myparcel",
                "locale"   => "nl-NL",
                "currency" => get_woocommerce_currency(),
            ],
            "strings" => [
                "addressNotFound"       => __("Address details are not entered", "woocommerce-postnl"),
                "city"                  => __("City", "woocommerce-postnl"),
                "closed"                => __("Closed", "woocommerce-postnl"),
                "deliveryStandardTitle" => $this->getDeliveryOptionsTitle(WCPN_Settings::SETTING_STANDARD_TITLE),
                "deliveryTitle"         => $this->getDeliveryOptionsTitle(WCPN_Settings::SETTING_DELIVERY_TITLE),
                "deliveryMorningTitle"  => $this->getDeliveryOptionsTitle(WCPN_Settings::SETTING_MORNING_DELIVERY_TITLE),
                "deliveryEveningTitle"  => $this->getDeliveryOptionsTitle(WCPN_Settings::SETTING_EVENING_DELIVERY_TITLE),
                "headerDeliveryOptions" => $this->getDeliveryOptionsTitle(WCPN_Settings::SETTING_HEADER_DELIVERY_OPTIONS_TITLE),
                "houseNumber"           => __("House number", "woocommerce-postnl"),
                "openingHours"          => __("Opening hours", "woocommerce-postnl"),
                "pickUpFrom"            => __("Pick up from", "woocommerce-postnl"),
                "pickupTitle"           => $this->getDeliveryOptionsTitle(WCPN_Settings::SETTING_PICKUP_TITLE),
                "postcode"              => __("Postcode", "woocommerce-postnl"),
                "retry"                 => __("Retry", "woocommerce-postnl"),
                "wrongHouseNumberCity"  => __("Postcode/city combination unknown", "woocommerce-postnl"),
                "signatureTitle"        => $this->getDeliveryOptionsTitle(WCPN_Settings::SETTING_SIGNATURE_TITLE),
                "onlyRecipientTitle"    => $this->getDeliveryOptionsTitle(WCPN_Settings::SETTING_ONLY_RECIPIENT_TITLE),
            ],
        ];

        foreach ($carriers as $carrier) {
            $allowMorningDeliveryOptions = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_DELIVERY_MORNING_ENABLED;
            $allowDeliveryOptions        = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_DELIVERY_ENABLED;
            $allowEveningDeliveryOptions = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_DELIVERY_EVENING_ENABLED;
            $allowPickupLocations        = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_PICKUP_ENABLED;
            $allowSignature              = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_SIGNATURE_ENABLED;
            $allowOnlyRecipient          = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_ONLY_RECIPIENT_ENABLED;
            $allowMondayDelivery         = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_MONDAY_DELIVERY_ENABLED;
            $cutoffTime                  = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_CUTOFF_TIME;
            $deliveryDaysWindow          = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW;
            $dropOffDays                 = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_DROP_OFF_DAYS;
            $dropOffDelay                = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_DROP_OFF_DELAY;
            $pricePickup                 = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_PICKUP_FEE;
            $priceSignature              = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_SIGNATURE_FEE;
            $priceOnlyRecipient          = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_ONLY_RECIPIENT_FEE;
            $priceEveningDelivery        = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_DELIVERY_EVENING_FEE;
            $priceMorningDelivery        = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_DELIVERY_MORNING_FEE;
            $priceSaturdayDelivery       = "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_SATURDAY_DELIVERY_FEE;

            $MyParcelConfig["config"]["carrierSettings"][$carrier] = [
                "allowDeliveryOptions" => $settings->isEnabled($allowDeliveryOptions),
                "allowEveningDelivery" => $settings->isEnabled($allowEveningDeliveryOptions),
                "allowMorningDelivery" => $settings->isEnabled($allowMorningDeliveryOptions),
                "allowPickupLocations" => $settings->isEnabled($allowPickupLocations),
                "allowSignature"       => $settings->isEnabled($allowSignature),
                "allowOnlyRecipient"   => $settings->isEnabled($allowOnlyRecipient),
                "allowMondayDelivery"  => $settings->isEnabled($allowMondayDelivery),

                "cutoffTime"         => $settings->getStringByName($cutoffTime),
                "deliveryDaysWindow" => $settings->getIntegerByName($deliveryDaysWindow),
                "dropOffDays"        => $settings->getByName($dropOffDays),
                "dropOffDelay"       => $settings->getIntegerByName($dropOffDelay),

                "pricePickup"           => $settings->getFloatByName($pricePickup),
                "priceSignature"        => $settings->getFloatByName($priceSignature),
                "priceOnlyRecipient"    => $settings->getFloatByName($priceOnlyRecipient),
                "priceEveningDelivery"  => $settings->getFloatByName($priceEveningDelivery),
                "priceMorningDelivery"  => $settings->getFloatByName($priceMorningDelivery),
                "priceSaturdayDelivery" => $settings->getFloatByName($priceSaturdayDelivery),
            ];
        }

        return json_encode($MyParcelConfig, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param string $title
     *
     * @return string
     */
    public function getDeliveryOptionsTitle(string $title): string
    {
        $settings = WCPN()->setting_collection;

        return __(strip_tags($settings->getStringByName($title)), "woocommerce-postnl");
    }

    /**
     * Output the delivery options template.
     */
    public function output_delivery_options()
    {
        do_action('woocommerce_postnl_before_delivery_options');
        require_once(WCPN()->includes . '/views/html-delivery-options-template.php');
        do_action('woocommerce_postnl_after_delivery_options');
    }

    /**
     * Get the array of enabled carriers by checking if they have either delivery or pickup enabled.
     *
     * @return array
     */
    private function get_carriers(): array
    {
        $settings = WCPN()->setting_collection;
        $carriers = [];

        foreach ([PostNLConsignment::CARRIER_NAME, DPDConsignment::CARRIER_NAME] as $carrier) {
            if ($settings->getByName("{$carrier}_" . WCPN_Settings::SETTING_CARRIER_PICKUP_ENABLED)
                || $settings->getByName(
                    "{$carrier}_" . WCPN_Settings::SETTING_CARRIER_DELIVERY_ENABLED
                )) {
                $carriers[] = $carrier;
            }
        }

        return $carriers;
    }

    /**
     * Save delivery options to order when used
     *
     * @param int   $order_id
     * @param array $posted
     *
     * @return void
     * @throws Exception
     */
    public static function save_delivery_options($order_id)
    {
        $order = WCX::get_order($order_id);

        $highestShippingClass = Arr::get($_POST, "postnl_highest_shipping_class");
        $shippingMethod       = Arr::get($_POST, "shipping_method");

        /**
         * Save the current version of our plugin to the order.
         */
        WCX_Order::update_meta_data(
            $order,
            WCPN_Admin::META_ORDER_VERSION,
            WCPN()->version
        );

        /**
         * Save the order weight here because it's easier than digging through order data after creating it.
         *
         * @see https://businessbloomer.com/woocommerce-save-display-order-total-weight/
         */
        WCX_Order::update_meta_data(
            $order,
            WCPN_Admin::META_ORDER_WEIGHT,
            WC()->cart->get_cart_contents_weight()
        );

        if ($highestShippingClass) {
            WCX_Order::update_meta_data(
                $order,
                WCPN_Admin::META_HIGHEST_SHIPPING_CLASS,
                $highestShippingClass
            );
        } elseif ($shippingMethod) {
            WCX_Order::update_meta_data(
                $order,
                WCPN_Admin::META_HIGHEST_SHIPPING_CLASS,
                $shippingMethod[0]
            );
        }

        $deliveryOptions = stripslashes(Arr::get($_POST, WCPN_Admin::META_DELIVERY_OPTIONS));

        if ($deliveryOptions) {

            $deliveryOptions = json_decode($deliveryOptions, true);
            /*
             * Create a new DeliveryOptions class from the data.
             */
            $deliveryOptions = DeliveryOptionsAdapterFactory::create($deliveryOptions);

            /*
             * Store it in the meta data. It will be serialized so class references will be kept.
             */
            WCX_Order::update_meta_data(
                $order,
                WCPN_Admin::META_DELIVERY_OPTIONS,
                $deliveryOptions
            );
        }
    }

    /**
     * @return array
     */
    private function getShippingMethodsForDeliveryOptions(): array
    {
        $allowed = [];

        $shippingClass = WCPN_Frontend::get_cart_shipping_class();
        $packageTypes  = WCPN()->setting_collection->getByName(WCPN_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES);
        $displayFor    = WCPN()->setting_collection->getByName(WCPN_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY);

        if ($displayFor === WCPN_Settings_Data::DISPLAY_FOR_SELECTED_METHODS) {
            /**
             *
             */
            foreach ($packageTypes as $packageType => $shippingMethods) {
                /**
                 *
                 */
                foreach ($shippingMethods as $shippingMethod) {
                    if ($shippingClass) {
                        $shippingMethodAndClass = "$shippingMethod:$shippingClass";

                        if (in_array($shippingMethodAndClass, $shippingMethods)) {
                            $allowed[] = $shippingMethodAndClass;
                        }
                    } elseif (in_array($shippingMethod, $shippingMethods)) {
                        $allowed[] = $shippingMethod;
                    }
                }
            }
        }

        return $allowed;
    }

    /**
     * @return bool
     */
    private function alwaysDisplayDeliveryOptions(): bool
    {
        $display = WCPN()->setting_collection->getByName(WCPN_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY);

        return $display === WCPN_Settings_Data::DISPLAY_FOR_ALL_METHODS;
    }
}

return new WCPN_Checkout();
