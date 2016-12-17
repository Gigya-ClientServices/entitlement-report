<?php
  require_once('GSSDK.php');
  require_once('conf/settings.php');

  $title = "Usage Report";
  $debugMode = false;
  $proxyMode = false;
  $proxyAddress = "127.0.0.1:8888";

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
    * formatTestImage
    *
    * Perfroms a test and output an image if true
    *
    * @param bool 		$test
    * @param string   $img
    * @param string	  $altText
    *
    */
    function formatTestImage($test, $img, $altText) {
      $outString = "";
      if ($altText == null) $altText = "";
      if ($test) {
        $outString = "<img src='{$img}' tooltip='{$altText} alt={$altText}'></img>";
      } else {
        $outString = "&nbsp;";
      }

      return $outString;
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
          $ret = 'Never';
        }
      } catch (Exception $e) {
        $ret = 'Never';
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
          $ret = 'Never';
        }
      } catch (Exception $e) {
        $ret = 'Never';
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
      $totalUsers = $totalUsers + $site['count'];
      if ($site['lastLogin'] > $lastLogin) $lastLogin = $site['lastLogin'];
      if ($site['lastCreated'] > $lastCreated) $lastCreated = $site['lastCreated'];
    }
    $GLOBALS['summaries']['count'] = $totalUsers;
    $GLOBALS['summaries']['lastLogin'] = $lastLogin;
    $GLOBALS['summaries']['lastCreated'] = $lastCreated;
  }

  /**
    * formatReport
    */
  function formatReport($sites, $summary) {
    addDebug('FormatReport', true);
    $outString =	"<table class='col-md-12'>" .
    						  "<thead>" .
    							"<tr>" .
                  "<th>SiteID</th>" .
                  "<th>APIKey</th>" .
                  "<th>DS</th>" .
                  "<th>IdS</th>" .
                  "<th>RaaS</th>" .
                  "<th>SSO</th>" .
                  "<th>Par</th>" .
                  "<th>Child Keys</th>" .
                  "<th>User Count</th>" .
                  "<th>Last Login</th>" .
                  "<th>Last Create</th>" .
                  "</tr>" .
    							"</thead>" .
    							"<tbody>";
    foreach ($sites as $site) {
      if (!$site['isChild']) {
        $outString = $outString .
        "<tr border='1px'>" .
        "<td>{$site["id"]}</td>" .
        "<td>{$site["apiKey"]}</td>" .
        "<td>" . formatTestImage($site['hasDS'],'img/pass.png','DS Enabled') . "</td>";

        if ($site['hasIdS']) {
          $outString = $outString .
          "<td>" . formatTestImage($site['hasIdS'],'img/pass.png','IdS Enabled') . "</td>" .
          "<td>" . formatTestImage($site['hasRaaS'],'img/pass.png','RaaS Enabled') . "</td>" .
          "<td>" . formatTestImage($site['hasSSO'],'img/pass.png','SSO Enabled') . "</td>" .
          "<td>" . formatTestImage($site['isParent'],'img/pass.png','Site Group Parent') . "</td>" .
          "<td>{$site["childSiteCount"]}</td>" .
          "<td>{$site["count"]}</td>" .
          "<td " . (($site["lastLogin"] == $summary["lastLogin"])?"style='background: #CFC;'":"") . ">{$site["lastLogin"]}</td>" .
          "<td " . (($site["lastCreated"] == $summary["lastCreated"])?"style='background: #CFC;'":"") . ">{$site["lastCreated"]}</td>" .
          "</tr>";
        } else {
          $outString = $outString . "<td colspan='8' style='text-align: center; background: #FEE;'>-Social Login Only-</td></tr>";
        }
      }
    }
    // Summary Info
    $outString = $outString .
      "<tr border='1px' style='background: #CCC;'>" .
      "<td colspan='8'><b>Summary</b></td>" .
      "<td>{$summary["count"]}</td>" .
      "<td>{$summary["lastLogin"]}</td>" .
      "<td>{$summary["lastCreated"]}</td>" .
      "</tr>";

    $outString = $outString . "</tbody></table>";

    return $outString;
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

      // Get Metrics from IdS enabled sites
      if (!$GLOBALS['sites'][$id]['isChild'] && $GLOBALS['sites'][$id]['hasIdS'] ) {
        // Get User Count from IdS enabled sites
        $GLOBALS['sites'][$id]['count'] = 0;
        $count = retrieveUserCounts($site['apiKey'], $site['dc']);
        if ($count != null) $GLOBALS['sites'][$id]['count'] = $count;
        // Get Last login from IdS enabled sites
        $GLOBALS['sites'][$id]['lastLogin'] = '-Never-';
        $lastLogin = retrieveLastLogin($site['apiKey'], $site['dc']);
        if ($count != null) $GLOBALS['sites'][$id]['lastLogin'] = $lastLogin;
        // Get Last created from IdS enabled sites
        $GLOBALS['sites'][$id]['lastCreated'] = '-Never-';
        $lastCreated  = retrieveLastCreated($site['apiKey'], $site['dc']);
        if ($count != null) $GLOBALS['sites'][$id]['lastCreated'] = $lastCreated;
      }
    }

    addDebug('GatherPartnerInformationStep3', true);
    // Step 3: Calculate Summaries
    // ===========================
    calculateSummary();

    addDebug('GatherPartnerInformationStep4', true);
    // Step 4: Format results
    $GLOBALS['results'] = formatReport($GLOBALS['sites'], $GLOBALS['summaries']);
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
  // HTML CODE STARTS BELOW
  // =============================================
