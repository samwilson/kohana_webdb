<h2>Please log in</h2>

<?php echo form::open() ?>

<p><?php echo form::label('username','Username:')?>
	<?php echo form::input('username', '', array('id'=>'focus-me')) ?></p>

<p><?php echo form::label('password','Password:')?>
	<?php echo form::password('password') ?></p>

<p><?php echo form::submit('login', 'Login') ?></p>


<?php echo form::close() ?>

