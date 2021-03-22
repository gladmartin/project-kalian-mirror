<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\DomCrawler\Crawler;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$token = $_ENV['GITHUB_ACCESS_TOKEN'];
$client = new Github\Client();
$client->authenticate($token, null, Github\Client::AUTH_ACCESS_TOKEN);

$userRepoName = 'sandhikagalih';
$repoName = 'project-kalian';
$readme = $client->api('repo')->contents()->readme($userRepoName, $repoName);
$readmeMd = base64_decode($readme['content']);

if (!isset($readme['content'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Rate limit has been reached',
    ]);
    die;
}

// $readmeMd = file_get_contents('readme.md');
$projects = [];
$readmeMd = str_replace(['# project-kalian', '## Menyimpan daftar Project Kalian yang sudah disubmit di Discord'], '', $readmeMd);
$periodes = explode('### ', $readmeMd);
foreach ($periodes as $periode) {
    $date = explode("\n", $periode);
    $date = trim($date[0]);
    if (!$date) continue;
    $items = [];
    $itemExplode = explode(". [", $periode);
    unset($itemExplode[0]);
    foreach ($itemExplode as $value) {
        $value = explode("\n", $value);
        unset($value[count($value) - 1]);
        $value = implode("\n", $value);

        $link = explode(']', $value);
        $link = trim($link[0]);

        $author = explode('**', $value);
        $author = trim($author[1] ?? null);
        $initial = 'NN';
        if ($author != null) {
            $initial = substr($author, 0, 1) . substr($author, -1);
            $initial = strtoupper($initial);
            $initial = mb_convert_encoding($initial, 'UTF-8', 'UTF-8');
        }
        $value = '[' . $value;

        $parsedown = new Parsedown();
        $readmeHtml = $parsedown->text($value);
        $crawler = new Crawler($readmeHtml);

        $descriptionFull = $crawler->filter('body')->html();
        $descriptionFull = str_replace(['<pre>', '</pre>', '<code>', '</code>'], '', $descriptionFull);
        $linkElement = $crawler->filter('a');
        $link = $linkElement->attr('href');
        $linkElement = $linkElement->eq(0);
        foreach ($linkElement->eq(0) as $value) {
            $value->parentNode->removeChild($value);
        }
        $githubLink = null;
        $crawler->filter('p')->each(function ($node) use (&$githubLink) {
            $text = $node->text();
            if (strpos($text, 'GitHub : [') !== false) {
                $githubLink = $node->filter('a')->attr('href');
            }
        });
        $description = $crawler->filter('body')->html();
        $description = str_replace(['<pre>', '</pre>', '<code>', '</code>'], '', $description);
        $description = str_replace(["<strong>$author</strong>", "[]", '<br>'], '', $description);
        $description = str_replace(["\n", '<p></p>'], '', $description);
        $description = trim($description);

        $color = '#' . substr(md5($date), 0, 6);
        $items[] = [
            'author' => $author,
            'metas' => [
                'initial' => $initial,
                'color_profile' => $color,
            ],
            'demo_link' => $link,
            'github_link' => $githubLink,
            'description' => $description,
            'description_full' => $descriptionFull,
        ];
    }
    array_unshift($projects, [
        'periode' => $date,
        'items' => $items,
        '__total' => count($items),
    ]);
}

echo json_encode([
    'success' => true,
    'data' => $projects,
    '__total' => count($projects),
], JSON_HEX_TAG);