<?php

namespace V4U\ZipChecker\Controller\ZipChecker;

use V4U\ZipChecker\Helper\Data as DataHelper;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

/*
 * Class ZipChecker
 * @package V4U\ZipChecker\Controller\ZipChecker
 */

class ZipChecker extends Action
{
    /**
     * @var ProductModel
     */
    protected $_productModel;
    /**
     * @var DataHelper
     */
    protected $_helper;

    /**
     * ZipChecker constructor.
     * @param Context $context
     * @param ProductFactory $productFactory
     * @param DataHelper $dataHelper
     */

    public function __construct(
        Context $context,
        ProductFactory $productFactory,
        DataHelper $dataHelper
    ) {
        parent::__construct($context);
        $this->productFactory = $productFactory;
        $this->dataHelper = $dataHelper;
    }

    public function execute()
    {
        $response = [];
        try {
            if(!$this->getRequest()->isAjax()){
                throw new \Exception("Invalid Request. Try again.");
            }

            if(!$zipcode = $this->getRequest()->getParam('zipcode')){
                throw new \Exception("Pease enter zipcode");
            }

            $productId = $this->getRequest()->getParam('id', 0);
            $product = $this->productFactory->create()->load($productId);

            if(!$product->getId()){
                throw new \Exception("Product not found");
            }

            $zipcodes = trim($product->getCheckDeliveryPostcodes());
            if(!$zipcodes){
                $zipcodes = $this->dataHelper->getZipCodes();
            }

            $zipcodes = array_map('trim',explode(',', $zipcodes));
            if(in_array($zipcode, $zipcodes)){
                $response['type'] = 'success';
                $response['message'] = __($this->dataHelper->getSuccessMessage(),$zipcode); 
            } else {
                $response['type'] = 'error';
                $response['message'] = __($this->dataHelper->getErrorMessage(),$zipcode);
            }
        } catch (\Exception $e) {
            $response['type'] = 'error';
            $response['message'] = $e->getMessage();
        }
        $this->getResponse()->setContent(json_encode($response));
    }
}
