<?php

namespace Salesbeat\Inc;

defined('ABSPATH') || exit;

if (class_exists('Salesbeat\\Inc\\AdminOrders', false))
    return new AdminOrders();

class AdminOrders
{
    public function __construct()
    {
        add_filter('set-screen-option', [$this, 'setScreenOption'], 10, 3);
    }

    /**
     * Создаем настройки экрана
     */
    public function showScreenOptions()
    {
        add_screen_option('per_page', [
            'label' => __('Количество элементов на странице:', 'salesbeat'),
            'default' => 10,
            'option' => 'plance_per_page'
        ]);
    }

    /**
     * Устанавливаем настройки экрана
     * @param $status
     * @param $option
     * @param $value
     * @return mixed
     */
    public function setScreenOption($status, $option, $value)
    {
        return 'plance_per_page' == $option ? $value : $status;
    }

    /**
     * Выводим страницы
     */
    public function outputView()
    {
        ob_start();
        include 'views/orders.php';
        ob_end_flush();
    }
}

return new AdminOrders();