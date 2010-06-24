<ol class="stages">
	<?php $class = 'completed' ?>
	<?php foreach ($stages as $s_pos=>$s_name): ?>
		<?php if ($stage==$s_name): ?>
	<li class="selected">
				<?php $class = '' ?>
		<strong><?php echo Webdb_Text::titlecase($s_name) ?></strong>
			<?php else: ?>
	<li class="<?php echo $class ?>">
				<?php echo Webdb_Text::titlecase($s_name) ?>
			<?php endif ?>
	</li>
	<?php endforeach ?>
</ol>






<?php /*if ($errors): ?>
<p class="info message">Some errors were encountered, please check the details you entered.</p>
<ul>
		<?php foreach ($errors as $message): ?>
    <li class="notice message"><?php echo $message ?></li>
		<?php endforeach ?>
</ul>
<?php endif*/ ?>



<?php /** Stage 1: Choose File *********************************************/ ?>
<?php if ($stage == 'choose_file'): ?>
<form action="<?php echo url::site('webdb/import/'.$database->get_name().'/'.$table->get_name()) ?>"
	  enctype="multipart/form-data" method="post">
	<fieldset>
		<p>
			<?php echo form::label('file', 'Select a CSV file to import:') ?><br />
			<?php echo form::file('file',array('size'=>80)) ?>
		</p>
		<p>
			<?php echo form::submit('upload', 'Upload') ?>
		</p>
	</fieldset>
</form>



<?php /** Stage 2: Match Fields ********************************************/ ?>
<?php elseif ($stage == 'match_fields'): ?>
<form action="<?php echo url::site('webdb/import/'.$database->get_name().'/'.$table->get_name().'/'.$file->hash) ?>"
	  method="post">
<table>
	<caption>Match up fields in the database<br/>with fields in the uploaded file.</caption>
	<thead>
		<tr>
			<th>Database</th>
			<th>Uploaded File</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($table->get_columns() as $column): ?>
		<tr>
			<td><?php echo Webdb_Text::titlecase($column->get_name()) ?></td>
			<td>
				<?php $options = array(''=>'') + $file->headers;
				$options = array_combine(array_map('strtolower', $options), $options);
				echo Form::select('columns['.$column->get_name().']', $options, strtolower(Webdb_Text::titlecase($column->get_name()))) ?>
			</td>
		</tr>
		<?php endforeach ?>
	<tbody>
	<tfoot>
		<tr>
			<td colspan="2" class="submit">
				<input type="submit" name="preview" value="Preview &rarr;"/>
			</td>
		</tr>
	</tfoot>
</table>
</form>



<?php /** Stage 2: Preview *************************************************/ ?>
<?php elseif ($stage == 'preview'): ?>
	<?php //echo Kohana::debug($_POST); echo Kohana::debug($file->data); exit(); ?>
<table>
	<caption>The following data will be imported.</caption>
	<thead>
		<tr>
			<?php foreach ($table->get_columns() as $column): ?>
			<th><?php echo Webdb_Text::titlecase($column->get_name()) ?></th>
			<?php endforeach ?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($file->data as $row): ?>
		<tr>
			<?php foreach ($table->get_columns() as $column): ?>
			<td>
				<?php echo (isset($row[$column->get_name()])) ? $row[$column->get_name()] : $column->get_default() ?>
			</td>
			<?php endforeach ?>
		</tr>
		<?php endforeach ?>
	</tbody>
</table>


<?php endif ?>




