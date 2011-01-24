<?php
/**
 * Provides a service into the Untappd public API.
 * 
 * @see    http://untappd.com/api/dashboard
 * @author Jason Austin - http://jasonawesome.com - @jason_austin
 *
 */
class Awsm_Service_Untappd
{
    /**
     * Base URI for the Untappd service
     * 
     * @var string
     */
    const URI_BASE = 'http://api.untappd.com/v2';
    
    /**
     * Username and password hash for the request signing
     * 
     * @var string
     */
    protected $_upHash = '';
    
    /**
     * API key
     * 
     * @var string
     */
    protected $_apiKey = '';
    
    /**
     * Stores the last parsed response from the server
     * 
     * @var stdClass
     */
    protected $_lastParsedResponse = null;
    
    /**
     * Stores the last raw response from the server
     * 
     * @var string
     */
    protected $_lastRawResponse = null;

    /**
     * Stores the last requested URI
     * 
     * @var string
     */
    protected $_lastRequestUri = null;
    
    /**
     * Constructor
     * 
     * @param string $username Untappd username
     * @param string $password Untappd password
     * @param string $apiKey Untappd-provided API key
     */
    public function __construct($username, $password, $apiKey)
    {
        $this->_apiKey = (string) $apiKey;
        $this->_upHash = (string) $username . ':' . md5((string) $password);
    }
    
    /**
     * Returns the authenticated user's friend feed
     * 
     * @param int $since numeric ID of the latest checkin
     * @param int $offset offset within the dataset to move to
     */
    public function myFriendFeed($since = '', $offset = '')
    {
        $args = array(
            'since'  => $since, 
            'offset' => $offset
        );
        
        return $this->_request('feed', $args);
    }
        
    /**
     * Gets a user's info
     * 
     * @param string $username Untappd username
     */
    public function userInfo($username = '')
    {
        $args = array(
            'user' => $username
        );
        
        return $this->_request('user', $args);
    }
    
    /**
     * Gets a user's checkins
     * 
     * @param string $username Untappd username
     * @param int $since numeric ID of the latest checkin
     * @param int $offset offset within the dataset to move to
     */
    public function userFeed($username = '', $since = '', $offset = '')
    {
        $args = array(
            'user'   => $username, 
            'since'  => $since, 
            'offset' => $offset
        );
        
        return $this->_request('user_feed', $args);
    }
    
    /**
     * Gets a user's distinct beer list
     * 
     * @param string $username Untappd username
     * @param int $offset offset within the dataset to move to
     */
    public function userDistinctBeers($username = '', $offset = '')
    {
        $args = array(
            'user'   => $username, 
            'offset' => $offset
        );
        
        return $this->_request('user_distinct', $args);
    }
    
    /**
     * Gets a list of a user's friends
     * 
     * @param string $username Untappd username
     * @param int $offset offset within the dataset to move to
     */
    public function userFriends($username = '', $offset = '')
    {
        $args = array(
            'user'   => $username, 
            'offset' => $offset
        );
        
        return $this->_request('friends', $args);
    }
    
    /**
     * Gets a user's wish list
     * 
     * @param string $username Untappd username
     * @param int $offset offset within the dataset to move to
     */
    public function userWishlist($username = '', $offset = '')
    {
        $args = array(
            'user'   => $username, 
            'offset' => $offset
        );
        
        return $this->_request('wish_list', $args);
    }

    
    /**
     * Gets a list of a user's badges they have won
     * 
     * @param string $username Untappd username
     * @param (beer|venue|special) $sort order to sort the badges in
     */
    public function userBadge($username = '', $sort = '')
    {
        $args = array(
            'user' => $username, 
            'sort' => $sort
        );
        
        return $this->_request('user_badge', $args);
    }
    
    /**
     * Gets a beer's critical info
     * 
     * @param int $beerId Untappd beer ID
     */
    public function beerInfo($beerId)
    {
        $args = array(
            'bid' => $beerId
        );
        
        return $this->_request('beer_info', $args);
    }
    
    /**
     * Searchs Untappd's database to find beers matching the query string
     * 
     * @param string $searchString query string to search
     */
    public function beerSearch($searchString)
    {
        $args = array(
            'q' => $searchString
        );
        
        return $this->_request('beer_search', $args);
    }
    
    /**
     * Get's the public feed of checkings, also known as "the pub"
     * 
     *@ param int $since numeric ID of the latest checkin
     * @param int $offset offset within the dataset to move to
     * @param float $longitude longitude to filter public feed
     * @param float $latitude latitude to filter public feed
     */
    public function publicFeed($since = '', $offset = '', $longitude = '', $latitude = '')
    {
        $args = array(
            'since'     => $since, 
            'offset'    => $offset, 
            'longitude' => $longitude, 
            'latitude'  => $latitude
        );
        
        return $this->_request('thepub', $args);
    }
    
    /**
     * Get's the details of a specific checkin
     * 
     * @param int $checkinId Untappd checkin ID
     */
    public function checkinInfo($checkinId)
    {
        $args = array(
            'id' => $checkinId
        );
        
        return $this->_request('details', $args);
    }
    
    /**
     * Sends a request using curl to the required URI
     * 
     * @param string $method Untappd method to call
     * @param array $args key value array or arguments
     * 
     * @throws Awsm_Service_Untappd_Exception
     * 
     * @return stdClass object
     */
    protected function _request($method, $args)
    {
        $this->_lastRequestUri = null;
        $this->_lastRawResponse = null;
        $this->_lastParsedResponse = null;
        
        // Append the API key to the args passed in the query string
        $args['key'] = $this->_apiKey;

        // remove any unnecessary args from the query string
        foreach ($args as $key => $a) {
            if ($a == '') {
                unset($args[$key]);
            }
        }
        
        $this->_lastRequestUri = self::URI_BASE . '/' . $method . '?' . http_build_query($args);
        
        // Set curl options and execute the request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_lastRequestUri);
        curl_setopt($ch, CURLOPT_USERPWD, $this->_upHash);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $this->_lastRawResponse = curl_exec($ch);
        
        if ($this->_lastRawResponse === false) {
            
            $this->_lastRawResponse = curl_error($ch);
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('CURL Error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        // Response comes back as JSON, so we decode it into a stdClass object
        $this->_lastParsedResponse = json_decode($this->_lastRawResponse);
        
        // If the http_code var is not found, the response from the server was unparsable
        if (!isset($this->_lastParsedResponse->http_code)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('Error parsing response from server.');
        }
        
        // Server provides error messages in http_code and error vars.  If not 200, we have an error.
        if ($this->_lastParsedResponse->http_code != '200') {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('Untappd Service Error ' .  
                $this->_lastParsedResponse->http_code . ': ' .  $this->_lastParsedResponse->error);
        }
        
        return $this->getLastParsedResponse();
    }    
    
    /**
     * Gets the last parsed response from the service
     * 
     * @return null|stdClass object
     */
    public function getLastParsedResponse()
    {
        return $this->_lastParsedResponse;
    }
    
    /**
     * Gets the last raw response from the service
     * 
     * @return null|json string
     */
    public function getLastRawResponse()
    {
        return $this->_lastRawResponse;
    }
    
    /**
     * Gets the last request URI sent to the service
     * 
     * @return null|string
     */
    public function getLastRequestUri()
    {
        return $this->_lastRequestUri;
    }
}