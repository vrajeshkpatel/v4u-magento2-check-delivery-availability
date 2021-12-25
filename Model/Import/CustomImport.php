<?php

namespace V4U\ZipChecker\Model\Import;
use V4U\ZipChecker\Model\Import\CustomImport\RowValidatorInterface as ValidatorInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

class CustomImport extends \Magento\ImportExport\Model\Import\Entity\AbstractEntity
{
    const ID = 'entity_id';
    const ZIPCODE = 'zipcode';
    const ISACTIVE = 'is_active';
    const TABLE_Entity = 'v4u_zip_checker';
    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = [ValidatorInterface::ERROR_MESSAGE_IS_EMPTY => 'Message is empty',];
    
    protected $_permanentAttributes = [self::ID];
    /**
     * If we should check column names
     *
     * @var bool
     */
    protected $needColumnCheck = true;
    /**
     * Valid column names
     *
     * @array
     */
    protected $validColumnNames = [
    self::ID,
    self::ZIPCODE,
    self::ISACTIVE,
    ];
    /**
     * Need to log in import history
     *
     * @var bool
     */
    protected $logInHistory = true;
    protected $_validators = [];
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_connection;
    protected $_resource;
    /**
     * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Stdlib\StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->_dataSourceModel = $importData;
        $this->_resource = $resource;
        $this->_connection = $resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $this->errorAggregator = $errorAggregator;
    }

    public function getValidColumnNames()
    {
        return $this->validColumnNames;
    }

    /**
     * Entity type code getter.
     *
     * @return string
     */

    public function getEntityTypeCode()
    {
        return 'v4u_zip_checker';
    }

    /**
     * Row validation.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     */
    public function validateRow(array $rowData, $rowNum)
    {
        $title = false;
            if (isset($this->_validatedRows[$rowNum])) {
                return !$this->getErrorAggregator()->isRowInvalid($rowNum);
            }
        $this->_validatedRows[$rowNum] = true;
        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Create Advanced message data from raw data.
     *
     * @throws \Exception
     * @return bool Result of operation.
     */
    protected function _importData()
    {
        //$this->saveEntity();
        if (\Magento\ImportExport\Model\Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            $this->deleteEntity();
        } elseif (\Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
            $this->replaceEntity();
        } elseif (\Magento\ImportExport\Model\Import::BEHAVIOR_APPEND == $this->getBehavior()) {
            $this->saveEntity();
        }        
        return true;
    }
    /**
     * Save Records
     *
     * @return $this
     */
    public function saveEntity()
    {
        $this->saveAndReplaceEntity();
        return $this;
    }
    /**
     * Replace Records
     *
     * @return $this
     */
    public function replaceEntity()
    {
        $this->saveAndReplaceEntity();
        return $this;
    } 
    /**
     * Deletes Records
     *
     * @return $this
     */
    public function deleteEntity()
    {
        $listTitle = [];
            while ($bunch = $this->_dataSourceModel->getNextBunch()) {
                foreach ($bunch as $rowNum => $rowData) {
                    $this->validateRow($rowData, $rowNum);
                    if (!$this->getErrorAggregator()->isRowInvalid($rowNum)) {
                        $rowTtile = $rowData[self::ID];
                        $listTitle[] = $rowTtile;
                    }
                    if ($this->getErrorAggregator()->hasToBeTerminated()) {
                        $this->getErrorAggregator()->addRowToSkip($rowNum);
                    }
                }
            }
        if ($listTitle) {
            $this->deleteEntityFinish(array_unique($listTitle),self::TABLE_Entity);
        }
        return $this;
    }       
    /**
     * Save and replace data message
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function saveAndReplaceEntity()
    {
        $behavior = $this->getBehavior();
        $listTitle = [];
            while ($bunch = $this->_dataSourceModel->getNextBunch()) {
                $entityList = [];
                foreach ($bunch as $rowNum => $rowData) {
                    if (!$this->validateRow($rowData, $rowNum)) {
                        $this->addRowError(ValidatorInterface::ERROR_TITLE_IS_EMPTY, $rowNum);
                        continue;
                    }
                    if ($this->getErrorAggregator()->hasToBeTerminated()) {
                        $this->getErrorAggregator()->addRowToSkip($rowNum);
                        continue;
                    }
                    $rowTtile= $rowData[self::ID];
                    $listTitle[] = $rowTtile;
                    $entityList[$rowTtile][] = [
                        self::ID => $rowData[self::ID],
                        self::ZIPCODE => $rowData[self::ZIPCODE],
                        self::ISACTIVE => $rowData[self::ISACTIVE],
                    ];
                }
                if (\Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE == $behavior) {
                    if ($listTitle) {
                        if ($this->deleteEntityFinish(array_unique($listTitle), self::TABLE_Entity)) {
                            $this->saveEntityFinish($entityList, self::TABLE_Entity);
                        }
                    }
                } elseif (\Magento\ImportExport\Model\Import::BEHAVIOR_APPEND == $behavior) {
                    $this->saveEntityFinish($entityList, self::TABLE_Entity);
                }
            }
        return $this;
    }
    /**
     * Save Records to customtable.
     *
     * @param array $priceData
     * @param string $table
     * @return $this
     */
    protected function saveEntityFinish(array $entityData, $table)
    {
        if ($entityData) {
            $tableName = $this->_connection->getTableName($table);
            $entityIn = [];
            foreach ($entityData as $id => $entityRows) {
                    foreach ($entityRows as $row) {
                        $entityIn[] = $row;
                    }
            }
            if ($entityIn) {
                $this->_connection->insertOnDuplicate($tableName, $entityIn,[
                    self::ID,
                    self::ZIPCODE,
                    self::ISACTIVE,
            ]);
            }
        }
        return $this;
    }

    protected function deleteEntityFinish(array $listTitle, $table)
    {
        if ($table && $listTitle) {
            try {
                $this->countItemsDeleted += $this->_connection->delete(
                    $this->_connection->getTableName($table),
                    $this->_connection->quoteInto('v4u_zip_checker IN (?)', $listTitle)
                );
                return true;
            } catch (\Exception $e) {
                return false;
            }
        } 
        else 
        {
        return false;
        }
    }    
}