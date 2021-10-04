<?php

declare(strict_types=1);

namespace Inchoo\ProductFaqNotifications\Observer;

use Inchoo\ProductFaq\Model\ProductFaq;
use Inchoo\ProductFaqNotifications\Model\Config;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ProductFaqNotificationObserver implements ObserverInterface
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
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var Config
     */
    protected $config;

    /**
     * CreateProductFaqObserver constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param LoggerInterface $logger
     * @param StateInterface $inlineTranslation
     * @param Config $config
     */
    public function __construct(
        ProductRepositoryInterface  $productRepository,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface       $storeManager,
        TransportBuilder            $transportBuilder,
        LoggerInterface             $logger,
        StateInterface              $inlineTranslation,
        Config                      $config
    ) {
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->logger = $logger;
        $this->inlineTranslation = $inlineTranslation;
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->config->getProductFaqNotificationsActive()) {
            return;
        }

        /**
         * @var ProductFaq $productFaq
         */
        $productFaq = $observer->getData('productFaq');

        try {
            $customer = $this->customerRepository->getById($productFaq->getCustomerId());
            $product = $this->productRepository->getById($productFaq->getProductId());
        } catch (\Exception $e) {
            $this->logger->error("Problem with Faq send email, ID: '{$productFaq->getId()}'", [
                'message' => $e->getMessage()
            ]);

            return;
        }

        $customerName = $customer->getFirstname() . " " . $customer->getLastname();

        $sender = [
            'name' => $customerName,
            'email' => $customer->getEmail()
        ];

        $receiver = $this->config->getReceiverEmail();

        if (!$receiver) {
            $this->logger->error("Receiver email is not set in the administration.");
            return;
        }

        $templateVars = [
            'question' => $productFaq->getQuestion(),
            'customer_name' => $customerName,
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
            $this->logger->critical('An issue occurred with sending a new product FAQ notification.', [
                'transport' => $transport,
                'customer' => $customer,
                'product' => $product,
                'productFaq' => $productFaq,
                'exception' => $e->getMessage()
            ]);
        }
    }
}
