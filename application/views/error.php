<div class="container">
	<h1>
		Error <?= $exception->getCode() ?>:
		<small><?= $exception->getMessage() ?></small>
	</h1>

	<p>Do you need to run <a href="<?=Route::url('admin', array('action'=>'install'))?>">the installer</a>?</p>

	<?php
	if ($previous)
	{
		$view = View::factory('kohana/error');
		$view->class = get_class($previous);
		$view->code = $previous->getCode();
		$view->message = $previous->getMessage();
		$view->file = $previous->getFile();
		$view->line = $previous->getLine();
		$view->trace = $previous->getTrace();
		echo $view->render();
	}
	?>

</div>
