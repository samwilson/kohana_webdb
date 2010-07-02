<form action="<?php echo URL::site('webdb/login') ?>" method="post" class="login-form">
	<table>
		<thead>
			<tr>
				<th colspan="2">Please log in</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th><?php echo form::label('username','Username:')?></th>
				<td><?php echo form::input('username', NULL, array('id'=>'focus-me')) ?></td>
			</tr>
			<tr>
				<th><?php echo form::label('password','Password:')?></th>
				<td><?php echo form::password('password') ?></td>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<th>
					<input type="hidden" name="return_to" value="<?php echo $return_to ?>"
					<a href="<?php echo $return_to ?>">Cancel</a>
				</th>
				<th>
					<input type="submit" name="login" value="Log in" />
				</th>
			</tr>
		</tfoot>
	</table>
</form>

