<?php

require_once __DIR__ . '/../yandex-metrika-core/MetrikaClient.php';

MetrikaClient::checkGitignore();
$config = MetrikaClient::loadConfig();

function parseArgs(array $argv): array
{
    $result = [
        'dateFrom' => date('Y-m-d', strtotime('-30 days')),
        'dateTo' => date('Y-m-d'),
        'type' => 'page',
        'sort' => 'visits',
        'order' => 'desc',
        'limit' => null,
        'site' => null
    ];
    
    $i = 1;
    while ($i < count($argv)) {
        $arg = $argv[$i];
        
        if (in_array($arg, ['--type', '-t']) && isset($argv[$i + 1])) {
            $result['type'] = $argv[++$i];
        } elseif (in_array($arg, ['--sort', '-s']) && isset($argv[$i + 1])) {
            $result['sort'] = $argv[++$i];
        } elseif (in_array($arg, ['--order', '-o']) && isset($argv[$i + 1])) {
            $result['order'] = $argv[++$i];
        } elseif (in_array($arg, ['--limit', '-l']) && isset($argv[$i + 1])) {
            $result['limit'] = (int)$argv[++$i];
        } elseif (in_array($arg, ['--site']) && isset($argv[$i + 1])) {
            $result['site'] = $argv[++$i];
        } elseif (!str_starts_with($arg, '-') && strlen($arg) === 10 && strpos($arg, '-') !== false) {
            if (!$result['dateFrom'] || $result['dateFrom'] === date('Y-m-d', strtotime('-30 days'))) {
                $result['dateFrom'] = $arg;
            } else {
                $result['dateTo'] = $arg;
            }
        }
        $i++;
    }
    
    return $result;
}

function getTypeLabel(string $type): string
{
    $map = [
        'page' => 'Страницы',
        'entry' => 'Страницы входа',
        'exit' => 'Страницы выхода'
    ];
    
    return $map[$type] ?? 'Страницы';
}

function getSortField(string $sort): string
{
    $map = [
        'visits' => 'ym:s:visits',
        'visitors' => 'ym:s:users',
        'pageviews' => 'ym:s:pageviews',
        'bounce_rate' => 'ym:s:bounceRate',
        'page_depth' => 'ym:s:pageDepth',
        'avg_duration' => 'ym:s:avgVisitDurationSeconds'
    ];
    
    return $map[$sort] ?? 'ym:s:visits';
}

$args = parseArgs($argv);
$counterId = MetrikaClient::getCounterIdFromConfig($config, $args['site']);
$siteName = $args['site'] ?? ($config['default_counter'] ?? null);

$client = new MetrikaClient(
    $config['client_id'],
    $config['client_secret'],
    $counterId,
    $siteName
);

function getPagesData(MetrikaClient $client, string $dateFrom, string $dateTo, string $type, string $sortField, string $order): array
{
    $prefix = $order === 'asc' ? '' : '-';
    
    if ($type === 'page') {
        $data = $client->request([
            'ids' => $client->getCounterId(),
            'metrics' => 'ym:pv:pageviews,ym:pv:users',
            'dimensions' => 'ym:pv:URL',
            'date1' => $dateFrom,
            'date2' => $dateTo,
            'limit' => 1000,
            'sort' => $prefix . 'ym:pv:pageviews'
        ]);
        
        $result = [];
        foreach ($data['data'] ?? [] as $item) {
            $result[] = [
                'url' => $item['dimensions'][0]['name'] ?? '',
                'pageviews' => (int)($item['metrics'][0] ?? 0),
                'visitors' => (int)($item['metrics'][1] ?? 0)
            ];
        }
    } else {
        $dimensionMap = [
            'entry' => 'ym:s:startURL',
            'exit' => 'ym:s:endURL'
        ];
        
        $data = $client->request([
            'ids' => $client->getCounterId(),
            'metrics' => 'ym:s:visits,ym:s:users,ym:s:pageviews,ym:s:bounceRate,ym:s:pageDepth,ym:s:avgVisitDurationSeconds',
            'dimensions' => $dimensionMap[$type],
            'date1' => $dateFrom,
            'date2' => $dateTo,
            'limit' => 1000,
            'sort' => $prefix . $sortField
        ]);
        
        $result = [];
        foreach ($data['data'] ?? [] as $item) {
            $result[] = [
                'url' => $item['dimensions'][0]['name'] ?? '',
                'visits' => (int)($item['metrics'][0] ?? 0),
                'visitors' => (int)($item['metrics'][1] ?? 0),
                'pageviews' => (int)($item['metrics'][2] ?? 0),
                'bounce_rate' => round($item['metrics'][3] ?? 0, 2),
                'page_depth' => round($item['metrics'][4] ?? 0, 2),
                'avg_duration' => round($item['metrics'][5] ?? 0, 2)
            ];
        }
    }
    
    return $result;
}

$pages = getPagesData($client, $args['dateFrom'], $args['dateTo'], $args['type'], getSortField($args['sort']), $args['order']);

if ($args['limit'] !== null && $args['limit'] > 0) {
    $pages = array_slice($pages, 0, $args['limit']);
}

$reportPath = MetrikaClient::createReportDir();
$timestamp = MetrikaClient::getFileTimestamp();

$label = getTypeLabel($args['type']);

echo "\n  Папка отчета: yandex_metrika_reports/" . basename($reportPath) . "\n";
if ($siteName) {
    echo "  Сайт: $siteName\n";
}
echo "  Период: {$args['dateFrom']} — {$args['dateTo']}\n";
echo "  Тип: {$label}\n";
echo "  Сортировка: {$args['sort']} ({$args['order']})\n";
if ($args['limit'] !== null) {
    echo "  Лимит: топ {$args['limit']}\n";
}
echo "\n";

MetrikaClient::saveCsv($pages, "$reportPath/yandex_metrika_pages_$timestamp.csv");
MetrikaClient::saveMarkdown($pages, "$reportPath/yandex_metrika_pages_$timestamp.md", $label, $args['dateFrom'], $args['dateTo']);

echo "  Создано файлов:\n";
echo "    - yandex_metrika_pages_$timestamp.csv\n";
echo "    - yandex_metrika_pages_$timestamp.md\n";
echo "\n  Найдено страниц: " . count($pages) . "\n";
