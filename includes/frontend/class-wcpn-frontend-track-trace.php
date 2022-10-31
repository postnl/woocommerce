<?php

use WPO\WC\PostNL\Compatibility\Order as WCX_Order;
use WPO\WC\PostNL\Compatibility\WCPN_WCPDF_Compatibility;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCPN_Frontend_Track_Trace')) {
    return;
}

/**
 * Track & Trace
 */
class WCPN_Frontend_Track_Trace
{
    public function __construct()
    {
        // Customer Emails
        add_action("woocommerce_email_before_order_table", [$this, "addTrackTraceToEmail"], 10, 2);

        // Track & Trace in my account
        add_filter("woocommerce_my_account_my_orders_actions", [$this, "showTrackTraceActionInMyAccount"], 10, 2);

        WCPN_WCPDF_Compatibility::add_filters();
    }

    /**
     * Filter the emails sent to customers, adding Track & Trace link(s) if related order is completed.
     *
     * @param WC_Order $order
     * @param bool     $sentToAdmin
     *
     * @throws Exception
     */
    public function addTrackTraceToEmail(WC_Order $order, bool $sentToAdmin): void
    {
        if (! WCPOST()->setting_collection->isEnabled(WCPOST_Settings::SETTING_TRACK_TRACE_EMAIL)) {
            return;
        }

        if ($sentToAdmin || WCX_Order::get_status($order) !== "completed") {
            return;
        }

        $orderId         = WCX_Order::get_id($order);
        $trackTraceLinks = WCPN_Frontend::getTrackTraceLinks($orderId);

        if (empty($trackTraceLinks)) {
            return;
        }

        $createLinkCallback = function ($trackTrace) {
            return sprintf('<a href="%s">%s</a>', $trackTrace["url"], $trackTrace["link"]);
        };

        printf(
            '<p>%s %s</p>',
            esc_attr(apply_filters(
                "wcpostnl_email_text",
                __("You can track your order with the following Track & Trace link:", "woocommerce-postnl"),
                $order
            )),
            wp_kses(implode(
                '<br />',
                array_map($createLinkCallback, $trackTraceLinks)
            ),['a'=>['href'=>[]]])
        );
    }

    /**
     * @param array    $actions
     * @param WC_Order $order
     *
     * @return array
     * @throws Exception
     */
    public function showTrackTraceActionInMyAccount(array $actions, WC_Order $order): array
    {
        if (! WCPOST()->setting_collection->isEnabled(WCPOST_Settings::SETTING_TRACK_TRACE_MY_ACCOUNT)) {
            return $actions;
        }

        $order_id = WCX_Order::get_id($order);

        $consignments = WCPN_Frontend::getTrackTraceLinks($order_id);

        foreach ($consignments as $key => $consignment) {
            $actions['postnl_tracktrace_' . $consignment['link']] = [
                'url'  => $consignment['url'],
                'name' => apply_filters(
                    'wcpostnl_myaccount_tracktrace_button',
                    __('Track & Trace', 'woocommerce-postnl')
                ),
            ];
        }

        return $actions;
    }
}
