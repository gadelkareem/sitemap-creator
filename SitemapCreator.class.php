<?php

/**
 * Sitemap Creator Main class
 * 
 * @desc 
 * Sitemap Creator creates XML sitemaps files compatible with the standard 
 * sitemaps.org protocol and supported by Google and Bing. 
 * 
 * @link http://sitemapcreator.org/
 * @package SitemapCreator
 * @category MainClass
 * @author Waleed Gadelkareem <gadelkareem@gmail.com>
 * @link http://gadelkareem.com/
 * @version 1.0
 * @license  GPLv2
 * 
 */
class SitemapCreator {

    public $class_version = "1.0";
    static $useragent = "Sitemaps Creator 1.0 (compatible; sitemapcreatorbot/1.0; +http://sitemapcreator.org/)";

    /**
     *  Disables priority calculations
     *  @see setPriority()
     * 
     */
    const PRIORITY_Disable = 0;
    /**
     *  Crawled first pages get higher priority
     *  Default
     *  @see setPriority()
     */
    const PRIORITY_CRAWLED_FIRST = 1;
    /**
     *  Deeper pathes get lower priority
     *  @see setPriority()
     */
    const PRIORITY_URL_STRUCTURE = 2;

    /**
     *  Priority mode 
     * @see setPriority()
     * @var int one of SitemapCreator::PRIORITY-constants
     */
    protected $priority_mode = 1; //0,1,2
    /**
     *  Minimum Priority
     * @see setMinPriority()
     * @var int 
     */
    protected $min_priority = 0;

    /**
     *  
     *  Disables frequency calculations
     * @see setFrequency()
     */

    const FREQUENCY_Disable = 0;
            /**
             *  Latest modified pages get higher frequency
             *  Default 
             * @see setFrequency()
             */
            const FREQUENCY_LAST_MODIFIED = 1;
            /**
             *  Higher priority pages get higher frequency
             * @see setFrequency()
             */
            const FREQUENCY_PRIORITY = 2;

    /**
     *  Frequency mode
     * @see setFrequency()
     * @var int one of SitemapCreator::FREQUENCY-constants
     */
    protected $frequency_mode = 1; //0,1,2
    /**
     *  Minimum Priority
     * @see setMinFrequency()
     * @var string one of  {@link $frequency_types}
     */
    protected $min_frequency = 'never';

    /**
     *  Array contains Frequency types as keys and max time in seconds as values.
     * @var array
     */
    protected $frequency_types = array
        (
        'always' => 3600, //1 hour
        'hourly' => 86400, //1 day
        'daily' => 604800, //1 week
        'weekly' => 2678400, //1 month
        'monthly' => 31536000, //1 year
        'yearly' => 63072000, //2 years
        'never' => 94608000 //3 years
    );

    /**
     *  Current time().
     * @var int
     */
    protected $now;

    /**
     * The URL of the website.It should be full qualified and normalized.
     * Set on class creation {@link __construct()} or {@link setSite()}
     * 
     * Default: 'http://' . $_SERVER['HTTP_HOST'] . '/'
     * 
     * @var string
     */
    protected $site;

    /**
     * Array contianing the entries of the sitemap.
     *
     * @var array
     */
    protected $entries = array();

    /**
     * Maximum number of entries per sitemap file 
     * @see  setEntriesPerSitemap()
     *
     * @var int
     */
    protected $entries_per_sitemap = 50000;

    /**
     * Number of sitemap files created
     *
     * @var int
     */
    protected $sitemaps_count = 0;

    /**
     * XML string containing sitemap <urlset></urlset> elements
     *
     * @var string
     */
            protected $xml_url_set = '',
            $xml_head,
            $xml_foot;

    /**
     * Data directory Path.
     * 
     * Valid system path for the directory where sitemaps directories
     * will be created.The directory should be writable.
     * @see setDataDir()
     * @see getDataDir()
     * 
     * Default: sys_get_temp_dir()
     *
     * @var string
     */
    protected $data_dir = '';

    /**
     * Sitemaps directory path auto created in {@link prepareSitemapsDir()}
     * @see getSitemapPath()
     *
     * @var string
     */
    protected $sitemaps_dir;

    /**
     * Sitemap URL where the sitemap file name will be appended to the end of the URL.
     * If not set then the link will be generated automatically.
     * @example
     *  http://www.example.com/sitemap.php?=
     * 
     * @see setSitemapURL()
     * @see getSitemapURL()
     *    
     * @var string
     */
    protected $sitemaps_url;

