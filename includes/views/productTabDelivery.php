<h2>Доставка</h2>
<?php
global $product;
do_action('salesbeat_product_widget', wc_get_product($product->get_id()));
?>
