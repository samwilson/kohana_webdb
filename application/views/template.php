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
			<?= WebDB::config('site_title') ?>
			<?php
			if ($table) echo ' &raquo; '.WebDB_Text::titlecase($table->get_name());
			if ($action) echo ' &raquo; '.WebDB_Text::titlecase($action) ?>
		</title>

		<link rel="stylesheet" href="<?=Media::url('css/jquery-ui.css') ?>">
		<link rel="stylesheet" href="<?=Media::url('css/screen.css') ?>" media="screen">
		<script type="text/javascript" src="<?=Media::url('js/jquery.js')?>"></script>
		<script type="text/javascript" src="<?=Media::url('js/jquery-ui.js')?>"></script>
		<script type="text/javascript" src="<?=Media::url('js/jquery-maskedinput.js')?>"></script>
		<script type="text/javascript" src="<?=Media::url('js/jquery-ui-autocomplete-autoSelect.js')?>"></script>
		<script type="text/javascript" src="<?=Media::url('js/scripts.js')?>"></script>
	</head>


	<body class="<?php echo $controller.' '.$action ?>">

		<div class="header">
			<div class="menu menu-user"><?=Menu::factory('user')?></div>
			<h1>
				<a href="<?=URL::site()?>">
					<?= WebDB::config('site_title') ?>
				</a>
				<?php
				if ($table)
				{
					echo ' &raquo; '.HTML::anchor(
						'index/'.$table->get_name(),
						WebDB_Text::titlecase($table->get_name())
					);
				} ?>
			</h1>

			<?php /*if (count($databases) > 0): ?>
			<ol class="databases tabnav">
			<?php foreach ($databases as $db): ?>
				<?php $selected = ($database && $db==$database->get_name()) ? 'selected' : '' ?>
				<li class="<?php echo $selected ?>">
					<?php echo HTML::anchor("index/$db", WebDB_Text::titlecase($db), array('class'=>$selected)) ?>
				</li>
			<?php endforeach ?>
			</ol>
			<?php endif*/ ?>

		</div>

		<div class="not-head-foot">

			<div class="sidebar">

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
								$t = ($name!='miscellaneous') ? substr($tab->get_name(), strlen($name)) : $tab->get_name();
								echo '<li>'.HTML::anchor(
									'index/'.$tab->get_name(),
									WebDB_Text::titlecase($t),
									array('class'=>$selected)
									).'</li>';
							}
							echo '</ol></li>';
						} ?>
				</ol>
				<?php endif ?>
			</div>

			<div class="content">

				<?php if ($table): ?>
				<div class="title">
					<h1><?php echo HTML::anchor(
						'index/'.$table->get_name(),
						WebDB_Text::titlecase($table->get_name())
						) ?>
					</h1>
					<?php if ($table->get_comment()) echo '<p>'.$table->get_comment().'</p>' ?>
				</div>
				<?php endif ?>

				<?php if ($table && count($actions) > 0): ?>
				<ol class="actions small tabnav">
						<?php foreach ($actions as $action_name=>$action_title): ?>
							<?php $selected = ($action_name==$action) ? 'selected' : '' ?>
					<li class="<?php echo $selected ?>">
								<?php echo HTML::anchor(
								"$action_name/".$table->get_name().'?'.$_SERVER['QUERY_STRING'],
								"$action_title",
								array('class'=>$selected)
								) ?>
					</li>
						<?php endforeach ?>
				</ol>
				<?php endif ?>

				<?php if (isset($messages) AND count($messages) > 0): ?>
				<?php // Thanks to http://en.wikipedia.org/wiki/Template:Ambox ?>
				<ul class="messages">
					<?php foreach ($messages as $message):
						$status = $message['status'];
						$icon_url = Media::url("img/icon_$status.png");
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
				<a href="http://github.com/samwilson/kohana_webdb" title="WebDB homepage on Github">WebDB <?= WebDB::VERSION ?></a>.
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
			<?php if (Kohana::$profiling): ?>
			<li id="kohana-profiler">
				<?php echo View::factory('profiler/stats') ?>
			</li>
			<?php endif ?>
		</ol>

	</body>
</html>

