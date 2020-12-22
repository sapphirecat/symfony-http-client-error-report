<?php

use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

require_once __DIR__ . '/vendor/autoload.php';


function simplifyOptions(array $options, array $aliases): array
{
	foreach ($aliases as $long => $short) {
		$options[$long] = isset($options[$long]) || isset($options[$short]);
	}
	return $options;
}

function generateBody(string $boundary, bool $plain = false): Generator
{
	$fields = [
		"Content-Disposition: form-data; name=\"user\"\r\n\r\nzer0cool",
	];
	if (!$plain) {
		$fields[] = "Content-Disposition: form-data; name=\"file1\"; filename=\"data.txt\"\r\nContent-Type: text/plain\r\n\r\n".
			"Proin vestibulum pharetra luctus.\nPellentesque elit eros, ultricies at iaculis suscipit, mattis id quam.\nVivamus non nisi in arcu mollis venenatis.\nCras nec purus sagittis.\n";
	}

	foreach ($fields as $text) {
		yield "--$boundary\r\n$text\r\n";
	}

	yield "--$boundary--\r\n";
}

function getTextBody(iterable $source): string
{
	$body = '';
	foreach ($source as $chunk) {
		$body .= $chunk;
	}
	return $body;
}



$options = getopt('ch:ps', ['curl', 'target:', 'plain', 'stringify']);
$options = simplifyOptions($options, ['curl' => 'c', 'plain' => 'p', 'stringify' => 's']);
if (isset($options['t']) && !isset($options['target'])) {
	$options['target'] = $options['t'];
} elseif (!isset($options['target'])) {
	$options['target'] = 'https://httpbin.org/anything';
}

$client = $options['curl'] ? new CurlHttpClient() : new NativeHttpClient();

try {
	$boundary = hash('sha512/256', random_bytes(32));
} catch (Exception $e) {
	error_log("Your random generator is broken: {$e->getMessage()}");
	exit(99);
}

$bodyIter = generateBody($boundary, $options['plain']);
try {
	$response = $client->request(
		'POST',
		$options['target'],
		[
			'headers' => [
				'Content-Type' => "multipart/form-data; boundary=$boundary",
			],
			'body' => $options['stringify'] ? getTextBody($bodyIter) : $bodyIter,
		]
	);

	if ($response->getStatusCode() >= 300) {
		error_log("HTTP status {$response->getStatusCode()}");
	}

	// we are passing $throw=false
	/** @noinspection PhpUnhandledExceptionInspection */
	echo $response->getContent(false), PHP_EOL;

} catch (TransportExceptionInterface $e) {
	error_log("Request failure: {$e->getMessage()}");
	exit(1);
}
