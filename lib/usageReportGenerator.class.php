<?php
require_once('lib/gigyaUtils.class.php');
require_once('lib/monthDate.class.php');
require_once('lib/logger.class.php');

class UsageReportGenerator {
  private $config;
  private $logger;
  private $gigyaUtils;

  private $hasRun = false;

  private $partnerID = "";
  private $userKey = "";
  private $userSecret = "";
  private $includeSegments = false;
  private $startMonth = null;
  private $startYear = null;
  private $endMonth = null;
  private $endYear = null;
  private $mode = null;

  private $partnerInfo = array();
  private $sites = array();
  private $summaries = array();

  public function __construct($params, $config) {
    $this->hasRun = false;
    $this->config = $config;
    $this->logger = new Logger(LogLevels::debug | LogLevels::error);
    $this->mode = UsageReportModes::Complete;
    $this->partnerID = $params['partnerID'];
    $this->userKey = $params['userKey'];
    $this->userSecret = $params['userSecret'];
    $this->gigyaUtils = new GigyaUtils($this->userKey, $this->userSecret, $config);
  }

  /**
    * getPartner
    */
  private function getPartner($apiKey, $dc) {
    $this->logger->addLog('GetPartnerStart', "", LogLevels::debug);
    // Retrieve Partner Info
    // =====================
    $params = new GSObject();
    $params->put("partnerID", $this->partnerID);
    $response = $this->gigyaUtils->request($apiKey, $dc, "admin.getPartner", $params);

    if($response->getErrorCode()==0)
    {
      // SUCCESS! response status = OK
      $this->logger->addLog('GetPartnerSuccess', "", LogLevels::debug);

      $this->partnerInfo['partnerID'] = $response->getString('partnerID');
      $this->partnerInfo['isTrial'] = $response->getBool('isTrial');
      $this->partnerInfo['isEnabled'] = $response->getBool('isEnabled');
      $this->partnerInfo['dataCenter'] = $response->getString('defaultDataCenter');
      $this->partnerInfo['companyName'] = $response->getObject('customData')->getString('companyName');

      $services = $response->getObject('services');
      $this->partnerInfo['allowsComments'] = $services->getObject('comments')->getBool('enabled');
      $this->partnerInfo['allowsGM'] = $services->getObject('gm')->getBool('enabled');
      $this->partnerInfo['allowsDS'] = $services->getObject('ds')->getBool('enabled');
      $this->partnerInfo['allowsIdS'] = $services->getObject('ids')->getBool('enabled');
      $this->partnerInfo['allowsAudit'] = $services->getObject('audit')->getBool('enabled');
      $this->partnerInfo['allowsSAMLIdP'] = $services->getObject('samlIdp')->getBool('enabled');
      $this->partnerInfo['allowsNexus'] = $services->getObject('nexus')->getBool('enabled');

      $accounts = $services->getObject('accounts');
      $this->partnerInfo['allowsRaaS'] = $accounts->getBool('enabled');

      $accountFeatures = $accounts->getArray('features');
      $this->partnerInfo['allowsCI'] = false;
      $this->partnerInfo['allowsCounters'] = false;
      if ($accountFeatures != null) {
        for ($i = 0; $i < $accountFeatures->length(); $i++) {
          $str =  $accountFeatures->getString($i);
          if ($str == 'insights') $this->partnerInfo['allowsCI'] = true;
          if ($str == 'counters') $this->partnerInfo['allowsCounters'] = true;
        }
      }
    }
    else
    {  // Error
      $this->logger->addLog("Error Retrieving Partner Information", $response->getResponseText(), LogLevels::error);
      return false;
    }
    $this->logger->addLog('GetPartnerFinsih', "", LogLevels::debug);
    return true;
  }

  /**
    * getUserSites
    */
  private function getUserSites() {
    $this->logger->addLog('GetUserSitesStart', "", LogLevels::debug);
    // Retrieve User Sites for Partner
    // ===============================
    $params = new GSObject();
    $params->put("targetPartnerID", $this->partnerID);
    $response = $this->gigyaUtils->request($this->config->apiKey, $this->config->dataCenter, "admin.getUserSites", $params);

    if($response->getErrorCode()==0)
    {
      // SUCCESS! response status = OK
      $this->logger->addLog('GetUserSitesSuccess', "", LogLevels::debug);
      $sites = $response->getArray("sites")->getObject(0)->getArray("sites");
      $this->logger->addLog('GetUserSitesCount: ' . $sites->length(), "", LogLevels::debug);
      for ($i = 0; $i < $sites->length(); $i++) {
        $obj = $sites->getObject($i);
        $id = $obj->getInt("siteID");
        $s = array(
          'id' => $id,
          'apiKey' => $obj->getString("apiKey"),
          "dc" => $obj->getString("dataCenter")
        );
        $this->sites[$id] = $s;
      }
    }
    else
    {  // Error
      $this->logger->addLog("Error Retrieving Partner Site Information", $response->getResponseText(), LogLevels::error);
      return false;
    }
    $this->logger->addLog('GetUserSitesFinish', "", LogLevels::debug);
    return true;
  }

