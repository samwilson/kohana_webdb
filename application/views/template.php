<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Base site-wide template.
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="Content-Script-Type" content="text/javascript" />

		<title>
			WebDB<?php if ($database) echo ': '.WebDB_Text::titlecase($database->get_name());
			if ($table) echo ' &raquo; '.WebDB_Text::titlecase($table->get_name());
			if ($action) echo ' &raquo; '.WebDB_Text::titlecase($action) ?>

		</title>

		<?php echo HTML::style('resources/css/all.css', array('media'=>'all')) ?>

		<?php echo HTML::style('resources/css/screen.css', array('media'=>'screen')) ?>

		<?php echo HTML::style('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.0/themes/cupertino/jquery-ui.css', array('media'=>'screen')) ?>


		<?php echo HTML::script('http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js') ?>

		<?php echo HTML::script('http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/jquery-ui.min.js') ?>

		<?php echo HTML::script('resources/js/jquery.maskedinput-1.2.2.min.js') ?>

		<?php echo HTML::script('resources/js/jquery.ui.autocomplete.autoSelect.js') ?>

		<script type="text/javascript">
			$(function() {

				// Date and time masks and pickers
				$('input.time').mask('99:99');
				$('input.datetime').mask('9999-99-99 99:99');
				$('input.datepicker').mask('9999-99-99');
				$('input.datepicker').datepicker( {dateFormat: 'yy-mm-dd'} );

				// Set initial focus element
				$('#focus-me').focus();

				// Table menu display
				$(".tables ol").hide();
				$(".tables .selected").parents(".tables ol").addClass("open").show();
				$(".tables .selected").parents(".tables ol").prev().children(".ui-icon").removeClass("ui-icon-triangle-1-e").addClass("ui-icon-triangle-1-s");
				$(".tables .section-head").click(function() {
					if ($(this).next().hasClass("open")) {
						$(this).next().slideUp("fast").removeClass("open");
						$(this).children(".ui-icon").removeClass("ui-icon-triangle-1-s").addClass("ui-icon-triangle-1-e");
					} else {
						$(this).next().addClass("open").slideDown("fast");
						$(this).children(".ui-icon").removeClass("ui-icon-triangle-1-e").addClass("ui-icon-triangle-1-s");
					}
				});

			});
		</script>

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