    /**
     * choose to save sitemaps in gzip format
     * @see useGzip()
     *
     * @var bool
     */
    protected $use_gzip = false;

    /**
     * Ping URLs of the search engines sitemaps API
     * @see addEngine()
     *
     * @var array
     */
    protected $engines = array
        (
        'Google' => 'http://www.google.com/webmasters/sitemaps/ping?sitemap=',
        'Live Search' => 'http://www.bing.com/webmaster/ping.aspx?siteMap='
    );
    /*
     * The SMCCrawler-Object: "Crawler" 
     *
     * @var SMCCrawler
     */
    public $Crawler;
    /*
     * Class path
     *
     * @var string
     */
    protected $classpath;
    /*
     * The PHPCrawlerProcessReport-Object:
     * contains summarizing report-information about the crawling-process after it has finished.  
     *
     * @var PHPCrawlerProcessReport
     */
    public $crawler_reports;

    /**
     * Initiates a new Sitemap.
     * 
     * @param string    $site   The url may contain the protocol (http://www.foo.com or https://www.foo.com), the port (http://www.foo.com:4500/index.php)
     *                                        and/or basic-authentication-data (http://loginname:passwd@www.foo.com)
     * @section 1 Settings                            
     */
    public function __construct($site = '') {
        // Include needed class-files        
        $this->classpath = dirname(__FILE__);
        // PHPCrawlerUtils class
        if (!class_exists("PHPCrawlerUtils"))
            require_once($this->classpath . "/libs/PHPCrawler/PHPCrawlerUtils.class.php");
        //set website URL to default host
        if ($site == '')
            $site = 'http://' . $_SERVER['HTTP_HOST'] . '/';
        $this->setSite($site);
        //get current time
        $this->now = time();
        //set data dir to system temp directory
        $this->data_dir = sys_get_temp_dir();
    }

    /**
     * Sets the URL of the website {@link $site}
     *
     * Normalizes the given URL and returns a full qualified and normalized URL.
     * The method also generates {@link $sitemaps_url} if not set.
     * 
     * * This method throws an exception if the URL is invalid
     *
     * @param string    $site   The url may contain the protocol (http://www.foo.com or https://www.foo.com), the port (http://www.foo.com:4500/index.php)
     *                                        and/or basic-authentication-data (http://loginname:passwd@www.foo.com)
     * @return string|bool  SitemapCreator::$site|false Returns the valid normalized URL on success or false on failure
     * @section 1 Settings  
     */
    public function setSite($site) {
        $site = trim($site);
        if (!empty($site) && is_string($site)) {
            $this->site = PHPCrawlerUtils::normalizeURL($site);

            //create sitemap URL from website URL
            //@example http://example.com/1/index.php -> http://example.com/1/sitemap.php?s=
            if (!isset($this->sitemaps_url))
                $this->sitemaps_url = preg_replace('`([^/]/)[^/]*$`', '\\1sitemap.php?s=', $this->site);
            return $this->site;
        }else
            throw new Exception("Invalid URL: {$site}.");
        return false;
    }

    /**
     * Sets the data directory path of the sitemaps {@link $data_dir}
     *
     * The directory is used to store sitemaps and csv files. If not set then
     * sys_get_temp_dir() will be used.
     * 
     * * This method throws an exception if the path is not valid or the directory is not writable.
     *
     * @example
     *  /server/html/example.com/sitemaps/
     * 
     * @see prepareSitemapsDir()
     *
     * @param string $dir data directory {@link $data_dir}
     * @section 1 Settings 
     */
    public function setDataDir($dir) {
        $dir = rtrim($dir, '/');
        if (!is_dir($dir) || !$this->isDataDirWritable($dir))
            throw new Exception("Invalid directory path or directory is not writable: '{$dir}'");
        $this->data_dir = $dir;
    }

    /**
     * Sets the URL of the sitemap files {@link $sitemaps_url}
     *
     * Sitemap URL where the sitemap file name will be appended to the end of the URL.
     * @example
     *  http://www.example.com/sitemap.php?=
     * 
     * @see getSitemapURL()
     *
     * @param string $url The sitemap URL
     * @section 1 Settings 
     */
    public function setSitemapURL($url) {
        $this->sitemaps_url = $url;
    }

    /**
     * Sets number of URLs set for each sitemap file {@link $entries_per_sitemap}
     *
     * Each sitemap file should have a maximum of 50,000 URL. 
     * Use this function to change the number of URLs set per sitemap.
     * @example
     *  $sitemap->entries_per_sitemap = 20000;
     *
     * @param int $number  number greater than 0
     * @return bool true on success, false otherwise
     * @section 1 Settings 
     */
    public function setEntriesPerSitemap($number) {
        if (!is_numeric($number) || 1 > $number || 50000 < $number)
            return false;
        $this->entries_per_sitemap = $number;
        return true;
    }

