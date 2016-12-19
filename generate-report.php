<?php
  require_once('GSSDK.php');
  require_once('conf/settings.php');

  $debugMode = false;
  $certFile = './cert/cacert.pem';
  $proxyMode = false;
  $proxyAddress = '127.0.0.1:8888';
  $proxyCertFile = './cert/charles-ssl-proxying-certificate.pem';

  if (array_key_exists ("debug", $_GET)) $GLOBALS['debugMode'] = trim($_GET["debug"]);

  $partnerID = "";
  $userKey = "";
  $userSecret = "";

  $debug = array();
  $errors = array();
  $summaries = array();
  $results = "";

  $sites = array();

  /**
    * addError
    *
    * Adds an error to be printed out on the page
    *
    * @param string		$message	the message heading to print
    * @param string		$log			the error log to print
    */
    function addError($message, $log)
    {
      $error = array(
        "message" => $message,
        "log" => $log
      );
      array_push($GLOBALS['errors'], $error);
    }

  /**
    * addDebug
    *
    * Adds an debug variable to print at the bottom of the page
    *
    * @param string		$key			the variable name
    * @param string		$value		the value for the variable
    */
    function addDebug($key, $value)
    {
      if ($GLOBALS['debugMode']) {
        $GLOBALS['debug'][$key] = $value;
      }
    }

  /**
    * initGigya
    */
  function initGigya() {
    $cafile = realpath('./cacert.pem');
    if ($GLOBALS['proxyMode']) $cafile = realpath('./charles-ssl-proxying-certificate.pem');
    GSRequest::setCAFile($cafile);
  }

  /**
    * getUserSites
    */
  function getUserSites() {
    addDebug('GetUserSitesStart', true);
    // Retrieve User Sites for Partner
    // ===============================
    $method = "admin.getUserSites";
    $request = new GSRequest($GLOBALS['apiKey'], $GLOBALS['userSecret'], $method, null, true, $GLOBALS['userKey']);
    $request->setParam("targetPartnerID", $GLOBALS['partnerID']);
    if ($GLOBALS['proxyMode']) $request->setProxy($GLOBALS['proxyAddress']);
    $response = $request->send();

    if($response->getErrorCode()==0)
    {
      // SUCCESS! response status = OK
      addDebug('GetUserSitesSuccess', true);
      $sites = $response->getArray("sites")->getObject(0)->getArray("sites");
      addDebug('GetUserSitesCount', $sites->length());
      for ($i = 0; $i < $sites->length(); $i++) {
        $obj = $sites->getObject($i);
        $id = $obj->getInt("siteID");
        $s = array(
          'id' => $id,
          'apiKey' => $obj->getString("apiKey"),
          "dc" => $obj->getString("dataCenter")
        );
        $GLOBALS['sites'][$id] = $s;
      }
    }
    else
    {  // Error
      addError("Error Retrieving Partner Information", $respone->getErrorMessage() . "<br/>" . $response->getResponseText());
      return false;
    }
    addDebug('GetUserSitesFinish', true);
    return true;
  }

  function retrieveSiteConfig($apiKey, $dc) {
    addDebug('RetrieveSiteConfigStart', true);
    //if ($dc == null) $dc = "us1";
    $method = "admin.getSiteConfig";
    $request = new GSRequest($apiKey, $GLOBALS['userSecret'], $method, null, true, $GLOBALS['userKey']);
    $request->setParam("includeServices", "true");
    $request->setParam("includeSiteGroupConfig", "true");
    $request->setParam("includeGigyaSettings", "true");
    $request->setParam("apiKey", $apiKey);
    $request->setAPIDomain($dc . ".gigya.com");
    if ($GLOBALS['proxyMode']) $request->setProxy($GLOBALS['proxyAddress']);
    $response = $request->send();
    $ret = array();

    if($response->getErrorCode()==0)
    {
      addDebug('RetrieveSiteConfigSuccess', true);

      $ret['isChild'] = false;
      $ret['isParent'] = false;
      $ret['childSiteCount'] = '';
      $ret['parentKey'] = "";
      try {
        $ret['parentKey'] = $response->getString('siteGroupOwner');
        if ($ret['parentKey'] != "") $ret['isChild'] = true;
      } catch (Exception $e) {

      }

      try {
        $arr = $response->getObject('siteGroupConfig')->getArray('members');
        if ($arr != null) {
          $ret['childSiteCount'] = $arr->length();
          $ret['isParent'] = true;
        }
      } catch (Exception $e) {

      }

      if ($ret['isChild'] == false) {
        $services = $response->getObject('services');
        $ret['hasRaaS'] = $services->getObject('accounts')->getBool('enabled');
        $ret['hasDS'] = $services->getObject('ds')->getBool('enabled');
        $ret['hasIdS'] = $services->getObject('ids')->getBool('enabled');
        $ret['hasSSO'] = $response->getObject('siteGroupConfig')->getBool('enableSSO');
        // TODO: Count of trusted sites
      }
    }
    else {
      addError("Retrieve Site Config Errors: " . $response->getErrorMessage(), $response->getResponseText());
      return null;
    }

    addDebug('RetrieveSiteConfigFinish', true);
    return $ret;
  }

  /**
    * retrieveUserCounts
    */
  function retrieveUserCounts($apiKey, $dc) {
    addDebug('RetrieveUserCountsStart', true);

    $method = "ids.search";
    $request = new GSRequest($apiKey, $GLOBALS['userSecret'], $method, null, true, $GLOBALS['userKey']);
    $request->setParam("query", "select count(*) from accounts");
    $request->setAPIDomain($dc . ".gigya.com");
    if ($GLOBALS['proxyMode']) $request->setProxy($GLOBALS['proxyAddress']);
    $response = $request->send();
    $ret = 0;
    if($response->getErrorCode()==0)
    {
      addDebug('RetrieveUserCountsSuccess', true);
      $ret = $response->getArray('results')->getObject(0)->getInt('count(*)');
    }
    else {
      addError("Errors retrieving counts: " . $response->getErrorMessage(), $response->getResponseText());
      return null;
    }

    addDebug('RetrieveUserCountsFinish', true);
    return $ret;
  }

  /**
    * retrieveLastLogin
    */
  function retrieveLastLogin($apiKey, $dc) {
    addDebug('RetrieveLastLoginStart', true);
    $method = "ids.search";
    $request = new GSRequest($apiKey, $GLOBALS['userSecret'], $method, null, true, $GLOBALS['userKey']);
    $request->setParam("query", "select lastLogin from accounts order by lastLogin DESC limit 1");
    $request->setAPIDomain($dc . ".gigya.com");
    if ($GLOBALS['proxyMode']) $request->setProxy($GLOBALS['proxyAddress']);
    $response = $request->send();
    $ret = '';

    if($response->getErrorCode()==0)
    {
      addDebug('RetrieveLastLoginSuccess', true);
      try {
        $results = $response->getArray('results');
        if ($results->length() > 0) {
          $ret = $results->getObject(0)->getString('lastLogin');
        } else {
          $ret = '-Never-';
        }
      } catch (Exception $e) {
        $ret = '-Never-';
      }
    }
    else {
      addError("Errors retrieving data: " . $response->getErrorMessage(), $response->getResponseText());
      return null;
    }

    addDebug('RetrieveLastLoginFinish', true);
    return $ret;
  }

  /**
    * retrieveLastCreated
    */
  function retrieveLastCreated($apiKey, $dc) {
    addDebug('RetrieveLastCreatedStart', true);
    $method = "ids.search";
    $request = new GSRequest($apiKey, $GLOBALS['userSecret'], $method, null, true, $GLOBALS['userKey']);
    $request->setParam("query", "select created from accounts order by created DESC limit 1");
    $request->setAPIDomain($dc . ".gigya.com");
    if ($GLOBALS['proxyMode']) $request->setProxy($GLOBALS['proxyAddress']);
    $response = $request->send();
    $ret = '';

    if($response->getErrorCode()==0)
    {
      addDebug('RetrieveLastCreatedSuccess', true);
      try {
        $results = $response->getArray('results');
        if ($results->length() > 0) {
          $ret = $results->getObject(0)->getString('created');
        } else {
          $ret = '-Never-';
        }
      } catch (Exception $e) {
        $ret = '-Never-';
      }
    }
    else {
      addError("Errors retrieving data: " . $response->getErrorMessage(), $response->getResponseText());
      return null;
    }

    addDebug('RetrieveLastCreatedFinish', true);
    return $ret;
  }

  function calculateSummary() {
    addDebug('CalculateSummary', true);
    $totalUsers = 0;
    $lastLogin = '';
    $lastCreated = '';
    foreach ($GLOBALS['sites'] as $site) {
      $totalUsers = $totalUsers + $site['userCount'];
      if ($site['lastLogin'] > $lastLogin) $lastLogin = $site['lastLogin'];
      if ($site['lastCreated'] > $lastCreated) $lastCreated = $site['lastCreated'];
    }
    $GLOBALS['summaries']['userCount'] = $totalUsers;
    $GLOBALS['summaries']['lastLogin'] = $lastLogin;
    $GLOBALS['summaries']['lastCreated'] = $lastCreated;
  }


    /**
      * formatReport
      */
    function formatReport($sites, $summary, $errors, $debug) {
      addDebug('FormatReport', true);
      $output = array();
      if (count($errors) > 0) {
        $output['errCode'] = 500;
        $output['errors'] = $errors;
      } else {
        $output['errCode'] = 0;
        $output['sites'] = $sites;
        $output['summary'] = $summary;
      }
      if (count($debug) > 0) {
        $output['hasDebug'] = true;
        $output['debug'] = $debug;
      }
      return json_encode($output, JSON_PRETTY_PRINT);
    }

  /**
  	* gatherPartnerInformation
  	*/
  function gatherPartnerInformation() {
    addDebug('GatherPartnerInformationStep1', true);
    // Step 1: Retrieve User Sites for Partner
    // =======================================
    if (!getUserSites()) return;

    addDebug('GatherPartnerInformationStep2', true);
    // Step 2: Loop through all the sites calling and gather additional information
    // ============================================================================
    foreach ($GLOBALS['sites'] as $site) {
      // Get Site configuration for the site
      $id = $site['id'];
      addDebug('Site-' . $id, true);
      $siteConfig = retrieveSiteConfig($site['apiKey'], $site['dc']);
      if ($siteConfig != null) {
        // Merge siteConfig into the site object array
        $GLOBALS['sites'][$id] = array_merge($GLOBALS['sites'][$id], $siteConfig);
      }

      $GLOBALS['sites'][$id]['userCount'] = null;
      $GLOBALS['sites'][$id]['lastLogin'] = null;
      $GLOBALS['sites'][$id]['lastCreated'] = null;

      // Get Metrics from IdS enabled sites
      if (!$GLOBALS['sites'][$id]['isChild'] && $GLOBALS['sites'][$id]['hasIdS'] ) {
        // Get User Count from IdS enabled sites
        $GLOBALS['sites'][$id]['userCount'] = 0;
        $count = retrieveUserCounts($site['apiKey'], $site['dc']);
        if ($count != null) $GLOBALS['sites'][$id]['userCount'] = $count;
        // Get Last login from IdS enabled sites
        $GLOBALS['sites'][$id]['lastLogin'] = '-Never-';
        $lastLogin = retrieveLastLogin($site['apiKey'], $site['dc']);
        if ($lastLogin != null) $GLOBALS['sites'][$id]['lastLogin'] = $lastLogin;
        // Get Last created from IdS enabled sites
        $GLOBALS['sites'][$id]['lastCreated'] = '-Never-';
        $lastCreated  = retrieveLastCreated($site['apiKey'], $site['dc']);
        if ($lastCreated != null) $GLOBALS['sites'][$id]['lastCreated'] = $lastCreated;
      }
    }

    addDebug('GatherPartnerInformationStep3', true);
    // Step 3: Calculate Summaries
    // ===========================
    calculateSummary();

    addDebug('GatherPartnerInformationStep4', true);
    // Step 4: Format results
    $GLOBALS['results'] = formatReport($GLOBALS['sites'], $GLOBALS['summaries'], $GLOBALS['errors'], $GLOBALS['debug']);
  }

  /**
  	* performFormValidation
  	*/
  function performFormValidation() {
    $missingFields = array();
    if ($GLOBALS['partnerID'] == "") {
      array_push($missingFields, "Partner ID");
    }
    if ($GLOBALS['userKey'] == "") {
      array_push($missingFields, "User Key");
    }
    if ($GLOBALS['userSecret'] == "") {
      array_push($missingFields, "User Secret");
    }

    if (count($missingFields) > 0) {
      $msg = "The following fields are missing: ";

      foreach ($missingFields as $value) {
        $msg = $msg . "<br/> * " . $value;
      }
      addError("Form Validation", $msg);
      return false;
    }
    return true;
  }

  /**
  	* performMain
  	*/
  function performMain() {
		// Increase allowable execution timeout
		set_time_limit( 20 );
    if (!empty($_POST))
    {
      if (array_key_exists ('partnerID', $_POST)) $GLOBALS['partnerID'] = trim($_POST['partnerID']);
      if (array_key_exists ('userKey', $_POST)) $GLOBALS['userKey'] = trim($_POST['userKey']);
      if (array_key_exists ('userSecret', $_POST)) $GLOBALS['userSecret'] = trim($_POST['userSecret']);
      // Initialize Gigya and perform both local and server-side validation
      initGigya();

      // Validate form contents
      if (!performFormValidation()) return;

      gatherPartnerInformation();
    }
  }

  // Run the page code
	performMain();
  // =============================================
  // JSON Output Below
  // =============================================
  echo $results;
?>
