<div class="wrap" id="tabs">
    <h1><?= get_admin_page_title() ?></h1>

    <form method="post" action="options.php">
        <?php
        $arTabs = [
            'system' => __('Настройки'),
            'pay_system' => __('Платежные системы'),
            'property' => __('Свойства заказа')
        ];
        ?>
        <div class="nav-tab-wrapper" data-tabs-list>
            <?php
            foreach ($arTabs as $key => $value):
                $selected = $key == key($arTabs) ? ' nav-tab-active' : '';
                ?>
                <a href="?page=sb_settings&tab=<?= $key ?>" class="nav-tab<?= $selected; ?>"
                   data-tab="salesbeat" data-tab-id="<?= $key ?>">
                    <?= $value; ?>
                </a>
            <?php
            endforeach;
            ?>
        </div>

        <div class="con-tab-wrapper">
            <?php
            $setting = new Salesbeat\Inc\AdminSettings;

            settings_fields($setting->page);
            foreach ($arTabs as $key => $value):
                $selected = $key == key($arTabs) ? ' is-active' : '';
                ?>
                <div class="tabs-panel<?= $selected; ?>" data-tab-content="salesbeat"
                     data-tab-content-id="<?= $key; ?>">
                    <?php
                    switch ($key):
                        case 'system':
                            \do_settings_sections($setting->tabSystem);
                            break;
                        case 'pay_system':
                            \do_settings_sections($setting->tabPaySystem);
                            break;
                        case 'property':
                            \do_settings_sections($setting->tabProperty);
                            break;
                        default:
                            echo 'Не известный таб';
                            break;
                    endswitch;
                    ?>
                </div>
                <?php
            endforeach;
            \submit_button();
            ?>
        </div>
    </form>
</div>
<script type="text/javascript">
    window.addEventListener('load', () => {
        SalesbeatAdminSettings.init();
    });
</script>