    /**
     *  Use gzip compressed sitemaps files {@link $use_gzip}
     *
     * @param bool $mode  true to enable gzip, false otherwise
     * @section 1 Settings 
     */
    public function useGzip($mode) {
        $this->use_gzip = ($mode);
    }

    /**
     *  Set the URLs sets manually {@link $entries}
     * 
     * * this method throws exception if $entires is not an array or 
     *   the first entry does not have 'URL' key 
     * 
     * @param array $entries  array of entries to be added to sitemap
     *                                        @example array(
     *                                                              array(
     *                                                                  "URL"=>"http://example.com/",
     *                                                                  "Priority" => 0.8,
     *                                                                  "Last-Modified" => 3455554,
     *                                                                  "Frequency" => "always"
     *                                                                   ),......);
     * @section 1 Settings 
     */
    public function setEntries($entries) {
        if (!is_array($entries) || empty($entries[0]['URL']))
            throw new exception('Invalid URLs set');
        $this->entries = $entries;
    }

    /**
     *  Set Priority mode {@link $priority_mode}
     * 
     * Choose how the Priority of every URL should be calculated
     * @link http://www.sitemaps.org/protocol.html#prioritydef
     * 
     * @see setMinPriority()
     *
     * @param int $mode   number between 0 and 2 or  use the predefined constants
     *                                  SitemapCreator::PRIORITY_Disable  Disables priority calculations
     *                                  SitemapCreator::PRIORITY_CRAWLED_FIRST Crawled first pages get higher priority
     *                                  SitemapCreator::PRIORITY_URL_STRUCTURE Deeper pathes get lower priority
     * @return bool true on success, false otherwise
     * @section 1 Settings 
     */
    public function setPriority($mode) {
        if (!preg_match("/^[0-2]{1}$/", $mode))
            return false;
        $this->priority_mode = $mode;
        return true;
    }

    /**
     *  Set minimum Priority value for all URLs {@link $min_priority}
     * 
     * @see setMinPriority()
     *
     * @param float $mode  number between 0 and 1.0
     * @return bool true on success, false otherwise
     * @section 1 Settings 
     */
    public function setMinPriority($mode) {
        if ($mode > 1 || !preg_match("/^[0-9\.]{3}$/", $mode))
            return false;
        $this->min_priority = $mode;
    }

    /**
     *  Set Frequency mode {@link $frequency_mode}
     * 
     * Choose how the Frequency of every URL should be calculated
     * @link http://www.sitemaps.org/protocol.html#changefreqdef
     * 
     * @see setMinFrequency()
     *
     * @param int $mode   number between 0 and 2 or  use the predefined constants
     *                                  SitemapCreator::FREQUENCY_Disable  Disables frequency calculations
     *                                  SitemapCreator::FREQUENCY_LAST_MODIFIED Latest modified pages get higher frequency
     *                                  SitemapCreator::FREQUENCY_PRIORITY Higher priority pages get higher frequency
     * @return bool true on success, false otherwise
     * @section 1 Settings 
     */
    public function setFrequency($mode) {
        if (!preg_match("/^[0-2]{1}$/", $mode))
            return false;
        $this->frequency_mode = $mode;
        return true;
    }

    /**
     *  Set minimum Frequency value for all URLs {@link $min_priority}
     * 
     * @see setMinFrequency()
     *
     * @param string $mode  one of {@link $frequency_types} keys
     * @return bool true on success, false otherwise
     * @section 1 Settings 
     */
    public function setMinFrequency($mode) {
        $mode = strtolower(trim($mode));
        if (!in_array($mode, array_flip($this->frequency_types)))
            return false;
        $this->min_frequency = $mode;
        return true;
    }

    /**
     *  add URL entry manually to {@link $entries}
     * 
     * * this method throws exception if $entry is not an array or 
     *   does not have 'URL' key 
     * 
     * @param array $entry  URL set to be added to sitemap
     *                                      @example 
     *                                                  array(
     *                                                                  "URL"=>"http://example.com/",
     *                                                                  "Priority" => 0.8,
     *                                                                  "Last-Modified" => 3455554,
     *                                                                  "Frequency" => "always"
     *                                                     );
     * @section 1 Settings 
     */
    public function addEntry($entry) {
        if (!is_array($entry) || empty($entry['URL']))
            throw new exception('Invalid URLs set');
        $this->entries[] = $entry;
    }

