<?php
/**
 * Created by PhpStorm.
 * User: Jan Monteros
 * Date: 1/12/2017
 * Time: 1:22 PM
 */

class SFC_Rewards_Model_Transfer extends TBT_Rewards_Model_Transfer
{
    /**
     * Create a new reorder point transfer
     * @param $custId
     * @param $points
     * @param $comments
     */
    public function setNewReorderPointTransfer($custId,$points,$comments)
    {
        $sfcRewardsHelper = Mage::helper('sfc_rewards');
        $qtyToDebit = $this->getPointsToSpend($custId,$points)* (-1);
        try {
            $this->setCustomerId($custId);
            $this->setQuantity($qtyToDebit);
            $this->setComments($comments);
            $this->setReasonId(1);
            $this->setIsDevMode(Mage::getStoreConfig('rewards/platform/dev_mode'));
            $this->setReferenceId(0);
            $this->setData('status_id', TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED);
            $this->save();
        } catch (Exception $e) {
            $sfcRewardsHelper->logInfo('Exception Error:' . __METHOD__);
            $sfcRewardsHelper->logInfo($e->getMessage());
        }
    }

    /**
     * Check customer spending
     * capabilities
     * @param $custId
     * @param $points
     * @return int
     */
    public function getPointsToSpend($custId,$points)
    {
        $customer = Mage::getModel('rewards/customer')->load($custId);
        $currency_id = Mage::helper('rewards/currency')->getDefaultCurrencyId();
        $customerUsablePoints = $customer->getUsablePointsBalance($currency_id);

        if($customerUsablePoints >= $points){
            $spendablePoints =  $points;
        }else{
            $spendablePoints = $customerUsablePoints > 0 ? $customerUsablePoints : 0;
        }

        return ($spendablePoints > 0) ? round($spendablePoints) : $spendablePoints;
    }
}