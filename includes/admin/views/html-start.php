<?php defined('ABSPATH') or die();?>

<!DOCTYPE html>
<html
    xmlns="http://www.w3.org/1999/xhtml"
    lang="<?php get_locale(); ?>">
<head>
    <meta
        http-equiv="Content-Type"
        content="text/html; charset=UTF-8">
    <title></title>
    <?php
    wp_enqueue_script(
        'wcpn-admin',
        WCPN()->plugin_url() . '/assets/js/wcpn-admin.js',
        ['jquery', 'thickbox'],
        WC_POSTNL_NL_VERSION
    );
    wp_localize_script(
        'wcpn-admin',
        'wc_postnl',
        [
            'ajax_url'                              => admin_url('admin-ajax.php'),
            'nonce'                                 => wp_create_nonce(WCPN::NONCE_ACTION),
            WCPN_Settings::SETTING_DOWNLOAD_DISPLAY => WCPN()->setting_collection->getByName(
                WCPN_Settings::SETTING_DOWNLOAD_DISPLAY
            ) ? WCPN()->setting_collection->getByName(WCPN_Settings::SETTING_DOWNLOAD_DISPLAY) : '',
        ]
    );

    wp_enqueue_style(
        'wcpn-admin-styles',
        WCPN()->plugin_url() . '/assets/css/wcpn-admin-styles.css',
        [],
        WC_POSTNL_NL_VERSION,
        'all'
    );

    // Legacy styles (WC 2.1+ introduced MP6 style with larger buttons)
    if (version_compare(WOOCOMMERCE_VERSION, '2.1', '<=')) {
        wp_enqueue_style(
            'wcpn-admin-styles-legacy',
            WCPN()->plugin_url() . '/assets/css/wcpn-admin-styles-legacy.css',
            [],
            WC_POSTNL_NL_VERSION,
            'all'
        );
    }

    wp_enqueue_style('wcpostnl-admin-styles');
    wp_enqueue_style('colors');
    wp_enqueue_style('media');
    wp_enqueue_script('jquery');
    do_action('admin_print_styles');
    do_action('admin_print_scripts');
    ?>
</head>
<body style="padding:10px 20px;">
