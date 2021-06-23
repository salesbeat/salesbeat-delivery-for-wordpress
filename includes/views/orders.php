<?php
$table = new Salesbeat\Inc\AdminOrdersTable();
$table->prepare_items();
?>
<div class="wrap" id="tabs">
    <h1><?= get_admin_page_title() ?></h1>

    <form method="get">
        <input type="hidden" name="page" value="<?= __CLASS__ ?>"/>
        <?php
        $table->search_box('search', 'search_id');
        $table->display();
        ?>
    </form>

    <script type="text/javascript">
        window.addEventListener('load', () => {
            SalesbeatAdminOrders.init();
        });
    </script>
</div>