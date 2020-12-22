<?php
header('Content-Type: text/plain; charset=utf-8');
@ini_set('html_errors', false);
if (! empty($_POST)) {
	echo "POST decoded:\n";
	var_dump($_POST);
} else {
	echo "POST as $_SERVER[CONTENT_TYPE]:\n";
	echo file_get_contents('php://input'), "\n";
}
if (! empty($_FILES)) {
	$phpFileUploadErrors = [
		0 => 'Success',
		1 => 'PHP upload_max_filesize exceeded',
		2 => 'Form MAX_FILE_SIZE exceeded',
		3 => 'Partial uploaded',
		4 => 'No data uploaded',
		6 => 'Missing temporary folder',
		7 => 'Write error',
		8 => 'Canceled by a PHP extension',
	];
	foreach ($_FILES as $name => $file) {
		$errorText = $phpFileUploadErrors[$file['error']];
		echo "$name: $file[name] => $file[tmp_name]\n- $file[size] bytes\n- type $file[type]\n- status $errorText\n";
	}
}
