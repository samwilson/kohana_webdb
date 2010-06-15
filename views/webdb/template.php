<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Base site-wide template.
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>

        <title>
            WebDB
			<?php if ($database) echo ': '.Webdb_Text::titlecase($database->get_name()) ?>
			<?php if ($table) echo ' &raquo; '.Webdb_Text::titlecase($table->get_name()) ?>
			<?php if ($action) echo ' &raquo; '.Webdb_Text::titlecase($action) ?>
        </title>

        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="Content-Script-Type" content="text/javascript" />

		<?php echo html::style('webdb/resources/css/all.css', array('media'=>'all')) ?>

		<?php echo html::style('webdb/resources/css/screen.css', array('media'=>'screen')) ?>

		<?php echo html::style('webdb/resources/css/jquery/jquery-ui-1.8.custom.css', array('media'=>'screen')) ?>

		<?php echo html::script('webdb/resources/js/jquery-1.4.2.min.js') ?>

		<?php echo html::script('webdb/resources/js/jquery-ui-1.8.custom.min.js') ?>

		<?php echo html::script('webdb/resources/js/jquery.maskedinput-1.2.2.min.js') ?>

        <script type="text/javascript">
            $(function() {

                // Date and time masks and pickers
                $('input.time').mask('99:99');
                $('input.datetime').mask('9999-99-99 99:99');
                $('input.datepicker').mask('9999-99-99');
                $('input.datepicker').datepicker( {dateFormat: 'yy-mm-dd'} );

				// Set initial focus element
				$('#focus-me').focus();
            });
        </script>

    </head>


    <body class="<?php echo $controller.' '.$action ?>">

        <div class="header">
			<p class="auth">
				<?php if (auth::instance()->logged_in()): ?>
				Logged in as <?php echo auth::instance()->get_user() ?>.
					<?php echo html::anchor('webdb/logout','[Log out]') ?>
				<?php else: ?>
					<?php echo html::anchor('webdb/login','[Log in]') ?>
				<?php endif ?>
			</p>
            <h1>
				<?php echo html::anchor('webdb', 'WebDB') ?>
				<?php if ($database)
				{
					echo ' :: '.html::anchor(
						'webdb/index/'.$database->get_name(),
						Webdb_Text::titlecase($database->get_name())
					);
				}
				if ($table)
				{
					echo ' &raquo; '.html::anchor(
						'webdb/index/'.$database->get_name().'/'.$table->get_name(),
						Webdb_Text::titlecase($table->get_name())
					);
				} ?>
            </h1>

			<?php if (count($databases) > 0): ?>
            <ol class="databases tabnav">
					<?php foreach ($databases as $db): ?>
						<?php $selected = ($database && $db==$database->get_name()) ? 'selected' : '' ?>
                <li class="<?php echo $selected ?>">
							<?php echo html::anchor("webdb/index/$db", Webdb_Text::titlecase($db),
							array('class'=>$selected)) ?>
                </li>
					<?php endforeach ?>
            </ol>
			<?php endif ?>

        </div>

		<div class="not-head-foot">

			<?php if (count($tables) > 0): ?>
			<ol class="tables">
					<?php $table_names = array_keys($tables);
					asort($table_names);
					foreach ($table_names as $tab): ?>
						<?php $selected = ($table && $tab==$table->get_name()) ? 'selected' : '' ?>
				<li>
							<?php echo html::anchor(
							'webdb/index/'.$database->get_name().'/'.$tab,
							Webdb_Text::titlecase($tab),
							array('class'=>$selected)
							) ?>
				</li>
					<?php endforeach ?>
			</ol>
			<?php endif ?>

			<div class="content">

				<?php if ($table): ?>
				<h1>
						<?php echo html::anchor(
						'webdb/index/'.$database->get_name().'/'.$table->get_name(),
						Webdb_Text::titlecase($table->get_name())
						) ?>
				</h1>
				<?php endif ?>

				<?php if ($database && $table && count($actions) > 0): ?>
				<ol class="actions small tabnav">
						<?php foreach ($actions as $action_name=>$action_title): ?>
							<?php $selected = ($action_name==$action) ? 'selected' : '' ?>
					<li class="<?php echo $selected ?>">
								<?php echo html::anchor(
								"$controller/$action_name/".$database->get_name().'/'.$table->get_name().'/',
								"$action_title",
								array('class'=>$selected)
								) ?>
					</li>
						<?php endforeach ?>
				</ol>
				<?php endif ?>

				<?php if (count($messages) > 0): ?>
					<?php // Thanks to http://en.wikipedia.org/wiki/Template:Ambox ?>
				<ul>
						<?php foreach ($messages as $message):
							$status = $message['status'];
							$icon_url = url::base()."webdb/resources/img/icon_$status.png";
							?>
					<li class="<?php echo $status ?> message"
						style="background-image: url('<?php echo $icon_url ?>');
						background-repeat:no-repeat; background-position: left center">
								<?php echo $message['message'] ?>
					</li>
						<?php endforeach ?>
				</ul>
				<?php endif ?>


				<?php echo $content ?>

			</div>

			<div style="clear:both">&nbsp;</div>
		</div>



		<ol class="footer">
			<li>Thank you for using
				<?php echo html::anchor('http://github.com/samwilson/kohana_webdb', 'WebDB') ?>
				.  Please report any bugs or feature requests through
				<?php echo html::anchor('http://github.com/samwilson/kohana_webdb/issues', 'Github') ?>.
			</li>
			<!--li>
				&copy; <a xmlns:cc="http://creativecommons.org/ns#"
						  href="http://github.com/samwilson"
						  property="cc:attributionName" rel="cc:attributionURL">Sam Wilson</a>
				2008&ndash;<?php echo date('Y') ?>.
				Released under the
				<a rel="license" href="http://opensource.org/licenses/bsd-license.php">
					Simplified BSD License</a>.
			</li-->
			<li>
					Built on
				<?php echo html::anchor('/guide', 'Kohana', array('title'=>'View the User Guide')) ?>
				<?php echo Kohana::VERSION ?>
				<dfn title="Kohana codename"><?php echo Kohana::CODENAME ?></dfn>.
					Currently in <?php echo Kohana::$environment ?> mode.
			</li>
			<?php if (Kohana::$environment == Kohana::DEVELOPMENT): ?>
			<li id="kohana-profiler">
					<?php echo View::factory('profiler/stats') ?>
			</li>
			<?php endif ?>
		</ol>

	</body>
</html>

