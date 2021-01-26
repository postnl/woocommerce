<?php

use PostNL\WooCommerce\Includes\Admin\OrderSettingsRows;
use WPO\WC\PostNL\Entity\SettingsFieldArguments;

/**
 * @var WC_Order $order
 */

defined('ABSPATH') or die();

try {
    $deliveryOptions = WCPOST_Admin::getDeliveryOptionsFromOrder($order);
} catch (Exception $e) {
    return;
}

?>
<div class="wcpn wcpn__shipment-options">
    <table>
    <?php

    WCPOST_Admin::renderPickupLocation($deliveryOptions);

    $optionRows = OrderSettingsRows::getOptionsRows($deliveryOptions, $order);
    $optionRows = OrderSettingsRows::filterRowsByCountry($order->get_shipping_country(), $optionRows);

    $namePrefix = WCPOST_Admin::SHIPMENT_OPTIONS_FORM_NAME . "[{$order->get_id()}]";

    foreach ($optionRows as $optionRow) :
        $class = new SettingsFieldArguments($optionRow, $namePrefix);

        // Cast boolean values to the correct enabled/disabled values.
        if (is_bool($optionRow["value"])) {
            $optionRow["value"] = $optionRow["value"] ? WCPN_Settings_Data::ENABLED : WCPN_Settings_Data::DISABLED;
        }

        $class->setValue($optionRow["value"]);
        ?>

        <tr>
            <td>
                <label for="<?php echo $class->getName() ?>">
                    <?php echo $class->getArgument('label'); ?>
                </label>
            </td>
            <td>
                <?php
                if (isset($optionRow['help_text'])) {
                    printf("<span class='ml-auto'>%s</span>", wc_help_tip($optionRow['help_text'], true));
                }
                ?>
            </td>
            <td>
                <?php WCPN_Settings_Callbacks::renderField($class); ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <tr>
        <td colspan="2">
            <div class="button wcpn__shipment-options__save">
                <?php
                _e("Save", "woocommerce-postnl");
                WCPOST_Admin::renderSpinner();
                ?>
            </div>
        </td>
    </tr>
    </table>
</div>