    /**
     *  add a ping URL to the array {@link $engines}
     * 
     * 
     * @param string $url  ping URL of the search engine
     * @section 1 Settings 
     */
    public function addEngine($url) {
        $this->engines[] = $url;
    }

    /**
     *  Get sitemap file path
     * 
     * @see setDataDir()
     *
     * @param string $filename  'index' string or number
     * @return bool|string false on invalid $filename parameter, sitemap path on success
     * @section 2 Info 
     */
    public function getSitemapPath($filename) {
        if (!self::validSitemapName($filename))
            return false;
        $filename = $this->addSitemapEXT($filename);
        return $this->getSitemapsDir() . "/$filename";
    }

    /**
     *  Get sitemap file URL 
     * 
     * @see validSitemapName()
     * @see addSitemapEXT()
     *
     * @param string $filename  'index' string or number
     * @return bool|string false on invalid $filename parameter, sitemap path on success
     * @section 2 Info 
     */
    public function getSitemapURL($filename) {
        if (!self::validSitemapName($filename))
            return false;
        //add file extension
        $filename = $this->addSitemapEXT($filename);
        return $this->sitemaps_url . $filename;
    }

    /**
     *  Get sitemap directory name
     * 
     *  Get this site's directory name where sitemaps are strored.
     *  The directory is created inside the {@link $data_dir} directory.
     * @example
     *  /server/datadir/http__example_com_
     * 
     * @return bool|string false if {@link $site} has not been set, sitemap dir path on success
     * @section 2 Info 
     */
    public function getSitemapDirName() {
        if ($this->site == '')
            return false;
        return preg_replace('`[^a-z]+`i', '_', $this->site);
    }

    /**
     *  Get sitemap directory path {@link $sitemaps_dir}
     * 
     *  Get this site's directory path where sitemaps are strored.
     *  The directory is created inside the {@link $data_dir} directory.
     * @example
     *  /server/datadir/http__example_com_
     * 
     * @return string SitemapCreator::sitemaps_dir sitemap dir path
     * @section 2 Info 
     */
    public function getSitemapsDir() {
        if (!isset($this->sitemaps_dir))
            $this->sitemaps_dir = $this->data_dir . '/' . $this->getSitemapDirName();
        return $this->sitemaps_dir;
    }

    /**
     *  Get URLs sets array {@link $entries}
     * 
     * @return array {@link $entries}
     * @section 2 Info 
     */
    public function getEntries() {
        return $this->entries;
    }

    /**
     *  Get data directory path {@link $data_dir}
     * 
     * @see setDataDir()
     * @return string {@link $data_dir}
     * @section 2 Info 
     */
    public function getDataDir() {
        return $this->data_dir;
    }

    /**
     *  Validates sitemap filename
     * 
     * @see getSitemapPath()
     * @see getSitemapURL()
     * @return bool
     * @section 2 Info 
     */
    static function validSitemapName($filename) {
        if (!preg_match('`^(index|[0-9]+)$`', $filename))
            return false;
        return true;
    }

    /**
     *  Initiate the crawler {@link $Crawler}
     * 
     * visit {@link http://phpcrawl.cuab.de/classreferences/index.html} for
     * full cralwer options which can be accessed through {@link $Crawler} object.
     * @example
     *  $sitemap->Crawler->enableCookieHandling(false);
     *       
     * * this method throws exception if {@link $site} has not been set
     * 
     * @see Crawl()

     * @return SMCCrawler object {@link SMCCrawler} 
     * @section 3 Crawler 
     */
    public function initCrawler() {
        if ($this->site == '')
            throw new Exception("Please add a Site URL 'SitemapCreator::setSite()' Before starting the cralwer 'SitemapCreator::initCrawler()'.");
        //load required class files
        //PHPCrawler
        if (!class_exists("PHPCrawler"))
            require_once( $this->classpath . "/libs/PHPCrawler/PHPCrawler.class.php");
        // Crawler-class
        if (!class_exists("SMCCrawler"))
            require_once( $this->classpath . "/SitemapCreatorCrawler.class.php");
        //Create Crwaler object
        $this->Crawler = new SMCCrawler();
        //set default settings
        $this->setCrawlerDefaults();
        //add site URL to the crawler
        $this->Crawler->setURL($this->site);
        //return the crawler object
        //@example $cralwer = $sitemap->initCrawler();
        return $this->Crawler;
    }

