<ol class="stages">
	<?php $class = 'completed' ?>
	<?php foreach ($stages as $s_pos=>$s_name): ?>
		<?php if ($stage==$s_name): ?>
	<li class="selected">
				<?php $class = '' ?>
		<strong><?php echo WebDB_Text::titlecase($s_name) ?></strong>
			<?php else: ?>
	<li class="<?php echo $class ?>">
				<?php echo WebDB_Text::titlecase($s_name) ?>
			<?php endif ?>
	</li>
	<?php endforeach ?>
</ol>





<?php /** Stage 1: Choose File *********************************************/ ?>
<?php if ($stage == 'choose_file'): ?>
<form action="<?php echo $form_action ?>" enctype="multipart/form-data" method="post">
	<fieldset>
		<p>
			<?php echo Form::label('file', 'Choose a CSV file (with column headers) to import:') ?><br />
			<?php echo Form::file('file',array('size'=>80)) ?>
		</p>
		<p>
			<input type="submit" name="upload" value="Upload &rarr;"/>
		</p>
	</fieldset>
</form>



<?php /** Stage 2: Match Fields ********************************************/ ?>
<?php elseif ($stage == 'match_fields'): ?>
<form action="<?php echo $form_action ?>" method="post">
	<fieldset>
		<p>
			Your file contains <?php echo $file->row_count() ?> rows.
			<strong>The first row has been skipped</strong> (it should contain column headers).
		</p>
		<p>Match up fields in the database with fields in the uploaded file.</p>
		<table>
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
			</tbody>
		</table>
		<p><input type="submit" name="preview" value="Preview &rarr;"/></p>
	</fieldset>
</form>



<?php /** Stage 3: Preview *************************************************/ ?>
<?php elseif ($stage == 'preview'): ?>
<form action="<?php echo $form_action ?>" method="post">
	<fieldset>
		<?php if (count($errors) > 0): ?>
			<p>
				The following incompatibilities were found in the data.
				Please correct and import again.
			</p>
			<p>
				The row numbers <em>do not</em> include the header row,
				and the column numbers start from one.
			</p>
			<p>
				<input type="submit" name="match_fields" value="&larr; Return to field matching"/>
				<a href="<?php echo $import_url ?>">Start import again</a>
			</p>
			<table>
				<thead>
					<tr>
						<th rowspan="2">Row</th>
						<th colspan="2">Columns</th>
						<th rowspan="2">Error</th>
					</tr>
					<tr>
						<th>Database Column</th><th>Uploaded File Column</th>
					</tr>
				</thead>
			<?php foreach ($errors as $error): ?>
				<tr>
					<td><?php echo $error['row_number'] ?></td>
					<td><?php echo WebDB_Text::titlecase($error['field_name']) ?></td>
					<td><?php echo 'Column #'.($error['column_number']+1).': '.$error['column_name'] ?></td>
					<td><?php echo join('<br />', $error['messages']) ?></td>
				</tr>
			<?php endforeach ?>
			</table>
		<?php else: ?>
			<p class="info message">All data is valid and ready to import.</p>
			<p>
				<input type="hidden" name="columns" value='<?php echo $columns ?>' >
				<input type="submit" name="match_fields" value="&larr; Return to field matching"/>
				<input type="submit" name="import" value="Import &rarr;"/>
			</p>
		<?php endif ?>
	</fieldset>
</form>
<?php endif ?>




