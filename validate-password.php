<?php
	require_once('lib/hex2bin.php');
	require_once('lib/password.php');
	require_once('lib/pbkdf2.php');
	require_once('conf/settings.php');
	require_once('GSSDK.php');
	require_once('utils.php');

	$debugMode = false;
	if (array_key_exists ("debug", $_GET)) $GLOBALS['debugMode'] = trim($_GET["debug"]);

	$algorithms = array(
		"md5" => "md5 (128 bits)",
		"md5_double_salted" => "*md5 Double Salted (128 bits)",
		"md5_crypt" => "*md5 Crypt (128 bits)",
		"sha1" => "sha1 (160 bits)",
		"sha256" => "sha256 (256 bits)",
		"sha512" => "sha512 (512 bits)",
		"sha512Hexa" => "*sha512 Hexa (512 bits)",
		"bcrypt" => "bcrypt (192 bits)",
		"crypt" => "crypt (variable)",
		"pbkdf2" => "pbkdf2 with sha1 [Gigya] (128 bits)",
		"pbkdf2_sha1" => "pbkdf2 with sha1 (160 bits)",
		"pbkdf2_sha256" => "pbkdf2 with sha256 (256 bits)",
		"drupal" => "*drupal (128 bits)"
	);

	$algorithmBits = array(
		"md5" => 128,
		"md5_double_salted" => 128,
		"md5_crypt" => 128,
		"sha1" => 160,
		"sha256" => 256,
		"sha512" => 512,
		"sha512Hexa" => 512,
		"bcyrpt" => 192,
		"pbkdf2" => 128,
		"pbkdf2_sha1" => 160,
		"pbkdf2_sha256" => 256,
		"drupal" => 128
	);

	$algorithmValidatable = array(
		"md5" => true,
		"md5_double_salted" => false,
		"md5_crypt" => false,
		"sha1" => true,
		"sha256" => true,
		"sha512" => true,
		"sha512Hexa" => false,
		"bcrypt" => true,
		"crypt" => true,
		"pbkdf2" => true,
		"pbkdf2_sha1" => true,
		"pbkdf2_sha256" => true,
		"drupal" => false
	);

	$saltLocations = array(
		"custom" => "custom",
		"before" => "before",
		"after" => "after",
		"none" => "none"
	);

	$testMessages = array(
		"decodeType" => "Decoding Test",
		"bits" => "Bit Depth Test",
		"doubleEncoding" => "Double Encoding Test",
		"setPassword" => "Set Password Test",
		"login" => "Login Test"
	);

	$hashText = "";
	$saltText = "";
	$saltLoc = "none";
	$algo = "";
	$iterations = 1;
	$decodeStr = "";
	$cryptStr = "";
	$binarySaltEncodingHex = "";
	$binarySaltEncodingBase64 = "";
	$passwordEncoding = "";
	$customHashFormat = "";
	$validationMode = "";

	$debug = array();
	$errors = array();
	$results = array();
	$tests = array();
	$notes = array();
	$data = array();

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
	* addTestResult
	*
	* Adds an test result to display on the page
	*
	* @param string		$key			the test (key) performed
	* @param string		$result		test result
	* @param string		$note			an annotation to include
	*/
	function addTestResult($key, $result, $note)
	{
		if (isset($GLOBALS['testMessages'][$key]))
		{
			$message = $GLOBALS['testMessages'][$key];
		}
		else
		{
			$message = $key;
		}

		$test = array("message" => $message, "result" => $result);
		$GLOBALS["tests"][$key] = $test;
		if (isset($note)) {
			$GLOBALS["notes"][$key] = $note;
		}
	}

