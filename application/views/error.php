<div class="container">
	<h1>
		Error <?= $exception->getCode() ?>:
		<small><?= $exception->getMessage() ?></small>
	</h1>

	<p>Do you need to run <a href="<?=Route::url('admin', array('action'=>'install'))?>">the installer</a>?</p>

	<?php
	if ($previous)
	{
		echo View::factory('kohana/error')
				->bind('class', $class = get_class($previous))
				->bind('code', $code = $previous->getCode())
				->bind('message', $message = $previous->getMessage())
				->bind('file', $file = $previous->getFile())
				->bind('line', $line = $previous->getLine())
				->bind('trace', $trace = $previous->getTrace())
				->render();
	}
	?>

</div>
