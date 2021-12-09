<?php

namespace V4U\ZipChecker\Plugin\Payment\Method\CashOnDelivery;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Backend\Model\Auth\Session as BackendSession;
use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\Checkout\Model\Session as CheckoutSession;
use V4U\ZipChecker\Helper\Data as DataHelper;

class Available
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var DataHelper
     */
    protected $dataHelper;
    /**
     * @var BackendSession
     */
    protected $backendSession;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @param CustomerSession $customerSession
     * @param BackendSession $backendSession
     * @param DataHelper $dataHelper
     */
    public function __construct(
        CustomerSession $customerSession,
        BackendSession $backendSession,
        CheckoutSession $checkoutSession,
        DataHelper $dataHelper
    ) {
        $this->customerSession = $customerSession;
        $this->backendSession = $backendSession;
        $this->checkoutSession  = $checkoutSession;
        $this->dataHelper = $dataHelper;
    }
    /**
     *
     * @param Cashondelivery $subject
     * @param $result
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterIsAvailable(Cashondelivery $subject, $result)
    {
        // Do not remove payment method for admin
        if ($this->backendSession->isLoggedIn()) {
            return $result;
        }
        $zipcode = $this->checkoutSession->getQuote()->getShippingAddress()->getPostcode();
        $zipcodes = $this->dataHelper->getZipCodes();
        $zipcodes = array_map('trim',explode(',', $zipcodes));
        if(in_array($zipcode, $zipcodes)){
            return true;
        }
        else{
            return false;
        }

    }
}