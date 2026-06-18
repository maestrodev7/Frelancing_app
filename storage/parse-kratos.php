<?php
$html = file_get_contents(__DIR__.'/kratos-doc.html');
preg_match_all('/\/api\/[a-zA-Z0-9\/_{}\.?-]+/', $html, $m);
foreach (array_unique($m[0]) as $path) {
    echo $path.PHP_EOL;
}
preg_match('/collection-info" content="([^"]+)"/', $html, $ci);
if (! empty($ci[1])) {
    file_put_contents(__DIR__.'/collection-info.json', html_entity_decode($ci[1]));
    echo PHP_EOL.'--- collection-info saved ---'.PHP_EOL;
}
