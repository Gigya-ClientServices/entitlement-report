<?php
require_once('lib/GSSDK.php');

class GigyaUtils {
  private $userKey;
  private $userSecret;
  private $config;

  public function __construct($key, $secret, $config) {
    $this->userKey = $key;
    $this->userSecret = $secret;
    $this->config = $config;

    $cafile = realpath($this->config->certFile);
    if ($this->config->proxy->enabled) $cafile = realpath($this->config->proxy->certFile);
    GSRequest::setCAFile($cafile);
  }

  public function request($apiKey, $dc = 'us1', $method, $params) {
    $request = new GSRequest($apiKey, $this->userSecret, $method, $params, true, $this->userKey);
    $request->setAPIDomain($dc . ".gigya.com");
    if ($this->config->proxy->enabled) $request->setProxy($this->config->proxy->address);
    return $request->send();
  }

  public function query($apiKey, $dc = 'us1', $query) {
    // TODO: Replace this with actual rate limit checking code
    // Temorary Execution throttling limits the number of calls to ~5 per second
    usleep(200000);
    $method = "ids.search";
    $request = new GSRequest($apiKey, $this->userSecret, $method, null, true, $this->userKey);
    $request->setAPIDomain($dc . ".gigya.com");
    $request->setParam("query", $query);
    if ($this->config->proxy->enabled) $request->setProxy($this->config->proxy->address);
    return $request->send();
  }
}
?>
