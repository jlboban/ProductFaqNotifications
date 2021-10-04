<?php

declare(strict_types=1);

namespace Inchoo\ProductFaqNotifications\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

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
     * @return mixed
     */
    public function getProductFaqNotificationsActive()
    {
        return $this->config->getValue(self::PRODUCT_FAQ_NOTIFICATIONS_ACTIVE);
    }

    /**
     * @return mixed
     */
    public function getReceiverEmail()
    {
        return $this->config->getValue(self::RECEIVER_EMAIL);
    }

    /**
     * @return mixed
     */
    public function getReceiverName()
    {
        return $this->config->getValue(self::RECEIVER_NAME);
    }
}
