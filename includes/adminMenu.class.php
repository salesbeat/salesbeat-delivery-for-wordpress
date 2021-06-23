<?php

namespace Salesbeat\Inc;

defined('ABSPATH') || exit;

if (class_exists('Salesbeat\\Inc\\AdminMenu', false))
    return new AdminMenu();

class AdminMenu
{
    public $option = 'manage_options';
    public $section = 'salesbeat';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addAdminMenu'], 1);
        add_action('admin_menu', [$this, 'removeAdminMenu'], 9999);
    }

    public function addAdminMenu()
    {
        add_menu_page(
            __('Salesbeat', 'salesbeat'),
            __('Salesbeat', 'salesbeat'),
            $this->option,
            $this->section,
            null,
            'dashicons-store',
            56
        );

        $hook = add_submenu_page(
            $this->section,
            __('Salesbeat: Выгрузка заказов', 'salesbeat'),
            __('Выгрузка заказов', 'salesbeat'),
            $this->option,
            'sb_orders',
            [new AdminOrders, 'outputView']
        );
        add_action('load-' . $hook, [new AdminOrders, 'showScreenOptions']);

        add_submenu_page(
            $this->section,
            __('Salesbeat: Настройки', 'salesbeat'),
            __('Настройки', 'salesbeat'),
            $this->option,
            'sb_settings',
            [new AdminSettings, 'outputView']
        );
    }

    public function removeAdminMenu()
    {
        remove_submenu_page($this->section, $this->section);
    }
}

return new AdminOrders();