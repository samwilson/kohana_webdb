<form action="<?php echo Route::url('profile') ?>" method="post">

	<h2>You are logged in as <em><?php echo Auth::instance()->get_user() ?></em></h2>
	<p>You can change your password here. You will need to log in again afterwards.</p>
	<p>
		<label>New password:</label>
		<input type="password" name="password" id="focus-me" />
	</p>
	<p>
		<label>Repeat, for confirmation:</label>
		<input type="password" name="password_verification" />
	</p>
	<p>
		<input type="submit" value="Change Password" />
	</p>

</form>
