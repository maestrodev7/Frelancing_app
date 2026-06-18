<?php
$j = json_decode(file_get_contents(__DIR__.'/kratos-collection.json'), true);

function walk(array $items, array &$out): void
{
    foreach ($items as $i) {
        if (isset($i['request']['url'])) {
            $url = $i['request']['url'];
            $out[] = [
                'name' => $i['name'] ?? '',
                'method' => $i['request']['method'] ?? '',
                'url' => is_array($url) ? ($url['raw'] ?? json_encode($url)) : $url,
                'body' => $i['request']['body']['raw'] ?? null,
            ];
        }
        if (! empty($i['item'])) {
            walk($i['item'], $out);
        }
    }
}

$out = [];
walk($j['item'], $out);
foreach ($out as $e) {
    echo $e['method'].' '.$e['url'].PHP_EOL;
    if ($e['body']) {
        echo '  body: '.str_replace("\n", ' ', substr($e['body'], 0, 200)).PHP_EOL;
    }
}
