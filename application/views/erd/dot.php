<?php defined('SYSPATH') OR die('No direct script access.') ?>
digraph <?php echo $database->get_name() ?>_ERD {
	rankdir=LR
	node [shape=none, fontsize=12];
<?php foreach ($database->get_tables() as $table): ?>

	<?php
	if ( ! in_array($table->get_name(), $selected_tables)) continue;
	echo $table->get_name()." [label=<<TABLE CELLBORDER=\"1\" CELLSPACING=\"0\" BORDER=\"0\">\n\t\t";
	echo "<TR><TD ALIGN=\"CENTER\"><FONT POINT-SIZE=\"16\"><B>".WebDB_Text::titlecase($table->get_name())."</B></FONT></TD></TR>";
	$cols = array();
	foreach ($table->get_columns() as $col)
	{
		$c = '<TD PORT="'.$col->get_name().'" ALIGN="LEFT">'.WebDB_Text::titlecase($col->get_name());
			//.' '.$col->get_type();
		//if ($size = $col->get_size()) $c .= '('.$size.')';
		$cols[] = $c."</TD>";
	}
	echo "<TR>".join("</TR>\n\t\t<TR>", $cols)."</TR>";
	echo "\n\t\t</TABLE>>];\n\t";

	foreach ($table->get_columns() as $col)
	{
		if ($col->is_foreign_key() AND in_array($col->get_referenced_table()->get_name(), $selected_tables))
		{
			echo $table->get_name().':'.$col->get_name();
			echo ' -> ';
			echo $col->get_referenced_table()->get_name().':'.$col->get_referenced_table()->get_pk_column()->get_name();
			echo ";\n\t";
		}
	}
	?>

<?php endforeach ?>

}