  /**
    * retrieveSiteConfig
    */
  private function retrieveSiteConfig($apiKey, $dc) {
    $this->logger->addLog('RetrieveSiteConfigStart', "", LogLevels::debug);
    // Retrieve APIKey Config
    // ======================
    $params = new GSObject();
    $params->put("includeServices", "true");
    $params->put("includeSiteGroupConfig", "true");
    $params->put("includeGigyaSettings", "true");
    $params->put("apiKey", $apiKey);
    $response = $this->gigyaUtils->request($apiKey, $dc, "admin.getSiteConfig", $params);

    $ret = array();

    if($response->getErrorCode()==0)
    {
      $this->logger->addLog('RetrieveSiteConfigSuccess', "", LogLevels::debug);

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

        // TODO: Get count of trusted sites and aggregation
      }
    }
    else {
      $this->logger->addLog("Retrieve Site Config Errors: " . $response->getErrorMessage(), $response->getResponseText(), LogLevels::error);
      return null;
    }

    $this->logger->addLog('RetrieveSiteConfigFinish', "", LogLevels::debug);
    return $ret;
  }

  /**
    * retrieveUserCounts
    */
  private function retrieveUserCounts($apiKey, $dc) {
    $this->logger->addLog('RetrieveUserCountsStart', "", LogLevels::debug);

    $query = "select count(*) from accounts";
    $response = $response = $this->gigyaUtils->query($apiKey, $dc, $query);
    $ret = 0;
    if($response->getErrorCode()==0)
    {
      $this->logger->addLog('RetrieveUserCountsSuccess', "", LogLevels::debug);
      $ret = $response->getArray('results')->getObject(0)->getInt('count(*)');
    }
    else {
      $this->logger->addLog("Errors retrieving counts: " . $response->getErrorMessage(), $response->getResponseText(), LogLevels::error);
      return null;
    }

    $this->logger->addLog('RetrieveUserCountsFinish', "", LogLevels::debug);
    return $ret;
  }

  /**
    * retrieveLastLogin
    */
  private function retrieveLastLogin($apiKey, $dc) {
    $this->logger->addLog('RetrieveLastLoginStart', "", LogLevels::debug);
    $query = "select lastLogin from accounts order by lastLogin DESC limit 1";
    $response = $response = $this->gigyaUtils->query($apiKey, $dc, $query);

    $ret = '';

    if($response->getErrorCode()==0)
    {
      $this->logger->addLog('RetrieveLastLoginSuccess', "", LogLevels::debug);
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
      $this->logger->addLog("Errors retrieving data: " . $response->getErrorMessage(), $response->getResponseText(), LogLevels::error);
      return null;
    }

    $this->logger->addLog('RetrieveLastLoginFinish', "", LogLevels::debug);
    return $ret;
  }

  /**
    * retrieveLastCreated
    */
  private function retrieveLastCreated($apiKey, $dc) {
    $this->logger->addLog('RetrieveLastCreatedStart', "", LogLevels::debug);
    $query = "select created from accounts order by created DESC limit 1";
    $response = $response = $this->gigyaUtils->query($apiKey, $dc, $query);

    $ret = '';

    if($response->getErrorCode()==0)
    {
      $this->logger->addLog('RetrieveLastCreatedSuccess', "", LogLevels::debug);
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
      $this->logger->addLog("Errors retrieving data: " . $response->getErrorMessage(), $response->getResponseText(), LogLevels::error);
      return null;
    }

    $this->logger->addLog('RetrieveLastCreatedFinish', "", LogLevels::debug);
    return $ret;
  }

  /**
  	* calculateSummary
  	*/
  private function calculateSummary() {
    $this->logger->addLog('CalculateSummary', "", LogLevels::debug);
    $totalUsers = 0;
    $lastLogin = '';
    $lastCreated = '';
    foreach ($this->sites as $site) {
      $totalUsers = $totalUsers + $site['userCount'];
      if ($site['lastLogin'] > $lastLogin) $lastLogin = $site['lastLogin'];
      if ($site['lastCreated'] > $lastCreated) $lastCreated = $site['lastCreated'];
    }
    $this->summaries['userCount'] = $totalUsers;
    $this->summaries['lastLogin'] = $lastLogin;
    $this->summaries['lastCreated'] = $lastCreated;
  }


  /**
  	* gatherPartnerInformation
  	*/
  private function gatherPartnerInformation() {
    $firstCount = true;
    // Step 1: Retrieve User Sites for Partner
    // =======================================
    if (!$this->getUserSites()) return false;

    // Step 2: Loop through all the sites calling and gather additional information
    // ============================================================================
    foreach ($this->sites as $site) {
      // Get Site configuration for the site
      $id = $site['id'];
      $apiKey = $site['apiKey'];
      $dc = $site['dc'];

      $siteConfig = $this->retrieveSiteConfig($apiKey, $dc);
      if ($siteConfig != null) {
        // Merge siteConfig into the site object array
        $this->sites[$id] = array_merge($this->sites[$id], $siteConfig);
      }

      // Initialize Site Data
      $this->sites[$id]['userCount'] = null;
      $this->sites[$id]['lastLogin'] = null;
      $this->sites[$id]['lastCreated'] = null;

      // First site we load use to get the Partner Information
      if ($firstCount) {
        $this->getPartner($apiKey, $dc);
        $firstCount = false;
      }

      // Get Metrics from IdS enabled sites
      if (!$this->sites[$id]['isChild'] && $this->sites[$id]['hasIdS'] ) {
        // Get User Count from IdS enabled sites
        $this->sites[$id]['userCount'] = 0;
        $count = $this->retrieveUserCounts($apiKey, $dc);
        if ($count != null) $this->sites[$id]['userCount'] = $count;
        // Get Last login from IdS enabled sites
        $this->sites[$id]['lastLogin'] = '-Never-';
        $lastLogin = $this->retrieveLastLogin($apiKey, $dc);
        if ($lastLogin != null) $this->sites[$id]['lastLogin'] = $lastLogin;
        // Get Last created from IdS enabled sites
        $this->sites[$id]['lastCreated'] = '-Never-';
        $lastCreated = $this->retrieveLastCreated($apiKey, $dc);
        if ($lastCreated != null) $this->sites[$id]['lastCreated'] = $lastCreated;
      }
    }

    // Step 3: Calculate Summaries
    // ===========================
    $this->calculateSummary();

    return true;
  }

  /**
    * formatReport
    */
  private function formatReport() {
    $this->logger->addLog('CalculateSummary', "", LogLevels::debug);
    $output = array();
    $errors = $this->logger->getErrorLog();
    $debug = $this->logger->getDebugLog();

    if (count($errors) > 0) {
      $output['errCode'] = 500;
      $output['errors'] = $errors;
    } else {
      $output['errCode'] = 0;
      $output['partner'] = $this->partnerInfo;
      $output['sites'] = $this->sites;
      $output['summary'] = $this->summaries;
    }
    if (count($debug) > 0 && $this->config->debug) {
      $output['hasDebug'] = true;
      $output['debug'] = $debug;
    }
    return json_encode($output, JSON_PRETTY_PRINT);
  }

  /**
  	* performDataValidation
  	*/
  private function performDataValidation() {
    $missingFields = array();
    if ($this->partnerID == "") {
      array_push($missingFields, "Partner ID");
    }
    if ($this->userKey == "") {
      array_push($missingFields, "User Key");
    }
    if ($this->userSecret == "") {
      array_push($missingFields, "User Secret");
    }

    if ($this->includeSegments == true) {
      if ($this->startMonth == null) {
        array_push($missingFields, "Start Month");
      }
      if ($this->startYear == null) {
        array_push($missingFields, "Start Year");
      }
      if ($this->endMonth == null) {
        array_push($missingFields, "End Month");
      }
      if ($this->endYear == null) {
        array_push($missingFields, "End Year");
      }
    }

    if (count($missingFields) > 0) {
      $msg = "The following fields are missing: ";

      foreach ($missingFields as $value) {
        $msg = $msg . "<br/> * " . $value;
      }

      $this->logger->addLog("Form Validation", $msg, LogLevels::error);
      return false;
    }
    return true;
  }

  public function getReport() {
    if (!$this->hasRun) {
      if ($this->performDataValidation()) {
        $this->gatherPartnerInformation();
      }
    }

    return $this->formatReport();
  }
}
?>
