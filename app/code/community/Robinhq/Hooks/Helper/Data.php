<?php


/**
 * Class Robinhq_Hooks_Helper_Data
 */
class Robinhq_Hooks_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * @var Robinhq_Hooks_Model_Queue
     */
    private $queue;

    /**
     * @var Robinhq_Hooks_Model_Logger
     */
    private $logger;

    /**
     * @var Robinhq_Hooks_Model_Robin_Converter
     */
    private $converter;

    private $bulkLimit;

    private $selectLimit;

    private $api;

    /**
     * @var Robinhq_Hooks_Model_Collector
     */
    private $collector;

    /**
     * Gets and sets the dependency's
     */
    public function __construct() {

        $config = Mage::getStoreConfig('settings/general');
        $this->bulkLimit = (int)$config['bulk_limit'];
        $this->selectLimit = (int)$config['select_limit'];
        $this->logger = new Robinhq_Hooks_Model_Logger();
        $this->api = new Robinhq_Hooks_Model_Api($this->logger);
        $this->queue = new Robinhq_Hooks_Model_Queue($this->logger, $this->api, $this->bulkLimit);
        $this->converter = new Robinhq_Hooks_Model_Robin_Converter();
        $this->collector = new Robinhq_Hooks_Model_Collector($this->queue, $this->converter, $this->selectLimit);
    }

    /**
     * @param $message
     */
    public function log($message) {

        $this->logger->log($message);
    }

    /**
     * @return Robinhq_Hooks_Model_Queue
     */
    public function getQueue() {

        return $this->queue;
    }

    /**
     * @return Robinhq_Hooks_Model_Api
     */
    public function getApi() {

        return $this->api;
    }

    public function getLogger() {

        return $this->logger;
    }

    public static function warnAdmin($warning) {

        Mage::getSingleton('adminhtml/session')->addWarning("Robin: " . $warning);
    }

    public static function noticeAdmin($notice) {

        Mage::getSingleton('adminhtml/session')->addSuccess("Robin: " . $notice);
    }

    public static function formatPrice($price) {

        return Mage::helper('core')->currency($price, true, false);
    }

    /**
     * @return Robinhq_Hooks_Model_Robin_Converter
     */
    public function getConverter() {

        return $this->converter;
    }

    /**
     * @return Robinhq_Hooks_Model_Collector
     */
    public function getCollector() {

        return $this->collector;
    }

    public function formatPhoneNumber($phoneNumber, $countryCode) {
        $phoneNumberClean = preg_replace("/[^\d]/", "", $phoneNumber);
        $length = strlen($phoneNumberClean);
        if($length == 10) {
            $phoneNumberFormatted = $phoneNumber;
        }
        elseif($length == 11) {
            if($countryCode=='NL')  {
                if(substr($phoneNumberClean, 0, 2)=='31') {
                    $phoneNumberFormatted = substr_replace($phoneNumberClean,'0',0,2);
                }
            }else{
                $phoneNumberFormatted = $phoneNumber;
            }
        }
        if(!isset($phoneNumberFormatted))   {
            $phoneNumberFormatted = $phoneNumberClean;
        }
        return $phoneNumberFormatted;
    }

    /**
     * Get reward points for a customer
     *
     * @param Mage_Customer_Model_Customer $_customer
     * @return int
     */
    public function getRewardPoints($_customer) {
        $points = 0;

        if(Mage::getConfig()->getModuleConfig('Rewardpoints')->is('active', 'true'))  {
            $allStores = Mage::app()->getStores();
            if ($_customer->getId()) {
                foreach ($allStores as $_eachStoreId => $val) {
                    $_storeId = Mage::app()->getStore($_eachStoreId)->getId();
                    if (Mage::getStoreConfig('rewardpoints/default/flatstats', $_storeId)) {
                        $reward_flat_model = Mage::getModel('rewardpoints/flatstats');
                        if($reward_flat_model)  {
                            $points += $reward_flat_model->collectPointsCurrent($_customer->getId(), $_storeId) + 0;
                        }
                    } else {
                        $reward_model = Mage::getModel('rewardpoints/stats');
                        if($reward_model)   {
                            $points += $reward_model->getPointsCurrent($_customer->getId(), $_storeId) + 0;
                        }

                    }
                }
            }
        }
        return $points;
    }

    /**
     * @param string $route
     * @param array  $params
     *
     * @return string
     */
    public function getUrl($route='', $params=array())    {
        return Mage::getModel('adminhtml/url')->setStore(Mage_Core_Model_App::ADMIN_STORE_ID)->getUrl($route, $params);
    }


}
	 