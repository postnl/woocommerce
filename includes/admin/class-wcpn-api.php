<?php

use MyParcelNL\Sdk\src\Helper\MyParcelCollection;
use WPO\WC\PostNL\Compatibility\Order as WCX_Order;
use WPO\WC\PostNL\Compatibility\WC_Core;
use WPO\WC\PostNL\Compatibility\WCPN_ChannelEngine_Compatibility as ChannelEngine;

if (! defined("ABSPATH")) {
    exit;
} // Exit if accessed directly

if (class_exists('WCPN_API')) {
    return;
}

class WCPN_API extends WCPN_Rest
{
    /**
     * @var string
     */
    public $apiUrl = "https://api.myparcel.nl/";

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $userAgent;

    /**
     * Default constructor
     *
     * @param string $key API Key provided by PostNL
     *
     * @throws Exception
     */
    public function __construct($key)
    {
        parent::__construct();

        $this->apiUrl    = WCPN_Data::API_URL;
        $this->userAgent = $this->getUserAgent();
        $this->key       = (string) $key;
    }

    /**
     * Add shipment
     *
     * @param array  $shipments array of shipments
     * @param string $type      shipment type: standard/return/unrelated_return
     *
     * @return array
     * @throws Exception
     * @deprecated Use MyParcel SDK instead
     */
    public function add_shipments(array $shipments, string $type = "standard"): array
    {
        $endpoint = "shipments";

        // define content type
        switch ($type) {
            case "return":
                $content_type = "application/vnd.return_shipment+json";
                $data_key     = "return_shipments";
                break;
            case "unrelated_return":
                $content_type = "application/vnd.unrelated_return_shipment+json";
                $data_key     = "unrelated_return_shipments";
                break;
            default:
                $content_type = "application/vnd.shipment+json";
                $data_key     = "shipments";
                break;
        }

        $data = [
            "data" => [
                $data_key => $shipments,
            ],
        ];

        $json = json_encode($data);

        $headers = [
            "Content-type"  => $content_type . "; charset=UTF-8",
            "Authorization" => "basic " . base64_encode("{$this->key}"),
            "user-agent"    => $this->userAgent,
        ];

        $request_url = $this->apiUrl . $endpoint;

        return $this->post($request_url, $json, $headers);
    }

    /**
     * Get shipments
     *
     * @param int|array $ids
     * @param array     $params request parameters
     *
     * @return array          response
     * @throws Exception
     */
    public function get_shipments($ids, array $params = []): array
    {
        $endpoint = "shipments";

        $headers = [
            "headers" => [
                "Accept"        => "application/json; charset=UTF-8",
                "Authorization" => "basic " . base64_encode("{$this->key}"),
                "user-agent"    => $this->userAgent,
            ],
        ];

        $request_url = $this->apiUrl . $endpoint . "/" . implode(";", (array) $ids);
        $request_url = add_query_arg($params, $request_url);

        return $this->get($request_url, $headers);
    }

    /**
     * Get Wordpress, WooCommerce, PostNL version and place theme in a array. Implode the array to get an UserAgent.
     *
     * @return string
     */
    private function getUserAgent(): string
    {
        $userAgents = [
            'Wordpress',
            get_bloginfo('version')
            . 'WooCommerce/'
            . WOOCOMMERCE_VERSION
            . 'PostNL-WooCommerce/'
            . WC_POSTNL_VERSION,
        ];

        // Place white space between the array elements
        return implode(" ", $userAgents);
    }

    /**
     * Get shipment labels, save them to the orders before showing them.
     *
     * @param array $shipment_ids Shipment ids.
     * @param array $order_ids
     * @param array $positions    Print position(s).
     * @param bool  $display      Download or display.
     *
     * @throws Exception
     */
    public function getShipmentLabels(array $shipment_ids, array $order_ids, array $positions = [], $display = true)
    {
        $collection = MyParcelCollection::findMany($shipment_ids, $this->key);

        /**
         * @see https://github.com/MyParcelNL/Sdk#label-format-and-position
         */
        if (WCPOST()->setting_collection->getByName(WCPOST_Settings::SETTING_LABEL_FORMAT) === "A6") {
            $positions = false;
        }

        if ($display) {
            $collection->setPdfOfLabels($positions);
            $this->updateOrderBarcode($order_ids, $collection);
            $collection->downloadPdfOfLabels($display);
        }

        if (! $display) {
            $collection->setLinkOfLabels($positions);
            $this->updateOrderBarcode($order_ids, $collection);
            echo esc_html($collection->getLinkOfLabels());
            die();
        }
    }

    /**
     * Update the status of given order based on the automatic order status settings.
     *
     * @param WC_Order $order
     * @param string   $changeStatusAtExportOrPrint
     */
    public function updateOrderStatus(WC_Order $order, string $changeStatusAtExportOrPrint): void
    {
        $statusAutomation     = WCPOST()->setting_collection->isEnabled(WCPOST_Settings::SETTING_ORDER_STATUS_AUTOMATION);
        $momentOfStatusChange = WCPOST()->setting_collection->getByName(WCPOST_Settings::SETTING_CHANGE_ORDER_STATUS_AFTER);
        $newStatus            = WCPOST()->setting_collection->getByName(WCPOST_Settings::SETTING_AUTOMATIC_ORDER_STATUS);

        if ($statusAutomation && $changeStatusAtExportOrPrint === $momentOfStatusChange) {
            $order->update_status(
                $newStatus,
                __("postnl_shipment_created", "woocommerce-postnl")
            );

            WCPN_Log::add("Status of order {$order->get_id()} updated to \"$newStatus\"");
        }
    }

    /**
     * @param array              $orderIds
     * @param MyParcelCollection $collection
     *
     * @throws Exception
     */
    private function updateOrderBarcode(array $orderIds, MyParcelCollection $collection): void
    {
        foreach ($orderIds as $orderId) {
            $order           = WC_Core::get_order($orderId);
            $lastShipmentIds = WCX_Order::get_meta($order, WCPOST_Admin::META_LAST_SHIPMENT_IDS);

            if (empty($lastShipmentIds)) {
                continue;
            }

            $trackTraceArray = $this->getTrackTraceForOrder($lastShipmentIds, $order);

            WCPN_Export::addTrackTraceNoteToOrder($orderId, $trackTraceArray);

            $this->updateOrderStatus($order, WCPN_Settings_Data::CHANGE_STATUS_AFTER_PRINTING);

            ChannelEngine::updateMetaOnExport($order, $trackTraceArray[0] ?? '');
        }
    }

    /**
     * @param  array     $lastShipmentIds
     * @param  \WC_Order $order
     *
     * @return array
     */
    private function getTrackTraceForOrder(array $lastShipmentIds, WC_Order $order): array
    {
        $shipmentData    = (new WCPN_Export())->getShipmentData($lastShipmentIds, $order);
        $trackTraceArray = [];

        foreach ($shipmentData as $shipment) {
            $trackTraceArray[] = $shipment['track_trace'] ?? null;
        }

        return $trackTraceArray;
    }
}