?>

<!DOCTYPE html>
<html class="light">
	<head>
		<title><?=$title?></title>
		<!-- Latest version of jQuery (required for Bootstrap) -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

		<!-- Latest compiled and minified JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

		<!-- Optional theme -->
		<!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css">-->

		<!-- Custom CSS -->
		<link rel="stylesheet" href="css/custom.css">

		<!-- Page Javascript Logic -->
		<script src="entitlement-report.js" type="text/javascript"></script>
	</head>
	<body>
	  <div class="header-global">
    <div id="headrow" class="headrow">
        <div class="logo">
          <a href="http://www.gigya.com" title="Gigya">
<!-- Generator: Adobe Illustrator 18.1.1, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="3 609.9 612 182.5" enable-background="new 3 609.9 612 182.5" height"64">
<g>
  <path class="gigya-logo" fill="#225CA7" d="M134.6,669.1c0-1.5-0.2-15.9-8.5-30c-5-8.7-12-15.5-20.7-20.4c-10.2-5.9-22.6-8.8-36.8-8.8
    c-14.1,0-26.5,3-36.7,8.8c-8.6,4.9-15.5,11.9-20.6,20.4c-8.1,14-8.3,27.8-8.3,29.4v67c0,1.5,0.2,15,8.5,28.8
    c7.8,12.9,24,28.2,57.1,28.2c33.3,0,49.5-15.4,57.4-28.5c8.3-13.7,8.5-27.4,8.5-29v-39.4h-15.3l0,0h-60l7.3,27.7h37.3v11.5
    c0,0.6-0.4,7.6-4.6,14c-5.5,8.5-15.7,12.7-30.6,12.7c-14.6,0-24.8-4.2-30.3-12.5c-4.1-6.4-4.5-13.2-4.5-13.8v-66.7
    c0-0.6,0.4-8,4.6-14.7c5.5-8.8,15.4-13.1,30.2-13.1c14.9,0,24.8,4.3,30.4,13.1c4.4,7.1,4.6,15.3,4.6,15.3c0-0.1,0-0.2,0-0.2
    L134.6,669.1L134.6,669.1z"></path>
  <path class="gigya-logo" fill="#225CA7" d="M338.4,669.1c0-1.5-0.2-15.9-8.5-30c-5-8.7-12-15.5-20.7-20.4c-10.2-5.9-22.6-8.8-36.7-8.8
    s-26.5,3-36.6,8.8c-8.7,4.9-15.5,11.8-20.6,20.4c-8.2,14-8.4,27.7-8.4,29.3v67c0,1.5,0.2,15,8.5,28.8c7.8,12.9,24,28.2,57.1,28.2
    c33.3,0,49.5-15.4,57.4-28.5c8.3-13.7,8.5-27.4,8.5-29v-39.3H323l0,0h-60l7.3,27.7h37.2v11.5c0,0.6-0.4,7.6-4.6,14
    c-5.5,8.5-15.7,12.7-30.6,12.7c-14.6,0-24.8-4.2-30.3-12.5c-4.1-6.4-4.5-13.2-4.5-13.8v-66.7c0-0.6,0.4-8,4.6-14.7
    c5.5-8.8,15.4-13.1,30.2-13.1c14.9,0,24.8,4.3,30.4,13.1c4.4,7.1,4.6,15.3,4.6,15.3c0-0.1,0-0.2,0-0.2L338.4,669.1L338.4,669.1z"></path>
  <rect class="gigya-logo" x="155.2" y="613.4" fill="#225CA7" width="30.8" height="175.4"></rect>
  <path class="gigya-logo" fill="#225CA7" d="M433.2,613.4l-27,80.1l-29.9-80.1h-32.8L390.3,739c-2.7,6.2-5.6,12-8.5,15.7c-3.2,3-5.9,3.3-18.9,3.2
    c-1.7,0-3.4,0-5.2,0v30.9c1.7,0,3.4,0,5,0c1.4,0,2.8,0,4.2,0c13.1,0,25.4-1,37.2-12.8c0.4-0.4,0.7-0.7,1-1.1
    c12-14.7,20.6-42.3,21.8-46.7l38.6-114.7h-32.5V613.4z"></path>
  <path class="gigya-logo" fill="#225CA7" d="M463.1,788.8l13.8-37.5h65.8l13.8,37.5h32.8l-13.8-37.5H615l-7.3-27.7h-42.5l-40.7-110.1h-29.8l-64.7,175.4
    h32.9V788.8z M509.8,662l22.7,61.5h-45.4L509.8,662z"></path>
