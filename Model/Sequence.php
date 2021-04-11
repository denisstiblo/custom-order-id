<?php

namespace Intsoft\CustomOrderId\Model;

use Magento\Framework\App\ResourceConnection as AppResource;
use Magento\Framework\DB\Sequence\SequenceInterface;
use Magento\SalesSequence\Model\Meta;


class Sequence extends \Magento\SalesSequence\Model\Sequence
{

    protected $connectionExt;

    protected $metaExt;

    protected $storeManager;

    private $scopeConfig;

    private const DEFAULT_CUSTOM_ORDER_LENGTH = 6;

    public function __construct(
        Meta $meta,
        AppResource $resource,
        $pattern = \Magento\SalesSequence\Model\Sequence::DEFAULT_PATTERN,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->connectionExt = $resource->getConnection('sales');
        $this->metaExt = $meta;
        $this->storeManager = $storeManager;
        parent::__construct($meta, $resource, $pattern);
    }


    public function getCurrentValue()
    {
        $patternLength = $this->getCustomOrderIdLength();
        $currentValue = $this->calculateCurrentValue();
        $currentValueLength = strlen($currentValue);
        $emptyStringLength = $patternLength - $currentValueLength;
        $emptyString = '';

        for ($i = 0; $i < $emptyStringLength; $i++) {
            $emptyString .= '0';
        }

        return $this->getPrefixPattern() .$this->getStoreIdentifierPattern() . $emptyString . $currentValue;
    }


    private function getPrefixPattern()
    {
        return $this->scopeConfig->getValue('OrderConfig/customOrderId/prefix_custom_order_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    private function getCustomOrderIdLength()
    {
        $orderLength =  $this->scopeConfig->getValue('OrderConfig/customOrderId/custom_order_id_length',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return !empty(intval($orderLength)) ? intval($orderLength) : self::DEFAULT_CUSTOM_ORDER_LENGTH;
    }

    private function getStoreIdentifierPattern()
    {
        return $this->scopeConfig->getValue('OrderConfig/customOrderId/store_id_prefix_custom_order_id',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
            );
    }

    /**
     * Calculate current value depends on start value
     *
     * @return string
     */
    private function calculateCurrentValue()
    {
        $lastInsertId =  $this->connectionExt->lastInsertId($this->metaExt->getSequenceTable());

        return ($lastInsertId - $this->metaExt->getActiveProfile()->getStartValue())
            * $this->metaExt->getActiveProfile()->getStep() + $this->metaExt->getActiveProfile()->getStartValue();
    }

    private function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}
