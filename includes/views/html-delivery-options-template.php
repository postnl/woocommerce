<div class="woocommerce-postnl__delivery-options">
    <?php
    // Add custom css to the delivery options, if any
    if (!empty(wCPN()->setting_collection->getByName(wCPN_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS))) {
        echo "<style>";
        echo wCPN()->setting_collection->getByName(wCPN_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS);
        echo "</style>";
    }
    ?>
  <div id="postnl-delivery-options"></div>
</div>
