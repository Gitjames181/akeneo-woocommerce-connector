<?php
// src/MugfulMuse/WooCommerceConnectorBundle/Service/SettingsManager.php
namespace MugfulMuse\WooCommerceConnectorBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use MugfulMuse\WooCommerceConnectorBundle\Entity\Setting;
use Psr\Log\LoggerInterface;

/**
 * Manages connector settings
 */
class SettingsManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    
    /** @var LoggerInterface */
    private $logger;
    
    /**
     * Constructor
     *
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }
    
    /**
     * Get a setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $repository = $this->entityManager->getRepository(Setting::class);
        $setting = $repository->findOneBy(['key' => $key]);
        
        if (!$setting) {
            return $default;
        }
        
        return $setting->getValue();
    }
    
    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $repository = $this->entityManager->getRepository(Setting::class);
        $setting = $repository->findOneBy(['key' => $key]);
        
        if (!$setting) {
            $setting = new Setting();
            $setting->setKey($key);
        }
        
        $setting->setValue($value);
        
        $this->entityManager->persist($setting);
        $this->entityManager->flush();
    }
    
    /**
     * Get all connection settings
     *
     * @return array
     */
    public function getConnectionSettings(): array
    {
        return [
            'store_url' => $this->get('store_url'),
            'consumer_key' => $this->get('consumer_key'),
            'consumer_secret' => $this->get('consumer_secret'),
        ];
    }
    
    /**
     * Save connection settings
     *
     * @param string $storeUrl
     * @param string $consumerKey
     * @param string $consumerSecret
     * @return void
     */
    public function saveConnectionSettings(string $storeUrl, string $consumerKey, string $consumerSecret): void
    {
        $this->set('store_url', $storeUrl);
        $this->set('consumer_key', $consumerKey);
        $this->set('consumer_secret', $consumerSecret);
    }
}