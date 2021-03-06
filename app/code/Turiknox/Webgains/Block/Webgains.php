<?php
/*
 * Turiknox_Webgains

 * @category   Turiknox
 * @package    Turiknox_Webgains
 * @copyright  Copyright (c) 2017 Turiknox
 * @license    https://github.com/Turiknox/magento2-webgains-tracking/blob/master/LICENSE.md
 * @version    1.0.0
 */
namespace Turiknox\Webgains\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

class Webgains extends Template
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Resolver
     */
    protected $locale;

    /*
     * Webgains XML Enable Path
     */
    const XML_PATH_WG_ENABLE = 'webgains/general/enable';

    /**
     * Webgains Program ID
     */
    const XML_PATH_WG_PROGRAM_ID = 'webgains/general/program_id';

    /**
     * Webgains Event ID
     */
    const XML_PATH_WG_EVENT_ID = 'webgains/general/event_id';

    /**
     * Webgains Version
     */
    const WG_VERSION = '1.2';

    /**
     * Webgains constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param Resolver $locale
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Resolver $locale,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->locale = $locale;
    }

    /**
     * Check if the module is enabled in admin
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->_scopeConfig->getValue(self::XML_PATH_WG_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Webgains Program ID
     *
     * @return int
     */
    public function getWebgainsProgramId()
    {
        return $this->_scopeConfig->getValue(self::XML_PATH_WG_PROGRAM_ID, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Webgains Event ID
     *
     * @return int
     */
    public function getWebgainsEventId()
    {
        return $this->_scopeConfig->getValue(self::XML_PATH_WG_EVENT_ID, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get current locale
     *
     * @return null|string
     */
    public function getLocaleCode()
    {
        return $this->locale->getLocale();
    }

    /**
     * Get Webgains Version
     *
     * @return string
     */
    public function getWebgainsVersion()
    {
        return self::WG_VERSION;
    }

    /**
     * Set order
     */
    public function setOrder()
    {
        $this->order = $this->checkoutSession->getLastRealOrder();
    }

    /**
     * Get order
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if (is_null($this->order)) {
            $this->setOrder();
        }
        return $this->order;
    }

    /**
     * Get the order ID
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }

    /**
     * Get the order total
     */
    public function getGrandTotal()
    {
        return number_format($this->order->getGrandTotal(), 2, '.', '');
    }

    /**
     * Get the shipping amount
     */
    public function getShippingAmount()
    {
        return number_format($this->order->getShippingInclTax(), 2, '.', '');
    }

    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->order->getCustomerId();
    }

    /**
     * Get the order currency code.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->order->getOrderCurrencyCode();
    }

    /**
     * Get all visible items from order
     *
     * @return array
     */
    public function getVisibleOrderItems()
    {
        return $this->order->getAllVisibleItems();
    }

    /**
     * Get coupon code from order
     *
     * @return string
     */
    public function getCouponCode()
    {
        return $this->order->getCouponCode();
    }

    /**
     * Get item string data
     *
     * @return string
     */
    public function getWebGainsItemData()
    {
        $shippingAmount = $this->getShippingAmount();
        $shippingRatio = $shippingAmount / count($items = $this->getVisibleOrderItems());
        $wgItems = '';
        $itemCount = 0;

        foreach ($items as $item) {
            $itemTotal = 0;

            if ($item->getQtyOrdered() > 1) {
                $shippingAmountPerProduct = $shippingRatio / $item->getQtyOrdered();
                for ($i = 1; $i <= $item->getQtyOrdered(); $i++) {
                    $itemTotal = 0;

                    // Event ID
                    $wgItems .= $this->getWebgainsEventId() . '::';

                    // Item Price
                    $itemTotal += $item->getPriceInclTax() - $item->getDiscountAmount()/$item->getQtyOrdered();
                    $itemTotal += $shippingAmountPerProduct;
                    $wgItems .= number_format($itemTotal, 2) . '::';

                    // Name
                    $wgItems .= $item->getName() . '::';

                    // SKU
                    $wgItems .= $item->getSku() . '::';

                    // Coupon code
                    if ($item->getDiscountAmount() > 0) {
                        $wgItems .= $this->getCouponCode() . '::';
                    }

                    // Add pipe
                    if ($i != $item->getQtyOrdered()) {
                        $wgItems .= ' | ';
                    } else {
                        if ($itemCount != count($items) - 1) {
                            $wgItems .= ' | ';
                        }
                    }
                }
            } else {
                // Event ID
                $wgItems .= $this->getWebgainsEventId() . '::';

                // Item Price
                $itemTotal += $item->getPriceInclTax() - $item->getDiscountAmount()/$item->getQtyOrdered();
                $itemTotal += $shippingRatio;
                $wgItems .= number_format($itemTotal, 2) . '::';

                // Name
                $wgItems .= $item->getName() . '::';

                // SKU
                $wgItems .= $item->getSku() . '::';

                // Coupon code
                if ($item->getDiscountAmount() > 0) {
                    $wgItems .= $this->getCouponCode() . '::';
                }

                if ($itemCount == count($item->getQtyOrdered()) - 1) {
                    $wgItems .= ' | ';
                }
            }
            $itemCount++;
        }
        return $wgItems;
    }
}
