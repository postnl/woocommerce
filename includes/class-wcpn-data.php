<?php

use MyParcelNL\Sdk\src\Factory\ConsignmentFactory;
use MyParcelNL\Sdk\src\Model\Consignment\AbstractConsignment;
use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use MyParcelNL\Sdk\src\Support\Arr;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCPN_Data')) {
    return new WCPN_Data();
}

class WCPN_Data
{
    public const API_URL = "https://api.myparcel.nl/";

    /**
     * @var array
     */
    public const CARRIERS_HUMAN = [
        PostNLConsignment::CARRIER_NAME => 'PostNL',
    ];

    public const HAS_MULTI_COLLO = false;

    public const DEFAULT_COUNTRY_CODE = "NL";
    public const DEFAULT_CARRIER      = PostNLConsignment::CARRIER_NAME;

    /**
     * @var array
     */
    private static $packageTypes;

    /**
     * @var array
     */
    private static $packageTypesHuman;

    /**
     * @var array
     */
    private static $deliveryTypesHuman;

    public function __construct()
    {
        self::$packageTypes = [
            AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME,
            AbstractConsignment::PACKAGE_TYPE_MAILBOX_NAME,
            AbstractConsignment::PACKAGE_TYPE_LETTER_NAME,
        ];

        self::$packageTypesHuman = [
            AbstractConsignment::PACKAGE_TYPE_PACKAGE_NAME       => __("Package", "woocommerce-postnl"),
            AbstractConsignment::PACKAGE_TYPE_MAILBOX_NAME       => __("Mailbox", "woocommerce-postnl"),
            AbstractConsignment::PACKAGE_TYPE_LETTER_NAME        => __("Unpaid letter", "woocommerce-postnl"),
        ];

        self::$deliveryTypesHuman = [
            AbstractConsignment::DELIVERY_TYPE_MORNING  => __("Morning delivery", "woocommerce-postnl"),
            AbstractConsignment::DELIVERY_TYPE_STANDARD => __("Standard delivery", "woocommerce-postnl"),
            AbstractConsignment::DELIVERY_TYPE_EVENING  => __("Evening delivery", "woocommerce-postnl"),
            AbstractConsignment::DELIVERY_TYPE_PICKUP   => __("Pickup", "woocommerce-postnl"),
        ];
    }

    /**
     * @return array
     */
    public static function getPackageTypes(): array
    {
        return self::$packageTypes;
    }

    /**
     * @return array
     */
    public static function getPackageTypesHuman(): array
    {
        return self::$packageTypesHuman;
    }

    /**
     * @param int|string $packageType
     *
     * @return string
     */
    public static function getPackageTypeHuman($packageType): string
    {
        return self::getHuman(
            $packageType,
            AbstractConsignment::PACKAGE_TYPES_NAMES_IDS_MAP,
            self::$packageTypesHuman
        );
    }

    /**
     * @param string $deliveryType
     *
     * @return string
     */
    public static function getPackageTypeId(string $deliveryType): string
    {
        return AbstractConsignment::PACKAGE_TYPES_NAMES_IDS_MAP[$deliveryType];
    }

    /**
     * @param string|int $key
     * @param array      $map
     * @param array      $humanMap
     *
     * @return string
     */
    private static function getHuman($key, array $map, array $humanMap): string
    {
        if (is_numeric($key)) {
            $integerMap = array_flip($map);
            $key        = (int) $key;

            if (! array_key_exists($key, $integerMap)) {
                return (string) $key;
            }

            $key = $integerMap[$key];
        }

        if (! array_key_exists($key, $humanMap)) {
            return $key;
        }

        return $humanMap[$key];
    }

    /**
     * @return array
     */
    public static function getInsuranceAmount(): array
    {
        $amount = [];

        /**
         * @type PostNLConsignment
         */
        $carrier             = ConsignmentFactory::createByCarrierName(WCPN_Settings::SETTINGS_POSTNL);
        $amountPossibilities = $carrier::INSURANCE_POSSIBILITIES_LOCAL;

        foreach ($amountPossibilities as $key => $value) {
            $amount[$value] = $value;
        }

        return $amount;
    }

    /**
     * @return array
     */
    public static function getPostnlName(): array
    {
        return [
            PostNLConsignment::CARRIER_NAME,
        ];
    }

    /**
     * @return array
     */
    public static function getCarriersHuman(): array
    {
        return [
            PostNLConsignment::CARRIER_NAME => __("PostNL", "woocommerce-postnl"),
        ];
    }
}

return new WCPN_Data();
