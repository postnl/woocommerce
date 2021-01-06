<?php use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/** @noinspection PhpUnhandledExceptionInspection */
$deliveryOptions = WCPOST_Admin::getDeliveryOptionsFromOrder($order);

?>
<table class="wcpn__settings-table" style="width: auto">
    <tr>
        <td>
            <?php _e("Shipment type", "woocommerce-postnl") ?>:<br/> <small class="calculated_weight">
                <?php printf(
                    __("Calculated weight: %s", "woocommerce-postnl"),
                    wc_format_weight($order->get_meta(WCPOST_Admin::META_ORDER_WEIGHT))
                ) ?>
            </small>
        </td>
        <td>
            <?php
            $name = "postnl_options[{$order_id}][package_type]";
            printf('<select name="%s" class="package_type">', $name);
            foreach ($package_types as $key => $label) {
                printf(
                    '<option value="%s">%s</option>',
                    AbstractConsignment::PACKAGE_TYPE_PACKAGE,
                    $label
                );
            }
            echo '</select>';
            ?>
        </td>
    </tr>
</table><br>
<?php if (! isset($skip_save)): ?>
    <div class="wcpn__d--flex">
        <a class="button save" data-order="<?php echo $order_id; ?>"><?php _e("Save", "woocommerce-postnl") ?>
            <?php WCPOST_Admin::renderSpinner() ?>
        </a>
    </div>
<?php endif ?>
