<?php

namespace V4U\ZipChecker\Controller\ZipChecker;

use V4U\ZipChecker\Helper\Data as DataHelper;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use V4U\ZipChecker\Model\GridFactory;

/*
 * Class ZipChecker
 * @package V4U\ZipChecker\Controller\ZipChecker
 */

class ZipChecker extends Action
{
    protected $modelGridFactory;    
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
        DataHelper $dataHelper,
        GridFactory $modelGridFactory
    ) {
        parent::__construct($context);
        $this->productFactory = $productFactory;
        $this->dataHelper = $dataHelper;
        $this->modelGridFactory = $modelGridFactory;
    }

    public function execute()
    {

        $resultPage = $this->modelGridFactory->create();
        $collection = $resultPage->getCollection();
        $collection = $collection->addFieldToSelect('zipcode')->addFieldToFilter('is_active',array('eq'=>'1'));
        
        $zipcodes = array();
        foreach ($collection as $zipCodes) {
            $zipcodes[] = $zipCodes->getZipCode(); 
            $zipCodes->getZipCode();
        }
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

            $zipcodesProd = trim($product->getCheckDeliveryPostcodes());

            $zipcodesProd = array_map('trim',explode(',', $zipcodesProd));

            $zipCodeFinal = array_merge($zipcodesProd,$zipcodes);

            if(in_array($zipcode,$zipCodeFinal)){
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
