<?php
/**
 * Created by PhpStorm.
 * User: Jan Monteros
 * Date: 1/12/2017
 * Time: 1:22 PM
 */

class SFC_Rewards_Helper_Data extends SubscribePro_Autoship_Helper_Api
{
    const SUBSCRIBE_PRO_API = 'https://api.subscribepro.com/services/v2/subscriptions/';
    const SFC_REWARDS_LOG = 'SFC_Rewards.log';

    //set spending rule ID
    const SPENDING_RULE_ID = 1337;
    const IS_ENABLED = true;

    /**
     * @param $subscriptionId
     * @return mixed
     */
    public function getSubscriptionData($subscriptionId)
    {
        $url = self::SUBSCRIBE_PRO_API.$subscriptionId;

        $response = $this->_initSubscribeProApi($url,
            $this->getClientId(),$this->getSecretKey());

        $toArray = json_decode($response);

        return $toArray->subscription;
    }

    /**
     * @param $url
     * @param $clientId
     * @param $secretKey
     * @return mixed
     */
    private function _initSubscribeProApi($url,$clientId,$secretKey)
    {
        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $coreHelper->decrypt($clientId).':'.$coreHelper->decrypt($secretKey));
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            $response = curl_exec($ch);
            curl_close($ch);
            return $response;

        }catch (Exception $e){
            $this->logInfo('Subscribe Pro API request failed:');
            $this->logInfo($e->getMessage());
        }

    }

    /**
     * Get subscription item from Quote
     * @param Mage_Sales_Model_Quote_Item $quote
     * @return Mage_Sales_Model_Quote_Item
     */
    public function getSubscriptionProductFromQuote($quote)
    {
        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if ($quoteItem->getData('subscription_id')) {
                return $quoteItem;
            }
        }
    }

    /**
     * Gett Sales rule conversion amount
     * @param $ruleId
     * @return mixed
     */
    public function getSalesRuleConversion($ruleId)
    {
        $salesRule = Mage::getModel('rewards/salesrule_rule')->load($ruleId);

        return $salesRule->getPointsDiscountAmount();
    }

    /**
     * Get Client Id
     * @return mixed
     */
    public function getClientId()
    {
        return Mage::getStoreConfig('autoship_general/platform_api/client_id',
            $this->getConfigStore());
    }

    /**
     * Get Secret Key
     * @return mixed
     */
    public function getSecretKey()
    {
        return Mage::getStoreConfig('autoship_general/platform_api/client_secret',
            $this->getConfigStore());
    }

    /**
     * Is extension enabled
     * @return mixed
     */
    public function isEnabled(){
        return self::IS_ENABLED;
    }

    /** Get Spending Rule Id
     * @return mixed
     */
    public function getSpendingRuleId(){
        return self::SPENDING_RULE_ID;
    }

    /**
     * @param $message
     */
    public function logInfo($message)
    {
        Mage::log($message,null,self::SFC_REWARDS_LOG);
    }
}