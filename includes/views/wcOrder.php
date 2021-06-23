<?php
/**
 * @var array $arDeliveryInfo
 */
?>
<table class="widefat fixed striped">
    <tbody>
    <?php
    foreach ($arDeliveryInfo as $field):
        if (empty($field['value']) && !is_numeric($field['value'])) continue;
        ?>
        <tr>
            <td style="width: 20%"><?= $field['name']; ?></td>
            <td style="width: 80%"><?= $field['value']; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>