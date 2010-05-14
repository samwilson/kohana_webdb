

<ol class="stages">
	<?php $class = 'completed' ?>
	<?php foreach ($stages as $s_pos=>$s_name): ?>
		<?php if ($stage==$s_name): ?>
	<li class="selected">
				<?php $class = '' ?>
		<strong><?php echo text::titlecase($s_name) ?></strong>
			<?php else: ?>
	<li class="<?php echo $class ?>">
				<?php echo text::titlecase($s_name) ?>
			<?php endif ?>
	</li>
	<?php endforeach ?>
</ol>

<?php if ($errors): ?>
<p class="info message">Some errors were encountered, please check the details you entered.</p>
<ul>
		<?php foreach ($errors as $message): ?>
    <li class="notice message"><?php echo $message ?></li>
		<?php endforeach ?>
</ul>
<?php endif ?>

<?php if ($stage == 'choose_file'): ?>

	<?php echo form::open(
	'webdb/import/'.$database->get_name().'/'.$table->get_name(),
	array('enctype'=>'multipart/form-data')
	) ?>
<fieldset>
	<p>
			<?php echo form::label('file', 'Select a CSV file to import:') ?><br />
			<?php echo form::file('file') ?>
	</p>
	<p>
			<?php echo form::submit('upload', 'Upload') ?>
	</p>
</fieldset>

	<?php echo form::close() ?>

<?php endif ?>


