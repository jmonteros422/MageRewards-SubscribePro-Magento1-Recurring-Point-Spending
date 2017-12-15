<?php
/**
 * Created by PhpStorm.
 * User: Jan Monteros
 * Date: 1/12/2017
 * Time: 1:22 PM
 */

class SFC_Rewards_Model_Observer
{
    /**
     * Set custom field set to subscription profile
     * @param Varien_Event_Observer $observer
     */
    public function setRewardsFieldOnTheFly(Varien_Event_Observer $observer)
    {
        $quote = $observer->getData('quote_item')->getQuote();
        $helper = Mage::helper('sfc_rewards');

        if($helper->isEnabled()){
            //@todo add flag if brain points for reorder is enabled
            if ($quote->getRewardsPointsSpending() > 0) {
                //Store quote data to subscription instance
                $subscription = $observer->getData('subscription');
                $rewardsDataFields = array(
                    'applied_rule_ids' => $quote->getAppliedRuleIds(),
                    'applied_redemptions' => $quote->getAppliedRedemptions(),
                    'rewards_points_spending' => $quote->getRewardsPointsSpending(),
                );
                $subscription->setUserDefinedFields($rewardsDataFields);
                $helper->logInfo(__METHOD__);
            }
        }
    }
    /**
     * Apply necessary data to new reorder quote
     * for rewards points to apply.
     * @param Varien_Event_Observer $observer
     */
    public function applyRewardsFieldToReorder(Varien_Event_Observer $observer)
    {
        $sfcRewardsHelper = Mage::helper('sfc_rewards');
        $quote = $observer->getData('quote');

        if($sfcRewardsHelper->isEnabled()){

            $subscriptionProduct = $sfcRewardsHelper->getSubscriptionProductFromQuote($quote);
            if ($subscriptionId = $subscriptionProduct->getSubscriptionId()) {
                $sfcRewardsTransfer = Mage::getModel('sfc_rewards/transfer');

                $subscriptionApi = $sfcRewardsHelper
                    ->getSubscriptionData($subscriptionId);

                $custId = $subscriptionApi->payment_profile->magento_customer_id;
                $userDefinedFields = (array)$subscriptionApi->user_defined_fields;

                //this check if rewards points was used for this item
                if ($userDefinedFields['rewards_points_spending'] > 0) {
                    //@todo system config for spending rule
                    $spendingConversion = $sfcRewardsHelper->getSalesRuleConversion($sfcRewardsHelper->getSpendingRuleId());
                    //$subTotal = $subscriptionProduct->getPrice(); //@todo
                    $subTotal = $quote->getSubtotal();
                    $pointsNeeded = $subTotal / $spendingConversion;
                    $pointsToSpend = ($userDefinedFields['rewards_points_spending'] > $pointsNeeded)
                        ? $pointsNeeded
                        : $userDefinedFields['rewards_points_spending'];
                    //set necessary field set to new quote before reorder.
                    $quote->setAppliedRuleIds($userDefinedFields['applied_rule_ids']);
                    $quote->setAppliedRedemptions($userDefinedFields['applied_redemptions']);
                    $quote->setRewardsPointsSpending($sfcRewardsTransfer->getPointsToSpend($custId, $pointsToSpend));

                    //save :)
                    $quote->save();

                    $sfcRewardsTransfer->setNewReorderPointTransfer(
                        $custId,
                        $pointsToSpend,
                        'Spent for reorder. Subscription Id: ' . $subscriptionProduct->getSubscriptionId() .
                        ' Product: ' . $subscriptionApi->product_sku);
                }
            }
        }
    }
}