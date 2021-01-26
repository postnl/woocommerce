<div class="woocommerce-postnl__delivery-options">
    <?php
    // Add custom css to the delivery options, if any
    if (!empty(WCPOST()->setting_collection->getByName(WCPOST_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS))) {
        echo "<style>";
        echo WCPOST()->setting_collection->getByName(WCPOST_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS);
        echo "</style>";
    }
    ?>
  <div id="myparcel-delivery-options"></div>
</div>
