<div class="woocommerce-myparcel__delivery-options">
    <?php
    // Add custom css to the delivery options, if any
    if (!empty(WCPN()->setting_collection->getByName(WCPN_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS))) {
        echo "<style>";
        echo WCPN()->setting_collection->getByName(WCPN_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS);
        echo "</style>";
    }
    ?>
  <div id="myparcel-delivery-options"></div>
</div>
