<?php
defined('ABSPATH') or die();

include('html-start.php');

/**
 * @var $request
 */
if ($request === WCPN_Export::ADD_RETURN) {
    printf('<h3>%s</h3>', esc_html__('Return email successfully sent to customer', 'woocommerce-postnl'));
}

include('html-end.php');
