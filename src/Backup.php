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
    
    /**
     * @var boolean
     */
    protected $setsComplete = false;

    
    public function __construct($configDir, $dataDir) {
	    // init stats
	    $this->flickrCalls = 0;
	    $this->filesLoaded = 0;
        $this->createTime = time();
        // load config and init dataDir
        $this->loadConfig($configDir);
        $this->dataDir = $dataDir;
        if(!file_exists($this->dataDir) && !is_dir($this->dataDir)) {
	        mkdir($this->dataDir);
        }
        // prepare access to api
        $metadata = new Flickr\Metadata($this->config['app_key'], $this->config['app_secret']);
		$metadata->setOauthAccess($this->config['access_token'], $this->config['access_secret']);
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
			"format" => "php_serial",
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
	    if((string)$res['stat'] == "ok" && !empty($res['photos']['photo'])) {
		    // store media
		    foreach($res['photos']['photo'] as $media) {
			    $this->media[] = $media;
		    }
		    if(count($this->media) < $max && (int)$res['photos']['pages'] > $page) {
			    // call next page
			    $page++;
			    return $this->loadMedia($max, $args_overwrite, $page);
		    } else {
			    // load set cache
			    $this->_getAllSets();
			    if(count($this->media) > count($this->sets)/2) { // TODO: > is correct
					// cache additional info for all photosets
					foreach($this->sets as $set) {
						$set = $this->_loadSetInfo($set);
					}
					$this->allSetsLoaded = true;
				}
				// backup all media
				foreach($this->media as $medium) {
					$this->download($medium);
				}
				
				
			    return true;
		    }
	    }
	    return false;
    }
    
    private function download($medium) {
	    //get contexts
	    $contexts = array();
		if($this->setsComplete) {
			foreach($this->sets as $set) {
				if(in_array($medium['id'], $set['fb_mids'])) {
					$contexts[] = $set;
				}
			}
		} else {
			$args = array(
			    "photo_id" => $medium['id'],
			    "format" => "php_serial",
		    );
		    $res = $this->call('flickr.photos.getAllContexts', $args);
		    
		    if((string)$res['stat'] == "ok" && !empty($res['set'])) {
			    // store sets to contexts
			    foreach($res['set'] as $set) {
				    if(!isset($this->sets[$set['id']]['fb_mids'])) {
					    // not yet prepared with additional info
					    $this->sets[$set['id']] = $this->_loadSetInfo($this->sets[$set['id']]);
				    }
				    $contexts[] = $this->sets[$set['id']];
			    }
		    }
		}
		
		// create paths to safe file
		$setpaths = array();
		$settags = array();
		if(!empty($contexts)) {
			foreach($contexts as $set) {
				// folder exists?
				$setpath = $this->dataDir.'/'.date('Y-m-d', $this->sets[$set['id']]['fb_startdate']).'_'.$this->sets[$set['id']]['fb_setname'].'_'.$set['id'];
				if(!file_exists($setpath) && !is_dir($setpath)) {
					mkdir($setpath);
				}
				// add setpath
				$setpaths[] = $setpath;
				// add settag
				$settags[] = 'set-'.$this->sets[$set['id']]['fb_startdate'].':'.$this->sets[$set['id']]['fb_setname'];
			}
		} else {
			// folder exists?
			$setpath = $this->dataDir.'/'.'no-sets';
			if(!file_exists($setpath) && !is_dir($setpath)) {
				mkdir($setpath);
			}
			$setpaths[] = $setpath;
		}
		
		// download file
		$fname = (!empty($medium['title']) ? preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(array(' ','/','_'), '-', $medium['title'])).'_' : '').$medium['id'].'.'.$medium['originalformat'];
		$dpath = $setpaths[0].'/'.$fname;
		file_put_contents($dpath, fopen($medium['url_o'], 'r'));
		$this->filesLoaded++;
		
		// prepare tags
		$atags = array_merge(explode(' ', $medium['tags']), $settags);
		
		// set default tags
		$et_tags = array(
			"force" => array(
				"ObjectName" => $medium['title'],
				"Headline" => $medium['title'],	
				"XMP-dc:Title" => $medium['title'],	
				"Caption-Abstract" => $medium['description']['_content'],
				"ImageDescription" => $medium['description']['_content'],
				"XMP-dc:Description" => $medium['description']['_content'],
				"DateCreated" => date('Y:m:d', strtotime($medium['datetaken'])),
				"TimeCreated" => date('H:i:sP', strtotime($medium['datetaken'])),
				"CreateDate" => date('Y:m:d H:i:s', strtotime($medium['datetaken'])),
				"ModifyDate" => date('Y:m:d H:i:s', strtotime($medium['datetaken'])),
				"DateTimeOriginal" => date('Y:m:d H:i:s', strtotime($medium['datetaken'])),
				"XMP-xmp:CreateDate" => date('Y:m:d H:i:sP', strtotime($medium['datetaken'])),
			),
			"notset" => array(),
			"lists" => array(
				"Keywords" => $atags,
				"XMP-dc:Subject" => $atags,
			)
		);
		
		// set gps tags if not set in original file
		// check if file has no gps data included
		$gpsfound = array();
		exec('exiftool -a -gps:all '.$dpath, $gpsfound);
		if(empty($gpsfound)) {
			if(!in_array('snogeo', $atags)) {
				echo "Looking for additional GPS-Data on Flickr: ".$dpath."\n";
				// check for gps data on flickr				
				$args = array(
				    "photo_id" => $medium['id'],
				    "format" => "php_serial",
			    );
			    $res = $this->call('flickr.photos.geo.getLocation', $args);
			    if($res['stat'] == 'ok') {
				    $lata = self::DECtoDMS($res['photo']['location']['latitude']);
					$lona = self::DECtoDMS($res['photo']['location']['longitude']);
					
					$et_tags['force']["GPSLatitudeRef"] = $lata['rlat'];
					$et_tags['force']["GPSLatitude"] = $res['photo']['location']['latitude'];
					$et_tags['force']["GPSLongitudeRef"] = $lata['rlong'];
					$et_tags['force']["GPSLongitude"] = $res['photo']['location']['longitude'];
			    } else {
				    echo "No geodata available for file: ".$dpath."\n";
			    }
			} else {
				echo "No geodata available (Taged in Flickr) for file: ".$dpath."\n";
			}
		} else {
			echo "GPS-Data found in file: ".$dpath."\n";
		}
		
		// update metadata by exiftool
		$command = "exiftool -F -m -overwrite_original";
		foreach($et_tags['force'] as $name => $value) {
			$command .= " -".$name."='".$value."'";
		}
		foreach($et_tags['notset'] as $name => $value) {
			$command .= " -".$name."-= -".$name."='".$value."'";
		}
		foreach($et_tags['lists'] as $name => $value) {
			foreach($value as $litem) {
				$command .= " -".$name."='".$litem."'";
			}
		}
		$command .= " ".$dpath;
		$return = exec($command);
		
		// symlink to other sets
		$fname = (!empty($medium['title']) ? preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(array(' ','/','_'), '-', $medium['title'])).'_' : '').$medium['id'].'_l.'.$medium['originalformat'];
		for($i=1; $i<count($setpaths); $i++) {
			if(is_file($setpaths[$i].'/'.$fname)) {
				unlink($setpaths[$i].'/'.$fname);
			}
			symlink($dpath, $setpaths[$i].'/'.$fname);
		}
    }
    
    public function getMedia() {
	    return $this->media;
    }
    
    private function _getAllSets($page = 1) {
	    $args = array(
		    "user_id" => $this->getConfig('user_id'),
		    "per_page" => "500",
		    "page" => (string)$page,
		    "format" => "php_serial",
	    );
	    $res = $this->call('flickr.photosets.getList', $args);
	    
	    if((string)$res['stat'] == "ok" && !empty($res['photosets']['photoset'])) {
		    // store set
		    foreach($res['photosets']['photoset'] as $set) {
			    $this->sets[$set['id']] = $set;
		    }
		    if((int)$res['photosets']['pages'] > $page) {
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
		if($page == 1) {
			// setup additional info
			$set['fb_setname'] = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(array(' ','/'), '-', $set['title']['_content']));
			$set['fb_mids'] = array();
			$set['tmp_photos'] = array();
		}
		$args = array(
			"photoset_id" => (string)$set['id'],
		    "user_id" => $this->getConfig('user_id'),
		    "extras" => 'date_taken',
		    "per_page" => "500",
		    "page" => (string)$page,
		    "format" => "php_serial",
	    );
	    $res = $this->call('flickr.photosets.getPhotos', $args);
	    
	    if((string)$res['stat'] == "ok" && !empty($res['photoset']['photo'])) {
		    // save result temporary
			$set['tmp_photos'] = array_merge($set['tmp_photos'], $res['photoset']['photo']);
		    foreach($res['photoset']['photo'] as $photo) {
			    // save media id to set
			    $set['fb_mids'][] = $photo['id'];
		    }
		    if((int)$res['photoset']['pages'] > $page) {
			    // call next page
			    $page++;
			    return $this->_loadSetInfo($set, $page);
		    } else {
			    // sort images and save photoset start date
			    usort($set['tmp_photos'], array('\FrankRenold\FlickrBackup\Backup','cmpSetByDateTaken'));
			    $set['fb_startdate'] = strtotime($set['tmp_photos'][0]['datetaken']);
			    unset($set['tmp_photos']);
			    return $set;
		    }
	    }
	    return false;
	}
	
	public static function cmpSetByDateTaken($a, $b) {
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