    /**
     *  Start the crawl process
     *  
     *  More related options could be found on {@link http://phpcrawl.cuab.de/classreferences/index.html}
     * 
     * @see initCrawler()
     * @section 3 Crawler 
     */
    public function Crawl() {
        //if the cralwr was not create, create one
        if (get_class($this->Crawler) != 'SMCCrawler')
            $this->initCrawler();
        //start crawling
        $this->Crawler->go();
        //add the entries from the crawler
        $this->setEntries($this->Crawler->entries);
        //save the reports from the crawler before destroying the object
        $this->crawler_reports = $this->Crawler->getProcessReport();
        //unset the crawler object to clear memory
        //@todo Not working! check PHPCrawl memory leak
        unset($this->Crawler);
        gc_collect_cycles();
    }

    /**
     *  Load default crawler settings for {@link $Crawler}
     *  
     * Internally load default options, no external calls allowed
     *  More related options could be found on 
     * {@link http://phpcrawl.cuab.de/classreferences/index.html}
     * 
     * @see Crawl()
     * @see initCrawler()
     * @section 3 Crawler 
     */
    protected function setCrawlerDefaults() {
        // Only receive content of files with content-type "text/html" 
        $this->Crawler->addContentTypeReceiveRule("#text/html#");
        // If this is set to TRUE, the crawler tries to find links everywhere in an html-page, even outside of html-tags.
        $this->Crawler->enableAggressiveLinkSearch(false);
        // The crawler will only follow links that lead to the same host like the one in the root-url.
        $this->Crawler->setFollowMode(2);
        //Sets the "User-Agent" identification-string that will be send with HTTP-requests.
        $this->Crawler->setUserAgentString(self::$useragent);
        //Decides whether the crawler should obey "nofollow"-tags
        $this->Crawler->obeyNoFollowTags(true);
        // Ignore links to pictures, dont even request pictures 
        $this->Crawler->addURLFilterRule("#\.(jpg|jpeg|gif|png|bmp|js|css|ico)$# i");
        // Store and send cookie-data like a browser does 
        $this->Crawler->enableCookieHandling(true);
        // enable Get Last-Modified date from page header
        $this->Crawler->enableLastModifiedCount(true);
        //Decides whether the crawler should parse and obey robots.txt-files.
        $this->Crawler->obeyRobotsTxt(true);
    }

    /**
     * Creates sitemaps directory {@link $sitemaps_dir}
     * 
     * * this method throws exception if data is not writable
     * @see setDataDir();
     * @section 4 Sitemap  
     */
    protected function prepareSitemapsDir() {
        //Get sitemap directory path  {@link $sitemaps_dir}
        $this->getSitemapsDir();
        //check if dir exists
        clearstatcache();
        if (file_exists($this->sitemaps_dir))
            return;
        //check if dir is writable
        if (!$this->isDataDirWritable($this->data_dir))
            throw new Exception("Data directory {$this->data_dir} is not writable");
        //create sitemap dir
        mkdir($this->sitemaps_dir);
    }

    /**
     * Check if data directory is writable {@link $data_dir}
     * 
     * @return bool true on success, false otherwise
     * @see setDataDir()
     * @see prepareSitemapsDir()
     * @section 4 Sitemap  
     */
    protected function isDataDirWritable($dir) {
        if (!is_writable($dir) && !chmod($dir, 0777))
            return false;
        return true;
    }

    /**
     * adds sitemap gzip extension to sitemap filename if {@link $use_gzip} enabled
     * 
     * @return string filename 
     * @see useGzip();
     * @section 4 Sitemap  
     */
    protected function addSitemapEXT($filename) {
        return "{$filename}.xml" . ( $this->use_gzip ? '.gz' : '' );
    }

    /**
     * Create sitemaps files and index
     * 
     * @param array $entries  array of entries to be added to sitemap
     *                                        @example array(
     *                                                              array(
     *                                                                  "URL"=>"http://example.com/",
     *                                                                  "Priority" => 0.8,
     *                                                                  "Last-Modified" => 3455554,
     *                                                                  "Frequency" => "always"
     *                                                                   ),......);
     * @section 4 Sitemap  
     */
    //@todo add benchmark
    public function CreateSitemaps($entries = array()) {
        if (!empty($entries))
            $this->setEntries($entries);
        //prepare write dir
        $this->prepareSitemapsDir();
        //@todo add stylesheet
        //adding XML schemas
        $this->xml_head = '<?xml version="1.0" encoding="UTF-8"?>' .
                '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $this->xml_foot = '</urlset>';
        //storing entries count
        $total_entries = count($this->entries);
        //iterate through entries
        for ($i = 0; $i < $total_entries; $i++) {
            //calculate priority
            $this->calcPriority($i, $this->entries[$i], $total_entries);
            //calculate frequency
            $this->calcFrequency($i, $this->entries[$i]);
            //add final XML code
            $this->addXMLURLSet($this->entries[$i]);
            //check if max {@link $entries_per_sitemap} has been reached
            //or reached Total URLs 
            $x = $i + 1;
            if (( $x % $this->entries_per_sitemap ) == 0
                    || $x == $total_entries) {
                //write sitemap file
                $this->writeSitemap();
            }
        }
        //create sitemaps index
        $this->writeIndex();
    }

