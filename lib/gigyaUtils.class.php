<?php
require_once('lib/GSSDK.php');

class GigyaUtils {
  const ratePerSecond = 3;
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
    // Temorary Execution throttling limits the number of calls to ~3 per second
    usleep(1/GigyaUtils::ratePerSecond * 1000000);
    $method = "ids.search";
    $request = new GSRequest($apiKey, $this->userSecret, $method, null, true, $this->userKey);
    $request->setAPIDomain($dc . ".gigya.com");
    $request->setParam("query", $query);
    if ($this->config->proxy->enabled) $request->setProxy($this->config->proxy->address);
    return $request->send();
  }
}
?>
