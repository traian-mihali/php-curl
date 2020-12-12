<?php

include_once('vendor/simple_html_dom_1_9_1/simple_html_dom.php');

?>

<?php

$URL = isset($argv[1]) ? $argv[1] : null;

if ($URL == null)
{
    echo "!!Missing URL!!";
    exit(1);
}

$totalSize = 0;
$totalResources = 0;

list($totalSize, $totalResources) = crawler($URL, $totalSize, $totalResources);

echo "Total Download Size: {$totalSize} Bytes \n";
echo "Total HTTP Requests: {$totalResources}";
exit(0);

function crawler($URL, $totalSize, $totalResources)
{
    if (!isHtml($URL))
        return [getFileSize($URL), ++$totalResources];

    $html = file_get_html($URL);

    foreach ($html->find('img') as $element)
    {
        $totalSize += getFileSize($element->src);
        $totalResources++;
    }

    foreach ($html->find('link') as $element)
    {
        if (strpos($element->href, '.css') !== false)
        {
            $totalSize += getFileSize($element->href);
            $totalResources++;
        }
    }

    foreach ($html->find('script') as $element)
    {
        if (strpos($element->src, '.js') !== false)
        {
            $totalSize += getFileSize($element->src);
            $totalResources++;
        }
    }

    foreach ($html->find('iframe') as $element)
        list($totalSize, $totalResources) = crawler($element->src, $totalSize, $totalResources);

    return [$totalSize, $totalResources];
}

function isHtml($url) {
    $c = curl_init();
    curl_setopt($c, CURLOPT_URL, $url);
    curl_setopt($c, CURLOPT_HEADER, 0);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($c, CURLOPT_NOBODY, TRUE);

    $result = curl_exec($c);
    if (!$result)
    {
        trigger_error(curl_error($c));
        exit(1);
    }

    $contentType = curl_getinfo($c, CURLINFO_CONTENT_TYPE);
    curl_close($c);
    
    return strpos($contentType,'text/html') !== false;
}

function getFileSize($url) {
    if (filter_var($url, FILTER_VALIDATE_URL) === false)
        return 0;

    $headers = get_headers($url, 1);

    if (isset($headers["Content-Length"]))
        return is_array($headers["Content-Length"]) ? $headers["Content-Length"][0] : $headers["Content-Length"];

    $c = curl_init();
    curl_setopt($c, CURLOPT_URL, $url);
    curl_setopt($c, CURLOPT_HEADER, 0);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($c, CURLOPT_NOBODY, TRUE);

    $result = curl_exec($c);
    if (!$result)
    {
        trigger_error(curl_error($c));
        exit(1);
    }
    
    $size = curl_getinfo($c, CURLINFO_SIZE_DOWNLOAD);
    curl_close($c);

    return $size;
}

?>