/**
	* performHash
	*
	* Hashes the string based on the settings provided
	*
	* @param string		$strToHash	the plain-text string to hash
	* @param string		$algo 			the name of the algorithm to use
	* @param string		$salt 			the unencoded salt to use
	* @param string		$loc 				the salt location used to create the digest to hash
	* @param int 			$itr 				the number of hashing iterations to use
	* @param string		$cryptStr 	the MCF (Modular Crypt Format) to use when using crypt (eg. $2a$)
	*
	* @return mixed
	*/
	function performHash($strToHash, $algo, $salt, $loc, $itr, $cryptStr, $customFormat)
	{
		$outputParams = array(
			"original" => $strToHash,
			"returned" => "",
			"raw" => "",
			"utf8" => "",
			"hex" => "",
			"base64" => "",
			"base64edHex" => "",
			"hexedBase64" => "",
			"returnedLength" => 0,
			"rawLength" => 0,
			"utf8Length" => 0,
			"hexLength" => 0,
			"base64Length" => 0,
			"base64edHexLength" => 0,
			"hexedBase64Length" => 0,
		);

		$baseString = $strToHash;
		//$baseString = utf8_encode($baseString);
		if ($algo == "bcrypt") {
			$outputParams["returned"] = doBCrypt($strToHash, $salt);
			$splode = explode("$", $outputParams["returned"]);
			$outputParams["raw"] = base64_decode($splode[3]);
			$outputParams["hex"] = bin2hex($outputParams["raw"]);
		}
		else if ($algo == "crypt") {
			$outputParams["returned"] = doCrypt($baseString, $salt, $cryptStr);
			$outputParams["raw"] = $outputParams["returned"];
			$outputParams["hex"] = bin2hex($outputParams["raw"]);
		}
		else if ($algo == "pbkdf2") {
			$outputParams["returned"] = PBKDF2::hash("sha1", $baseString, $salt, $itr, 32);
			$outputParams["hex"] = $outputParams["returned"];
			$outputParams["raw"] = tryHex2Bin($outputParams["hex"]);
		}
		else if ($algo == "pbkdf2_sha1") {
			$outputParams["returned"] = PBKDF2::hash("sha1", $baseString, $salt, $itr, 40);
			$outputParams["hex"] = $outputParams["returned"];
			$outputParams["raw"] = tryHex2Bin($outputParams["hex"]);
		}
		else if ($algo == "pbkdf2_sha256") {
			$outputParams["returned"] = PBKDF2::hash("sha256", $baseString, $salt, $itr, 64);
			$outputParams["hex"] = $outputParams["returned"];
			$outputParams["raw"] = tryHex2Bin($outputParams["hex"]);
		}
		else {
			switch ($loc) {
				case "before":
					$outputParams["returned"] = doHash($algo, $salt . $baseString, $itr);
				break;
				case "after":
					$outputParams["returned"] = doHash($algo, $baseString . $salt, $itr);
				break;
				case "none":
					$outputParams["returned"] = doHash($algo, $baseString, $itr);
				break;
				case "custom":
					// Do replacement of "$salt" and "$password"
					$newBase = str_replace("$salt",$salt,$customFormat);
					$newBase = str_replace("$password",$baseString,$newBaser);
					$outputParams["returned"] = doHash($algo, $newBase, $itr);
					break;
				default:
					$outputParams["returned"] = doHash($algo, $baseString, $itr);
				break;
			}
			$outputParams["hex"] = $outputParams["returned"];
			$outputParams["raw"] = tryHex2Bin($outputParams["hex"]);
		}

		$outputParams["utf8"] = utf8_encode($outputParams["raw"]);
		$outputParams["base64"] = base64_encode($outputParams["raw"]);
		$outputParams["base64edHex"] = base64_encode($outputParams["hex"]);
		$outputParams["hexedBase64"] = bin2hex(base64_encode($outputParams["raw"]));

		$outputParams["returnedLength"] = (strlen($outputParams["returned"]) * 8);
		$outputParams["rawLength"] = (strlen($outputParams["raw"]) * 8);
		$outputParams["utf8Length"] = (mb_strlen($outputParams["utf8"]) * 8);
		$outputParams["hexLength"] = (strlen($outputParams["hex"]) * 8);
		$outputParams["base64Length"] = (strlen($outputParams["base64"]) * 8);
		$outputParams["base64edHexLength"] = (strlen($outputParams["base64edHex"]) * 8);
		$outputParams["hexedBase64Length"] = (strlen($outputParams["hexedBase64"]) * 8);

		return $outputParams;
	}

