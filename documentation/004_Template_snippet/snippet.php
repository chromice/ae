<?php

if (empty($data) || !is_array($data))
{
    return;
}

?>
<dl>
<?php foreach ($data as $term => $definition): ?>
    <dt><?= $term ?></dt>
    <dd><?= $definition ?></dd>
<?php endforeach ?>
</dl>
