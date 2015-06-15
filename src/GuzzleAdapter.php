<?php
namespace FrankRenold\FlickrBackup;

/**
 * HTTP-Adapter based on guzzlehttp/guzzle
 * 
 * @vendor FrankRenold
 * @package FlickrBackup
 * @author Frank Renold <frank.renold@gmail.com>
 * @license opensource.org/licenses/MIT The MIT License (MIT)
 */

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class GuzzleAdapter implements \Rezzza\Flickr\Http\AdapterInterface
{
    
    private $client;
    
    public function __construct()
    {
        if (!class_exists('\GuzzleHttp\Client')) {
            throw new \LogicException('Please, install guzzlehttp/guzzle before using this adapter.');
        }
        
        $this->client  = new Client(array('allow_redirects' => false, 'expect' => false));
    }
    
    /**
     * {@inheritdoc}
     */
    public function post($url, array $data = array(), array $headers = array())
    {
        $request = $this->client->post($url, array('form_params' => $data, 'headers' => $headers));
        $response = $request->getBody();
        
        // var_dump($response->read($response->getSize()));
        return unserialize($response->read($response->getSize()));
    }

    /**
     * @param array $requests
     * An array of Requests
     * Each Request is an array with keys: url, data and headers
     *
     * @return \SimpleXMLElement[]
     */
    public function multiPost(array $requests)
    {
	    $responses = array();
        foreach ($requests as &$request) {
	        $request = $this->client->post($request['url'], array('form_params' => $request['data'], 'headers' => $request['headers']));
			$response = $request->getBody();
			$responses[] = unserialize($response->read($response->getSize()));
        }

        return $responses;
    }
    
    /**
     * @return $client
     */
    public function getClient()
    {
        return $this->client;
    }

}