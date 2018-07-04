<?php

namespace PaynlPayment\Components;


use Shopware\Components\Plugin\ConfigReader;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Paynl\Config as SDKConfig;

class Config
{
    /**
     * @var ConfigReader
     */
    protected $configReader;

    /**
     * @var array
     */
    protected $data = null;

    public function __construct(ConfigReader $configReader)
    {
        $this->configReader = $configReader;
    }

    /**
     * Get a config value
     *
     * @param string $key Key of the config value, leave empty to get all configs for this plugin
     * @param mixed $default If the config key is not found, this value will be returned
     * @return mixed
     */
    public function get($key = null, $default = null){
        if(is_null($this->data)){
            try{
                $shop = Shopware()->Shop();
            } catch(ServiceNotFoundException $e){
                $shop = null;
            }
            $parts = explode('\\', __NAMESPACE__);
            $pluginName = array_shift($parts);

            $this->data = $this->configReader->getByPluginName($pluginName, $shop);
        }

        if(!is_null($key)){
            return isset($this->data[$key])?$this->data[$key]:$default;
        }
        return $this->data;
    }

    /**
     * @return string
     */
    public function tokenCode(){
        return $this->get('tokenCode');
    }

    /**
     * @return string
     */
    public function apiToken(){
        return $this->get('apiToken');
    }

    /**
     * @return string
     */
    public function serviceId(){
        return $this->get('serviceId');
    }

    /**
     * @return boolean
     */
    public function testMode(){
        return $this->get('testMode', false);
    }

    /**
     * @return boolean
     */
    public function sendStatusMail(){
        return $this->get('status_mail', false);
    }

    public function loginSDK(){
        SDKConfig::setTokenCode($this->tokenCode());
        SDKConfig::setApiToken($this->apiToken());
        SDKConfig::setServiceId($this->serviceId());
    }
}