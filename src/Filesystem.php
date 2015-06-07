<?php
namespace Frankrenold\Flickrbackup;

use OAuth\Common\Token\TokenInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Storage\Exception\TokenNotFoundException;
use OAuth\Common\Storage\Exception\AuthorizationStateNotFoundException;

/**
 * Extension to OAuth
 * Stores a token in a JSON File.
 */
class Filesystem implements TokenStorageInterface
{
    /**
     * @var string
     */
    protected $fileDir;
    
    /**
     * @var array
     */
    protected $tokens;
    
    /**
     * @var array
     */
    protected $states;

    /**
     * @param string $tokenFile path to token file
     */
    public function __construct($fileDir = __DIR__ . '/../config/') {
        $this->fileDir = $fileDir;
        if(file_exists($this->fileDir.'tokens.json')) {
	        $this->tokens = json_decode(file_get_contents($this->fileDir.'tokens.json'), true);
        } else {
	        $this->tokens = array();
        }
        if(file_exists($this->fileDir.'states.json')) {
	        $this->states = json_decode(file_get_contents($this->fileDir.'states.json'), true);
        } else {
	        $this->states = array();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveAccessToken($service)
    {
        if ($this->hasAccessToken($service)) {
            return unserialize($this->tokens[$service]);
        }

        throw new TokenNotFoundException('Token not found in file, are you sure you stored it?');
    }

    /**
     * {@inheritDoc}
     */
    public function storeAccessToken($service, TokenInterface $token)
    {
        $serializedToken = serialize($token);

        if (isset($this->tokens)
            && is_array($this->tokens)
        ) {
            $this->tokens[$service] = $serializedToken;
        } else {
            $this->tokens = array(
                $service => $serializedToken,
            );
        }
        //write to file
		file_put_contents($this->fileDir.'tokens.json', json_encode($this->tokens));

        // allow chaining
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasAccessToken($service)
    {
        return isset($this->tokens, $this->tokens[$service]);
    }

    /**
     * {@inheritDoc}
     */
    public function clearToken($service)
    {
        if (array_key_exists($service, $this->tokens)) {
            unset($this->tokens[$service]);
        }
        //write to file
		file_put_contents($this->fileDir.'tokens.json', json_encode($this->tokens));

        // allow chaining
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clearAllTokens()
    {
        unset($this->tokens);
        //write to file
		file_put_contents($this->fileDir.'tokens.json', json_encode($this->tokens));

        // allow chaining
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function storeAuthorizationState($service, $state)
    {
        if (isset($this->states)
            && is_array($this->states)
        ) {
            $this->states[$service] = $state;
        } else {
            $this->states = array(
                $service => $state,
            );
        }
        //write to file
		file_put_contents($this->fileDir.'states.json', json_encode($this->states));

        // allow chaining
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasAuthorizationState($service)
    {        
        return isset($this->states, $this->states[$service]);
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveAuthorizationState($service)
    {
        if ($this->hasAuthorizationState($service)) {
            return $this->states[$service];
        }

        throw new AuthorizationStateNotFoundException('State not found in file, are you sure you stored it?');
    }

    /**
     * {@inheritDoc}
     */
    public function clearAuthorizationState($service)
    {
        if (array_key_exists($service, $this->states)) {
            unset($this->states[$service]);
        }
        //write to file
		file_put_contents($this->fileDir.'states.json', json_encode($this->states));

        // allow chaining
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clearAllAuthorizationStates()
    {
        unset($this->states);
        //write to file
		file_put_contents($this->fileDir.'states.json', json_encode($this->states));

        // allow chaining
        return $this;
    }
}
