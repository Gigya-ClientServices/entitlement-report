<?php
  require_once('lib/GSSDK.php');
  require_once('lib/utils.class.php');
  require_once('lib/usageReportGenerator.class.php');

  abstract class UsageReportModes
  {
    const Complete = 0;
    const Summary = 1;
    // etc.
  }

  $config = include('conf/settings.php');

  $params = array(
    "partnerID" => "",
    "userKey" => "",
    "userSecret" => "",
    "includeSegments" => false,
    "startMonth" => null,
    "startYear" => null,
    "endMonth" => null,
    "endYear" => null,
    "mode" => null
  );

  // Increase allowable execution timeout
	set_time_limit( 20 );

  if (array_key_exists ("debug", $_GET)) $config->debug = trim($_GET["debug"]);

  // Ensure that this is only called securely
  if (!Utils::isSecureConnection()) {
    header('HTTP/1.0 403 Forbidden');
    echo '<h1>403 Forbidden</h1><p>This resource can only be accessed over "HTTPS".</p>';
    die();
  } else {
    if (!empty($_POST)) {
      if (array_key_exists ('partnerID', $_POST)) $params['partnerID'] = trim($_POST['partnerID']);
      if (array_key_exists ('userKey', $_POST)) $params['userKey'] = trim($_POST['userKey']);
      if (array_key_exists ('userSecret', $_POST)) $params['userSecret'] = trim($_POST['userSecret']);
      if (array_key_exists ('includeSegments', $_POST)) $params['includeSegments'] = json_decode(trim($_POST['includeSegments']));
      if (array_key_exists ('startMonth', $_POST)) $params['startMonth'] = trim($_POST['startMonth']);
      if (array_key_exists ('startYear', $_POST)) $params['startYear'] = trim($_POST['startYear']);
      if (array_key_exists ('endMonth', $_POST)) $params['endMonth'] = trim($_POST['endMonth']);
      if (array_key_exists ('endYear', $_POST)) $params['endYear'] = trim($_POST['endYear']);
    }

    $report = new UsageReportGenerator($params, $config);
    // Format results
    $results = $report->getReport();
  }

  // =============================================
  // JSON Output Below
  // =============================================
  echo $results;
?>