</g>
</svg>
          </a>
        </div><h1><?=$title?></h1>
    </div>
  </div>
	<div id="outer" class="outer">
<?php
	if (sizeof($errors) > 0) {
?>
		<div class="bs-callout bs-callout-danger"><h4>There were errors!</h4></div>
<?php
	}
?>
		<div id="container">
		<div id="outertop">
			<form action="" method="post" class="form-horizontal" autocomplete="off">
			<div class="main">
				<div class="left">
					<div class="form-group">
						<label class="col-sm-2 control-label">Partner ID</label>
						<div class="col-sm-1">
							<input type="text" name="partnerID" value="<?=$partnerID?>" class="form-control">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">User Key</label>
						<div class="col-sm-2">
							<input type="text" name="userKey" value="<?=$userKey?>" class="form-control" autocomplete="nope">
						</div>
					</div>
          <div class="form-group">
						<label class="col-sm-2 control-label">User Secret</label>
						<div class="col-sm-4">
							<input type="password" name="userSecret" value="<?=$userSecret?>" class="form-control" autocomplete="new-password">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">&nbsp;</label>
						<div class="col-sm-10">
							<input type="button" value="Generate Report" class="btn btn-primary" class="form-control" onClick="EntitlementReport.submitReport(this)">
						</div>
					</div>
				</div>
			</div>
			<div id="report" class="report bs-callout bs-callout-primary bottom">
				<h4>Report</h4>
        <div class="row" style="margin: 0px 30px 0px 10px;">
				<?=$results?>
        </div>
			</div>
			</form>
		</div>
	</div>
<?php
	foreach ($errors as $value) {
?>
		<div class="bs-callout bs-callout-danger"><h4><?=$value['message']?></h4><pre><?=$value['log']?></pre></div>
<?php
	}
?>
<?php
	if (count($debug) > 0) {
?>
	<div id="debug" class="bs-callout bs-callout-info">
		<h4>Debug</h4>
		<pre><?=json_encode($debug, JSON_PRETTY_PRINT)?></pre>
	</div>
<?php
	}
?>
	</div>
  <div class="overlay" style="display: none;">
    <div class="cssload-dots">
    	<div class="cssload-dot"></div>
    	<div class="cssload-dot"></div>
    	<div class="cssload-dot"></div>
    	<div class="cssload-dot"></div>
    	<div class="cssload-dot"></div>
    </div>

    <svg version="1.1" xmlns="http://www.w3.org/2000/svg">
    	<defs>
    		<filter id="goo">
    			<feGaussianBlur in="SourceGraphic" result="blur" stdDeviation="12" ></feGaussianBlur>
    			<feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0	0 1 0 0 0	0 0 1 0 0	0 0 0 18 -7" result="goo" ></feColorMatrix>
    			<!--<feBlend in2="goo" in="SourceGraphic" result="mix" ></feBlend>-->
    		</filter>
    	</defs>
    </svg>
  </div>
	</body>
</html>