    /**
     * calculates Priority for each entry {@link $priority_mode}
     * 
     *
     * @param int  $key array offset
     *                      $entry reference to array offset in {@link $entries}
     *                      $total_entries {@link $entries} array count
     * @section 4 Sitemap  
     */
    protected function calcPriority($key, &$entry, $total_entries) {
        //no priority?
        if ($this->priority_mode == self::PRIORITY_Disable)
            return;
        //Home page is highest priority
        if ($key == 0) {
            $entry['Priority'] = 1.0;
            return;
        }
        //storing homepage URL slices count to save from over-counting
        static $site_url_slices_num = 0;
        if ($this->priority_mode == self::PRIORITY_URL_STRUCTURE
                && !$site_url_slices_num)
            $site_url_slices_num = count(explode('/', $this->entries[0]['URL']));

        switch ($this->priority_mode) {
            case self::PRIORITY_CRAWLED_FIRST:
            default:
                //crawled first URLs should have higher priority
                $entry['Priority'] = round(($total_entries - $key) / $total_entries, 1);
                break;
            case self::PRIORITY_URL_STRUCTURE:
                //deeper URL have more slices thus have less priority
                $URL_slices = explode('/', $entry['URL']);
                //URL depth is always related to homepage depth
                $entry['Priority'] = round(( (1 / count($URL_slices)) * $site_url_slices_num) + 0.1, 1);
                break;
        }
        //respect min priority
        if ($entry['Priority'] < $this->min_priority)
            $entry['Priority'] = $this->min_priority;
    }

    /**
     * calculates Frequency for each entry {@link $priority_mode}
     * 
     * @see $frequency_types
     * @param int  $key array offset
     *                      $entry reference to array offset in {@link $entries}
     * @section 4 Sitemap  
     */
    //@todo Cache-Control: max-age=86400
    protected function calcFrequency($key, &$entry) {
        //Frequency disabled?
        if ($this->frequency_mode == self::FREQUENCY_Disable)
            return;
        //Home page is highest Frequency
        if ($key == 0) {
            $entry['Frequency'] = 'always';
            return;
        }
        switch ($this->frequency_mode) {
            case self::FREQUENCY_LAST_MODIFIED:
            default:
                if (!empty($entry['Last-Modified'])) {
                    //get difference in time between the date page last modified
                    //and current date.
                    $diff = $this->now - $entry['Last-Modified'];
                    //compare difference to {@link $frequency_types} values
                    foreach ($this->frequency_types as $type => $value) {
                        if ($diff < $value) {
                            //set frequency to the suitable frequency type in {@link $frequency_types} 
                            $entry['Frequency'] = $type;
                            break;
                        }
                    }
                }
                break;
            case self::FREQUENCY_PRIORITY:
                //set Frequency based on priority
                if ($entry['Priority'] >= 0.9)
                    $entry['Frequency'] = 'always';
                elseif ($entry['Priority'] >= 0.7)
                    $entry['Frequency'] = 'hourly';
                elseif ($entry['Priority'] >= 0.6)
                    $entry['Frequency'] = 'daily';
                elseif ($entry['Priority'] >= 0.4)
                    $entry['Frequency'] = 'weekly';
                elseif ($entry['Priority'] >= 0.2)
                    $entry['Frequency'] = 'monthly';
                elseif ($entry['Priority'] >= 0.1)
                    $entry['Frequency'] = 'yearly';
                else
                    $entry['Frequency'] = 'never';
                break;
        }
        //respect min frequency
        if ($this->frequency_types[$entry['Frequency']] > $this->frequency_types[$this->min_frequency])
            $entry['Frequency'] = $this->min_frequency;
    }

