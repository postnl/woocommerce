<?php

declare(strict_types=1);

use MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter;
use WPO\WC\PostNL\Compatibility\Order as WCX_Order;
use WPO\WC\PostNL\Compatibility\Product as WCX_Product;

class OrderSettings
{
    /**
     * @var \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter
     */
    private $deliveryOptions;

    /**
     * @var \WC_Order
     */
    private $order;

    /**
     * @var string|null
     */
    private $carrier;

    /**
     * @var \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractShipmentOptionsAdapter|null
     */
    private $shipmentOptions;

    /**
     * @var float
     */
    private $weight;

    /**
     * @var int
     */
    private $digitalStampRangeWeight;

    /**
     * @var bool
     */
    private $ageCheck;

    /**
     * @var bool
     */
    private $insured;

    /**
     * @var int
     */
    private $insuranceAmount;

    /**
     * @var string
     */
    private $labelDescription;

    /**
     * @var bool
     */
    private $largeFormat;

    /**
     * @var bool
     */
    private $onlyRecipient;

    /**
     * @var string
     */
    private $packageType;

    /**
     * @var bool
     */
    private $returnShipment;

    /**
     * @var bool
     */
    private $signature;

    /**
     * @var int
     */
    private $colloAmount;

    /**
     * @var array
     */
    private $extraOptionsMeta;

    /**
     * @param \MyParcelNL\Sdk\src\Adapter\DeliveryOptions\AbstractDeliveryOptionsAdapter $deliveryOptions
     * @param WC_Order                                                                   $order
     *
     * @throws \Exception
     */
    public function __construct(
        AbstractDeliveryOptionsAdapter $deliveryOptions,
        WC_Order $order
    ) {
        $this->order           = $order;
        $this->deliveryOptions = $deliveryOptions;
        $this->carrier         = $deliveryOptions->getCarrier() ?? WCPN_Data::DEFAULT_CARRIER;
        $this->shipmentOptions = $deliveryOptions->getShipmentOptions();

        $this->extraOptionsMeta = WCX_Order::get_meta($this->order, WCPOST_Admin::META_SHIPMENT_OPTIONS_EXTRA);

        $this->setAllData();
    }

    /**
     * @return float
     */
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * @return int
     */
    public function getDigitalStampRangeWeight(): int
    {
        return $this->digitalStampRangeWeight;
    }

    /**
     * @return bool
     */
    public function hasAgeCheck(): bool
    {
        return $this->ageCheck;
    }

    /**
     * @return int
     */
    public function getColloAmount(): int
    {
        return $this->colloAmount;
    }

    /**
     * @return bool
     */
    public function isInsured(): bool
    {
        return $this->insured;
    }

    /**
     * @return int
     */
    public function getInsuranceAmount(): int
    {
        return $this->insuranceAmount;
    }

    /**
     * @return mixed|string
     */
    public function getLabelDescription(): string
    {
        return $this->labelDescription;
    }

    /**
     * @return bool
     */
    public function hasLargeFormat(): bool
    {
        return $this->largeFormat;
    }

    /**
     * @return bool
     */
    public function hasOnlyRecipient(): bool
    {
        return $this->onlyRecipient;
    }

    /**
     * @return string
     */
    public function getPackageType(): string
    {
        return $this->packageType;
    }

    /**
     * @return bool
     */
    public function hasReturnShipment(): bool
    {
        return $this->returnShipment;
    }

    /**
     * @return bool
     */
    public function hasSignature(): bool
    {
        return $this->signature;
    }

    /**
     * @throws \Exception
     */
    private function setAllData(): void
    {
        $this->setPackageType();
        $this->setColloAmount();
        $this->setLabelDescription();

        $this->setAgeCheck();
        $this->setOnlyRecipient();
        $this->setReturnShipment();
        $this->setSignature();

        $this->setInsuranceData();

        $this->setWeight();
        $this->setDigitalStampRangeWeight();
    }

    /**
     * @return void
     */
    private function setWeight(): void
    {
        $orderWeight = $this->order->get_meta(WCPOST_Admin::META_ORDER_WEIGHT);

        $this->weight = (float) $orderWeight;
    }

    /**
     * @return void
     */
    private function setAgeCheck(): void
    {
        $settingName                 = "{$this->carrier}_" . WCPOST_Settings::SETTING_CARRIER_DEFAULT_EXPORT_AGE_CHECK;
        $ageCheckFromShipmentOptions = $this->shipmentOptions->hasAgeCheck();
        $ageCheckOfProduct           = $this->getAgeCheckOfProduct();
        $ageCheckFromSettings        = (bool) WCPOST()->setting_collection->getByName($settingName);

        $this->ageCheck = $ageCheckFromShipmentOptions ?? $ageCheckOfProduct ?? $ageCheckFromSettings;
    }

