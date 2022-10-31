<?php

/**
 * The shipment summary that shows when you click (i) in an order.
 */

use MyParcelNL\Sdk\src\Support\Arr;
use WPO\WC\PostNL\Compatibility\WC_Core as WCX;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

$post = wp_unslash(filter_input_array(INPUT_POST));

$order_id    = (int) sanitize_text_field($post['order_id']);
$shipment_id = (int) sanitize_text_field($post['shipment_id']);

$order = WCX::get_order($order_id);

$shipments       = WCPOST()->export->getShipmentData([$shipment_id], $order);
$deliveryOptions = WCPOST_Admin::getDeliveryOptionsFromOrder($order);

$option_strings = [
    "signature"      => __("shipment_options_signature", "woocommerce-postnl"),
    "only_recipient" => __("shipment_options_only_recipient", "woocommerce-postnl"),
];

$firstShipment = $shipments[$shipment_id];

/**
 * Show options only for the first shipment as they are all the same.
 */
$insurance        = Arr::get($firstShipment, "shipment.options.insurance");
$labelDescription = Arr::get($firstShipment, "shipment.options.label_description");

echo '<ul class="wcpn__shipment-summary wcpn__ws--nowrap">';

/**
 *  Package type
 */
printf(
    '%s: %s',
    esc_html__("Shipment type", "woocommerce-postnl"),
    esc_html(WCPN_Data::getPackageTypeHuman(Arr::get($firstShipment, "shipment.options.package_type")))
);

foreach ($option_strings as $key => $label) {
    if (Arr::get($firstShipment, "shipment.options.$key")
        && (int) Arr::get($firstShipment, "shipment.options.$key") === 1) {
        printf('<li class="%s">%s</li>', $key, $label);
    }
}

if ($insurance) {
    $price = number_format(Arr::get($insurance, "amount") / 100, 2);
    printf('<li>%s: € %s</li>', esc_html__("insured_for", "woocommerce-postnl"), esc_html($price));
}

if ($labelDescription) {
    printf(
        '<li>%s: %s</li>',
        esc_html__("Label description", "woocommerce-postnl"),
        esc_html($labelDescription)
    );
}
echo '</ul>';

echo "<hr>";

/**
 * Do show the Track & Trace status for all shipments.
 */
foreach ($shipments as $shipment_id => $shipment) {
    $trackTrace = Arr::get($shipment, "track_trace");

    /**
     * Show Track & Trace status.
     */
    if (! $trackTrace) {
        continue;
    }

    printf(
        '<a href="%2$s" target="_blank" title="%3$s">%3$s</a><br/> %1$s: %4$s<br/>',
        esc_html__("Status", "woocommerce-postnl"),
        esc_html(WCPOST_Admin::getTrackTraceUrl($order_id, $trackTrace)),
        esc_html($trackTrace),
        esc_html(Arr::get($shipment, "status"))
    );
}
