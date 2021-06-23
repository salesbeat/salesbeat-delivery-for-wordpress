<?php

namespace Salesbeat\Inc;

defined('ABSPATH') || exit;

if (class_exists('Salesbeat\\Inc\\AdminSettings', false))
    return new AdminSettings();

class AdminSettings
{
    public $page = 'sb_settings';
    public $tabSystem = 'sb_settings_system';
    public $tabPaySystem = 'sb_settings_pay_system';
    public $tabProperty = 'sb_settings_property';

    public function __construct()
    {
        add_action('admin_init', [$this, 'init']);
    }

    /**
     * Инициализируем свойства и секции
     */
    public function init()
    {
        // System
        add_settings_section('system', __('Системные настройки', 'salesbeat'), '', $this->tabSystem);
        add_settings_field(
            'salesbeat_system_api_token', __('API-токен', 'salesbeat'), [$this, 'outputFields'], $this->tabSystem, 'system',
            ['type' => 'text', 'name' => 'salesbeat_system_api_token', 'value' => get_option('salesbeat_system_api_token')]
        );
        add_settings_field(
            'salesbeat_system_secret_token', __('Secret-токен', 'salesbeat'), [$this, 'outputFields'], $this->tabSystem, 'system',
            ['type' => 'text', 'name' => 'salesbeat_system_secret_token', 'value' => get_option('salesbeat_system_secret_token')]
        );
        register_setting($this->page, 'salesbeat_system_api_token', [$this, 'validate']);
        register_setting($this->page, 'salesbeat_system_secret_token', [$this, 'validate']);

        // PaySystem
        $gateways = WC()->payment_gateways->payment_gateways();

        $paySystems = [];
        foreach ($gateways as $gateway)
            $paySystems[$gateway->id] = $gateway->title;

        add_settings_section('pay_system', __('Платежные системы', 'salesbeat'), '', $this->tabPaySystem);
        add_settings_field(
            'salesbeat_pay_systems_cash', __('Оплата наличными', 'salesbeat'), [$this, 'outputFields'], $this->tabPaySystem, 'pay_system',
            ['type' => 'multi_checkbox', 'name' => 'salesbeat_pay_systems_cash[]', 'list' => $paySystems, 'value' => get_option('salesbeat_pay_systems_cash')]
        );
        add_settings_field(
            'salesbeat_pay_systems_card', __('Оплата картой', 'salesbeat'), [$this, 'outputFields'], $this->tabPaySystem, 'pay_system',
            ['type' => 'multi_checkbox', 'name' => 'salesbeat_pay_systems_card[]', 'list' => $paySystems, 'value' => get_option('salesbeat_pay_systems_card')]
        );
        add_settings_field(
            'salesbeat_pay_systems_online', __('Оплата онлайн', 'salesbeat'), [$this, 'outputFields'], $this->tabPaySystem, 'pay_system',
            ['type' => 'multi_checkbox', 'name' => 'salesbeat_pay_systems_online[]', 'list' => $paySystems, 'value' => get_option('salesbeat_pay_systems_online')]
        );
        add_settings_field(
            'salesbeat_pay_systems_sync', __('Ручная синхронизация', 'salesbeat'), [$this, 'outputFields'], $this->tabPaySystem, 'pay_system',
            ['type' => 'link', 'href' => '#', 'text' => __('Синхронизировать платежные системы', 'salesbeat'), 'attr' => ' data-action-sync']
        );
        register_setting($this->page, 'salesbeat_pay_systems_cash', [$this, 'validate']);
        register_setting($this->page, 'salesbeat_pay_systems_card', [$this, 'validate']);
        register_setting($this->page, 'salesbeat_pay_systems_online', [$this, 'validate']);
        register_setting($this->page, 'salesbeat_pay_systems_sync', [$this, 'validate']);

        // Property
        $fields = WC()->countries->get_address_fields(
            WC()->countries->get_base_country(),
            'billing_'
        );

        $properties = [];
        foreach ($fields as $key => $field) {
            if ($key == 'billing_address_2') $field['label'] = __('Адрес 2', 'salesbeat');
            $properties[$key] = $field['label'];
        }
        add_settings_section('property', __('Свойства заказа', 'salesbeat'), '', $this->tabProperty);
        add_settings_field(
            'salesbeat_property_checkout_hidden', __('Скрыть свойства', 'salesbeat'), [$this, 'outputFields'], $this->tabProperty, 'property',
            ['type' => 'multi_checkbox', 'name' => 'salesbeat_property_checkout_hidden[]', 'list' => $properties, 'value' => get_option('salesbeat_property_checkout_hidden')]
        );
        register_setting($this->page, 'salesbeat_property_checkout_hidden', [$this, 'validate']);
    }

    public function validate($input)
    {
        return $input;
    }

    /**
     * Выводим свойства
     */
    public function outputFields($option)
    {
        $field = '';

        switch ($option['type']) {
            case 'text':
                $field .= '<input type="' . esc_attr($option['type']) . '" name="' . esc_attr($option['name']) . '" value="' . esc_attr($option['value']) . '" class="input-text" size="80">';
                break;
            case 'multi_checkbox':
                $field .= '<div class="multicheckbox">';
                foreach ($option['list'] as $key => $value):
                    $checked = '';

                    if (is_array($option['value']))
                        $checked = in_array($key, $option['value']) ? ' checked="checked"' : '';

                    $field .= '<label><input type="checkbox" name="' . esc_attr($option['name']) . '" value="' . esc_attr($key) . '"' . $checked . '>' . $value . '</label>';
                endforeach;
                $field .= '</div>';
                break;
            case 'link':
                $field .= '<a href="' . esc_attr($option['href']) . '" class="button button-primary"' . esc_attr($option['attr']) . '>' . esc_attr($option['text']) . '</a>';
                break;
            default:
                do_action('salesbeat_admin_field_' . $option['type'], $option);
                break;
        }

        echo $field;
    }

    /**
     * Выводим страницы
     */
    public function outputView()
    {
        ob_start();
        include 'views/settings.php';
        ob_end_flush();
    }
}

return new AdminSettings();