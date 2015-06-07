<?php
namespace FrankRenold\FlickrBackup;

/**
 * Backing up from Flickr
 * 
 * Used for command line tool
 * 
 * @vendor FrankRenold
 * @package FlickrBackup
 * @author Frank Renold <frank.renold@gmail.com>
 * @license opensource.org/licenses/MIT The MIT License (MIT)
 */

require 'vendor/autoload.php';
use Rezzza\Flickr;

/**
* Backing up from Flickr
*/
class Backup {
	/**
     * @var int
     */
    protected $flickrCalls;
    
    /**
     * @var int
     */
    protected $filesLoaded;
    
    /**
     * @var int
     */
    protected $createTime;
    
    /**
     * @var array
     */
    protected $config;
    
    /**
     * @var string
     */
    protected $dataDir;
    
    /**
     * @var ApiFactory
     */
    protected $api;
    
    /**
     * @var array
     */
    protected $media = array();
    
    /**
     * @var array
     */
    protected $sets = array();

    
    public function __construct($configDir, $dataDir) {
	    // init stats
	    $this->flickrCalls = 0;
	    $this->filesLoaded = 0;
        $this->createTime = time();
        // load config and init dataDir
        $this->loadConfig($configDir);
        $this->dataDir = $dataDir;
        // prepare access to api
        $metadata = new Flickr\Metadata($this->config['app_key'], $this->config['app_secret']);
		$metadata->setOauthAccess($this->config['access_token'], $this->config['access_secret']);
		//$this->api = new Flickr\ApiFactory($metadata, new Flickr\Http\GuzzleAdapter());
		$this->api = new Flickr\ApiFactory($metadata, new GuzzleAdapter());
    }
    
    public function loadConfig($configDir) {
	    $config = json_decode(file_get_contents($configDir.'/config.json'), true);
	    $tokens = json_decode(file_get_contents($configDir.'/tokens.json'), true);
	    $this->config = array_merge($config, $tokens);
    }
    
    public function getConfig($key = null) {
	    if(!empty($key)) {
		    return $this->config[$key];
	    } else {
		    return $this->config;
	    }
    }
    
    public function call($request, $args) {
	    $this->flickrCalls++;
	    return $this->api->call($request, $args);
    }
    
    public function loadMedia($max = null, $args_overwrite = null, $page = 1) {
	    $args = array(
			"user_id" => $this->getConfig('user_id'),
			"extras" => "original_format,url_o,description,tags,date_taken",
			"sort" => "date-taken-asc",
			"per_page" => "500",
			"page" => (string)$page,
			"format" => "json",
		);
	    if(!empty($args_overwrite) && is_array($args_overwrite)) {
		    foreach($args_overwrite as $key => $value) {
			    $args[$key] = $value;
		    }
	    }
	    if(!empty($max)) {
		    $stored = count($this->media);
		    if($max < $stored + (int)$args['per_page']) {
			    $args['per_page'] = (string)($max-$stored);
		    }
	    }
	    $res = $this->call('flickr.photos.search', $args);
	    var_dump($res);
	    die();
	    if((string)$res->attributes()->stat == "ok" && !empty($res->photos->photo)) {
		    // store media
		    foreach($res->photos->photo as $media) {
			    $this->media[] = $media;
		    }
		    if(count($this->media) < $max && (int)$res->photos->attributes()->pages > $page) {
			    // call next page
			    $page++;
			    return $this->loadMedia($max, $args_overwrite, $page);
		    } else {
			    // load set cache
			    $this->_getAllSets();
			    if(count($this->media) < count($this->sets)/2) { // TODO: > is correct
					// cache all photosetsinfo
					foreach($this->sets as $set) {
						$this->_loadSetInfo($set);
					}
				}
			    return true;
		    }
	    }
	    return false;
    }
    
    public function getMedia() {
	    return $this->media;
    }
    
    private function _getAllSets($page = 1) {
	    $args = array(
		    "user_id" => $this->getConfig('user_id'),
		    "per_page" => "500",
		    "page" => (string)$page,
	    );
	    $res = $this->call('flickr.photosets.getList', $args);
	    
	    if((string)$res->attributes()->stat == "ok" && !empty($res->photosets->photoset)) {
		    // store set
		    foreach($res->photosets->photoset as $set) {
			    $this->sets[] = $set;
		    }
		    if((int)$res->photosets->attributes()->pages > $page) {
			    // call next page
			    $page++;
			    return $this->_getAllSets($page);
		    } else {
			    return true;
		    }
	    }
	    return false;
	}
	
	private function _loadSetInfo($set, $page = 1) {
		// load image IDs
		$imgs = $set->addChild('imgs');
		$args = array(
			"photoset_id" => (string)$set->attributes()->id,
		    "user_id" => $this->getConfig('user_id'),
		    "per_page" => "2",
		    "page" => (string)$page,
	    );
	    $res = $this->call('flickr.photosets.getPhotos', $args);
	    
	    if((string)$res->attributes()->stat == "ok" && !empty($res->photoset->photo)) {
		    // store image IDs
		    $imgs->addChild();
		    $set->addChild();
		    foreach($res->photosets->photoset as $set) {
			    $this->sets[] = $set;
		    }
		    if((int)$res->photos->photosets()->pages > $page) {
			    // call next page
			    $page++;
			    return $this->_getAllSets($page);
		    } else {
			    return true;
		    }
	    }
	    return false;
		
		$perPage = 500;
		$ps = $f->photosets_getPhotos($setid,'date_taken',5,$perPage,1);
		$flickrCalls++;
		$photos = $ps['photoset']['photo'];
		for($p=2; $p<=$ps['photoset']['pages']; $p++) {
			$pst = $f->photosets_getPhotos($setid,'date_taken',5,$perPage,$p);
			$flickrCalls++;
			$photos = array_merge($photos, $pst['photoset']['photo']);
		}
		$ids = array();
		foreach($photos as $photo) {
			$ids[] = $photo['id'];
		}
		usort($photos, 'cmpSetByDateTaken');
		return array('startDate' => strtotime($photos[0]['datetaken']), 'photoIds' => $ids);
	}
	
	function cmpSetByDateTaken($a, $b) {
		return (strtotime($a['datetaken']) < strtotime($b['datetaken'])) ? -1 : 1;
	}
    
    public function stats($print = true) {
	    $runTimeEnd = time();
	    $stats = array(
		    'RunTime' => gmdate("H:i:s", ($runTimeEnd - $this->createTime)),
		    'FlickrAPICalls' => $this->flickrCalls,
		    'FilesLoaded' => $this->filesLoaded,
	    );
	    if($print) {
		    echo "DONE!\n";
		    foreach($stats as $label => $data) {
			    echo $label.": ".$data."\n";
		    }
	    } else {
		    return $stats;
	    }
    }
    
    // Converts decimal longitude / latitude to DMS
	// ( Degrees / minutes / seconds ) 
	
	// This is the piece of code which may appear to 
	// be inefficient, but to avoid issues with floating
	// point math we extract the integer part and the float
	// part by using a string function.
	public static function DECtoDMS($dec) {
	    $vars = explode(".",$dec);
	    $deg = $vars[0];
	    $tempma = "0.".$vars[1];
	
	    $tempma = $tempma * 3600;
	    $min = intval(floor($tempma / 60));
	    $sec = $tempma - ($min*60);
	
	    return array("deg"=>abs($deg),"min"=>$min,"sec"=>$sec, "rlat" => ($deg<0 ? "S" : "N"), "rlong" => ($deg<0 ? "W" : "E"));
	}
	
}
