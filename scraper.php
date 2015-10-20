<?php

require __DIR__ . '/vendor/autoload.php';

use Goutte\Client;
use Inline\PDF\PDF;

$client = new Client();
// Set a "real" user agent, otherwise the server will run away and hide
$client->config['headers']['User-Agent'] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36";


// Collect the maximum ID from main page
$online_ids = [];
$crawler = $client->request('GET', 'http://www.pna.ro/faces/index.xhtml');
$crawler->filterXPath("//*[contains(@class, 'section-list')]/li/a")->each(function($node) {
  global $online_ids;
  array_push($online_ids, preg_replace('/.+\?id=/', '', $node->attr('href')));
});
$max_online = max($online_ids);
echo "Maximum ID published on pna.ro: $max_online\n";


// Collect the maximum collected ID
$local_ids = [];
foreach(scandir('./pdf') as $file) {
  if(preg_match("/\d+\.pdf/", $file)) {
    array_push($local_ids, trim(preg_replace('/\.pdf/', '', $file)));
  }
}
$max_local = (count($local_ids) > 0 ? max($local_ids) : 0);
echo "Maximum ID stored: $max_local\n";

echo ($max_online - $max_local) . " documents to save\n";

// Set the ID limits for collecting the data
if($max_local < 1) {
  $id = 1;
} else {
  $id = $max_local + 1;
}

// Start iterating through the defined interval
for($id; $id <= $max_online; $id++) {
  // Go to the pna.ro website
  $crawler = $client->request('GET', 'http://www.pna.ro/faces/comunicat.xhtml?id=' . $id);

  // Get the latest post in this category and display the titles
  $crawler->filter('div.content-holder')->each(function ($node) {
    global $id;
    // Manipulate the charset, avoid question marks in the output
    $html = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $node->html());
    // Create a PDF instance, then do some filesystem output
    $pdf = new PDF('/usr/bin/wkhtmltopdf');
    $pdf->loadHTML($html)
      ->encoding('UTF-8')
      ->save("$id.pdf", new League\Flysystem\Adapter\Local(__DIR__.'/pdf'), true);
    echo "Saved $id.pdf\r";
  });
}

echo "Done (".($max_online - $max_local)." documents downloaded).\n";

// Generate a file with all the links to downloaded files
echo "Generating index file... ";
$fp = fopen('index.html', 'w');
foreach(scandir('./pdf') as $file) {
  if(preg_match("/\d+\.pdf/", $file)) {
    fwrite($fp, "<a href='./pdf/$file'>$file</a><br>\n");
  }
}
fclose($fp);
echo "done!\n";