    /**
     * Add single XML code to {@link $xml_url_set}
     * 
     * @param array  $entry  URL set entry
     * @section 4 Sitemap  
     */
    protected function addXMLURLSet($entry) {
        //ignore if 'URL' offset is empty
        if (empty($entry['URL']))
            return;
        $this->xml_url_set .= '<url>';
        //encode URL
        $this->xml_url_set .= '<loc>' . utf8_encode(htmlentities($entry['URL'], ENT_QUOTES)) . '</loc>';
        if (!empty($entry['Last-Modified']))
            $this->xml_url_set.= "<lastmod>" . gmdate("Y-m-d\TH:i:s", $entry['Last-Modified']) . "+00:00</lastmod>";
        if (!empty($entry['Frequency']))
            $this->xml_url_set.= "<changefreq>{$entry['Frequency']}</changefreq>";
        if (!empty($entry['Priority']))
            $this->xml_url_set .= "<priority>{$entry['Priority']}</priority>";
        $this->xml_url_set .= '</url>';
    }

    /**
     *  Write sitemap file
     * @section 4 Sitemap  
     */
    protected function writeSitemap() {
        $this->sitemaps_count += 1;
        $this->putContent($this->getSitemapPath($this->sitemaps_count), $this->xml_head . $this->xml_url_set . $this->xml_foot);
        $this->xml_url_set = '';
    }

