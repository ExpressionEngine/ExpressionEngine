<?php

/**
 * Stands in for the update server while the one-click updater is tested.
 *
 * The site under test is pointed at this script's own address, and each
 * request it makes is passed on to the update server named in the
 * UPDATE_SERVER_UPSTREAM environment variable. Answers refer to this
 * script's address in place of that server's, and its log records the
 * outcome of each request.
 *
 * Run it with PHP's built-in server, on a port of its own:
 *
 *   UPDATE_SERVER_UPSTREAM=<address> php -S 127.0.0.1:8889 update-server-proxy.php
 *
 * A GET request answers 200 once an update server is configured, and 503
 * otherwise, without contacting it.
 */

// Failures are reported through this script's own answers and notes
ini_set('display_errors', '0');
ini_set('log_errors', '0');
ini_set('zend.exception_ignore_args', '1');

// A package is held in memory on its way through
ini_set('memory_limit', '1G');
set_time_limit(0);

/**
 * Writes a line to the built-in server's console
 */
function note($line)
{
    file_put_contents('php://stderr', '[update-server-proxy] ' . $line . "\n");
}

function respond($status, $content_type, $body, array $headers = [])
{
    http_response_code($status);
    header_remove('X-Powered-By');

    foreach ($headers as $name => $value) {
        header($name . ': ' . $value);
    }

    if ($content_type === null) {
        // PHP would otherwise name a type of its own
        ini_set('default_mimetype', '');
        header_remove('Content-Type');
    } else {
        header('Content-Type: ' . $content_type);
    }

    header('Content-Length: ' . strlen($body));

    echo $body;
}

/**
 * Answers in the form the updater reads an update server's own errors from
 */
function refuse($status, $reason)
{
    respond($status, 'application/json', json_encode(['error' => $reason]));
}

/**
 * Makes a piece of text refer to this script's address wherever it refers to
 * the update server's, however the text has encoded it.
 *
 * A part of the address, such as its path, can also match text that has
 * nothing to do with it. In an error such parts are removed wherever they
 * appear. In any other answer they are removed only where they stand on
 * their own, so that the rest of the answer arrives as it was sent.
 */
function withoutUpstream($text, $upstream, array $parts, $self, $is_error)
{
    $self_parts = parse_url($self);
    $self_host = $self_parts['host'] . (isset($self_parts['port']) ? ':' . $self_parts['port'] : '');

    $host = $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    $origin = $parts['scheme'] . '://' . $host;
    $path = (isset($parts['path']) ? $parts['path'] : '') . (isset($parts['query']) ? '?' . $parts['query'] : '');

    $whole = [
        $upstream => $self,
        rtrim($upstream, '/') => $self,
        $origin . $path => $self,
        $origin => $self,
        $host => $self_host,
        $parts['host'] => $self_parts['host'],
    ];

    $encodings = [
        function ($value) {
            return $value;
        },
        function ($value) {
            return str_replace('/', '\/', $value);
        },
        function ($value) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        },
        'rawurlencode',
        'urlencode',
    ];

    $replacements = [];

    foreach ($encodings as $encode) {
        foreach ($whole as $find => $replace) {
            if ($find !== '') {
                $replacements[$encode($find)] = $encode($replace);
            }
        }
    }

    // Longest first, so that a whole address is not left as a changed part
    uksort($replacements, function ($a, $b) {
        return strlen($b) - strlen($a);
    });

    $text = str_ireplace(array_keys($replacements), array_values($replacements), $text);

    $fragments = [];

    foreach ($is_error ? ['user', 'pass', 'path', 'query'] : ['path', 'query'] as $part) {
        // A lone "/" or a very short value would match far too much
        if (isset($parts[$part]) && strlen($parts[$part]) > 3) {
            foreach ($encodings as $encode) {
                $fragments[] = $encode($parts[$part]);
            }
        }
    }

    $fragments = array_unique($fragments);

    usort($fragments, function ($a, $b) {
        return strlen($b) - strlen($a);
    });

    if ($is_error) {
        return str_ireplace($fragments, '', $text);
    }

    foreach ($fragments as $fragment) {
        // On its own: not the continuation of a longer address or word
        $alone = preg_replace(
            '~(?<![A-Za-z0-9._:/\\\\%-])' . preg_quote($fragment, '~') . '(?![A-Za-z0-9_])~',
            '',
            $text
        );

        $text = ($alone === null) ? str_ireplace($fragment, '', $text) : $alone;
    }

    return $text;
}

try {
    $upstream = getenv('UPDATE_SERVER_UPSTREAM');
    $parts = is_string($upstream) ? parse_url($upstream) : false;
    $configured = is_array($parts)
        && isset($parts['scheme'], $parts['host'])
        && in_array(strtolower($parts['scheme']), ['http', 'https'], true);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $configured
            ? respond(200, 'text/plain', "ready\n")
            : respond(503, 'text/plain', "UPDATE_SERVER_UPSTREAM is not set to an http or https address\n");

        return;
    }

    if (! $configured) {
        note('refused a request: no update server is configured');
        refuse(503, 'The test proxy has no update server to pass this request on to.');

        return;
    }

    // Only what the updater reads from an answer is passed back to it
    $wanted = ['content-type', 'package-signature'];
    $headers = [];

    $request_headers = [
        'Content-Type: ' . (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'application/x-www-form-urlencoded'),
    ];

    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $request_headers[] = 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'];
    }

    $curl = curl_init($upstream);

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => file_get_contents('php://input'),
        CURLOPT_HTTPHEADER => $request_headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 600,
        CURLOPT_HEADERFUNCTION => function ($curl, $line) use (&$headers, $wanted) {
            // An interim answer's headers are not the final answer's
            if (strncmp($line, 'HTTP/', 5) === 0) {
                $headers = [];
            } elseif (strpos($line, ':') !== false) {
                list($name, $value) = explode(':', $line, 2);

                if (in_array(strtolower(trim($name)), $wanted, true)) {
                    $headers[trim($name)] = trim($value);
                }
            }

            return strlen($line);
        },
    ]);

    $body = curl_exec($curl);

    if ($body === false) {
        // cURL's error number says what went wrong
        note('no answer from the update server (cURL error ' . curl_errno($curl) . ')');
        refuse(502, 'The update server did not answer the test proxy.');

        return;
    }

    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $content_type = null;

    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'content-type') {
            $content_type = $value;
            unset($headers[$name]);
        }
    }

    // A package goes through untouched: it is signed. Only a 200 answer that
    // says it is a zip file is taken for one. Any other answer is made to
    // refer to this script's address.
    $media_type = strtolower(trim(explode(';', (string) $content_type)[0], " \t\"'"));

    if ($status !== 200 || $media_type !== 'application/zip') {
        $body = withoutUpstream(
            $body,
            $upstream,
            $parts,
            'http://' . $_SERVER['HTTP_HOST'],
            $status < 200 || $status >= 300
        );
    }

    respond($status, $content_type, $body, $headers);
    note('passed on a request: the update server answered ' . $status . ' with ' . strlen($body) . ' bytes');
} catch (\Throwable $e) {
    note('failed while passing on a request (' . get_class($e) . ')');
    refuse(500, 'The test proxy for the update server failed.');
}
