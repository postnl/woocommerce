<?php

declare(strict_types=1);

use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * @var \WC_Order $order
 * @var int       $order_id
 */

/** @noinspection PhpUnhandledExceptionInspection */
$orderSettings = new OrderSettings($order);

?>
<table class="wcpn__settings-table" style="width: auto">
    <tr>
        <td>
            <?php _e("Shipment type", "woocommerce-postnl") ?>:<br/> <small class="calculated_weight">
                <?php printf(
                    esc_html__("calculated_order_weight", "woocommerce-postnl"),
                    esc_html(wc_format_weight($orderSettings->getWeight()))
                ) ?>
            </small>
        </td>
        <td>
            <?php
            $name = "postnl_options[{$order_id}][package_type]";
            printf('<select name="%s" class="package_type">', esc_attr($name));
            foreach (WCPN_Data::getPackageTypesHuman() as $key => $label) {
                $isReturnPackageType = in_array(
                    $key,
                    [
                        AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME,
                        AbstractConsignment::PACKAGE_TYPE_MAILBOX_NAME,
                    ]
                );

                if (! $isReturnPackageType) {
                    return;
                }

                printf(
                    '<option value="%s">%s</option>',
                    esc_attr(WCPN_Data::getPackageTypeId($key)),
                    esc_html($label)
                );
            }
            echo '</select>';
            ?>
        </td>
    </tr>
</table><br>
<?php if (! isset($skip_save)): ?>
    <div class="wcpn__d--flex">
        <a class="button save" data-order="<?php echo esc_html($order_id); ?>"><?php esc_html_e("Save", "woocommerce-postnl") ?>
            <?php WCMYPA_Admin::renderSpinner() ?>
        </a>
    </div>
<?php endif ?>
