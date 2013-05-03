<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Base site-wide template.
 */
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset='utf-8'>
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="Content-Script-Type" content="text/javascript" />

		<title>
			WebDB<?php if ($database) echo ': '.WebDB_Text::titlecase($database->get_name());
			if ($table) echo ' &raquo; '.WebDB_Text::titlecase($table->get_name());
			if ($action) echo ' &raquo; '.WebDB_Text::titlecase($action) ?>

		</title>

		<?php echo HTML::style('resources/css/jquery-ui.css', array('media'=>'screen')) ?>

		<?php echo HTML::style('resources/css/all.css', array('media'=>'all')) ?>

		<?php echo HTML::style('resources/css/screen.css', array('media'=>'screen')) ?>

		<?php echo HTML::script('resources/js/jquery.js') ?>

		<?php echo HTML::script('resources/js/jquery-ui.js') ?>

		<?php echo HTML::script('resources/js/jquery-maskedinput.js') ?>

		<?php echo HTML::script('resources/js/jquery-ui-autocomplete-autoSelect.js') ?>

		<?php echo HTML::script('resources/js/scripts.js') ?>

	</head>


	<body class="<?php echo $controller.' '.$action ?>">

		<div class="header">
			<p class="auth">
				<?php if (Auth::instance()->logged_in()): ?>
				Logged in as <?php echo Auth::instance()->get_user() ?>.
					<?php echo HTML::anchor('logout','[Log out]') ?>
				<?php else: ?>
					<?php echo HTML::anchor('login?return_to='.urlencode(URL::base(Request::current())),'[Log in]') ?>
				<?php endif ?>
			</p>
			<h1>
				<?php echo HTML::anchor('', 'WebDB') ?>
				<?php if ($database)
				{
					echo ' :: '.HTML::anchor(
						'index/'.$database->get_name(),
						WebDB_Text::titlecase($database->get_name())
					);
				}
				if ($table)
				{
					echo ' &raquo; '.HTML::anchor(
						'index/'.$database->get_name().'/'.$table->get_name(),
						WebDB_Text::titlecase($table->get_name())
					);
				} ?>
            </h1>

			<?php if (count($databases) > 0): ?>
            <ol class="databases tabnav">
					<?php foreach ($databases as $db): ?>
						<?php $selected = ($database && $db==$database->get_name()) ? 'selected' : '' ?>
                <li class="<?php echo $selected ?>">
							<?php echo HTML::anchor("index/$db", WebDB_Text::titlecase($db),
							array('class'=>$selected)) ?>
                </li>
					<?php endforeach ?>
            </ol>
			<?php endif ?>

        </div>

		<div class="not-head-foot">

			<?php if (count($tables) > 0): ?>
			<ol class="tables">
					<?php
					ksort($tables);
					foreach ($tables as $name=>$group)
					{
						if (count($group)<1) continue;
						echo '<li class=".ui-state-default"><em class="section-head">'
							.'<span class="ui-icon ui-icon-triangle-1-e"></span>'
							.WebDB_Text::titlecase($name)
							.'</em><ol>';
						ksort($group);
						foreach ($group as $tab)
						{
							$selected = ($table && $tab->get_name()==$table->get_name()) ? 'selected' : '';
							$title = ($name!='miscellaneous') ? substr($tab->get_name(), strlen($name)) : $tab->get_name();
							echo '<li>'.HTML::anchor(
								'index/'.$database->get_name().'/'.$tab->get_name(),
								WebDB_Text::titlecase($title),
								array('class'=>$selected)
								).'</li>';
						}
						echo '</ol></li>';
					} ?>
			</ol>
			<?php endif ?>

			<div class="content">

				<?php if ($table): ?>
				<div class="title">
					<h1><?php echo HTML::anchor(
							'index/'.$database->get_name().'/'.$table->get_name(),
							WebDB_Text::titlecase($table->get_name())
							) ?>
					</h1>
						<?php if ($table->get_comment()) echo '<p>'.$table->get_comment().'</p>' ?>
				</div>
				<?php endif ?>

				<?php if ($database && $table && count($actions) > 0): ?>
				<ol class="actions small tabnav">
						<?php foreach ($actions as $action_name=>$action_title): ?>
							<?php $selected = ($action_name==$action) ? 'selected' : '' ?>
					<li class="<?php echo $selected ?>">
								<?php echo HTML::anchor(
								"$action_name/".$database->get_name().'/'.$table->get_name().'?'.$_SERVER['QUERY_STRING'],
								"$action_title",
								array('class'=>$selected)
								) ?>
					</li>
						<?php endforeach ?>
				</ol>
				<?php endif ?>

				<?php if (count($messages) > 0): ?>
					<?php // Thanks to http://en.wikipedia.org/wiki/Template:Ambox ?>
				<ul class="messages">
						<?php foreach ($messages as $message):
							$status = $message['status'];
							$icon_url = URL::site("resources/img/icon_$status.png");
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
				<a href="http://github.com/samwilson/kohana_webdb" title="WebDB homepage on Github">WebDB</a>.
				Please report any bugs or feature requests through the
				<a href="http://github.com/samwilson/kohana_webdb/issues" title="Github issue tracker">issue tracker</a>.
			</li>
			<li>
				Released under the
				<a rel="license" href="https://github.com/samwilson/kohana_webdb#simplified-bsd-license">
					Simplified BSD License</a>.
				Built on <a href="http://kohanaframework.org/" title="Go to the Kohana homepage">Kohana</a>
				<?php echo Kohana::VERSION ?>
				<dfn title="Kohana codename"><?php echo Kohana::CODENAME ?></dfn>.
			</li>
			<?php if (Kohana::$environment != Kohana::PRODUCTION): ?>
			<li id="kohana-profiler">
					<?php echo View::factory('profiler/stats') ?>
			</li>
			<?php endif ?>
		</ol>

	</body>
</html>

