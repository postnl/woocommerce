<?php

use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use WPO\WC\PostNL\Compatibility\Order as WCX_Order;
use WPO\WC\PostNL\Entity\SettingsFieldArguments;

/**
 * @var int      $order_id
 * @var WC_Order $order
 */

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

try {
    $deliveryOptions = WCPN_Admin::getDeliveryOptionsFromOrder($order);
} catch (Exception $e) {
    return;
}

$extraOptions = WCX_Order::get_meta($order, WCPN_Admin::META_SHIPMENT_OPTIONS_EXTRA);

?>
<div class="wcpn wcpn__change-order">
    <?php
    if ($deliveryOptions->isPickup()) {
        $pickup = $deliveryOptions->getPickupLocation();

        printf(
            "<div class=\"pickup-location\"><strong>%s:</strong><br /> %s<br />%s %s<br />%s %s</div>",
            __("Pickup location", "woocommerce-postnl"),
            $pickup->getLocationName(),
            $pickup->getStreet(),
            $pickup->getNumber(),
            $pickup->getPostalCode(),
            $pickup->getCity()
        );

        echo "<hr>";
    }

    $isCarrierDisabled     = $deliveryOptions->getCarrier();
    $isPackageTypeDisabled = count(WCPN_Data::getPackageTypes()) === 1 || $deliveryOptions->isPickup();
    $shipment_options      = $deliveryOptions->getShipmentOptions();

    $packageTypes        = array_flip(AbstractConsignment::PACKAGE_TYPES_NAMES_IDS_MAP);
    $selectedPackageType = WCPN()->export->getPackageTypeForOrder($order_id);

    $postnl          = PostNLConsignment::CARRIER_NAME;
    $insurance       = false;
    $insuranceAmount = 0;
    $signature       = false;
    $onlyRecipient   = false;
    $ageCheck        = false;
    $returnShipment  = false;

    $insurance = WCPN_Export::getChosenOrDefaultShipmentOption(
        $shipment_options->getInsurance(),
        "{$postnl}_" . WCPN_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED
    );

    $signature = WCPN_Export::getChosenOrDefaultShipmentOption(
        $shipment_options->hasSignature(),
        "{$postnl}_" . WCPN_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE
    );

    $onlyRecipient = WCPN_Export::getChosenOrDefaultShipmentOption(
        $shipment_options->hasOnlyRecipient(),
        "{$postnl}_" . WCPN_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT
    );

    $ageCheck = WCPN_Export::getChosenOrDefaultShipmentOption(
        $shipment_options->hasAgeCheck(),
        "{$postnl}_" . WCPN_Settings::SETTING_CARRIER_DEFAULT_EXPORT_AGE_CHECK
    );

    $returnShipment = WCPN_Export::getChosenOrDefaultShipmentOption(
        $shipment_options->isReturn(),
        "{$postnl}_" . WCPN_Settings::SETTING_CARRIER_DEFAULT_EXPORT_RETURN
    );

    $insuranceAmount = WCPN_Export::getChosenOrDefaultShipmentOption(
        $shipment_options->getInsurance(),
        "{$postnl}_" . WCPN_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT
    );


    $option_rows = [
        [
            "name"              => "[carrier]",
            "label"             => __("Carrier", "woocommerce-postnl"),
            "type"              => "select",
            "options"           => WCPN_Data::CARRIERS_HUMAN,
            "custom_attributes" => ($isCarrierDisabled ?? $postnl) ? ["disabled" => "disabled"] : [],
            "value"             => $deliveryOptions->getCarrier(),
        ],
        [
            "name"              => "[package_type]",
            "label"             => __("Shipment type", "woocommerce-postnl"),
            "description"       => sprintf(
                __("Calculated weight: %s", "woocommerce-postnl"),
                wc_format_weight($order->get_meta(WCPN_Admin::META_ORDER_WEIGHT))
            ),
            "type"              => "select",
            "options"           => array_combine(WCPN_Data::getPackageTypes(), WCPN_Data::getPackageTypesHuman()),
            "value"             => $packageTypes[$selectedPackageType],
            "custom_attributes" => [
                "disabled" => $isPackageTypeDisabled ? "disabled" : null,
            ],
        ],
        [
            "name"              => "[extra_options][collo_amount]",
            "label"             => __("Number of labels", "woocommerce-postnl"),
            "type"              => "number",
            "value"             => isset($extraOptions["collo_amount"]) ? $extraOptions["collo_amount"] : 1,
            "custom_attributes" => [
                "step" => "1",
                "min"  => "1",
                "max"  => "10",
            ],
        ],
        [
            "name"      => "[shipment_options][only_recipient]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCPN_Data::getPostnlName(),
                "set_value"    => WCPN_Settings_Data::DISABLED,
            ],
            "label"     => __("Home address only", "woocommerce-postnl"),
            "value"     => $onlyRecipient,
        ],
        [
            "name"      => "[shipment_options][signature]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCPN_Data::getPostnlName(),
                "set_value"    => WCPN_Settings_Data::DISABLED,
            ],
            "label"     => __("Signature on delivery", "woocommerce-postnl"),
            "value"     => $signature,
        ],
        [
            "name"      => "[shipment_options][age_check]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCPN_Data::getPostnlName(),
                "set_value"    => WCPN_Settings_Data::DISABLED,
            ],
            "label"     => __("Age check", "woocommerce-postnl"),
            "value"     => $ageCheck,
        ],
        [
            "name"      => "[shipment_options][return_shipment]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCPN_Data::getPostnlName(),
                "set_value"    => WCPN_Settings_Data::DISABLED,
            ],
            "label"     => __("Return shipment", "woocommerce-postnl"),
            "value"     => $returnShipment,
        ],
        [
            "name"      => "[shipment_options][insured]",
            "type"      => "toggle",
            "condition" => [
                "name"         => "[carrier]",
                "type"         => "disable",
                "parent_value" => WCPN_Data::getPostnlName(),
                "set_value"    => WCPN_Settings_Data::ENABLED,
            ],
            "label"     => __("Insured", "woocommerce-postnl"),
            "value"     => (bool) $insurance,
        ],
        [
            "name"    => "[shipment_options][insured_amount]",
            "label"   => __("Insurance amount", "woocommerce-postnl"),
            "type"    => "select",
            "options" => WCPN_Data::getInsuranceAmount(),
            "value"   => (int) $insuranceAmount,
        ],
    ];

    if (isset($recipient) && isset($recipient["cc"]) && $recipient["cc"] !== "NL") {
        unset($option_rows["[signature]"]);
        unset($option_rows["[only_recipient]"]);
    }

    $namePrefix = WCPN_Admin::SHIPMENT_OPTIONS_FORM_NAME . "[$order_id]";

    foreach ($option_rows as $option_row) {
        if (isset($option_row["condition"])) {
            $option_row["condition"]["name"] = $namePrefix . $option_row["condition"]["name"];
        }

        $class = new SettingsFieldArguments($option_row);

        // Cast boolean values to the correct enabled/disabled values.
        if (is_bool($option_row["value"])) {
            $option_row["value"] = $option_row["value"] ? WCPN_Settings_Data::ENABLED : WCPN_Settings_Data::DISABLED;
        }

        woocommerce_form_field(
            $namePrefix . $option_row["name"],
            $class->getArguments(false),
            $option_row["value"] ?? null
        );
    }
    ?>
    <div>
        <div class="button wcpm__shipment-settings__save">
            <?php
            _e("Save", "woocommerce-postnl");
            WCPN_Admin::renderSpinner();
            ?>
        </div>
    </div>
</div>
