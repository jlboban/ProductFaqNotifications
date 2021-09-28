<?php

declare(strict_types=1);

namespace Inchoo\ProductFaqNotifications\Observer;

use Inchoo\ProductFaq\Model\ProductFaq;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CreateProductFaqObserver implements ObserverInterface
{
    private const TEMPLATE_IDENTIFIER = 'product_faq_notification';

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $config;

    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * CreateProductFaqObserver constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $config
     * @param StateInterface $inlineTranslation
     */
    public function __construct(
        ProductRepositoryInterface  $productRepository,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface       $storeManager,
        TransportBuilder            $transportBuilder,
        LoggerInterface             $logger,
        ScopeConfigInterface        $config,
        StateInterface              $inlineTranslation
    ) {
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->logger = $logger;
        $this->config = $config;
        $this->inlineTranslation = $inlineTranslation;
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /**
         * @var ProductFaq $productFaq
         */
        $productFaq = $observer->getData('productFaq');

        try {
            $customer = $this->customerRepository->getById($productFaq->getCustomerId());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        try {
            $product = $this->productRepository->getById($productFaq->getProductId());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $sender = [
            'name' => $customer->getFirstname() . " " . $customer->getLastname(),
            'email' => $customer->getEmail()
        ];

        $receiver = $this->config->getValue('trans_email/ident_product_faq/email');

        $templateVars = [
            'question' => $productFaq->getQuestion(),
            'customer_name' => $customer->getFirstname() . " " . $customer->getLastname(),
            'product_name' => $product->getName(),
            'product_url' => $product->getProductUrl()
        ];

        $templateOptions = [
            'area' => Area::AREA_FRONTEND,
            'store' => $this->storeManager->getStore()->getId()
        ];

        $this->inlineTranslation->suspend();
        $transport = $this->transportBuilder
            ->setTemplateIdentifier(self::TEMPLATE_IDENTIFIER)
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($templateVars)
            ->setFrom($sender)
            ->addTo($receiver)
            ->getTransport();

        try {
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
