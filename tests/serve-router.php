<?php

// Register request uri
$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$caching = true;
$url = parse_url($requestUri);

// Handle app resources with caching
if (preg_match('/\.(?:png|jpg|jpeg|gif|xml|js|json|css|eot|svg|otf|ttf|woff|woff2|scss|less|txt|ico)$/', $url['path'])) {
    // Generate file name
    $fileName = __DIR__ . '/..' . $url['path'];

    // Parse file data
    if (!file_exists($fileName)) {
        return false;
    }

    $lastModified = filemtime($fileName);
    $etagFile = md5_file($fileName);
    $ifModifiedSince = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false);
    $etagHeader = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);

    // Set caching header
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
    header('Etag: ' . $etagFile);
    header('Cache-Control: public');
    $info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($info, $fileName);
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);

    switch ($extension) {
        case 'css':
            $mime_type = 'text/css';
            break;
        case 'js':
            $mime_type = 'application/javascript';
            break;
        default:
            break;
    }

    // Serve requested resource
    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($fileName));

    // Check if the requested resource has changed
    if ($caching && (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModified || $etagHeader == $etagFile)) {
        // File has not changed
        header('HTTP/1.1 304 Not Modified');
        exit();
    } else {
        @readfile($fileName);
        exit();
    }
}

return false;
