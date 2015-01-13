<?php

/**
 * Sitemap Creator Example
 * 
 * 
 * @link http://sitemapcreator.org/
 * @package SitemapCreator
 * @author Waleed Gadelkareem <gadelkareem@gmail.com>
 * @link http://gadelkareem.com/
 * @filesource
 */
error_reporting( E_ALL );
ini_set('display_errors', 1);
set_time_limit(10000);
ini_set("memory_limit", '300M');

require '../SitemapCreator.class.php';

if (PHP_SAPI == "cli") $lb = "\n"; 
else $lb = "<br />"; 

//create object
$sitemap = new SitemapCreator('http://www.dmoz.org/');



//Sitemap Creator creates a URL to sitemap files
//For example
//echo $sitemap->getSitemapURL(1) . $lb;
//however you should add your formated URL
$sitemap->setSitemapURL('http://www.dmoz.org/Sitemap_Creator/data/' . $sitemap->getSitemapDirName() . '/' );

//Sitemap Creator creates a path to sitemaps in system tmp
//For example
//echo $sitemap->getSitemapPath(1) . $lb;
//however you should add your writable directory path
//$sitemap->setDataDir('/server/sitemaps/data');

//choose to gzip compress the sitemaps
//this option is useful if you will use NGINX sendfile {@link http://wiki.nginx.org/HttpCoreModule#sendfile}
//$sitemap->useGzip(true);

//read the sitemaps
if( isset($_GET['sitemap']) ){
    $sitemap->readSitemap($_GET['sitemap']);
    exit;
    }
    
//choose sitemaps options
//check docs for details
$sitemap->setPriority(SitemapCreator::PRIORITY_CRAWLED_FIRST);
$sitemap->setFrequency(SitemapCreator::FREQUENCY_PRIORITY);
$sitemap->setEntriesPerSitemap(10);
$sitemap->setMinFrequency('yearly');
$sitemap->setMinPriority(0.3);


//all set, let start the crawler
//create $sitemap->Crawler
$sitemap->initCrawler(); 

//set the Cralwer options
//for more options check {@link http://phpcrawl.cuab.de/classreferences/index.html}
$sitemap->Crawler->setPageLimit(10); // Set the page-limit to 50 for testing
//if we are not calculating Frequency based on last-modified header then we can disable
$sitemap->Crawler->enableLastModifiedCount(false);

//start the crawling process
$sitemap->Crawl();

// At the end, after the process is finished, we print a short 
// report (see method getProcessReport() for more information) 
$report = $sitemap->crawler_reports; 
     
echo "Summary:".$lb; 
echo "Links followed: ".$report->links_followed.$lb; 
echo "Documents received: ".$report->files_received.$lb; 
echo "Bytes received: ".$report->bytes_received." bytes".$lb; 
echo "Process runtime: ".$report->process_runtime." sec".$lb;  
flush();

//create sitemaps
$sitemap->CreateSitemaps();

echo "Sitemap Created at {$sitemap->getSitemapPath('index')} {$lb}";
if( PHP_SAPI != "cli") 
       echo "Click <a href='{$_SERVER['PHP_SELF']}?sitemap=index' >here</a> to view your sitemap{$lb}";

$results = $sitemap->Ping();     

foreach( $results as $engine => $result)
    if( isset($result['body']) )
        echo "{$engine} pinged successfully{$lb}";
    else
        echo "Error pinging {$engine} : {$result['error']}{$lb}";

