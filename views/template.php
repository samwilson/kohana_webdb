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
			<?php if ($dbname) echo ': '.text::titlecase($dbname) ?>
			<?php if ($tablename) echo ' &raquo; '.text::titlecase($tablename) ?>
			<?php if ($action) echo ' &raquo; '.text::titlecase($action) ?>
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

            });
        </script>

    </head>


    <body class="<?php echo $controller.' '.$action ?>">

        <div class="header">
            <h1>
                WebDB
				<?php if ($dbname) echo ':: '.html::anchor("webdb/index/$dbname",text::titlecase($dbname)) ?>
				<?php if ($tablename) echo ' &raquo; '.html::anchor("webdb/index/$dbname/$tablename",text::titlecase($tablename)) ?>
            </h1>

			<?php if (count($databases) > 0): ?>
            <ol class="databases tabs">
					<?php foreach ($databases as $database): ?>
						<?php $selected = ($dbname && $database==$dbname) ? 'selected' : '' ?>
                <li>
							<?php echo html::anchor("webdb/index/$database", text::titlecase($database),
							array('class'=>$selected)) ?>
                </li>
					<?php endforeach ?>
            </ol>
			<?php endif ?>

        </div>

		<?php if ($dbname && count($tables) > 0): ?>
		<ol class="tables">
				<?php foreach ($tables as $tab): ?>
					<?php $selected = ($tablename && $tab==$tablename) ? 'selected' : '' ?>
			<li>
						<?php echo html::anchor("webdb/index/$dbname/$tab", text::titlecase($tab),
						array('class'=>$selected)) ?>
			</li>
				<?php endforeach ?>
		</ol>
		<?php endif ?>

		<?php /*if (count($actions) > 0): ?>
        <ol class="actions tabs">
				<?php foreach ($actions as $action_name=>$action_title): ?>
					<?php $selected = ($action_name==$action) ? 'selected' : '' ?>
            <li><?php echo html::anchor(
						"$controller/$action_name/$dbname/$tablename/",
						"$action_title",
						array('class'=>$selected)
						) ?></li>
				<?php endforeach ?>
        </ol>
		<?php endif*/ ?>

		<div class="content">

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




		<ol class="footer">
			<li>Please report any bugs or feature requests through
				<?php echo html::anchor('http://github.com/samwilson/kohana_webdb/issues', 'Github') ?>.
			</li>
			<li>
				<span xmlns:dc="http://purl.org/dc/elements/1.1/"
					  href="http://purl.org/dc/dcmitype/InteractiveResource"
					  property="dc:title"
					  rel="dc:type">WebDB</span>
				by <a xmlns:cc="http://creativecommons.org/ns#"
					  href="http://github.com/samwilson/kohana_webdb"
					  property="cc:attributionName" rel="cc:attributionURL">Sam Wilson</a>
				is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.5/au/">
					Creative Commons Attribution-Share Alike 2.5 Australia License
				</a>
				<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.5/au/">
					<img alt="Creative Commons License" src="http://i.creativecommons.org/l/by-sa/2.5/au/80x15.png" />
				</a>
			</li>
			<li>
					Built on
				<?php echo html::anchor('/guide', 'Kohana', array('title'=>'View the User Guide')) ?>
				<?php echo Kohana::VERSION ?>
				<dfn title="Kohana codename"><?php echo Kohana::CODENAME ?></dfn>.
					Currently in <?php echo Kohana::$environment ?> mode.
			</li>
		</ol>

	</body>
</html>

