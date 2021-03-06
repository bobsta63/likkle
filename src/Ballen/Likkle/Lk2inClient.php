<?php namespace Ballen\Likkle;

/**
 * Likkle is a composer compatible PHP client library for the lk2.in
 *  URL shortener service (http://lk2.in).
 *
 * @author Bobby Allen <ballen@bobbyallen.me>
 * @version 2.0.0
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/bobsta63/likkle
 * @link http://bobbyallen.me
 *
 */
class Lk2inClient
{

    /**
     * lk2.in API specific settings.
     */
    const HTTP_LK2IN_URL = 'http://lk2.in/';
    const HTTP_LK2IN_WSPATH = 'api/v1/';

    /**
     * Object storage for the web service response.
     * @var string JSON response data.
     */
    private $response = null;

    /**
     * Object storage for the API method eg. 'get', 'clicks' etc.
     * @var string
     */
    private $request_wsmethod = null;

    /**
     * Optional proxy server hostname or IP address.
     * @var string
     */
    private $proxy_host = null;

    /**
     * Optional proxy server TCP port (defaulted to 8080)
     * @var integer
     */
    private $proxy_port = 8080;

    /**
     * Optional proxy server BASIC authentication string (Base64)
     * @var string
     */
    private $proxy_auth = null;

    /**
     * The HTTP method type used for the request.
     * @var string
     */
    private $request_httpmethod = 'GET';

    /**
     * Generates the RESTful URI ready to be sent to the LK2.IN web service.
     * @return string The prepared lk2.in webservice URL.
     */
    protected function generateRequestUri()
    {
        return self::HTTP_LK2IN_URL . self::HTTP_LK2IN_WSPATH . $this->request_wsmethod;
    }

    /**
     * Sends the request to the lk2.in web serivce and returns the raw response.
     * @param string $uri The full URI to request and get the raw response from.
     * @return \Ballen\Likkle\Lk2inClient
     */
    protected function sendRequest($uri)
    {
        $aContext = array(
            'http' => array(
                'method' => $this->request_httpmethod,
                'request_fulluri' => true,
            ),
        );
        if ($this->proxy_host) {
            $aContext['http'] = array_merge($aContext['http'], array('proxy' => $this->proxy_host . ':' . $this->proxy_port));
        }
        if ($this->proxy_auth) {
            if (!isset($aContext['http']['header'])) {
                $aContext['http']['header'] = array();
            }
            array_push($aContext['http']['header'], "Proxy-Authorization: Basic $this->proxy_auth");
        }
        $cxContext = stream_context_create($aContext);
        $this->response = file_get_contents($uri, false, $cxContext);
        $this->resetRequest();
        return $this;
    }

    /**
     * Returns a shortcode for the given URL.
     * @param string $url The current URL of which you wish to shorten.
     * @param boolean $urlencode URL encode the URL?
     * @return mixed Will return the full short url or 'false' if the API fails to respond with an new short code.
     */
    public function getShortUrl($url, $urlencode = true)
    {
        $this->request_wsmethod = 'shorten';
        if ($urlencode) {
            $url = urlencode($url);
        }
        $this->sendRequest($this->generateRequestUri() . '?long_url=' . $url);

        $apiresponse = json_decode($this->response);
        if (isset($apiresponse->data->hash)) {
            return self::HTTP_LK2IN_URL . $apiresponse->data->hash;
        } else {
            return false;
        }
    }

    /**
     * Returns the statistics object for a given shortcode.
     * @return object|boolean
     */
    public function getStats($shortcode)
    {
        $this->request_wsmethod = 'stats';
        $this->sendRequest($this->generateRequestUri() . '?hash=' . $shortcode);
        $apiresponse = json_decode($this->response);
        if (isset($apiresponse->data->hash)) {
            return $apiresponse->data->stats;
        }
        return false;
    }

    /**
     * Returns the number of times a link has been visited/clicked.
     * @param string $shortcode The shortcode as supplied by the webservice (the random alphanumeric characters after the TLD eg. 'http://lk2.in/XXX')
     * @return int|boolean Will return the number of clicks/visits or 'false' if the API fails to respond with the total number of clicks.
     */
    public function getClicks($shortcode)
    {
        $clicks = $this->getStats($shortcode);
        if (isset($clicks->total_visits)) {
            return $clicks->total_visits;
        }
        return false;
    }

    /**
     * Returns a replaced string of multiple URLs with newly created lk2.in URLs.
     * @param string $string Replaced string with newly generated short URLs.
     */
    public function getShortUrlReplacementInString($string)
    {
        preg_match_all('/\b(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)[-A-Z0-9+&@#\/%=~_|$?!:,.]*[A-Z0-9+&@#\/%=~_|$]/i', $string, $result, PREG_PATTERN_ORDER);
        $replacement_urls = array();
        foreach ($result[0] as $url) {
            $replacement_urls[] = $this->getShortURL($url);
        }
        $new_content = $string;
        $pass = 0;
        foreach ($replacement_urls as $new_url) {
            $new_content = str_replace($result[0][$pass], $new_url, $new_content);
            $pass++;
        }
        return $new_content;
    }

    /**
     * Forces the API to create a new URL shortcode so that the user does not get global click stats for the original URL.
     * @deprecated since 2.0.0
     * @return \Ballen\Likkle\Lk2inClient
     */
    public function forceNewCounter()
    {
        return $this;
    }

    /**
     * Returns the raw response content (as a JSON string.)
     * @return string
     */
    public function getRawResponse()
    {
        return $this->response;
    }

    /**
     * Returns the response (JSON decoded) so you can access like $object->message and $object->error etc.
     * @return object JSON decoded object containing response elements.
     */
    public function getResponseObject()
    {
        return json_decode($this->response);
    }

    /**
     * Sets Proxy server host and port infomation (if required.)
     * @param string $host The hostname or IP address of the proxy server.
     * @param string $port The TCP port to use to connect to the proxy (default is set to 8080)
     * @return \Ballen\Likkle\Lk2inClient
     */
    public function setProxyHost($host, $port = null)
    {
        $this->proxy_host = $host;
        if ($port) {
            $this->proxy_port = $port;
        }
        return $this;
    }

    /**
     * Sets Proxy BASIC authentication.
     * @param string $username Username to use to authenticate with the proxy.
     * @param string $password Password to use to authenticate with the proxy.
     * @return \Ballen\Likkle\Lk2inClient
     */
    public function setProxyAuth($username, $password)
    {
        $this->proxy_auth = base64_encode("$username:$password");
        return $this;
    }

    /**
     * Resets the request parameters/settings ready for the next request.
     * @return \Ballen\Likkle\Lk2inClient
     */
    protected function resetRequest()
    {
        $this->request_wsmethod = null;
        return $this;
    }
}
