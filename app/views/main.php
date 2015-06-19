<!doctype html>
<html class="no-js" lang="">
	<head>
		<meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
	</head>
	<body>
		<!-- <h1><?= $hello ?></h1> -->
		<!-- DELETE FROM HERE -->

		<?php
			$form = new Form(APP.'/form/forms/myform.json');

			$field = new Field("text");
			$field->setValue("cristian.barlutiu @gmail.com");
			$field->setValidator(array("type" => "email", "message" => "It must be a valid email address"));

			echo '<pre>';
			var_dump($field->validate());
			echo '</pre>';

			echo '<pre>';
			print_r($field->getErrors());
			echo '</pre>';

			$form->addField($field);

			echo '<pre>';
			print_r($form);
			echo '</pre>';

			echo $form->render();
		?>

		<!-- TO HERE -->
	</body>
</html>
