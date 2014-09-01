<h1>Install</h1>

<form method="post" action="<?= Route::url('admin', array('action' => 'install')) ?>" class="">
	<label>
		Install or upgrade all required database tables:
		<input type="submit" name="install" value="Install" />
	</label>
</form>