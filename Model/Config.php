<?php

declare(strict_types=1);

namespace Inchoo\ProductFaqNotifications\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config extends \Inchoo\ProductFaq\Model\Config
{
    public const PRODUCT_FAQ_NOTIFICATIONS_ACTIVE = "catalog/product_faq/product_faq_notifications_active";
    public const RECEIVER_EMAIL = "catalog/product_faq/receiver_email";
    public const RECEIVER_NAME = "catalog/product_faq/receiver_name";

    /**
     * @var ScopeConfigInterface
     */
    protected $config;

    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @return bool
     */
    public function getProductFaqNotificationsActive(): bool
    {
        return $this->getProductFaqActive() && $this->config->isSetFlag(
            self::PRODUCT_FAQ_NOTIFICATIONS_ACTIVE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string|null
     */
    public function getReceiverEmail(): ?string
    {
        return $this->config->getValue(self::RECEIVER_EMAIL, ScopeInterface::SCOPE_STORES);
    }

    /**
     * @return string|null
     */
    public function getReceiverName(): ?string
    {
        return $this->config->getValue(self::RECEIVER_NAME, ScopeInterface::SCOPE_STORES);
    }
}
