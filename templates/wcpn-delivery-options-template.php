<!-- Add the Custom styles to the checkout -->
<?php if ( ! empty(WooCommerce_PostNL()->checkout_settings['custom_css'])) {
    echo "<style>";
    echo WooCommerce_PostNL()->checkout_settings['custom_css'];
    echo "</style>";
} ?>

<div id="postnl-load" class="postnl-delivery-options">
    <input style="display:none;" name='postnl-postnl-nl-data' id="postnl-input" />

    <div id="postnl-spinner-model">
        <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 99" enable-background="new 0 0 100 99" xml:space="preserve">
            <image id="postnl-logo" width="100" height="99" href="<?php echo WooCommerce_PostNL()->plugin_url() . '/assets/img/wcpn-postnl-logo.png' ?>" />
        </svg>
        <div id="postnl-spinner"></div>
    </div>

    <div class="postnl-message-model">
        <div id="postnl-message"></div>
    </div>
    <div id="postnl-location-details"></div>
    <div id="postnl-delivery-option-form">
        <table class="postnl-delivery-option-table">
            <tbody>
            <tr id="header-delivery-options-title">
                <td colspan="3">
                    <label for="postnl-delivery-options-title">
                        <h3><span id="postnl-delivery-options-title"></span></h3>
                    </label>
                </td>
            </tr>
            <tr>
                <td>
                    <input name="postnl-deliver-or-pickup" id="postnl-select-delivery" value="postnl-deliver" type="radio">
                </td>
                <td colspan="2">
                    <label id="postnl-select-delivery-title" for="postnl-select-delivery">
                    <span id="postnl-delivery-title"></span></label>
                </td>
            </tr>
            <tr id="postnl-delivery-date-select">
                <td></td>
                <td colspan="2">
                    <select name="postnl-delivery-date-select" id="postnl-select-date" title="Delivery date"></select>
                </td>
            </tr>
            <tr id="postnl-delivery-date-text">
                <td></td>
                <td colspan="2">
                    <div name="postnl-delivery-date-text" id="postnl-date" title="Delivery date"></div>
                </td>
            </tr>
            <tr id="method-postnl-delivery-morning-div">
                <td></td>
                <td>
                    <div class="postnl-delivery-option">
                        <input name="shipping-method" id="method-postnl-delivery-morning" type="radio" value="postnl-morning">
                        <label for="method-postnl-delivery-morning"><span id="postnl-morning-title"></span></label>
                    </div>
                </td>
                <td>
                    <div class="postnl-delivery-option">
                        <span id="postnl-morning-delivery"></span>
                    </div>
                </td>
            </tr>
            <tr id="postnl-delivery-option method-postnl-normal-div">
                <td></td>
                <td>
                    <div id="postnl-delivery" class="postnl-delivery-option">
                        <input name="shipping-method" id="method-postnl-normal" type="radio" value="postnl-normal">
                        <label for="method-postnl-normal"><span id="postnl-standard-title"></span></label>
                    </div>
                </td>
                <td>
                    <div class="postnl-delivery-option">
                        <span id="postnl-normal-delivery"></span>
                    </div>
                </td>
            </tr>
            <tr id="method-postnl-delivery-evening-div">
                <td></td>
                <td>
                    <div class="postnl-delivery-option">
                        <input name="shipping-method" id="method-postnl-delivery-evening" type="radio" value="postnl-delivery-evening">
                        <label for="method-postnl-delivery-evening"><span id="postnl-evening-title"></span></label>
                    </div>
                </td>
                <td>
                    <div class="postnl-delivery-option">
                        <span id="postnl-evening-delivery"> </span>
                    </div>
                </td>
            </tr>
            <tr class="postnl-extra-delivery-option-signature">
                <td></td>
                <td id="postnl-signature" class=" postnl-extra-delivery-options-padding-top">
                    <div class="postnl-delivery-option">
                        <input name="postnl-signature-selector" id="postnl-signature-selector" type="checkbox" value="postnl-signature-selector">
                        <label for="postnl-signature-selector"><span id="postnl-signature-title"></span></label>
                    </div>
                </td>
                <td class="postnl-extra-delivery-options-padding-top">
                    <span id="postnl-signature-price"></span>
                </td>
            </tr>
            <tr class="postnl-extra-delivery-options">
                <td></td>
                <td id="postnl-only-recipient">
                    <div class="postnl-delivery-option">
                        <input name="method-postnl-only-recipient-selector" id="postnl-only-recipient-selector" type="checkbox" value="postnl-only-recipient-selector">
                        <label for="postnl-only-recipient-selector"><span id="postnl-only-recipient-title"></span></label>
                    </div>
                </td>
                <td>
                    <span id="postnl-only-recipient-price"></span>
                </td>
            </tr>
            <tr id="postnl-pickup-location-selector" class="postnl-is-pickup-element">
                <td>
                    <input name="postnl-deliver-or-pickup" id="postnl-pickup-delivery" value="postnl-pickup" type="radio">
                </td>
                <td colspan="2">
                    <label for="postnl-pickup-delivery"><span id="postnl-pickup-title"></span></label>
                </td>
            </tr>
            <tr id="postnl-pickup-options" class="postnl-is-pickup-element">
                <td></td>
                <td colspan="2">
                    <select name="postnl-pickup-location" id="postnl-pickup-location">
                        <option value="">Geen Locatie</option>
                    </select> <span id="postnl-show-location-details">
                        <svg class="svg-inline--fa postnl-fa-clock fa-w-16" aria-hidden="true" data-prefix="fas" data-icon="clock" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                            <path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm57.1 350.1L224.9 294c-3.1-2.3-4.9-5.9-4.9-9.7V116c0-6.6 5.4-12 12-12h48c6.6 0 12 5.4 12 12v137.7l63.5 46.2c5.4 3.9 6.5 11.4 2.6 16.8l-28.2 38.8c-3.9 5.3-11.4 6.5-16.8 2.6z"></path>
                        </svg>
                    </span>
                </td>
            </tr>
            <tr id="postnl-pickup" class="postnl-is-pickup-element">
                <td></td>
                <td>
                    <input name="method-postnl-pickup-selector" id="postnl-pickup-selector" type="radio" value="postnl-pickup-selector">
                    <label for="postnl-pickup-selector"><span class="postnl-pickup-delivery-titel"></span> 15:00</label>
                </td>
                <td>
                    <span id="postnl-pickup-price"></span>
                </td>
            </tr>
            <tr id="postnl-pickup-express" class="postnl-is-pickup-element">
                <td></td>
                <td>
                    <input name="method-postnl-pickup-selector" id="postnl-pickup-express-selector" type="radio" value="postnl-pickup-express-selector">
                    <label for="postnl-pickup-express-selector"><span class="postnl-pickup-delivery-title"></span> 09:00</label>
                </td>
                <td>
                    <span id="postnl-pickup-express-price"></span>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