    /**
     * Gets product age check value based on if it was explicitly set to either true or false. It defaults to inheriting from the default export settings.
     *
     * @return ?bool
     */
    private function getAgeCheckOfProduct(): ?bool
    {
        $hasAgeCheck = null;

        foreach ($this->order->get_items() as $item) {
            $product         = $item->get_product();
            $productAgeCheck = WCX_Product::get_meta($product, WCPOST_Admin::META_AGE_CHECK, true);

            if ($productAgeCheck === 1) {
                return true;
            }

            if ($productAgeCheck === 0) {
                $hasAgeCheck = false;
            }
        }

        return $hasAgeCheck;
    }

    /**
     * @return void
     */
    private function setColloAmount(): void
    {
        $this->colloAmount = (int) ($this->extraOptionsMeta['collo_amount'] ?? 1);
    }

    /**
     * @return void
     */
    private function setDigitalStampRangeWeight(): void
    {
        $orderWeight = $this->getWeight();
        $metaWeight  = ((float) $this->extraOptionsMeta["weight"]) ?? null;

        $this->digitalStampRangeWeight = WCPN_Export::getDigitalStampRangeFromWeight($metaWeight ?? $orderWeight);
    }

    /**
     * Sets insured and insuranceAmount.
     *
     * @return void
     */
    private function setInsuranceData(): void
    {
        $isInsured       = false;
        $insuranceAmount = 0;

        $isDefaultInsured                  = $this->getCarrierSetting(
            WCPOST_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED
        );
        $isDefaultInsuredFromPrice         = $this->getCarrierSetting(
            WCPOST_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_FROM_PRICE
        );
        $orderTotalExceedsInsuredFromPrice = $this->order->get_total() >= $isDefaultInsuredFromPrice;
        $insuranceFromDeliveryOptions      = $this->shipmentOptions->getInsurance();

        if ($insuranceFromDeliveryOptions) {
            $isInsured       = (bool) $insuranceFromDeliveryOptions;
            $insuranceAmount = $insuranceFromDeliveryOptions;
        } elseif ($isDefaultInsured && $orderTotalExceedsInsuredFromPrice) {
            $isInsured       = true;
            $insuranceAmount = $this->getCarrierSetting(WCPOST_Settings::SETTING_CARRIER_DEFAULT_EXPORT_INSURED_AMOUNT);
        }

        $this->insured         = $isInsured;
        $this->insuranceAmount = (int) $insuranceAmount;
    }

    /**
     * @return void
     */
    private function setLabelDescription(): void
    {
        $defaultValue     = "Order: " . $this->order->get_id();
        $valueFromSetting = WCPOST()->setting_collection->getByName(WCPOST_Settings::SETTING_LABEL_DESCRIPTION);
        $valueFromOrder   = $this->shipmentOptions->getLabelDescription();

        $this->labelDescription = (string) ($valueFromOrder ?? $valueFromSetting ?? $defaultValue);
    }

    /**
     * @return void
     */
    private function setOnlyRecipient(): void
    {
        $this->onlyRecipient = (bool) WCPN_Export::getChosenOrDefaultShipmentOption(
            $this->shipmentOptions->hasOnlyRecipient(),
            "{$this->carrier}_" . WCPOST_Settings::SETTING_CARRIER_DEFAULT_EXPORT_ONLY_RECIPIENT
        );
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function setPackageType(): void
    {
        $packageType = WCPOST()->export->getPackageTypeFromOrder($this->order, $this->deliveryOptions);
        $this->packageType = $packageType;
    }

    /**
     * @return void
     */
    private function setReturnShipment(): void
    {
        $this->returnShipment = (bool) WCPN_Export::getChosenOrDefaultShipmentOption(
            $this->shipmentOptions->isReturn(),
            "{$this->carrier}_" . WCPOST_Settings::SETTING_CARRIER_DEFAULT_EXPORT_RETURN
        );
    }

    /**
     * @return void
     */
    private function setSignature(): void
    {
        $this->signature = (bool) WCPN_Export::getChosenOrDefaultShipmentOption(
            $this->shipmentOptions->hasSignature(),
            "{$this->carrier}_" . WCPOST_Settings::SETTING_CARRIER_DEFAULT_EXPORT_SIGNATURE
        );
    }

    /**
     * @param string $settingName
     *
     * @return mixed
     */
    private function getCarrierSetting(string $settingName)
    {
        return WCPOST()->setting_collection->getByName("{$this->carrier}_" . $settingName);
    }
}