/**
	* formatHashes
	*
	* Formats the hash results as HTML array
	*
	* @param array	$hashArray	hash results array to format
	*
	* @return string
	*/
	function formatHashes($hashArray)
	{
		$outString =	"<table>" .
										"<caption>Hashing Results</caption>" .
										"<thead>" .
											"<tr><th>Algorithm</th><th>Bits</th><th>Value</th></tr>" .
										"</thead>" .
										"<tbody>" .
											"<tr style='border: 1px; background: #ffffdd;' >" .
												"<td class='algo'>Returned</td>" .
												"<td class='bits'>{$hashArray["returnedLength"]}</td>" .
												"<td>{$hashArray["returned"]}</td>" .
											"</tr>" .
											"<tr style='border: 1px; background: #ddddff;' >" .
												"<td class='algo'>Raw</td>" .
												"<td class='bits'>{$hashArray["rawLength"]}</td>" .
												"<td>{$hashArray["raw"]}</td>" .
											"</tr>" .
											"<tr style='border: 1px; background: #ffddff;' >" .
												"<td class='algo'>UTF-8</td>" .
												"<td class='bits'>{$hashArray["utf8Length"]}</td>" .
												"<td>{$hashArray["utf8"]}</td>" .
											"</tr>" .
											"<tr style='border: 1px; background: #ddffdd;' >" .
												"<td class='algo'>Hex</td>" .
												"<td class='bits'>{$hashArray["hexLength"]}</td>" .
												"<td>{$hashArray["hex"]}</td>" .
											"</tr>" .
											"<tr style='border: 1px; background: #ffdddd;' >" .
												"<td class='algo'>Base64</td>" .
												"<td class='bits'>{$hashArray["base64Length"]}</td>" .
												"<td>{$hashArray["base64"]}</td>" .
											"</tr>" .
											"<tr style='border: 1px; background: #ffffff;'>" .
												"<td class='algo'>Base64 Encoded Hex‡</td>" .
												"<td class='bits'>&nbsp;</td>" .
												"<td>{$hashArray["base64edHex"]}</td>" .
											"</tr>" .
											"<tr style='border: 1px; background: #ffffff;'>" .
												"<td class='algo'>Hex Encoded Base64‡</td>" .
												"<td class='bits'>&nbsp;</td>" .
												"<td>{$hashArray["hexedBase64"]}</td>" .
											"</tr>" .
										"</tbody>" .
									"</table>";
		return $outString;
	}

	function performDecode($strToDecode)
	{
		$outputParams = array(
			"original" => $strToDecode,
			"hexDecode" => "",
			"base64Decode" => "",
			"hexBase64Decode" => "",
			"hexDecodeLength" => 0,
			"base64DecodeLength" => 0,
			"hexBase64DecodeLength" => 0
		);

		try {
			$outputParams["hexDecode"] = tryHex2Bin($strToDecode);
		} catch (Exception $e) {}
		try {
			$outputParams["base64Decode"] = base64_decode($strToDecode);
		} catch (Exception $e) {}
		try {
			$outputParams["hexBase64Decode"] = tryHex2Bin(base64_decode($strToDecode));
		} catch (Exception $e) {}
		try {
			$outputParams["hexEncodedBase64Decode"] = getBase64DecodeHexArray($strToDecode);
		} catch (Exception $e) {}

		$outputParams["originalLength"] = (strlen($strToDecode) * 8);
		$outputParams["hexDecodeLength"] = (strlen($outputParams["hexDecode"]) * 8);
		$outputParams["base64DecodeLength"] = (strlen($outputParams["base64Decode"]) * 8);
		$outputParams["hexBase64DecodeLength"] = (strlen($outputParams["hexBase64Decode"]) * 8);
		$outputParams["hexEncodedBase64DecodeLength"] = (strlen($outputParams["hexEncodedBase64Decode"]) * 8);

		return $outputParams;
	}

	function formatDecoding($formatArray)
	{
		$outString = 	"<table>" .
							"<caption>Decoding Results</caption>" .
							"<thead>" .
							"<tr><th>Algorithm</th><th>Bits</th><th>Value</th></tr>" .
							"<tbody>" .
							"<tr style='border: 1px; background: #ddffdd;' >" .
								"<td class='algo'>Original</td>" .
								"<td class='bits'>{$formatArray['originalLength']}</td>" .
								"<td>{$formatArray['original']}</td>" .
							"</tr>" .
							"<tr style='border: 1px; background: #ddddff;' >" .
								"<td class='algo'>Hex Decode</td>" .
								"<td class='bits'>{$formatArray['hexDecodeLength']}</td>" .
								"<td>{$formatArray['hexDecode']}</td>" .
							"</tr>" .
							"<tr style='border: 1px; background: #ddddff;' >" .
								"<td class='algo'>Base64 Decode</td>" .
								"<td class='bits'>{$formatArray['base64DecodeLength']}</td>" .
								"<td>{$formatArray['base64Decode']}</td>" .
							"</tr>" .
							"<tr style='border: 1px; background: #ffffff;'>" .
								"<td class='algo'>Hex Decode Base64 Decoded‡</td>" .
								"<td class='bits'>{$formatArray['hexBase64DecodeLength']}</td>" .
								"<td>{$formatArray['hexBase64Decode']}</td>" .
							"</tr>" .
							"<tr style='border: 1px; background: #ffffff;'>" .
								"<td class='algo'>Hex Encoded Base64 Decoded‡</td>" .
								"<td class='bits'>{$formatArray['hexEncodedBase64DecodeLength']}</td>" .
								"<td>{$formatArray['hexEncodedBase64Decode']}</td>" .
							"</tr>" .
							"</tbody>" .
						"</table>";
		return $outString;
	}

	function performSettings($strToDecode, $strToHash, $algo, $salt, $loc, $itr, $cryptStr, $hexSalt, $base64Salt, $pwEncoding, $customFormat)
	{
		// $outputParams = array(
		// 	"hashedPassword" => null,
		// 	"pwHashFormat" => null,
		// 	"pwHashSalt" => null,
		// 	"pwBinaryHashSalt" => null,
		// 	"pwHashAlgorithm" => null,
		// 	"compoundHashedPassword" => null,
		// 	"pwHashRounds" => null
		// );

		$outputParams = array();

		if ($itr > 1) {
			$outputParams["pwHashRounds"] = $itr;
		}

		switch ($algo) {
			case "crypt":
				$hash = bin2hex(doCrypt($strToHash, $salt, $cryptStr));
				$outputParams["compoundHashedPassword"] = $cryptStr . $salt . '$' . $hash;
			break;

			case "bcrypt":
				$outputParams["compoundHashedPassword"] = doBCrypt($strToHash, $salt);
			break;

			default:
				if ($strToDecode != "") {
					$outputParams["hashedPassword"] = $strToDecode;
				}
				$outputParams["pwHashAlgorithm"] = $algo;
			break;
		}

		if ($algo == "pbkdf2") {
			$outputParams["pwHashSalt"] = $salt;
		}
		else if ($salt != "") {
			$pwHalf = "\$password";
			$saltHalf = "\$salt";

			if ($hexSalt !== "") {
				$saltHalf = $saltHalf . ":hex";
			}
			else if ($base64Salt !== "") {
				$saltHalf = $saltHalf . ":base64";
			}

			$pwHalf = $pwHalf . ":" . $pwEncoding;

			switch ($loc) {
				case "before":
					if ($hexSalt !== "" || $base64Salt !== "" || $pwEncoding !== "utf8") {
						$outputParams["pwHashBinaryFormat"] = $saltHalf . $pwHalf;
					}
					else {
						$outputParams["pwHashFormat"] = "\$salt\$password";
					}
					$outputParams["pwHashSalt"] = $salt;
					break;
				case "after":
					if ($hexSalt !== "" || $base64Salt !== "" || $pwEncoding !== "utf8") {
						$outputParams["pwHashBinaryFormat"] = $pwHalf . $saltHalf;
					}
					else {
						$outputParams["pwHashFormat"]  = "\$password\$salt";
					}
					$outputParams["pwHashSalt"] = $salt;
					break;
				case "custom":
					// Binary Formated
					if (strstr($customFormat, ":") !== FALSE) {
						$outputParams["pwHashBinaryFormat"]  = $customFormat;
					} else { // Normal
						$outputParams["pwHashFormat"]  = $customFormat;
					}
					$outputParams["pwHashSalt"] = $salt;
					break;
				default:
					break;
			}
		}

		return $outputParams;
	}

	function getStatusImage($status)
	{
		$imgString = "";
		switch($status) {
			case "pass":
			case "fail":
			case "cancel":
			case "error":
			case "info":
				$imgString = "{$status}.png";
				break;
			default:
				$imgString = "error.png";
				break;
		}
		$outputString = "<img src='img/{$imgString}' alt='{$status}'/> ";
		return $outputString;
	}

	function getCalloutType($status)
	{
		$typeString = "";
		switch($status) {
			case "pass":
				$typeString = " bs-callout-success";
				break;
			case "fail":
				$typeString = " bs-callout-danger";
				break;
			case "cancel":
				$typeString = " bs-callout-warning";
				break;
			case "error":
				$typeString = " bs-callout-danger";
				break;
			case "info":
				$typeString = " bs-callout-info";
				break;
			default:
				$typeString = "";
				break;
		}
		return $typeString;
	}


	function performValidation($hashArray, $decodeArray)
	{
		// Test the encoding type
		$base64Test = isBase64Encoded($decodeArray['original']);
		$hexTest = isHexEncoded($decodeArray['original']);
		$encoding = "unknown";

		// While it is possible for a base64 has to look like a hex hash
		// the odds of this are extremely unlikely.
		if ($hexTest == true) {
			$encoding = "hex";
		}
		else if ($base64Test == true) {
			$encoding = "base64";
		}

		addTestResult("decodeType", "info", "Hash appears to be <b>{$encoding}</b> encoded.");

		// Test that the decoded hash bit depth matches the algorithm (bits)
		$bits = $GLOBALS['algorithmBits'][$GLOBALS['algo']];
		switch ($encoding) {
			case "hex":
				if ($decodeArray['hexDecodeLength'] == $bits) {
					addTestResult("bits", "pass", null);
					break;
				}
				// If it doesn't match hex, fall back and test the base64
			case "base64":
				if ($decodeArray['base64DecodeLength'] == $bits) {
					addTestResult("bits", "pass", null);
					$encoding = "base64";
				}
				else {
					addTestResult("bits", "fail", null);
				}
				break;
			default:
				// Unknown encoding -- exit
				addTestResult("bits", "cancel", null);
				break;
		}

		$result = array(
			'encoding' => $encoding
		);

		return $result;
	}

	function formatValidation()
	{
		$outputString = "";
		foreach ($GLOBALS["tests"] as $key => $value) {
			$img = getStatusImage($GLOBALS["tests"][$key]["result"]);
			$cls = getCalloutType($GLOBALS["tests"][$key]["result"]);
			$msg = $GLOBALS["tests"][$key]["message"];
			$note = "";
			if (isset($GLOBALS["notes"][$key]))
			{
				$note = $GLOBALS["notes"][$key];
			}
			$outputString = $outputString . "<div class='bs-callout bs-test" . $cls . "'><h4>" . $img . $msg . "</h4>" . $note . "</div>";
		}

		return $outputString;
	}

	function initGigya() {
		$cafile = realpath('./cacert.pem');
		GSRequest::setCAFile($cafile);
	}

	function getGigyaLoginID() {
		$loginID = "";
		$method = "accounts.getAccountInfo";
		$request = new GSRequest($GLOBALS['apiKey'], $GLOBALS['secretKey'], $method, null, true);
		$request->setParam("UID", $GLOBALS['testUserUID']);
		$request->setParam("include", "loginIDs");

		$response = $request->send();
		if($response->getErrorCode()==0)
		{
			// SUCCESS! response status = OK
			$loginID = $response->getObject("loginIDs")->getArray("emails")->getString(0);
		}
		else
		{  // Error
			addError("Error Retrieving Gigya LoginID", $respone->getErrorMessage() . "<br/>" . $response->getResponseText());
		}
		return $loginID;
	}

	function performGigyaSetPassword($settings, $encoding) {
		// Setting must contain the compountHashedPassword or the hashedPassword and algorithm
		if (array_key_exists('compoundHashedPassword', $settings) ||
			 (array_key_exists('hashedPassword', $settings) &&
			  array_key_exists('pwHashAlgorithm', $settings)))
		{
			$method = "accounts.setPassword";
			$request = new GSRequest($GLOBALS['apiKey'], $GLOBALS['secretKey'], $method, null, true);
			$request->setParam("UID", $GLOBALS['testUserUID']);  // set the "uid" parameter to user's ID
			foreach ($settings as $key => $value) {
				// Convert Hex Hash to base64
				if ($key == "hashedPassword" && $encoding['encoding'] == "hex") {
					$value = base64_encode(tryHex2Bin($value));
				}
				$request->setParam($key, $value);
			}
			$response = $request->send();

			if($response->getErrorCode()==0)
			{    // SUCCESS! response status = OK
				addDebug("setPassword", $response->getLog());
				addTestResult("setPassword", "pass", null);
				return true;
			}
			else
			{  // Error
				addError("Error setting password: " . $response->getErrorMessage(), $response->getResponseText());
				addTestResult("setPassword", "fail", null);
				error_log($response->getLog());
				return false;
			}
		}
	}

	function performGigyaLogin($loginID, $password)
	{
		if ($loginID != "" && $password != "")
		{
			$method = "accounts.login";
			$request = new GSRequest($GLOBALS['apiKey'], $GLOBALS['secretKey'], $method, null, true);
			$request->setParam("loginID", $loginID);  // set the "uid" parameter to user's ID
			$request->setParam("password", $password);
			$response = $request->send();

			if($response->getErrorCode()==0)
			{    // SUCCESS! response status = OK
				addTestResult("login", "pass", null);
			}
			else if ($response->getErrorCode()==403042)
			{		// SUCCESS! But Login Failed
				addTestResult("login", "fail", "Password is incorrect.");
			}
			else
			{  // Error
				addError("Login Errors: " . $response->getErrorMessage(), $response->getResponseText()); //$response->getLog());
				addTestResult("login", "error", $response->getErrorMessage());
				error_log($response->getLog());
			}
		}
	}

	function performMain() {
		// Increase allowable execution timeout
		set_time_limit( 20 );

		$results = array();
		$data = array();

		// Initialize Gigya and perform both local and server-side validation
		initGigya();
		$GLOBALS['loginID'] = getGigyaLoginID();
		addDebug('loginID', $GLOBALS['loginID']);

		if (!empty($_GET))
		{
			if (array_key_exists ("string", $_GET)) $GLOBALS['hashText'] = trim($_GET["string"]);
			if (array_key_exists ("algo", $_GET)) $GLOBALS['algo'] = trim($_GET["algo"]);
			if (array_key_exists ("saltText", $_GET)) $GLOBALS['saltText'] = trim($_GET["saltText"]);
			if (array_key_exists ("saltLoc", $_GET)) $GLOBALS['saltLoc'] = trim($_GET["saltLoc"]);
			if (array_key_exists ("iterations", $_GET)) $GLOBALS['iterations'] = intval(trim($_GET["iterations"]));
			if (array_key_exists ("decodeStr", $_GET)) $GLOBALS['decodeStr'] = trim($_GET["decodeStr"]);
			if (array_key_exists ("cryptStr", $_GET)) $GLOBALS['cryptStr'] = trim($_GET["cryptStr"]);
			if (array_key_exists ("binarySaltEncodingHex", $_GET)) $GLOBALS['binarySaltEncodingHex'] = trim($_GET["binarySaltEncodingHex"]);
			if (array_key_exists ("binarySaltEncodingBase64", $_GET)) $GLOBALS['binarySaltEncodingBase64'] = trim($_GET["binarySaltEncodingBase64"]);
			if (array_key_exists ("passwordEncoding", $_GET)) $GLOBALS['passwordEncoding'] = trim($_GET["passwordEncoding"]);
			if (array_key_exists ("customHashFormat", $_GET)) $GLOBALS['customHashFormat'] = trim($_GET["customHashFormat"]);
			if (array_key_exists ("mode", $_GET)) $GLOBALS['mode'] = trim($_GET["mode"]);

			if ($GLOBALS['iterations'] <= 0) $GLOBALS['iterations'] = 1;

			if ($GLOBALS['hashText'] != "")
			{
				if ($GLOBALS['binarySaltEncodingHex'] === "hex") {
					$data['hash'] = performHash($GLOBALS['hashText'], $GLOBALS['algo'], tryHex2Bin($GLOBALS['saltText']), $GLOBALS['saltLoc'], $GLOBALS['iterations'], $GLOBALS['cryptStr'], $GLOBALS['customHashFormat']);
				}
				else if ($GLOBALS['binarySaltEncodingBase64'] === "base64") {
					$data['hash'] = performHash($GLOBALS['hashText'], $GLOBALS['algo'], base64_decode($GLOBALS['saltText']), $GLOBALS['saltLoc'], $GLOBALS['iterations'], $GLOBALS['cryptStr'], $GLOBALS['customHashFormat']);
				}
				else {
					$data['hash'] = performHash($GLOBALS['hashText'], $GLOBALS['algo'], $GLOBALS['saltText'], $GLOBALS['saltLoc'], $GLOBALS['iterations'], $GLOBALS['cryptStr'], $GLOBALS['customHashFormat']);
				}

				$results['hash'] = formatHashes($data['hash']);
			}

			if ($GLOBALS['decodeStr'] != "")
			{
				$data['decode'] = performDecode($GLOBALS['decodeStr']);
				$results['decode'] = formatDecoding($data['decode']);
			}

			$data['settings'] = performSettings($GLOBALS['decodeStr'], $GLOBALS['hashText'], $GLOBALS['algo'], $GLOBALS['saltText'], $GLOBALS['saltLoc'], $GLOBALS['iterations'], $GLOBALS['cryptStr'], $GLOBALS['binarySaltEncodingHex'], $GLOBALS['binarySaltEncodingBase64'], $GLOBALS['passwordEncoding'], $GLOBALS['customHashFormat']);
			$results['settings'] = json_encode($data['settings'], JSON_PRETTY_PRINT);

			// Only Validate Algorithms which support Validation
			$al = $GLOBALS['algo'];
			if ($GLOBALS['algorithmValidatable'][$al] === false) {
				$GLOBALS['mode'] = "test";
			}

			// Do local validation
			if ($GLOBALS['mode'] == "validate") {
				$data['validation'] = performValidation($data['hash'], $data['decode']);

				if ($data['validation']['encoding'] != "unknown") {
					// Set Password
					$data['setPassword'] = performGigyaSetPassword($data['settings'], $data['validation']);
					if ($data['setPassword'] == true)
					{
						// Login
						$data['login'] = performGigyaLogin($GLOBALS['loginID'], $GLOBALS['hashText']);
					}
					else
					{
						addTestResult('login', 'cancel', null);
					}
				}
				else
				{
					addTestResult('setPassword', 'cancel', null);
					addTestResult('login', 'cancel', null);
				}
			}

			if ($GLOBALS['mode'] == "test") {
				// Set Password
				$data['setPassword'] = performGigyaSetPassword($data['settings'], $data['validation']);
				if ($data['setPassword'] == true)
				{
					// Login
					$data['login'] = performGigyaLogin($GLOBALS['loginID'], $GLOBALS['hashText']);
				}
				else
				{
					addTestResult('login', 'cancel', null);
				}
			}

			$results['validation'] = formatValidation();

			$GLOBALS['data'] = $data;
			$GLOBALS['results'] = $results;
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
		<title>Password Validator</title>
		<!-- Main Solarize Theme -->
		<link rel="stylesheet" href="css/solarize.css">

		<!-- Latest version of jQuery (required for Bootstrap) -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>

		<!-- Latest compiled and minified JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>

		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">

		<!-- Optional theme -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">

		<!-- Custom CSS -->
		<link rel="stylesheet" href="css/custom.css">

		<!-- Page Javascript Logic -->
		<script src="validate-password.js" type="text/javascript"></script>
	</head>
	<body>
	  <div class="header-global">
    <div id="headrow" class="headrow">
        <div class="logo">
          <a href="http://www.gigya.com" title="Gigya">

<!-- Generator: Adobe Illustrator 18.1.1, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="3 609.9 612 182.5" enable-background="new 3 609.9 612 182.5" width="512" height"256">
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
        </div>
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
			<h2><b>Password Validator</b></h2>
			<form name="hash" action="" method="get" class="form-horizontal">
			<div class="main">
				<div class="left">
					<div class="form-group">
						<label class="col-sm-2 control-label">Password Hash</label>
						<div class="col-sm-8">
							<input type="text" name="decodeStr" value="<?=$decodeStr?>" class="form-control">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Password Text</label>
						<div class="col-sm-6">
							<input type="text" name="string" value="<?=$hashText?>" class="form-control">
						</div>
						<div class="col-sm-2">
							<input type="radio" name="passwordEncoding" value="utf8" class="radio-inline" <?php if (($passwordEncoding === "") || (strstr($passwordEncoding, 'utf8') !== FALSE)) echo 'checked'; ?> >
							<label class="control-label">utf8</label>&nbsp;
							<input type="radio" name="passwordEncoding" value="utf16" class="radio-inline" <?php if (strstr($passwordEncoding, 'utf16') !== FALSE) echo 'checked'; ?> >
							<label class="control-label">utf16</label>&nbsp;
							<input type="radio" name="passwordEncoding" value="utf32" class="radio-inline" <?php if (strstr($passwordEncoding, 'utf32') !== FALSE) echo 'checked'; ?> >
							<label class="control-label">utf32</label>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Salt Location/Format</label>
						<div id="customFormat" class="col-sm-2" style="display:none;">
							<input type="text" name="customHashFormat" value="<?=$customHashFormat?>" size="64" class="form-control">
						</div>
						<div class="col-sm-4" sytle="min-height: 34px !important; height: 34px; vertical-align: middle;">
						<?php
							foreach ($saltLocations as $loc) {
								$chk = ($loc == $saltLoc)?"checked=\"checked\"":"";
								echo "<input type='radio' name='saltLoc' id='saltLoc_" . $loc ."' value='" . $loc ."' " . $chk . " onclick='ValidatePassword.hashFormatChanged(this)'/> </label for='" . $loc . "' class='radio-inline'>" . $loc . "</label> ";
							}
						?>
						</div>
					</div>
					<div id="saltGroup" class="form-group">
						<label class="col-sm-2 control-label">Salt</label>
						<div class="col-sm-3">
							<input type="text" name="saltText" value="<?=escapeQuotes($saltText)?>" size="40" class="form-control">
						</div>
						<div id="binaryModifiers" class="col-sm-2">
							<input type="checkbox" name="binarySaltEncodingHex" id="binarySaltEncodingHex" value="hex" class="checkbox-inline" <?php if (strstr($binarySaltEncodingHex, 'hex') !== FALSE) echo 'checked'; ?> >
							<label class="control-label">Hex</label>&nbsp;
							<input type="checkbox" name="binarySaltEncodingBase64" id="binarySaltEncodingBase64" value="base64" class="checkbox-inline" <?php if (strstr($binarySaltEncodingBase64, 'base64') !== FALSE) echo 'checked'; ?> >
							<label class="control-label">Base64</label>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Algorithm<sup>*</sup></label>
						<div class="col-sm-2">
							<select name="algo" class="form-control" onchange="ValidatePassword.algorithmChanged(this)">
							<?php
								foreach ($algorithms as $al => $altext) {
									$sel = ($al == $algo)?"selected":"";
									echo "<option value='" . $al . "' " . $sel . " class='form-control'>" . $altext . "</option>";
								}
							?>
							</select>
						</div>
					</div>
					<div id="cryptGroup" class="form-group">
						<label class="col-sm-2 control-label">Crypt Prefix</label>
						<div class="col-sm-2">
							<input type="text" id="cryptStr" name="cryptStr" value="<?=$cryptStr?>" size="15" class="form-control">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Hashing Rounds</label>
						<div class="col-sm-1">
							<input type="number" name="iterations" value="<?=$iterations?>" size="5" class="form-control">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">&nbsp;</label>
						<div class="col-sm-10">
							<input id="mode" type="hidden" name="mode" value="test">
							<input type="button" value="Validate and Test" class="btn btn-primary" class="form-control" onClick="ValidatePassword.submitValidate(this)">
							<input type="button" value="Only Test" class="btn btn-primary" class="form-control" onClick="ValidatePassword.submitTest(this)">
						</div>
					</div>
				</div>
				<div class="right">
					<h4>JSON Settings<span style="float:right;"><button type="button" class="btn btn-primary btn-xs" onClick="ValidatePassword.selectText('resultsSettings')">Select Text</button></span></h4>
					<div id="resultsSettings" style="font: Courier New, monospace"><pre><?php echo $results['settings'] ?></pre></div>
				</div>
			</div>
			<div id="bottom" class="bs-callout bs-callout-primary bottom">
				<h4>Test Results</h4>
				<?php echo $results['validation'] ?>
			</div>
			</form>
		</div>
		<div id="outerbottom">
			<div style="padding: 10px">
				<div id="resultsHash" style=""><?php echo $results['hash'] ?></div><br/>
				<div id="resultsDecode" style=""><?php echo $results['decode'] ?></div>
			</div>
		</div>

		<div id="outerbottom">
			<div style="padding: 10px">
				<ul>
					<li>Check hashed password is not double-encoded ‡</li>
					<li>Check hashed password bit-depth matches the algorithm selected</li>
					<li>Check the Hex hash or Base64 hash matches the original hashed password</li>
					<li>The UTF-8 field is include because sometimes customers will accidentally UTF-8 encode the raw hash data when they attempt to convert hex encoded data to base64 encoding (which is Gigya's default).<br/>In this scenario, check that one of the hashed password decodings does not look like UTF-8 data. It should instead look like the raw field.</li>
					<li>PBKDF2 when used for password hashing, Gigya typically uses sha1 and 2000 rounds of hashing.</li>
					<li>Algorithms with a <sup>*</sup> do not support validation.</li>
				</ul>
			</div>
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
	<!-- Latest version of jQuery (required for Bootstrap) -->
	<script src="https://jquery-json.googlecode.com/files/jquery.json-2.3.min.js"></script>
	<!-- Latest compiled and minified JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
	<script>
		$(document).ready(function() {
			ValidatePassword.afterLoad();
		});
	</script>
	</body>
</html>
