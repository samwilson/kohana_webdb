<html>
	<head>
		<title></title>
	</head>
	<body>
		<h1>WebDB</h1>

		<ul id="databases">
			<?php foreach ($databases as $db): ?>
			<li><?php echo html::anchor("webdb/index/$db->Database", text::titlecase($db->Database)) ?></li>
			<?php endforeach ?>
		</ul>

		<?php if ($tables): ?>
		<ul id="tables">
			<?php foreach ($tables as $tab): ?>
			<li><?php echo html::anchor("webdb/browse/$database/$tab", text::titlecase($tab)) ?></li>
			<?php endforeach ?>
		</ul>
		<?php endif ?>

	</body>

</html>


