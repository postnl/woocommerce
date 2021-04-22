<?php

use migration\WCPN_Upgrade_Migration;

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WCPN_Upgrade_Migration_v4_2_1')) {
    return new WCPN_Upgrade_Migration_v4_2_1();
}

/**
 * Migrates pre v4.2.1 settings
 */
class WCPN_Upgrade_Migration_v4_2_1 extends WCPN_Upgrade_Migration
{
    /**
     * @var void
     */
    private $defaultExportSettings = [];

    public function __construct()
    {
        parent::__construct();
    }

    protected function import(): void
    {
        require_once(WCPOST()->plugin_path() . '/vendor/autoload.php');
        require_once(WCPOST()->plugin_path() . '/includes/admin/settings/class-wcpost-settings.php');
        require_once(WCPOST()->plugin_path() . '/includes/class-wcpn-data.php');
    }

    protected function migrate(): void
    {
        $this->defaultExportSettings = $this->getSettings("woocommerce_postnl_export_defaults_settings");

        $this->replaceEmptyParcelWeight();
    }

    protected function replaceEmptyParcelWeight(): void
    {
        $this->defaultExportSettings = $this->replaceValue(
            $this->defaultExportSettings,
            WCPOST_Settings::SETTING_EMPTY_PARCEL_WEIGHT,
            $this->calculateNewWeight()
        );
    }

    protected function setOptionSettingsMap(): void
    {
        $this->optionSettingsMap = [
            "woocommerce_postnl_export_defaults_settings" => $this->defaultExportSettings,
        ];
    }

    protected function calculateNewWeight(): float
    {
        $emptyParcelWeight = (float) $this->defaultExportSettings[WCPOST_Settings::SETTING_EMPTY_PARCEL_WEIGHT];
        $weightUnit        = get_option('woocommerce_weight_unit');
        $weight            = $emptyParcelWeight;

        if ('kg' === $weightUnit) {
            $dividedWeight = $emptyParcelWeight / 1000;

            // Don't allow the weight to go below 1 gram.
            if ($dividedWeight > 0.001) {
                $weight = $dividedWeight;
            }
        }

        return $weight;
    }
}

return new WCPN_Upgrade_Migration_v4_2_1();
