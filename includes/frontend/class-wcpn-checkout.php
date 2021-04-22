<?php

use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
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
    private const DELIVERY_OPTIONS_KEY_MAP = [
        'deliveryType'                   => 'delivery_type',
        'isPickup'                       => 'is_pickup',
        'labelDescription'               => 'label_description',
        'pickupLocation'                 => 'pickup_location',
        'packageType'                    => 'package_type',
        'shipmentOptions'                => 'shipment_options',
        'shipmentOptions.ageCheck'       => 'shipment_options.age_check',
        'shipmentOptions.insuredAmount'  => 'shipment_options.insured_amount',
        'shipmentOptions.largeFormat'    => 'shipment_options.large_format',
        'shipmentOptions.onlyRecipient'  => 'shipment_options.only_recipient',
        'shipmentOptions.returnShipment' => 'shipment_options.return_shipment',
    ];

    /**
     * WCPN_Checkout constructor.
     */
    public function __construct()
    {
        add_action("wp_enqueue_scripts", [$this, "enqueue_frontend_scripts"], 100);

        // Save delivery options data
        add_action("woocommerce_checkout_update_order_meta", [$this, "save_delivery_options"], 10, 2);

        add_action("wp_ajax_wcpn_get_delivery_options_config", [$this, "getDeliveryOptionsConfigAjax"]);
    }

    /**
     * Load styles & scripts on the checkout page.
     *
     * @throws \Exception
     */
    public function enqueue_frontend_scripts(): void
    {
        // The order received page has the same page id as the checkout so `is_checkout()` returns true on both...
        if (! is_checkout() || is_order_received_page()) {
            return;
        }

        // if using split address fields
        $useSplitAddressFields = WCPOST()->setting_collection->isEnabled(WCPOST_Settings::SETTING_USE_SPLIT_ADDRESS_FIELDS);
        if ($useSplitAddressFields) {
            wp_enqueue_script(
                "wcpn-checkout-fields",
                WCPOST()->plugin_url() . "/assets/js/wcpn-checkout-fields.js",
                ["wc-checkout"],
                WC_POSTNL_VERSION,
                true
            );
        }

        // Don"t load the delivery options scripts if it"s disabled
        if (! WCPOST()->setting_collection->isEnabled(WCPOST_Settings::SETTING_DELIVERY_OPTIONS_ENABLED)) {
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
            $deps[] = "wcpn-checkout-fields";
        }

        /*
         * Show delivery options also for shipments on backorder
         */
        if (! $this->shouldShowDeliveryOptions()) {
            return;
        }

        wp_enqueue_script(
            "wc-postnl",
            WCPOST()->plugin_url() . "/assets/js/myparcel.js",
            $deps,
            WC_POSTNL_VERSION,
            true
        );

        wp_enqueue_script(
            "wc-postnl-frontend",
            WCPOST()->plugin_url() . "/assets/js/wcpn-frontend.js",
            array_merge($deps, ["wc-postnl", "jquery"]),
            WC_POSTNL_VERSION,
            true
        );

        $this->inject_delivery_options_variables();
    }

    /**
     * Localize variables into the delivery options scripts.
     *
     * @throws Exception
     */
    public function inject_delivery_options_variables(): void
    {
        wp_localize_script(
            'wc-postnl-frontend',
            'wcpn',
            [
                "ajax_url" => admin_url("admin-ajax.php"),
            ]
        );

        wp_localize_script(
            "wc-postnl-frontend",
            "PostNLDisplaySettings",
            [
                // Convert true/false to int for JavaScript
                "isUsingSplitAddressFields" => (int) WCPOST()->setting_collection->isEnabled(
                    WCPOST_Settings::SETTING_USE_SPLIT_ADDRESS_FIELDS
                ),
                "splitAddressFieldsCountries" => WCPN_NL_Postcode_Fields::COUNTRIES_WITH_SPLIT_ADDRESS_FIELDS,
            ]
        );

        wp_localize_script(
            "wc-postnl",
            "PostNLDeliveryOptions",
            [
                "allowedShippingMethods"    => json_encode($this->getShippingMethodsAllowingDeliveryOptions()),
                "disallowedShippingMethods" => json_encode(WCPN_Export::DISALLOWED_SHIPPING_METHODS),
                "alwaysShow"                => $this->alwaysDisplayDeliveryOptions(),
                "hiddenInputName"           => WCPOST_Admin::META_DELIVERY_OPTIONS,
            ]
        );

        wp_localize_script(
            'wc-postnl',
            'MyParcelConfig',
            $this->getDeliveryOptionsConfig()
        );

        // Load the checkout template.
        add_action(
            apply_filters(
                'wc_wcpn_delivery_options_location',
                WCPOST()->setting_collection->getByName(WCPOST_Settings::SETTING_DELIVERY_OPTIONS_POSITION)
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
        $packageTypes = WCPOST()->setting_collection->getByName(WCPOST_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES);

        if (! is_array($packageTypes)) {
            $packageTypes = [];
        }

        $shipping_methods = [];

        if (array_key_exists(AbstractConsignment::PACKAGE_TYPE_PACKAGE, $packageTypes ?? [])) {
            // settings_checkout_display_for_selected_methods = enable delivery options
            $shipping_methods = $packageTypes[AbstractConsignment::PACKAGE_TYPE_PACKAGE];
        }

        return json_encode($shipping_methods);
    }

    /**
     * Get the delivery options config in JSON for passing to JavaScript.
     *
     * @return array
     */
    public function getDeliveryOptionsConfig(): array
    {
        $settings                   = WCPOST()->setting_collection;
        $carriers                   = $this->getCarriers();
        $cartTotals                 = WC()->session->get('cart_totals');
        $chosenShippingMethodPrice  = (float) $cartTotals['shipping_total'];
        $displayIncludingTax        = WC()->cart->display_prices_including_tax();
        $priceFormat                = self::getDeliveryOptionsTitle(WCPOST_Settings::SETTING_DELIVERY_OPTIONS_PRICE_FORMAT);

        if ($displayIncludingTax) {
            $chosenShippingMethodPrice += (float) $cartTotals['shipping_tax'];
        }

        $postNLConfig = [
            "config" => [
                "currency"                   => get_woocommerce_currency(),
                "locale"                     => "nl-NL",
                "platform"                   => "myparcel",
                "basePrice"                  => $chosenShippingMethodPrice,
                "showPriceSurcharge"         => WCPN_Settings_Data::DISPLAY_SURCHARGE_PRICE === $priceFormat,
                "pickupLocationsDefaultView" => self::getPickupLocationsDefaultView(),
            ],
            "strings" => [
                "addressNotFound"       => __("Address details are not entered", "woocommerce-postnl"),
                "city"                  => __("City", "woocommerce-postnl"),
                "closed"                => __("Closed", "woocommerce-postnl"),
                "deliveryEveningTitle"  => self::getDeliveryOptionsTitle(WCPOST_Settings::SETTING_EVENING_DELIVERY_TITLE),
                "deliveryMorningTitle"  => self::getDeliveryOptionsTitle(WCPOST_Settings::SETTING_MORNING_DELIVERY_TITLE),
                "deliveryStandardTitle" => self::getDeliveryOptionsTitle(WCPOST_Settings::SETTING_STANDARD_TITLE),
                "deliveryTitle"         => self::getDeliveryOptionsTitle(WCPOST_Settings::SETTING_DELIVERY_TITLE),
                "headerDeliveryOptions" => self::getDeliveryOptionsTitle(WCPOST_Settings::SETTING_HEADER_DELIVERY_OPTIONS_TITLE),
                "houseNumber"           => __("House number", "woocommerce-postnl"),
                "onlyRecipientTitle"    => self::getDeliveryOptionsTitle(WCPOST_Settings::SETTING_ONLY_RECIPIENT_TITLE),
                "openingHours"          => __("Opening hours", "woocommerce-postnl"),
                "pickUpFrom"            => __("Pick up from", "woocommerce-postnl"),
                "pickupTitle"           => self::getDeliveryOptionsTitle(WCPOST_Settings::SETTING_PICKUP_TITLE),
                "postcode"              => __("Postcode", "woocommerce-postnl"),
                "retry"                 => __("Retry", "woocommerce-postnl"),
                "signatureTitle"        => self::getDeliveryOptionsTitle(WCPOST_Settings::SETTING_SIGNATURE_TITLE),
                "wrongHouseNumberCity"  => __("Postcode/city combination unknown", "woocommerce-postnl"),
            ],
        ];

        foreach ($carriers as $carrier) {
            foreach (self::getDeliveryOptionsConfigMap($carrier) as $key => $setting) {
                [$settingName, $function, $addBasePrice] = $setting;

                $value = $settings->{$function}($carrier . '_' . $settingName);

                if (is_numeric($value) && $this->useTotalPrice() && $addBasePrice) {
                    $value += $chosenShippingMethodPrice;
                }

                Arr::set($postNLConfig, 'config.' . $key, $value);
            }
        }

        $postNLConfig['config']['priceStandardDelivery'] = $this->useTotalPrice() ? $chosenShippingMethodPrice : null;

        return $postNLConfig;
    }

    /**
     * Echoes the delivery options config as a JSON string for use with AJAX.
     */
    public function getDeliveryOptionsConfigAjax(): void
    {
        echo json_encode($this->getDeliveryOptionsConfig(), JSON_UNESCAPED_SLASHES);
        die();
    }

    /**
     * @return bool
     */
    public function useTotalPrice(): bool
    {
        $priceFormat = WCPOST()->setting_collection->getByName(WCPOST_Settings::SETTING_DELIVERY_OPTIONS_PRICE_FORMAT);

        if (! isset($priceFormat) || WCPN_Settings_Data::DISPLAY_TOTAL_PRICE === $priceFormat){
            return true;
        }

        return false;
    }

    /**
     * @param string $title
     *
     * @return string
     */
    public static function getDeliveryOptionsTitle(string $title): string
    {
        $settings = WCPOST()->setting_collection;

        return __(strip_tags($settings->getStringByName($title)), "woocommerce-postnl");
    }

    /**
     * @return string
     */
    public static function getPickupLocationsDefaultView(): string
    {
        $settings = WCPOST()->setting_collection;

        return $settings->getStringByName(WCPOST_Settings::SETTING_PICKUP_LOCATIONS_DEFAULT_VIEW);
    }

    /**
     * Output the delivery options template.
     */
    public function output_delivery_options(): void
    {
        do_action('woocommerce_postnl_before_delivery_options');
        require_once(WCPOST()->includes . '/views/html-delivery-options-template.php');
        do_action('woocommerce_postnl_after_delivery_options');
    }

    /**
     * Get the array of enabled carriers by checking if they have either delivery or pickup enabled.
     *
     * @return array
     */
    private function getCarriers(): array
    {
        $settings = WCPOST()->setting_collection;
        $carriers = [];

        foreach ([PostNLConsignment::CARRIER_NAME] as $carrier) {
            if ($settings->getByName("{$carrier}_" . WCPOST_Settings::SETTING_CARRIER_PICKUP_ENABLED)
                || $settings->getByName(
                    "{$carrier}_" . WCPOST_Settings::SETTING_CARRIER_DELIVERY_ENABLED
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

        $shippingMethod       = Arr::get($_POST, "shipping_method");
        $highestShippingClass = Arr::get($_POST, "postnl_highest_shipping_class") ?? $shippingMethod[0];

        /**
         * Save the current version of our plugin to the order.
         */
        WCX_Order::update_meta_data(
            $order,
            WCPOST_Admin::META_ORDER_VERSION,
            WCPOST()->version
        );

        WCX_Order::update_meta_data(
            $order,
            WCPOST_Admin::META_SHIPMENT_OPTIONS_EXTRA,
            [
                'collo_amount' => 1,
                'weight'       => WC()->cart->get_cart_contents_weight(),
            ]
        );

        if ($highestShippingClass) {
            WCX_Order::update_meta_data(
                $order,
                WCPOST_Admin::META_HIGHEST_SHIPPING_CLASS,
                $highestShippingClass
            );
        }

        $deliveryOptionsFromPost          = Arr::get($_POST, WCPOST_Admin::META_DELIVERY_OPTIONS);
        $deliveryOptionsFromShippingClass = $highestShippingClass
            ? [
                'packageType' => WCPN_Export::getPackageTypeFromShippingMethod(
                    $shippingMethod[0],
                    $highestShippingClass
                ),
            ]
            : null;

        $deliveryOptions = empty($deliveryOptionsFromPost)
            ? $deliveryOptionsFromShippingClass
            : stripslashes($deliveryOptionsFromPost);

        if ($deliveryOptions) {
            if (! is_array($deliveryOptions)) {
                $deliveryOptions = json_decode($deliveryOptions, true);
            }
            $deliveryOptions = self::convertDeliveryOptionsForAdapter($deliveryOptions);
            $deliveryOptions = WCPOST_Admin::removeDisallowedDeliveryOptions(
                $deliveryOptions,
                $order->get_shipping_country()
            );

            /*
             * Create a new DeliveryOptions class from the data.
             */
            $deliveryOptions = new WCPN_DeliveryOptionsFromOrderAdapter(null, $deliveryOptions);

            /*
             * Store it in the meta data.
             */
            WCX_Order::update_meta_data(
                $order,
                WCPOST_Admin::META_DELIVERY_OPTIONS,
                $deliveryOptions->toArray()
            );
        }
    }

    /**
     * Return the names of shipping methods that will show delivery options. If DISPLAY_FOR_ALL_METHODS is enabled it'll
     * return an empty array and the frontend will allow any shipping except any that are specifically disallowed.
     *
     * @return string[]
     * @throws Exception
     * @see WCPN_Export::DISALLOWED_SHIPPING_METHODS
     */
    private function getShippingMethodsAllowingDeliveryOptions(): array
    {
        $allowedMethods               = [];
        $displayFor                   = WCPOST()->setting_collection->getByName(WCPOST_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY);
        $shippingMethodsByPackageType = WCPOST()->setting_collection->getByName(WCPOST_Settings::SETTING_SHIPPING_METHODS_PACKAGE_TYPES);

        if (WCPN_Settings_Data::DISPLAY_FOR_ALL_METHODS === $displayFor || ! $shippingMethodsByPackageType) {
            return $allowedMethods;
        }

        $shippingMethodsForPackage = $shippingMethodsByPackageType[AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME];

        foreach ($shippingMethodsForPackage as $shippingMethod) {
            [$methodId] = self::splitShippingMethodString($shippingMethod);

            if (! in_array($methodId, WCPN_Export::DISALLOWED_SHIPPING_METHODS)) {
                $allowedMethods[] = $shippingMethod;
            }
        }

        return $allowedMethods;
    }

    /**
     * @return bool
     */
    private function alwaysDisplayDeliveryOptions(): bool
    {
        $display = WCPOST()->setting_collection->getByName(WCPOST_Settings::SETTING_DELIVERY_OPTIONS_DISPLAY);

        return $display === WCPN_Settings_Data::DISPLAY_FOR_ALL_METHODS;
    }

    /**
     * Split a <rateId>:<instanceId> string into an array. If there is no instanceId, the second array element will be
     * null.
     *
     * @param $shippingMethod
     *
     * @return array
     */
    public static function splitShippingMethodString(string $shippingMethod): array
    {
        $split = explode(':', $shippingMethod, 2);

        if (count($split) === 1) {
            $split[] = null;
        }

        return $split;
    }

    /**
     * Map keys from the delivery options to the keys used in the adapters.
     *
     * @param array $deliveryOptions
     *
     * @return array
     */
    private static function convertDeliveryOptionsForAdapter(array $deliveryOptions): array
    {
        foreach (self::DELIVERY_OPTIONS_KEY_MAP as $camel => $snake) {
            $value = Arr::get($deliveryOptions, $camel);
            if (isset($value)) {
                Arr::set($deliveryOptions, $snake, $value);
                Arr::forget($deliveryOptions, $camel);
            }
        }

        return $deliveryOptions;
    }

    /**
     * @param string $carrier
     *
     * @return array[]
     */
    private static function getDeliveryOptionsConfigMap(string $carrier): array
    {
        return [
           "carrierSettings.$carrier.allowDeliveryOptions"  => [WCPOST_Settings::SETTING_CARRIER_DELIVERY_ENABLED, 'isEnabled', false],
           "carrierSettings.$carrier.allowEveningDelivery"  => [WCPOST_Settings::SETTING_CARRIER_DELIVERY_EVENING_ENABLED, 'isEnabled', false],
           "carrierSettings.$carrier.allowMondayDelivery"   => [WCPOST_Settings::SETTING_CARRIER_MONDAY_DELIVERY_ENABLED, 'isEnabled', false],
           "carrierSettings.$carrier.allowMorningDelivery"  => [WCPOST_Settings::SETTING_CARRIER_DELIVERY_MORNING_ENABLED, 'isEnabled', false],
           "carrierSettings.$carrier.allowOnlyRecipient"    => [WCPOST_Settings::SETTING_CARRIER_ONLY_RECIPIENT_ENABLED, 'isEnabled', false],
           "carrierSettings.$carrier.allowPickupLocations"  => [WCPOST_Settings::SETTING_CARRIER_PICKUP_ENABLED, 'isEnabled', false],
           "carrierSettings.$carrier.allowSaturdayDelivery" => [WCPOST_Settings::SETTING_CARRIER_SATURDAY_DELIVERY_ENABLED, 'isEnabled', false],
           "carrierSettings.$carrier.allowSignature"        => [WCPOST_Settings::SETTING_CARRIER_SIGNATURE_ENABLED, 'isEnabled', false],
           "carrierSettings.$carrier.priceEveningDelivery"  => [WCPOST_Settings::SETTING_CARRIER_DELIVERY_EVENING_FEE, 'getPriceByName', true],
           "carrierSettings.$carrier.priceMondayDelivery"   => [WCPOST_Settings::SETTING_CARRIER_MONDAY_DELIVERY_FEE, 'getPriceByName', true],
           "carrierSettings.$carrier.priceMorningDelivery"  => [WCPOST_Settings::SETTING_CARRIER_DELIVERY_MORNING_FEE, 'getPriceByName', true],
           "carrierSettings.$carrier.priceOnlyRecipient"    => [WCPOST_Settings::SETTING_CARRIER_ONLY_RECIPIENT_FEE, 'getPriceByName', false],
           "carrierSettings.$carrier.pricePickup"           => [WCPOST_Settings::SETTING_CARRIER_PICKUP_FEE, 'getPriceByName', true],
           "carrierSettings.$carrier.priceSaturdayDelivery" => [WCPOST_Settings::SETTING_CARRIER_SATURDAY_DELIVERY_FEE, 'getPriceByName', true],
           "carrierSettings.$carrier.priceSignature"        => [WCPOST_Settings::SETTING_CARRIER_SIGNATURE_FEE, 'getPriceByName', false],
           "cutoffTime"                                     => [WCPOST_Settings::SETTING_CARRIER_CUTOFF_TIME, 'getStringByName', false],
           "deliveryDaysWindow"                             => [WCPOST_Settings::SETTING_CARRIER_DELIVERY_DAYS_WINDOW, 'getIntegerByName', false],
           "dropOffDays"                                    => [WCPOST_Settings::SETTING_CARRIER_DROP_OFF_DAYS, 'getByName', false],
           "dropOffDelay"                                   => [WCPOST_Settings::SETTING_CARRIER_DROP_OFF_DELAY, 'getIntegerByName', false],
           "fridayCutoffTime"                               => [WCPOST_Settings::SETTING_CARRIER_FRIDAY_CUTOFF_TIME, 'getStringByName', false],
           "saturdayCutoffTime"                             => [WCPOST_Settings::SETTING_CARRIER_SATURDAY_CUTOFF_TIME, 'getStringByName', false],
       ];
    }

    /**
     * Show delivery options also for shipments on backorder
     * @return bool
     */
    private function shouldShowDeliveryOptions(): bool
    {
        // $backorderDeliveryOptions causes the options to be displayed also when product is in backorder
        $backorderDeliveryOptions = WCPOST()->setting_collection->isEnabled(WCPOST_Settings::SETTINGS_SHOW_DELIVERY_OPTIONS_FOR_BACKORDERS);
        $show                     = true;

        if ($backorderDeliveryOptions) {
            return $show;
        }

        foreach (WC()->cart->get_cart() as $cartItem) {
            /**
             * @var WC_Product $product
             */
            $product       = $cartItem['data'];
            $isOnBackorder = $product->is_on_backorder($cartItem['quantity']);

            if ($isOnBackorder) {
                $show = false;
                break;
            }
        }

        return $show;
    }
}

return new WCPN_Checkout();
