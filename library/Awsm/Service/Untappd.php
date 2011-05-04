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
    const URI_BASE = 'http://api.untappd.com/v3';
    
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
     * @param string $apiKey Untappd-provided API key
     * @param string *optional* $username Untappd username
     * @param string *optional* $password Untappd password
     */
    public function __construct($apiKey, $username = '', $password = '')
    {
        $this->_apiKey = (string) $apiKey;
        
        $this->setAuthenticatedUser($username, $password);
    }
    
    /**
     * Sets the authenticated user for untappd.  If username and
     * password vars are set to empty string, will null out the
     * password hash needed for authenticated methods.
     * 
     * @param string $username Untappd username
     * @param string $password Untappd password
     */
    public function setAuthenticatedUser($username, $password)
    {
        if ($username != '' && $password != '') {
            $this->_upHash = (string) $username . ':' . md5((string) $password);
        } else {
            $this->_upHash = null;
        }
        
        return $this;
    }
    
    /**
     * Returns the authenticated user's friend feed
     * 
     * @param int *optional* $since numeric ID of the latest checkin
     * @param int *optional* $offset offset within the dataset to move to
     */
    public function myFriendFeed($since = '', $offset = '')
    {
        $args = array(
            'since'  => $since, 
            'offset' => $offset
        );
        
        return $this->_request('feed', $args, true);
    }

    /**
     * Adds a beer to the logged-in-user's wishlist
     *
     * @param int $beerId Untappd beer ID to add
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function addToMyWishlist($beerId)
    {
        if (empty($beerId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('beerId parameter must be set and not empty');
        }

        $args = array(
            'bid' => $beerId
        );

        return $this->_request('add_to_wish', $args, true);
    }

    /**
     * Removes a beer from the logged-in-user's wishlist
     *
     * @param int $beerId Untappd beer ID to remove
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function removeFromMyWishlist($beerId)
    {
        if (empty($beerId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('beerId parameter must be set and not empty');
        }

        $args = array(
            'bid' => $beerId
        );

        return $this->_request('remove_from_wish', $args, true);
    }

    /**
     * Lists any pending requests to become friends
     *
     */
    public function myPendingFriends()
    {
        $args = array();

        return $this->_request('friend_pending', $args, true);
    }

    /**
     * Accepts a friend request from the user for the logged-in-user
     *
     * @param string $requestingUserId Untappd user ID
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function acceptMyFriendRequest($requestingUserId)
    {
        if (empty($requestingUserId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('requestingUserId parameter must be set and not empty');
        }

        $args = array(
            'target_id' => $requestingUserId
        );

        return $this->_request('friend_accept', $args, true);
    }

    /**
     * Rejects a friend request from the user for the logged-in-user
     *
     * @param string $requestingUserId Untappd user ID
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function rejectMyFriendRequest($requestingUserId)
    {
        if (empty($requestingUserId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('requestingUserId parameter must be set and not empty');
        }

        $args = array(
            'target_id' => $requestingUserId
        );

        return $this->_request('friend_reject', $args, true);
    }

    /**
     * Un-friends a user from the logged-in-user
     *
     * @param string $friendUserId Untappd user ID
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function removeMyFriend($friendUserId)
    {
        if (empty($friendUserId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('friendUserId parameter must be set and not empty');
        }

        $args = array(
            'target_id' => $friendUserId
        );

        return $this->_request('friend_revoke', $args, true);
    }

    /**
     * Makes a friend requets from the logged-in-user to the user passed
     *
     * @param string $userId Untappd user ID
     */
    public function makeMyFriendRequest($userId)
    {
        if (empty($userId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('userId parameter must be set and not empty');
        }

        $args = array(
            'target_id' => $userId
        );

        return $this->_request('friend_request', $args, true);
    }

    /**
     * Gets a user's info
     * 
     * @param string *optional* $username Untappd username
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function userInfo($username = '')
    {
        if ($username == '' && is_null($this->_upHash)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('username parameter or Untappd authentication parameters must be set.');
        }
        
        $args = array(
            'user' => $username
        );
        
        return $this->_request('user', $args);
    }
    
    /**
     * Gets a user's checkins
     * 
     * @param string *optional* $username Untappd username
     * @param int *optional* $since numeric ID of the latest checkin
     * @param int *optional* $offset offset within the dataset to move to
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function userFeed($username = '', $since = '', $offset = '')
    {
        if ($username == '' && is_null($this->_upHash)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('username parameter or Untappd authentication parameters must be set.');
        }
                
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
     * @param string *optional* $username Untappd username
     * @param int *optional* $offset offset within the dataset to move to
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function userDistinctBeers($username = '', $offset = '')
    {
        if ($username == '' && is_null($this->_upHash)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('username parameter or Untappd authentication parameters must be set.');
        }
                
        $args = array(
            'user'   => $username, 
            'offset' => $offset
        );
        
        return $this->_request('user_distinct', $args);
    }
    
    /**
     * Gets a list of a user's friends
     * 
     * @param string *optional* $username Untappd username
     * @param int *optional* $offset offset within the dataset to move to
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function userFriends($username = '', $offset = '')
    {
        if ($username == '' && is_null($this->_upHash)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('username parameter or Untappd authentication parameters must be set.');
        }
                
        $args = array(
            'user'   => $username, 
            'offset' => $offset
        );
        
        return $this->_request('friends', $args);
    }
    
    /**
     * Gets a user's wish list
     * 
     * @param string *optional* $username Untappd username
     * @param int *optional* $offset offset within the dataset to move to
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function userWishlist($username = '', $offset = '')
    {
        if ($username == '' && is_null($this->_upHash)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('username parameter or Untappd authentication parameters must be set.');
        }
                
        $args = array(
            'user'   => $username, 
            'offset' => $offset
        );
        
        return $this->_request('wish_list', $args);
    }

    
    /**
     * Gets a list of a user's badges they have won
     * 
     * @param string *optional* $username Untappd username
     * @param (all|beer|venue|special) *optional* $sort order to sort the badges in
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function userBadge($username = '', $sort = 'all')
    {
        if ($username == '' && is_null($this->_upHash)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('username parameter or Untappd authentication parameters must be set.');
        }
                
        $validSorts = array('all', 'beer', 'venue', 'special');
        if (!in_array($sort, $validSorts)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('Sort parameter must be one of the following: ' . implode(', ', $validSorts));            
        }
        
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
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function beerInfo($beerId)
    {
        if (empty($beerId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('beerId parameter must be set and not empty');            
        }
                
        $args = array(
            'bid' => $beerId
        );
        
        return $this->_request('beer_info', $args);
    }
    
    /**
     * Searches Untappd's database to find beers matching the query string
     * 
     * @param string $searchString query string to search
     * @param int *optional* $offset offset within the dataset to move to
     * @param (name|count|*empty*) *optional* flag to sort the results
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function beerSearch($searchString, $offset = '', $sort = '')
    {
        if (empty($searchString)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('searchString parameter must be set and not empty');            
        }

        if (!empty($sort) && ($sort != 'count' && $sort != 'name')) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('If set, sort can only be "count" or "name"');
        }
                
        $args = array(
            'q'      => $searchString,
            'offset' => $offset,
            'sort'   => $sort
        );
        
        return $this->_request('beer_search', $args);
    }
    
    /**
     * Gets all checkins for a specified beer
     * 
     * @param int $beerId Untappd ID of the beer to search for
     * @param int *optional* $since numeric ID of the latest checkin
     * @param int *optional* $offset offset within the dataset to move to
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function beerFeed($beerId, $since = '', $offset = '')
    {
        if (empty($beerId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('beerId parameter must be set and not empty');            
        }
        
        $args = array(
            'bid'    => $beerId,
            'since'  => $since,
            'offset' => $offset,
        );
        
        return $this->_request('beer_checkins', $args);          
    }
    
    /**
     * Gets information about a given venue
     * 
     * @param int $venueId Untappd ID of the venue
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function venueInfo($venueId)
    {
        if (empty($venueId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('venueId parameter must be set and not empty');            
        }
                
        $args = array(
            'venue_id' => $venueId,
        );
        
        return $this->_request('venue_info', $args);          
    }

    /**
     * Gets all checkins at a given venue
     * 
     * @param int $venueId Untappd ID of the venue
     * @param int *optional* $since numeric ID of the latest checkin
     * @param int *optional* $offset offset within the dataset to move to
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function venueFeed($venueId, $since = '', $offset = '')
    {
        if (empty($venueId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('venueId parameter must be set and not empty');            
        }
                
        $args = array(
            'venue_id' => $venueId,
            'since'    => $since,
            'offset'   => $offset,
        );
        
        return $this->_request('venue_checkins', $args);          
    }
    
    /**
     * Gets all for beers of a certain brewery
     * 
     * @param int $breweryId Untappd ID of the brewery
     * @param int *optional* $since numeric ID of the latest checkin
     * @param int *optional* $offset offset within the dataset to move to
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function breweryFeed($breweryId, $since = '', $offset = '')
    {
        if (empty($breweryId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('breweryId parameter must be set and not empty');            
        }
        
        $args = array(
            'brewery_id' => $breweryId,
            'since'      => $since,
            'offset'     => $offset,
        );
        
        return $this->_request('brewery_checkins', $args);          
    }

    /**
     * Gets the basic info for a brewery
     *
     * @param int $breweryId Untappd brewery ID
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function breweryInfo($breweryId)
    {
        if (empty($breweryId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('breweryId parameter must be set and not empty');
        }

        $args = array(
            'brewery_id' => $breweryId
        );

        return $this->_request('brewery_info', $args);
    }

    /**
     * Searches for all the breweries based on a query string
     *
     * @param string $searchString search term to search breweries
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function brewerySearch($searchString)
    {
        if (empty($searchString)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('searchString parameter must be set and not empty');
        }

        $args = array(
            'q' => $query
        );

        return $this->_request('brewery_search', $args, true);
    }
    
    /**
     * Gets the public feed of checkings, also known as "the pub"
     * 
     *@ param int *optional* $since numeric ID of the latest checkin
     * @param int *optional* $offset offset within the dataset to move to
     * @param float *optional* $longitude longitude to filter public feed
     * @param float *optional* $latitude latitude to filter public feed
     * @param int *optional* $radius radius from the lat and long to filter feed
     */
    public function publicFeed($since = '', $offset = '', $longitude = '', $latitude = '', $radius = '')
    {
        $args = array(
            'since'  => $since, 
            'offset' => $offset, 
            'geolng' => $longitude,
            'geolat' => $latitude,
            'radius' => $radius
        );
        
        return $this->_request('thepub', $args);
    }
    
    /**
     * Gets the trending list of beers based on location
     * 
     * @param (all|macro|micro|local) *optional* $type Type of beers to search for
     * @param int *optional* $limit Number of results to return
     * @param (daily|weekly|monthly) *optional* $age Age of checkins to consider
     * @param float *optional* $latitude Numeric latitude to filter the feed
     * @param float *optional* $longitude Numeric longitude to filter the feed
     * @param int *optional* $radius Radius in miles from the long/lat points
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function publicTrending($type = 'all', $limit = 10, $age = 'daily', $latitude = '', $longitude = '', $radius = '')
    {
        $validTypes = array('all', 'macro', 'micro', 'local');
        if (!in_array($type, $validTypes)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('Type parameter must be one of the following: ' . implode(', ', $validTypes));
        }
        
        $validAges = array('daily', 'weekly', 'monthly');
        if (!in_array($age, $validAges)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('Age parameter must be one of the following: ' . implode(', ', $validAges));
        }
        
        // Set limit to default if it is outside of the available params
        if ($limit > 10 || $limit < 1) {
            $limit = 10;
        }
        
        $args = array(
            'type'   => $type,
            'limit'  => $limit,
            'age'    => $age,
            'geolat' => $latitude,
            'geolng' => $longitude,
            'radius' => $radius
        );
        
        return $this->_request('trending', $args);        
    }
    
    /**
     * Gets the details of a specific checkin
     * 
     * @param int $checkinId Untappd checkin ID
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function checkinInfo($checkinId)
    {
        if (empty($checkinId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('checkinId parameter must be set and not empty');            
        }
                
        $args = array(
            'id' => $checkinId
        );
        
        return $this->_request('details', $args);
    }
    
    /**
     * Perform a live checkin
     *
     * @param int $gmtOffset - Hours the user is away from GMT
     * @param int $beerId - Untappd beer ID
     * @param string *optional* $foursquareId - MD5 hash ID of the venue to check into
     * @param float *optional* $userLat - Latitude of the user.  Required if you add a location.
     * @param float *optional* $userLong - Longitude of the user.  Required if you add a location.
     * @param string *optional* $shout - Text to include as a comment
     * @param boolean *optional* $facebook - Whether or not to post to facebook
     * @param boolean *optional* $twitter - Whether or not to post to twitter
     * @param boolean *optional* $foursquare - Whether or not to checkin on foursquare
     * @param int *optional* $rating - Rating for the beer
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function checkin($gmtOffset, $beerId, $foursquareId = '', $userLat = '', $userLong = '', $shout = '', $facebook = false, $twitter = false, $foursquare = false, $rating = '')
    {
        if (empty($gmtOffset)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('gmtOffset parameter must be set and not empty');
        }

        if (empty($beerId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('beerId parameter must be set and not empty');
        }

        // If $foursquareId is set, must past Lat and Long to the API
        if (!empty($foursquareId) && (empty($userLat) || empty($userLong))) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('userLat and userLong parameters required since foursquareId is set');
        }

        if (!empty($rating) && (!is_int($rating) || $rating < 1 || $rating > 5)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('If set, rating must be an integer between 1 and 5');
        }

        $args = array(
            'gmt_offset'    => $gmtOffset,
            'bid'           => $beerId,
            'foursquare_id' => $foursquareId,
            'user_lat'      => $userLat,
            'user_long'     => $userLong,
            'shout'         => $shout,
            'facebook'      => ($facebook) ? 'on' : 'off',
            'twitter'       => ($twitter) ? 'on' : 'off',
            'foursquare'    => ($foursquare) ? 'on' : 'off',
            'rating_value'  => $rating
        );
        
        return $this->_request('checkin', $args, true);
    }

    /**
     * Adds a comment to a specific checkin
     *
     * @param int $checkinId - Checkin to comment on
     * @param string $comment - Comment to add to the checkin
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function checkinComment($checkinId, $comment)
    {
        if (empty($checkinId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('checkinId parameter must be set and not empty');
        }

        if (empty($comment)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('comment parameter must be set and not empty');
        }

        $args = array(
            'checkin_id' => $checkinId,
            'comment'    => $comment,
        );
        
        return $this->_request('add_comment', $args, true);
    }

    /**
     * Remove a comment from a checkin
     *
     * @param int $commentId
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function checkinRemoveComment($commentId)
    {
        if (empty($commentId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('commentId parameter must be set and not empty');
        }

        $args = array(
            'comment_id' => $commentId,
        );
        
        return $this->_request('delete_comment', $args, true);
    }

    /**
     * Toast a checkin
     *
     * @param int $checkinId
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function checkinToast($checkinId)
    {
        if (empty($commentId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('commentId parameter must be set and not empty');
        }

        $args = array(
            'comment_id' => $commentId,
        );
        
        return $this->_request('toast', $args, true);
    }

    /**
     * Remove a toast from a checkin
     *
     * @param int $commentId
     *
     * @throws Awsm_Service_Untappd_Exception
     */
    public function checkinRemoveToast($commentId)
    {
        if (empty($commentId)) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('commentId parameter must be set and not empty');
        }

        $args = array(
            'comment_id' => $commentId,
        );

        return $this->_request('delete_toast', $args, true);
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
    protected function _request($method, $args, $requireAuth = false)
    {
        $this->_lastRequestUri = null;
        $this->_lastRawResponse = null;
        $this->_lastParsedResponse = null;
        
        if (is_null($this->_upHash) && $requireAuth) {
            require_once 'Awsm/Service/Untappd/Exception.php';
            throw new Awsm_Service_Untappd_Exception('Method requires Untappd user authentication which is not set.');
        }
        
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
        
        if (!is_null($this->_upHash)) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->_upHash);
        }
          
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