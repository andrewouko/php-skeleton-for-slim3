<?php
namespace Services;

use DateTimeZone;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;

class Log{ 
    private $logger;
    private $chanel_name;
    function __construct(string $application_name, string $channel_name, HandlerInterface $handler = null, $usePsrLogMessageProcessor = false, $useIntrospectionProcessor = false, $useWebProcessor = false, $usePerformanceProcessors = false)
    {
        foreach(['application_name', 'channel_name'] as $var_name){
            $$var_name = preg_replace('/\s+/', '', $$var_name);
        }
        Logger::setTimezone(new DateTimeZone('Africa/Nairobi'));
        $this->chanel_name = $application_name . '.' . $channel_name;
        $this->logger = new Logger($this->chanel_name);
        if(!isset($handler)){
            $current_time = new \DateTime(null, new \DateTimeZone('Africa/Nairobi'));
            $this->logger->pushHandler(new StreamHandler($_ENV['default_log_file_path'] . $current_time->format('Y-m-d').'.log'));
        }
        else
            $this->logger->pushHandler($handler);
        $this->logger->pushProcessor(new UidProcessor());
        if($usePsrLogMessageProcessor){
            $this->logger->pushProcessor(new PsrLogMessageProcessor());
        }
        if($useIntrospectionProcessor){
            $this->logger->pushProcessor(new IntrospectionProcessor());
        }
        if($useWebProcessor){
            $this->logger->pushProcessor(new WebProcessor());
        }
        if($usePerformanceProcessors){
            $this->logger->pushProcessor(new MemoryUsageProcessor());
            $this->logger->pushProcessor(new MemoryPeakUsageProcessor());
            $this->logger->pushProcessor(new ProcessIdProcessor());
        }
    } 
    function getLogger(){
        return $this->logger;
    }
    function getLogChannel(){
        return $this->chanel_name;
    }
    function setChannelName(string $new_name){
        return $this->logger->withName($new_name);
    }
}
/* $log = new Log('Default App', ',Default Channel', null, false, true, false, true);
$log = $log->getLogger();
$log->info("Test the channel"); */
