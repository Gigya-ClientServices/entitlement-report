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
        </div><div style='float: left; display: inline; vertical-align: middle; padding-left: 10px;'><h2><?=$title?></h2></div>
    </div>
  </div>
	<div id="outer" class="outer">
		<div id="container">
		<div id="outertop">
			<form action="" method="post" class="form-horizontal" autocomplete="off">
			<div class="main">
				<div class="left">
					<div class="form-group">
						<label class="col-sm-2 control-label">Partner ID</label>
						<div class="col-sm-1">
							<input type="text" id="partnerID" name="partnerID" value="<?=$partnerID?>" class="form-control">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">User Key</label>
						<div class="col-sm-2">
							<input type="text" id="userKey" name="userKey" value="<?=$userKey?>" class="form-control" autocomplete="nope">
						</div>
					</div>
          <div class="form-group">
						<label class="col-sm-2 control-label">User Secret</label>
						<div class="col-sm-4">
							<input type="password" id="userSecret" name="userSecret" value="<?=$userSecret?>" class="form-control" autocomplete="new-password">
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
