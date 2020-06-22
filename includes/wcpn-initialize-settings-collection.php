<?php

use MyParcelNL\Sdk\src\Model\Consignment\PostNLConsignment;
use WPO\WC\PostNL\Collections\SettingsCollection;

class WCPN_Initialize_Settings_Collection
{
    /**
     * Initialize the PHP 7.1+ settings collection.
     */
    public function initialize(): SettingsCollection
    {
        // Load settings
        $settings = new SettingsCollection();

        $settings->setSettingsByType($this->getOption("woocommerce_postnl_general_settings"), "general");
        $settings->setSettingsByType($this->getOption("woocommerce_postnl_checkout_settings"), "checkout");
        $settings->setSettingsByType($this->getOption("woocommerce_postnl_export_defaults_settings"), "export");

        $settings->setSettingsByType(
            $this->getOption("woocommerce_postnl_postnl_settings"),
            "carrier",
            PostNLConsignment::CARRIER_NAME
        );

        return $settings;
    }

    /**
     * @param $option
     *
     * @return array
     */
    private function getOption($option): array
    {
        $option = get_option($option);

        if (! $option) {
            return [];
        }

        return $option;
    }
}
