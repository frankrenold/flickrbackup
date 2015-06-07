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

require 'vendor/autoload.php';
use GuzzleHttp\Client;

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
	    $avar = array();
	    foreach($data as $key => $value) {
		    $avar[] = urlencode($key).'='.urlencode($value);
	    }
	    $data = implode('&', $avar);
        $response = $this->client->post($url, array('body' => $data, 'headers' => $headers));
        
        var_dump($response);
        
        return $response->getBody();
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
        $multi_request = $this->client->getCurlMulti();
        foreach ($requests as &$request) {
            $request = $this->client->post($request['url'], $request['headers'], $request['data']);
            $multi_request->add($request);
        }
        unset($request);

        $multi_request->send();

        $responses = array();
        /** @var RequestInterface[] $requests */
        foreach ($requests as $request) {
            $responses[] = $request->getResponse()->json();
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