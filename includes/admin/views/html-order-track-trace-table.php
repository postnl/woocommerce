<?php

/**
 * This template is for the Track & Trace information in the PostNL meta box in a single order/
 */

/**
 * @var array $consignments
 * @var int   $order_id
 * @var bool  $downloadDisplay
 */

$shipments = [];

try {
    $shipments = WCPOST()->export->getShipmentData(array_keys($consignments), $order);
} catch (Exception $e) {
    $message = $e->getMessage();
}

if (isset($message)) {
    echo "<p>$message</p>";
}

/**
 * Don't render the table if no shipments have been exported.
 */
if (! count($shipments)) {
    return;
}

?>

<table class="wcpn__table--track-trace">
  <thead>
  <tr>
    <th><?php _e("Track & Trace", "woocommerce-postnl"); ?></th>
    <th><?php _e("Status", "woocommerce-postnl"); ?></th>
    <th>&nbsp;</th>
  </tr>
  </thead>
  <tbody>
  <?php

  foreach ($shipments as $shipment_id => $shipment):
      $consignment = $shipments[$shipment_id];

      ?>
    <tr>
      <td class="wcpn__order__track-trace">
          <?php WCPOST_Admin::renderTrackTraceLink($shipment, $order_id); ?>
      </td>
      <td class="wcpn__order__status">
          <?php WCPOST_Admin::renderStatus($shipment, $order_id) ?>
      </td>
      <td class="wcpn__td--create-label">
          <?php
          $action    = WCPN_Export::EXPORT;
          $getLabels = WCPN_Export::GET_LABELS;

          $order            = wc_get_order($order_id);
          $returnShipmentId = $order->get_meta(WCPOST_Admin::META_RETURN_SHIPMENT_IDS);

          WCPOST_Admin::renderAction(
              admin_url("admin-ajax.php?action=$action&request=$getLabels&shipment_ids=$shipment_id&return_shipment_id=$returnShipmentId"),
              __("Print PostNL label", "woocommerce-postnl"),
              WCPOST()->plugin_url() . "/assets/img/print.svg"
          );
          ?>
      </td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
