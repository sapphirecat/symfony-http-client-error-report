# symfony/http-client error report

Demonstration of differential behavior between the Symfony CurlHttpClient and NativeHttpClient.

# Test Cases

## Problems

    $ php client.php --curl --target http://{apache}/show-post.php
    $ php client.php --curl --target http://{apache}/show-post.php --plain
    $ php client.php --curl --target http://{apache}/show-post.php --encode-chunked
    $ php client.php --curl --target https://httpbin.org/anything --encode-chunked

Neither form data nor file attachment (when not using `--plain`) are received.  `php://input` is also empty.

## Successes

    $ php client.php --target http://{apache}/show-post.php
    $ php client.php --curl --target http://{apache}/show-post.php --stringify
    $ php client.php --curl --target http://{apache}/show-post.php --encode-chunked --stringify
    $ php client.php --curl --target http://{php-standalone}/show-post.php
    $ php client.php --curl --target http://{php-standalone}/show-post.php --encode-chunked
    $ php client.php --curl --target https://httpbin.org/anything

Form data and file attachment are both reported as successfully received.

# Options

- `-t|--target {URL}` sets the destination for the POST request.
  The default is httpbin.org's /anything endpoint.
- `-c|--curl` uses the CurlHttpClient instead of the default NativeHttpClient.
- `-p|--plain` suppresses the file attachment, sending only form data in `multipart/form-data` format.
- `-E|--encode-chunked` includes chunk length headers in the Generator output.
- `-s|--stringify` pre-processes the chunk data into one string to pass as the request body.

`--stringify` overrules `--encode-chunked` if both are given, as the former
produces a non-chunked request.
I expect `--encode-chunked` to be the incorrect way to generate chunks in practice,
but it has been included for completeness.

AFAICT, BrowserKit ends up using a Generator as body when it calls the HttpClient.

# Backstory

I had a repository using Mink to drive a website to automate some data transfer.

It was using `behat/mink-goutte-driver`, but PHP 8.0 support was not guaranteed at the time.
I changed it to `behat/mink-browserkit-driver` with `symfony/http-client` and `symfony/mime`,
which was intended to be an unobservable change.
Some time later, someone noticed that the data flow had stopped.

(In general, I want to get away from Goutte entirely, which would allow that repository to use
Guzzle 7 in its dependency chain.)

I set up a test webpage in Apache with PHP-FPM, but the production Web server (owned by a third party) is IIS and ASP.NET,
and also exhibits the problem.
While creating this standalone test, I discovered that the other web servers did **not** exhibit a problem.

# License

MIT.
