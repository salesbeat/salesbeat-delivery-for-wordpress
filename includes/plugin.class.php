<?php

namespace Salesbeat\Inc;

defined('ABSPATH') || exit;

class Plugin
{
    public static function activate()
    {
        global $wpdb;

        if (!current_user_can('activate_plugins')) return;

        $sql = file_get_contents(plugins_url(SALESBEAT_PLUGIN_NAME . '/db/install.sql'));
        $sql = str_replace('[prefix]', $wpdb->prefix, $sql);
        $wpdb->query($sql);
    }

    public static function deactivate()
    {
        if (!current_user_can('activate_plugins')) return;
    }

    public static function uninstall()
    {
        global $wpdb;

        if (!current_user_can('activate_plugins')) return;

        $sql = file_get_contents(plugins_url(SALESBEAT_PLUGIN_NAME . '/db/uninstall.sql'));
        $sql = str_replace('[prefix]', $wpdb->prefix, $sql);
        $wpdb->query($sql);
    }

    public static function settingsLink($actions, $pluginFile)
    {
        if (strpos($pluginFile, 'salesbeat') === false)
            return $actions;

        $settingsLink = '<a href="admin.php?page=sb_settings">' . __('Настройки', 'salesbeat') . '</a>';
        array_unshift($actions, $settingsLink);
        return $actions;
    }
}