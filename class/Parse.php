<?php

class Parse {

    public $sessionDir;
    public $logDir;
    public $menuDir;
    public $keydes;
    public $langFile;
    public $langDir;
    public $simBanking;
    public $genConfigDir;
    public $pageOneMenuCount;
    public $accPrefix;
    public $defaultLang;
    public $ATMMultiples;
    public $safaricomPrefixes;
    public $phoneIdentiierLength;
    public $defaultPrefix;
    public $countryCode;
    public $httpserver;

    function __construct($configFile) {
        try {
            $config = parse_ini_file('app_config/' . $configFile, true);
            $this->sessionDir = $config['sessionDir'];
            $this->logDir = $config['logDir'];
            $this->menuDir = $config['menuDir'];
            $this->keydes = $config['keydes'];
            $this->langFile = $config['langFile'];
            $this->langDir = $config['langDir'];
            $this->genConfigDir = $config['genConfigDir'];
            $this->pageOneMenuCount = $config['pageOneMenuCount'];
            $this->accPrefix = $config['accPrefix'];
            $this->defaultLang = $config['defaultLang'];
            $this->atmMultiples = $config['atmMultiples'];
            $this->safaricomPrefixes = $config['safaricomPrefixes'];
            $this->defaultPrefix = $config['defaultPrefix'];
            $this->httpserver = $config['httpserver'];
            $this->countFile = $config['countFile'];
            $this->logsFile = $config['logsFile'];
        } catch (Exception $ex) {
            die($ex->getMessage());
        }
    }
}
