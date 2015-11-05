<?php

require_once __DIR__ . '/vendor/autoload.php';

use Goutte\Client;

$client = new Client();
// Set a "real" user agent, otherwise the server will run away and hide
$client->config['headers']['User-Agent'] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36";

try {
  switch($argv[1]) {
    case "pna.ro":
    case "pna":
    case "dna":
        if(!include('scrapers/pna.ro.php'))
            throw new \Exception("Scraper does not exist!");
        break;
    case "linx.crji.org":
    case "linx":
        if(!include('scrapers/linx.crji.org.php'))
            throw new \Exception("Scraper does not exist!");
        break;
    }
} catch (Exception $e) {
      echo "Exception: " . $e->getMessage();
}
