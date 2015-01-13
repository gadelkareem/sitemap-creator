<?php

/**
 * @file 
 * Sitemap Creator Crawler class
 *  
 * extends PHPCrawler class 
 * @link http://phpcrawl.cuab.de/classreferences/index.html
 * 
 * @package SitemapCreator
 * @category Crawler
 * @author Waleed Gadelkareem <gadelkareem@gmail.com>
 * @link http://gadelkareem.com/
 * @license  GPLv2 
 */

/**
 * Loading external PHPCrawler-class
 * 
 * Uncomment for standalone
 */
//if (!class_exists("PHPCrawler"))
//    require_once(dirname(__FILE__) . "/libs/PHPCrawler/PHPCrawler.class.php");

class SMCCrawler extends PHPCrawler {

    /**
     *  get Last Modified header
     * @see enableLastModifiedCount()
     * @var bool 
     */
    var $LastModifiedCount = true;

    /**
     * Array contianing the entries.
     *
     * @var array
     */
    var $entries = array();

    /**
     *  get access to all information about a page or file the crawler found and received.
     *
     * @param PHPCrawlerDocumentInfo A PHPCrawlerDocumentInfo-object containing all information about the currently received document.
     * @section 3 Crawler
     */
    //@todo crawl reporting for ajax getCrawlerStatus()
    public function handleDocumentInfo(PHPCrawlerDocumentInfo $PageInfo) {
        $entry = array(
            'URL' => $PageInfo->url,
        );
        //set 'Last-Modified'
        $this->getLastModified($PageInfo, $entry);
        //add new entry
        $this->addURL_Entry($entry); //unset($PageInfo);
        //if ($this->checkForAbort())
        //    echo 'aborted';
    }

    /**
     *  get Last-Modified header
     *
     * @param PHPCrawlerDocumentInfo A PHPCrawlerDocumentInfo-object containing all information about the currently received document.
     * @section 3 Crawler
     */
    protected function getLastModified(PHPCrawlerDocumentInfo $PageInfo, &$entry) {
        //check if enabled
        if (!$this->LastModifiedCount)
            return;

        //get 'Last-Modified' header from the Document Info
        $last_modified = strtotime(PHPCrawlerUtils::getHeaderValue($PageInfo->header, 'last-modified'));
        //if 'Last-Modified' header not found then get 'Date' header
        if (!$last_modified)
            $last_modified = strtotime(PHPCrawlerUtils::getHeaderValue($PageInfo->header, 'date'));
        //set last modified
        $entry['Last-Modified'] = $last_modified;
    }

    /**
     *  add URL entry {@link $entries}
     * 
     * @param array $entry  URL set to be added to sitemap
     * @section 3 Crawler
     */
    protected function addURL_Entry($entry) {
        $this->entries[] = $entry;
    }

    /**
     * Enable or diable last-Modified calculation {@link $LastModifiedCount}
     * 
     * @param bool $mode trure to enable, false otherwise
     * @section 3 Crawler
     */
    public function enableLastModifiedCount($mode) {
        $this->LastModifiedCount = ($mode);
    }

}