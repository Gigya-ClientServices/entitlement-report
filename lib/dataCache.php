<?php
  require_once('conf/settings.php');
  class DataCache {
    private dbConn;

    function __construct() {
        $this->dbConn = null;
        if (!initDB()) {
          throw new Exception("Database connection failed: " . mysqli_connect_error());
        }
    }

    function initDB() {
        $this->dbConn = new mysqli($GLOBALS['mysql_server'], $GLOBALS['mysql_user'], $GLOBALS['mysql_pass']);

        // Check connection
        if (mysqli_connect_error()) {
          return false;
        }
        return true;
    }

  }
?>