    /**
     *  Write sitemap index file
     * @section 4 Sitemap  
     */
    protected function writeIndex() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' .
                '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        for ($i = 1; $i <= $this->sitemaps_count; $i++) {
            $xml .= '<sitemap>' .
                    '<loc>' . $this->getSitemapURL($i) . '</loc>' .
                    '<lastmod>' . gmdate("Y-m-d\TH:i:s", time()) . '</lastmod>' .
                    '</sitemap>';
        }
        $xml .= '</sitemapindex>';
        $this->putContent($this->getSitemapPath('index'), $xml);
    }

    /**
     * Ping search engines 
     * 
     * @see addEngine()
     * 
     * @param string|int  $filename 'index' string or number
     * @return array $results Array contains errors as $results['google']['error'], or respond body as $request['google']['body']
     * @section 4 Sitemap  
     */
    public function ping($filename = 'index') {
        $results = array();
        //get full sitemap encoded URL
        $sitemap = urlencode($this->getSitemapURL($filename));
        foreach ($this->engines as $engine => $url)
            $results[$engine] = self::openURL($url . $sitemap);

        return $results;
    }

    /**
     * Open URL and get respond body or error
     * 
     * @see Ping()
     * 
     * @param string  $url  URL to retrieve
     * @param int $max_redirects max allowed redirects (optional)
     * @param int $timeout process timeout (optional )
     * @return array $results Array contains errors as $results['error'], or respond body as $request['body']
     * @section 4 Sitemap  
     */
    static function openURL($url, $max_redirects = 5, $timeout = 15) {
        $user_agent = self::$useragent;
        ini_set('user_agent', $user_agent);
        static $redirects = 0;
        $result = array();
        $url_parts = PHPCrawlerUtils::splitURL($url);
        if (($fp = @fsockopen($url_parts['host'], $url_parts['port'], $errno, $errstr, $timeout)) === false) {
            switch ($errno) {
                case -3: $result['error'] = 'Socket creation failed (-3)';
                    break;
                case -4: $result['error'] = 'DNS lookup failure (-4)';
                    break;
                default: $result['error'] = 'Connection failed (' . $errno . ') ' . $errstr;
                    break;
            }
            return $result;
        }
        $get = "GET {$url_parts['path']}{$url_parts['file']}{$url_parts['query']} HTTP/1.1\r\n";
        $get .= "Host: {$url_parts['host']}\r\n";
        $get .= "User-Agent: {$user_agent})\r\n";
        $get .= "Connection: close\r\n\r\n";
        socket_set_timeout($fp, $timeout);
        stream_set_blocking($fp, 3);
        fwrite($fp, $get);
        //reading headers
        while ('' != ($line = trim(fgets($fp)))) {
            if (false !== ($pos = strpos($line, ':'))) {
                $header = strtolower(trim(substr($line, 0, $pos)));
                $val = trim(substr($line, $pos + 1));
                //redirection
                if ($header == 'location') {
                    if ($redirects >= $max_redirects) {
                        $result['error'] = "Max redirects reached: {$max_redirects}";
                        return $result;
                    }
                    $redirects++;
                    return self::openURL($val);
                }
                //response code
            } elseif (preg_match('#(?:^|\s)(?!200|302|301)([0-9]{3})\s?(.*)$#', $line, $code)) {
                $result['error'] = 'HTTP Error (' . $code[1] . ') ' . $code[2];
                return $result;
            }
        }
        //body
        $result['body'] = '';
        while (!feof($fp)) {
            $result['body'] .= fread($fp, 1024);
        }
        fclose($fp);
        return $result;
    }

    /**
     * Write XML to disk
     * 
     * @param string  $file file path
     * @param $XML XML code
     * @section 4 Sitemap  
     */
    public function putContent($file, $XML) {
        //write files with gzip format
        if ($this->use_gzip) {
            $fh = gzopen($file, 'wb');
            gzwrite($fh, $XML);
            gzclose($fh);
        }else
            file_put_contents($file, $XML, LOCK_EX);
    }

    /**
     * Read sitemap file from disk
     * 
     * *this method throws exception if sitemap file doesn't exsit
     * @param string|int  $filename 'index' string or number
     * @section 4 Sitemap  
     */
    public function readSitemap($filename) {
        $file = $this->getSitemapPath($filename);
        if (!file_exists($file))
            throw new Exception("Invalid sitemap {$file}");
        //add XML header
        header("Content-Type: text/xml");
        if ($this->use_gzip)
            readgzfile($file);
        else
            readfile($file);
    }

    /**
     * Add index sitemap URL to robots.txt file
     * 
     * * this method throws exception if robots file doesn't exist and is writable
     * @param string $robots_file Robots.txt file path
     * @return string $robotstxt robots.txt text 
     * @section 4 Sitemap  
     */
    public function addToRobots($robots_file = '') {
        //if no path given create one
        if ($robots_file == '')
            $robots_file = $_SERVER['DOCUMENT_ROOT'] . '/robots.txt';
        //check if file doesn't exist and is writable
        if (!@touch($robots_file))
            throw new Exception("File {$robots_file}is not writable");
        $robotstxt = file_get_contents($robots_file);
        $addtxt = "\nSitemap : " . $this->getSitemapURL('index') . "\n";
        //check if sitemap URL already added
        if (stripos($robotstxt, $addtxt) !== false)
            return false;
        //add sitemap URL
        if (!file_put_contents($robots_file, $robotstxt . $addtxt, LOCK_EX))
            return false;
        //return robots text
        return $robotstxt;
    }

    /**
     * Delete all sitemap dir and files
     * 
     * @return bool true on success, false otherwise
     * @section 4 Sitemap  
     */
    public function removeSitemaps() {
        $this->getSitemapsDir();
        //check if dir exists
        if (!file_exists($this->sitemaps_dir))
            return false;
        //iterate through the sitemap directory to remove files
        $iterator = new RecursiveDirectoryIterator($this->sitemaps_dir, RecursiveIteratorIterator::CHILD_FIRST | FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $path)
            if ($path->isFile())
                unlink($path->__toString());
        //remove dir
        return rmdir($this->sitemaps_dir);
    }

    /**
     * Write to CSV file
     * 
     * @param string file path
     * @return bool true on success, false otherwise
     * @section 5 CSV  
     */
    public function writeToCSV($file = '') {
        //end if not entries
        if (empty($this->entries))
            return false;
        //if path is set check if it's valid otherwise use default
        if ($file != '')
            $file = $this->csvFile();
        //check if file path is valid
        if (!touch($file))
            return false;
        //write file
        $fp = fopen($file, 'w');
        //CSV header
        fwrite($fp, "URL\tPriority\tLast-Modified\tFrequency\n");
        foreach ($this->entries as $entry)
            fwrite($fp, "{$entry['URL']}\t{$entry['Priority']}\t{$entry['Last-Modified']}\t{$entry['Frequency']}\n");
        fclose($fp);
        return true;
    }

    /**
     * Read from CSV file and add to {@link $entries}
     * 
     * @param string file path
     * @return bool true on success, false otherwise
     * @section 5 CSV  
     */
    public function readFromCSV($file = '') {
        //if path is set check if it's valid otherwise use default
        if ($file == '')
            $file = $this->csvFile();
        //open file and make sure is readable
        $fp = fopen($file, 'r');
        if (!$fp)
            return false;
        //get file header
        $header = fgetcsv($fp, 1000, "\t");
        $header_count = count($header);
        //add entries
        while (($data = fgetcsv($fp, 1000, "\t")) !== false) {
            //add headers to entry array
            for ($i = 0, $entry = array(); $i < $header_count; $i++)
                $entry[$header[$i]] = $data[$i];
            $this->addEntry($entry);
        }
        fclose($fp);
        return true;
    }

    /**
     * get CSV file path
     * 
     * @return string CSV file path
     * @section 5 CSV  
     */
    public function csvFile() {
        return "{$this->data_dir}/" . $this->getSitemapDirName() . "/entries.csv";
    }

}