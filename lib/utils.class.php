<?php
  abstract class Utils {
    /*
     * isSecureConnection
     *
     * Checks that the page is loaded via HTTPS (or from localhost)
     *
     */
    function isSecureConnection() {
      if ($_SERVER['HTTP_HOST'] == 'localhost') return true;
      return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
    }
  }
?>
