<?php
  return (object) array (
    "apiKey" => "",
    "dataCenter",
    "database" => (object) array (
      "type" => "mysql",
      "server" => "",
      "user" => "",
      "pass" => ""
    ),
    "logging" => true,
    "debug" => false,
    "useThrottle" => true,
    "certFile" => "cert/cacert.pem",
    "proxy" => (object) array (
      "enabled" => false,
      "address" => "127.0.0.1:8888",
      "certFile" => "cert/charles-ssl-proxying-certificate.pem"
    )
  );
?>
