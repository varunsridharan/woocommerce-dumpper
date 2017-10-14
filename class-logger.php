<?php

class WooCommerce_Dumpper_LoggerException extends Exception {}
class WooCommerce_Dumpper_Logger {
    protected $fileHandle = NULL;
    protected $timeFormat = 'd.m.Y - H:i:s';
    const FILE_CHMOD = 756;
    const NOTICE = '[NOTICE]';
    const WARNING = '[WARNING]';
    const ERROR = '[ERROR]';
    const FATAL = '[FATAL]';

    public function __construct($logfile) { 
        if($this->fileHandle == NULL){ 
            $this->openLogFile($logfile); 
        } 
    }
    
    public function __destruct() { $this->closeLogFile(); }
    
    public function log($message, $messageType = WooCommerce_Dumpper_Logger::WARNING) {
        if($this->fileHandle == NULL){ throw new WooCommerce_Dumpper_LoggerException('Logfile is not opened.'); }
        if(!is_string($message)){ throw new WooCommerce_Dumpper_LoggerException('$message is not a string'); }
        if($messageType != WooCommerce_Dumpper_Logger::NOTICE && $messageType != WooCommerce_Dumpper_Logger::WARNING && $messageType != WooCommerce_Dumpper_Logger::ERROR && $messageType != WooCommerce_Dumpper_Logger::FATAL ){ throw new WooCommerce_Dumpper_LoggerException('Wrong $messagetype given.'); }
        $this->writeToLogFile("[".$this->getTime()."]".$messageType." - ".$message);
    }
    
    public function simple_log($message) {
        if($this->fileHandle == NULL){ throw new WooCommerce_Dumpper_LoggerException('Logfile is not opened.'); }
        if(!is_string($message)){ throw new WooCommerce_Dumpper_LoggerException('$message is not a string'); }
        $this->writeToLogFile($message);
    }
    
    private function writeToLogFile($message) {
        flock($this->fileHandle, LOCK_EX);
        fwrite($this->fileHandle, $message.PHP_EOL);
        flock($this->fileHandle, LOCK_UN);
    }
    
    private function getTime() { return date($this->timeFormat); }
    
    protected function closeLogFile() { if($this->fileHandle != NULL) { fclose($this->fileHandle); $this->fileHandle = NULL; } }
    
    public function openLogFile($logFile) {
        $this->closeLogFile();

        if(!is_dir(dirname($logFile))){
            if(!mkdir(dirname($logFile), WooCommerce_Dumpper_Logger::FILE_CHMOD, true)){
                throw new WooCommerce_Dumpper_LoggerException('Could not find or create directory for log file.');
            }
        }

        if(!$this->fileHandle = fopen($logFile, 'a+')){
            throw new WooCommerce_Dumpper_LoggerException('Could not open file handle.');
        }
    }
}