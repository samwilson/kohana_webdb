<?php
/**
 * This view creates paginator with limited amount of links in following way:
 * First Previous · 1 2 3 ... 22 23 24 25 26 [27] 28 29 30 31 32 ... 48 49 50 · Next Last
 *
 * @author Alexey Khrulev
 * @link http://dev.kohanaframework.org/issues/2812
 */

// Number of page links in the begin and end of whole range
$cnt_out = 3;
// Number of page links on each side of current page
$cnt_in = 5;
// Links to display as array(page_num => displayed_content)
$links = array();

// Beginning group of pages: $n1...$n2
$n1 = 1;
$n2 = min($cnt_out, $total_pages);

// Ending group of pages: $n7...$n8
$n7 = max(1, $total_pages - $cnt_out + 1);
$n8 = $total_pages;

// Middle group of pages: $n4...$n5
$n4 = max($n2 + 1, $current_page - $cnt_in);
$n5 = min($n7 - 1, $current_page + $cnt_in);
$use_middle = $n5 >= $n4;

// Point $n3 between $n2 and $n4
$n3 = (integer) (($n2 + $n4) / 2);
$use_n3 = $use_middle && (($n4 - $n2) > 1);

// Point $n6 between $n5 and $n7
$n6 = (integer) (($n5 + $n7) / 2);
$use_n6 = $use_middle && (($n7 - $n5) > 1);

// Generate links data in accordance with calculated numbers
for ($i = $n1; $i <= $n2; $i++)
	$links[$i] = $i;
if ($use_n3)
	$links[$n3] = '&hellip;';
for ($i = $n4; $i <= $n5; $i++)
	$links[$i] = $i;
if ($use_n6)
	$links[$n6] = '&hellip;';
for ($i = $n7; $i <= $n8; $i++)
	$links[$i] = $i;

?>
<p class="pagination"><em>Page:</em>

	<?php if ($first_page !== FALSE): ?>
	<a href="<?php echo $page->url($first_page) ?>" class="first"><?php echo __('First') ?></a>
	<?php else: ?>
	<span class="first disabled"><?php echo __('First') ?></span>
	<?php endif ?>

	<?php if ($previous_page !== FALSE): ?>
	<a href="<?php echo $page->url($previous_page) ?>" class="previous"><?php echo __('Previous') ?></a>
	<?php else: ?>
	<span class="previous disabled"><?php echo __('Previous') ?></span>
	<?php endif ?>

	&#183

	<?php foreach ($links as $number => $content): ?>

		<?php if ($number == $current_page): ?>
	<strong>[<?php echo $content ?>]</strong>
		<?php else: ?>
	<a href="<?php echo $page->url($number) ?>"><?php echo $content ?></a>
		<?php endif ?>

	<?php endforeach ?>

	&#183

	<?php if ($next_page !== FALSE): ?>
	<a href="<?php echo $page->url($next_page) ?>" class="next"><?php echo __('Next') ?></a>
	<?php else: ?>
	<span class="next disabled"><?php echo __('Next') ?></span>
	<?php endif ?>

	<?php if ($last_page !== FALSE): ?>
	<a href="<?php echo $page->url($last_page) ?>" class="last"><?php echo __('Last') ?></a>
	<?php else: ?>
		<span class="last disabled"><?php echo __('Last') ?></span>
	<?php endif ?>

</p><!-- .pagination -->