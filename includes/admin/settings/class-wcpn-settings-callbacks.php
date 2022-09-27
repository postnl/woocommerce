<?php

use WPO\WC\PostNL\Entity\SettingsFieldArguments;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (class_exists('WCPN_Settings_Callbacks')) {
    return new WCPN_Settings_Callbacks();
}

class WCPN_Settings_Callbacks
{
    /**
     * Validate options.
     *
     * @param array $input options to valid.
     *
     * @return array        validated options.
     */
    public function validate($input)
    {
        // Create our array for storing the validated options.
        $output = [];

        if (empty($input) || ! is_array($input)) {
            return $input;
        }

        // Loop through each of the incoming options.
        foreach ($input as $key => $value) {
            // Check to see if the current option has a value. If so, process it.
            if (isset($input[$key])) {
                if (is_array($input[$key])) {
                    foreach ($input[$key] as $sub_key => $sub_value) {
                        $output[$key][$sub_key] = $input[$key][$sub_key];
                    }
                } else {
                    $output[$key] = $input[$key];
                }
            }
        }

        // Return the array processing any additional functions filtered by this action.
        return apply_filters('wcpn_settings_validate_input', $input, $input);
    }

    /**
     * @param \WPO\WC\PostNL\Entity\SettingsFieldArguments|array $args
     *
     * @throws \Exception
     */
    public static function enhanced_select($args): void
    {
        if (is_array($args)) {
            $args = new SettingsFieldArguments($args);
        }

        include("class-wcpn-settings-callbacks-enhanced-select.php");
        new WCPN_Settings_Callbacks_Enhanced_Select($args);
    }

    /**
     * @param array $args
     */
    public static function renderSection(array $args): void
    {
        if (isset($args["description"])) {
            echo '<p>', wp_kses_post($args["description"]), '</p>';
        }
    }

    /**
     * Output a WooCommerce style form field.
     *
     * @param SettingsFieldArguments $class
     */
    public static function renderField(SettingsFieldArguments $class): void
    {
        $arguments  = $class->getArguments();
        $attributes = $class->getCustomAttributes();

        if (isset($arguments["description"])) {
            $description = $arguments["description"];
            unset ($arguments["description"]);
        }

        if (isset($attributes['data-type']) && $attributes['data-type'] === 'toggle') {
            self::renderToggle($class);
        } else {
            woocommerce_form_field(
                $class->getName(),
                $arguments,
                $class->getValue()
            );
        }

        if (isset($arguments["append"])) {
            echo wp_kses_post($arguments["append"]);
        }

        // Render the description here instead of inside the above function.
        if (isset($description)) {
            WCPN_Settings_Callbacks::renderDescription($description);
        }
    }

    /**
     * Get the order statuses as options array.
     *
     * @return array
     */
    public static function get_order_status_options(): array
    {
        $order_statuses = [];

        if (version_compare(WOOCOMMERCE_VERSION, '2.2', '<')) {
            $statuses = (array) get_terms('shop_order_status', ['hide_empty' => 0, 'orderby' => 'id']);
            foreach ($statuses as $status) {
                $order_statuses[esc_attr($status->slug)] = esc_html__($status->name, 'woocommerce');
            }
        } else {
            $statuses = wc_get_order_statuses();
            foreach ($statuses as $status_slug => $status) {
                $status_slug = 'wc-' === substr($status_slug, 0, 3) ? substr($status_slug, 3) : $status_slug;

                $order_statuses[$status_slug] = $status;
            }
        }

        return $order_statuses;
    }

    /**
     * @param $description
     */
    private static function renderDescription($description): void
    {
        echo '<p class="description">', wp_kses_post($description), '</p>';
    }

    /**
     * Render a custom toggle element. Uses classes from WooCommerce but has a custom JS implementation.
     *
     * @param \WPO\WC\PostNL\Entity\SettingsFieldArguments $class
     */
    private static function renderToggle(SettingsFieldArguments $class): void
    {
        $arguments                = $class->getArguments();
        $arguments['type']        = ['hidden'];
        $arguments['input_class'] = ['wcpn__input--toggle'];
        unset($arguments['description']);

        echo '<a class="wcpn__toggle wcpn__d--inline-block">';

        printf(
            '<input type="hidden" name="%s" value="%s" %s>',
            esc_html($class->getName()),
            esc_html($class->getValue()),
            esc_attr($class->getCustomAttributesAsString())
        );

        if (wc_string_to_bool($class->getValue())) {
            printf(
                "<span class=\"woocommerce-input-toggle woocommerce-input-toggle--enabled\">%s</span>",
                esc_attr__('Yes', 'woocommerce')
            );
        } else {
            printf(
                "<span class=\"woocommerce-input-toggle woocommerce-input-toggle--disabled\">%s</span>",
                esc_attr__('No', 'woocommerce')
            );
        }

        echo "</a>";
    }
}

return new WCPN_Settings_Callbacks();
