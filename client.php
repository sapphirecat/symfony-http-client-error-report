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

function generateBody(string $boundary, bool $plain = false, bool $addLength = false): Generator
{
	$fields = [
		"Content-Disposition: form-data; name=\"user\"\r\n\r\nzer0cool",
	];
	if (!$plain) {
		$fields[] = "Content-Disposition: form-data; name=\"file1\"; filename=\"data.txt\"\r\nContent-Type: text/plain\r\n\r\n".
			"Proin vestibulum pharetra luctus.\nPellentesque elit eros, ultricies at iaculis suscipit, mattis id quam.\nVivamus non nisi in arcu mollis venenatis.\nCras nec purus sagittis.\n";
	}

	foreach ($fields as $text) {
		if (!strlen($text)) {
			continue;
		}

		// no trailing "\r\n" here; it's being added automatically.  but we need
		// to account for that in the length header.
		$packet = "--$boundary\r\n$text";
		if ($addLength) {
			$packet = (2 + strlen($packet))."\r\n".$packet;
		} else {
			$packet .= "\r\n";
		}

		yield $packet;
	}

	// add final boundary, then terminate chunked encoding with 0-byte trailer
	$packet = "--$boundary--";
	if ($addLength) {
		$packet = (2 + strlen($packet))."\r\n".$packet;
		yield $packet."\r\n0";
	} else {
		yield $packet."\r\n";
	}
}

function getTextBody(iterable $source): string
{
	$body = '';
	foreach ($source as $chunk) {
		$body .= $chunk;
	}
	return $body;
}



$options = getopt('cEh:ps', ['curl', 'encode-chunked', 'target:', 'plain', 'stringify']);
$options = simplifyOptions($options, ['curl' => 'c', 'plain' => 'p', 'stringify' => 's', 'encode-chunked' => 'E']);
if (isset($options['t']) && !isset($options['target'])) {
	$options['target'] = $options['t'];
} elseif (!isset($options['target'])) {
	$options['target'] = 'https://httpbin.org/anything';
}
if ($options['encode-chunked'] && $options['stringify']) {
	error_log("encode-chunked takes priority over stringify");
	$options['stringify'] = false;
}

$client = $options['curl'] ? new CurlHttpClient() : new NativeHttpClient();

try {
	$boundary = hash('sha512/256', random_bytes(32));
} catch (Exception $e) {
	error_log("Your random generator is broken: {$e->getMessage()}");
	exit(99);
}

$bodyIter = generateBody($boundary, $options['plain'], $options['encode-chunked']);
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
