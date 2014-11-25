<?php
/**
 * This file is the same as MODPATH/menu/views/templates/menu/default.php but
 * with full PHP tags.
 *
 * It can be removed when https://github.com/anroots/kohana-menu/issues/4
 * is resolved.
 */
?>
<nav>
	<ul>
		<?php foreach ($menu->get_visible_items() as $item): ?>
			<li class="<?= $item->get_classes() ?>">
				<?= (string) $item ?>
			</li>
		<?php endforeach ?>
	</ul>
</nav>
