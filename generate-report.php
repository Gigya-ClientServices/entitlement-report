<?php
  require_once('GSSDK.php');
  require_once('lib/monthDate.php');
  require_once('conf/settings.php');

  abstract class UsageReportModes
  {
    const Complete = 0;
    const Summary = 1;
    // etc.
  }

  $debugMode = false;
  $enableLogging = false;
  $certFile = 'cert/cacert.pem';
  $proxyMode = false;
  $proxyAddress = '127.0.0.1:8888';
  $proxyCertFile = 'cert/charles-ssl-proxying-certificate.pem';

  if (array_key_exists ("debug", $_GET)) $GLOBALS['debugMode'] = trim($_GET["debug"]);

  $partnerID = "";
  $userKey = "";
  $userSecret = "";
  $includeSegments = false;
  $startMonth = null;
  $startYear = null;
  $endMonth = null;
  $endYear = null;

  $mode = UsageReportMOdes::Complete;

  $debug = array();
  $errors = array();
  $summaries = array();
  $results = "";

  $partnerInfo = array();
  $sites = array();


  /**
    * isSecure
    *
    * Checks that the page is loaded via HTTPS (or from localhost)
    *
    */
    function isSecure() {
      if ($_SERVER['HTTP_HOST'] == 'localhost') return true;
      return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
    }

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
      if ($GLOBALS['enableLogging']) error_log($key . ": " . $value);
      if ($GLOBALS['debugMode']) {
        $GLOBALS['debug'][$key] = $value;
      }
    }

  /**
    * initGigya
    */
  function initGigya() {
    addDebug('InitGigyaStart', true);
    $cafile = realpath($GLOBALS['certFile']);
    if ($GLOBALS['proxyMode']) $cafile = realpath($GLOBALS['proxyCertFile']);
    GSRequest::setCAFile($cafile);
  }

  /**
    * getPartner
    */
  function getPartner($apiKey, $dc) {
    addDebug('GetPartnerStart', true);
    // Retrieve Partner Info
    // =====================
    $method = "admin.getPartner";
    $request = new GSRequest($apiKey, $GLOBALS['userSecret'], $method, null, true, $GLOBALS['userKey']);
    $request->setParam("partnerID", $GLOBALS['partnerID']);
    $request->setAPIDomain($dc . ".gigya.com");
    if ($GLOBALS['proxyMode']) $request->setProxy($GLOBALS['proxyAddress']);
    $response = $request->send();

    if($response->getErrorCode()==0)
    {
      // SUCCESS! response status = OK
      addDebug('GetPartnerSuccess', true);
      $GLOBALS['partnerInfo']['partnerID'] = $response->getString('partnerID');
      $GLOBALS['partnerInfo']['isTrial'] = $response->getBool('isTrial');
      $GLOBALS['partnerInfo']['isEnabled'] = $response->getBool('isEnabled');
      $GLOBALS['partnerInfo']['dataCenter'] = $response->getString('defaultDataCenter');
      $GLOBALS['partnerInfo']['companyName'] = $response->getObject('customData')->getString('companyName');

      $services = $response->getObject('services');
      $GLOBALS['partnerInfo']['allowsComments'] = $services->getObject('comments')->getBool('enabled');
      $GLOBALS['partnerInfo']['allowsGM'] = $services->getObject('gm')->getBool('enabled');
      $GLOBALS['partnerInfo']['allowsDS'] = $services->getObject('ds')->getBool('enabled');
      $GLOBALS['partnerInfo']['allowsIdS'] = $services->getObject('ids')->getBool('enabled');
      $GLOBALS['partnerInfo']['allowsAudit'] = $services->getObject('audit')->getBool('enabled');
      $GLOBALS['partnerInfo']['allowsSAMLIdP'] = $services->getObject('samlIdp')->getBool('enabled');
      $GLOBALS['partnerInfo']['allowsNexus'] = $services->getObject('nexus')->getBool('enabled');

      $accounts = $services->getObject('accounts');
      $GLOBALS['partnerInfo']['allowsRaaS'] = $accounts->getBool('enabled');

      $accountFeatures = $accounts->getArray('features');
      $GLOBALS['partnerInfo']['allowsCI'] = false;
      $GLOBALS['partnerInfo']['allowsCounters'] = false;
      if ($accountFeatures != null) {
        for ($i = 0; $i < $accountFeatures->length(); $i++) {
          $str =  $accountFeatures->getString($i);
          if ($str == 'insights') $GLOBALS['partnerInfo']['allowsCI'] = true;
          if ($str == 'counters') $GLOBALS['partnerInfo']['allowsCounters'] = true;
        }
      }
    }
    else
    {  // Error
      addError("Error Retrieving Partner Information", $response->getResponseText());
      return false;
    }
    addDebug('GetPartnerFinsih', true);
    return true;
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
      addError("Error Retrieving Partner Site Information", $response->getResponseText());
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
    function formatReport($partner, $sites, $summary, $errors, $debug) {
      addDebug('FormatReport', true);
      $output = array();
      if (count($errors) > 0) {
        $output['errCode'] = 500;
        $output['errors'] = $errors;
      } else {
        $output['errCode'] = 0;
        $output['partner'] = $partner;
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
    $firstCount = true;
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

      // First site we load use to get the Partner Information
      if ($firstCount) {
        getPartner($site['apiKey'], $site['dc']);
        $firstCount = false;
      }

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

    if ($GLOBALS['includeSegments'] == true) {
      if ($GLOBALS['startMonth'] == null) {
        array_push($missingFields, "Start Month");
      }
      if ($GLOBALS['startYear'] == null) {
        array_push($missingFields, "Start Year");
      }
      if ($GLOBALS['endMonth'] == null) {
        array_push($missingFields, "End Month");
      }
      if ($GLOBALS['endYear'] == null) {
        array_push($missingFields, "End Year");
      }
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
    addDebug("PerformMainStart", true);
		// Increase allowable execution timeout
		set_time_limit( 20 );

    // Ensure that this is only called securely
    if (!isSecure()) {
      header('HTTP/1.0 403 Forbidden');
      echo '<h1>403 Forbidden</h1><p>This resource can only be accessed over "HTTPS".</p>';
      die();
    } else {
      addDebug("PerformMainCheckEmptyPost", true);
      if (!empty($_POST)) {
        addDebug("PerformMainIsNotEmptyPost", true);
        if (array_key_exists ('partnerID', $_POST)) $GLOBALS['partnerID'] = trim($_POST['partnerID']);
        if (array_key_exists ('userKey', $_POST)) $GLOBALS['userKey'] = trim($_POST['userKey']);
        if (array_key_exists ('userSecret', $_POST)) $GLOBALS['userSecret'] = trim($_POST['userSecret']);
        if (array_key_exists ('includeSegments', $_POST)) $GLOBALS['includeSegments'] = trim($_POST['includeSegments']);
        if (array_key_exists ('startMonth', $_POST)) $GLOBALS['startMonth'] = trim($_POST['startMonth']);
        if (array_key_exists ('startYear', $_POST)) $GLOBALS['startYear'] = trim($_POST['startYear']);
        if (array_key_exists ('endMonth', $_POST)) $GLOBALS['endMonth'] = trim($_POST['endMonth']);
        if (array_key_exists ('endYear', $_POST)) $GLOBALS['endYear'] = trim($_POST['endYear']);
      }
      // Validate form contents
      addDebug("PerformMainPerformValidation", true);
      if (performFormValidation()) {
        addDebug("PerformMainInitGigya", true);
        // Initialize Gigya and perform both local and server-side validation
        initGigya();

        addDebug("PerformMainGatherInfo", true);
        // Step 1-3
        gatherPartnerInformation();
      }

      addDebug("PerformMainFomatData", true);
      // Step 4: Format results
      $GLOBALS['results'] = formatReport($GLOBALS['partnerInfo'], $GLOBALS['sites'], $GLOBALS['summaries'], $GLOBALS['errors'], $GLOBALS['debug']);

    }
  }

  // Run the page code
	performMain();
  // =============================================
  // JSON Output Below
  // =============================================
  echo $results;
?>
