<?php
  abstract class LogLevels {
    const debug    = 0x00000001;
    const info     = 0x00000010;
    const warn     = 0x00000100;
    const error    = 0x00001000;
    const critical = 0x00010000;
  }

  class Logger {
    const defaultLogLevel = LogLevels::info;
    private $logLevel = LogLevels::error;
    private $logLevelNames = array(
      LogLevels::debug => "Debug",
      LogLevels::info => "Info",
      LogLevels::warn => "Warn",
      LogLevels::error => "Error",
      LogLevels::critical => "critical"
    );

    private $allowSystemLogging = false;

    private $l_debug = array();
    private $l_info = array();
    private $l_warn = array();
    private $l_error = array();
    private $l_critical = array();

    public function __construct($level = NULL) {
      if ($level !== NULL) $this->logLevel = $level;
    }

    public function enableSystemLogging() {
      $this->allowSystemLogging = true;
    }
    public function disableSystemLogging() {
      $this->allowSystemLogging = false;
    }

    public function isLevelSet($compare, $level) {
      if ($level === NULL) $level = $this->defaultLogLevel;
      return (($level & $compare) != 0x00000000);
    }
    public function allowsDebug($level = NULL) {
      return $this->isLevelSet(LogLevels::debug, $level);
    }
    public function allowsInfo($level = NULL) {
      return $this->isLevelSet(LogLevels::info, $level);
    }
    public function allowsWarn($level = NULL) {
      return $this->isLevelSet(LogLevels::warn, $level);
    }
    public function allowsError($level = NULL) {
      return $this->isLevelSet(LogLevels::error, $level);
    }
    public function allowsCritical($level = NULL) {
      return $this->isLevelSet(LogLevels::critical, $level);
    }

    public function setLogLevel($level) {
      if ($level !== NULL) $this->logLevel = $level;
    }
    public function getLogLevel() {
      return $this->logLevel;
    }

    private function createLogMessage($message, $extended = "") {
      if ($message == NULL) return;
      $logMessage = array (
        "message" => $message,
        "extended" => $extended
      );
      return $logMessage;
    }

    private function logToSystem($message, $extended, $level) {
      if ($this->allowSystemLogging) {
        error_log("[" + $this->logLevelNames[$level] + "] " + $message + " :: " + $extended);
      }
    }

    public function addDebug($message, $extended = "") {
      if ($message == NULL) return;
      $log = $this->createLogMessage($message, $extended);
      if ($log !== NULL) array_push($this->l_debug, $log);
      $this->logToSystem($message, $extended, LogLevels::debug);
    }

    public function addInfo($message, $extended = "") {
      if ($message == NULL) return;
      $log = $this->createLogMessage($message, $extended);
      if ($log !== NULL) array_push($this->l_info, $log);
      $this->logToSystem($message, $extended, LogLevels::info);
    }

    public function addWarn($message, $extended = "") {
      if ($message == NULL) return;
      $log = $this->createLogMessage($message, $extended);
      if ($log !== NULL) array_push($this->l_warn, $log);
      $this->logToSystem($message, $extended, LogLevels::warn);
    }

    public function addError($message, $extended = "") {
      if ($message == NULL) return;
      $log = $this->createLogMessage($message, $extended);
      if ($log !== NULL) array_push($this->l_error, $log);
      $this->logToSystem($message, $extended, LogLevels::error);
    }

    public function addCritical($message, $extended = "") {
      if ($message == NULL) return;
      $log = $this->createLogMessage($message, $extended);
      if ($log !== NULL) array_push($this->l_critical, $log);
      $this->logToSystem($message, $extended, LogLevels::criticial);
    }

    public function addLog($message, $extended = "", $level = Logger::defaultLogLevel) {
      if ($message == NULL || $message == "") return;
      if ($level == NULL) $level = $this->defaultLogLevel;

      if (($level & LogLevels::debug) > 0) $this->addDebug($message, $extended);
      if (($level & LogLevels::info) > 0) $this->addInfo($message, $extended);
      if (($level & LogLevels::warn) > 0) $this->addWarn($message, $extended);
      if (($level & LogLevels::error) > 0) $this->addError($message, $extended);
      if (($level & LogLevels::critical) > 0) $this->addCritical($message, $extended);
    }

    public function addFunctionDebug($funcName, $stepName, $message = "", $extended = "") {
      $fullMsg = $funcName . "::" . $stepName . ($message!=""?" - " . $message:"");
      $this->addDebug($fullMsg, $extended);
    }

    public function getDebugLog() {
      return $this->l_debug;
    }

    public function getInfoLog() {
      return $this->l_info;
    }

    public function getWarnLog() {
      return $this->l_warn;
    }

    public function getErrorLog() {
      return $this->l_error;
    }

    public function getCriticalLog() {
      return $this->l_critical;
    }
  }
?>
