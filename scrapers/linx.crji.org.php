<?php

require_once __DIR__ . '/../vendor/autoload.php';
use Inline\PDF\PDF;

define('DOMAIN', 'linx.crji.org');
$source  = "http://linx.crji.org/feed";

$sourceCount = $saveCount = 0;
$items = $localFiles = [];
foreach(scandir('./pdf/'.DOMAIN) as $file) {
  if(preg_match("/\d+\.pdf/", $file)) {
    array_push($localFiles, $file);
  }
}

$crawler = $client->request('GET', $source);

$crawler->filterXPath("//default:item")->each(function ($node) {
  global $items, $localFiles, $sourceCount, $saveCount;
  $sourceCount++;

  //$url = str_replace(' ', '', $node->filterXPath('//default:link')->text());
  $url = $node->filterXPath("//default:link")->text();
  $fileName= str_replace('&', '-', preg_replace('/[^\d\&]+/', '', $url)) . ".pdf";
  //echo "$fileName -> $url\n";
  $items[$fileName] = $url;

  if(!in_array($fileName, $localFiles)) {
    $title = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $node->filterXPath("//default:title")->text());
    $date  = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $node->filterXPath("//default:*//dc:date")->text());
    $html  = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $node->filterXPath("//default:description")->text());
    $pdf   = new PDF('/usr/bin/wkhtmltopdf');
    $pdf->loadHTML($html)
      ->encoding('UTF-8')
      ->save($fileName, new League\Flysystem\Adapter\Local('./pdf/'.DOMAIN), true);
    echo "Saved $fileName ($title)";


    $jsonData = "\n{";
    $jsonData .= "\turl: \""   . str_replace(' ', '', $url) . "\",\n";
    $jsonData .= "\tfile: \""  . $fileName                  . "\",\n";
    $jsonData .= "\ttitle: \"" . $title                     . "\",\n";
    $jsonData .= "\tcollected_at: \"" . date('Y-m-d')       . "\",\n";
    $jsonData .= "\tpublished_at: \"" . $date               . "\"\n";
    $jsonData .= "}";
    file_put_contents('./json/' . DOMAIN . '/' . $fileName . '.json', $jsonData);
    echo " + $fileName.json \n";

    global $saveCount;
    $saveCount++;
  }
});

echo "Done (".($sourceCount - count($localFiles))." documents to be downloaded, ". $saveCount ." downloaded).\n";


// Generate a file with all the links to downloaded files
echo "Generating index file... ";
$fp = fopen('html/'.DOMAIN.'.html', 'w');
foreach(scandir('./pdf/' . DOMAIN) as $file) {
  if(preg_match("/\d+\.pdf/", $file)) {
    fwrite($fp, "<a href='../pdf/'.DOMAIN.'/$file'>$file</a><br>\n");
  }
}
fclose($fp);
echo "done!\n";



// Concatenate all the JSON files
echo "Generating master JSON file... ";
$fp = fopen('json/'.DOMAIN.'.json', 'w');
fwrite( $fp, "[\n");

$jsonData = '';
foreach(scandir('./json/' . DOMAIN) as $file) {
  global $jsonData;
  if(preg_match("/\.json$/", $file)) {
    global $jsonData;
    $jsonData .= file_get_contents('./json/'.DOMAIN.'/'.$file) . ",\n";
  }
}
$jsonData = preg_replace('/,\n$/', '', $jsonData);
fwrite( $fp, $jsonData);

fwrite( $fp, "\n]");
fclose($fp);
echo "done!\n";


