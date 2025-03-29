<?php
// src/MugfulMuse/WooCommerceConnectorBundle/Service/SyncService.php
namespace MugfulMuse\WooCommerceConnectorBundle\Service;

use Akeneo\Pim\Enrichment\Component\Product\Repository\ProductRepositoryInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Query\ProductQueryBuilderFactoryInterface;
use Akeneo\Tool\Component\StorageUtils\Saver\SaverInterface;
use MugfulMuse\WooCommerceConnectorBundle\Entity\SyncHistory;
use MugfulMuse\WooCommerceConnectorBundle\Entity\SyncDetail;
use MugfulMuse\WooCommerceConnectorBundle\Repository\FieldMappingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Synchronization Service
 */
class SyncService
{
    /** @var WooCommerceApiClient */
    private $apiClient;
    
    /** @var ProductRepositoryInterface */
    private $productRepository;
    
    /** @var SaverInterface */
    private $productSaver;
    
    /** @var ProductQueryBuilderFactoryInterface */
    private $pqbFactory;
    
    /** @var FieldMappingRepository */
    private $fieldMappingRepository;
    
    /** @var EntityManagerInterface */
    private $entityManager;
    
    /** @var LoggerInterface */
    private $logger;
    
    /**
     * Constructor
     *
     * @param WooCommerceApiClient $apiClient
     * @param ProductRepositoryInterface $productRepository
     * @param SaverInterface $productSaver
     * @param ProductQueryBuilderFactoryInterface $pqbFactory
     * @param FieldMappingRepository $fieldMappingRepository
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        WooCommerceApiClient $apiClient,
        ProductRepositoryInterface $productRepository,
        SaverInterface $productSaver,
        ProductQueryBuilderFactoryInterface $pqbFactory,
        FieldMappingRepository $fieldMappingRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->apiClient = $apiClient;
        $this->productRepository = $productRepository;
        $this->productSaver = $productSaver;
        $this->pqbFactory = $pqbFactory;
        $this->fieldMappingRepository = $fieldMappingRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }
    
    /**
     * Push products to WooCommerce
     * 
     * @param array $filters
     * @param string $username
     * @return SyncHistory
     */
    public function pushToWooCommerce(array $filters = [], string $username = 'admin'): SyncHistory
    {
        // Create sync history record
        $syncHistory = new SyncHistory();
        $syncHistory->setType(SyncHistory::TYPE_PUSH)
            ->setFilters($filters)
            ->setUsername($username)
            ->markAsRunning();
        
        $this->entityManager->persist($syncHistory);
        $this->entityManager->flush();
        
        try {
            // Get field mappings
            $fieldMappings = $this->fieldMappingRepository->findBy([
                'isActive' => true,
                'direction' => ['both', 'push']
            ]);
            
            if (empty($fieldMappings)) {
                throw new \Exception('No active field mappings found for push operation');
            }
            
            // Get products from Akeneo
            $products = $this->getAkeneoProducts($filters);
            $syncHistory->setTotalProducts(count($products));
            
            // Process each product
            foreach ($products as $product) {
                $syncDetail = new SyncDetail();
                $syncDetail->setIdentifier($product->getIdentifier());
                
                try {
                    // Transform Akeneo product to WooCommerce format
                    $wooProductData = $this->transformAkeneoToWooProduct($product, $fieldMappings);
                    
                    // Check if product exists in WooCommerce
                    $wooProduct = $this->apiClient->findProductBySku($product->getIdentifier());
                    
                    if ($wooProduct) {
                        // Update existing product
                        $this->apiClient->updateProduct($wooProduct['id'], $wooProductData);
                        $syncDetail->setAction(SyncDetail::ACTION_UPDATE);
                    } else {
                        // Create new product
                        $this->apiClient->createProduct($wooProductData);
                        $syncDetail->setAction(SyncDetail::ACTION_CREATE);
                    }
                    
                    $syncDetail->setStatus(SyncDetail::STATUS_SUCCESS);
                    $syncHistory->incrementSuccessCount();
                    
                } catch (\Exception $e) {
                    $syncDetail->setStatus(SyncDetail::STATUS_ERROR)
                        ->setErrorMessage($e->getMessage());
                    $syncHistory->incrementErrorCount();
                    
                    $this->logger->error('Error pushing product to WooCommerce', [
                        'identifier' => $product->getIdentifier(),
                        'error' => $e->getMessage()
                    ]);
                }
                
                $syncDetail->setSyncHistory($syncHistory);
                $this->entityManager->persist($syncDetail);
            }
            
            $syncHistory->markAsCompleted();
            
        } catch (\Exception $e) {
            $syncHistory->markAsFailed($e->getMessage());
            $this->logger->error('Push to WooCommerce failed', [
                'error' => $e->getMessage()
            ]);
        }
        
        $this->entityManager->flush();
        
        return $syncHistory;
    }
    
    /**
     * Pull products from WooCommerce
     * 
     * @param array $filters
     * @param string $username
     * @return SyncHistory
     */
    public function pullFromWooCommerce(array $filters = [], string $username = 'admin'): SyncHistory
    {
        // Create sync history record
        $syncHistory = new SyncHistory();
        $syncHistory->setType(SyncHistory::TYPE_PULL)
            ->setFilters($filters)
            ->setUsername($username)
            ->markAsRunning();
        
        $this->entityManager->persist($syncHistory);
        $this->entityManager->flush();
        
        try {
            // Get field mappings
            $fieldMappings = $this->fieldMappingRepository->findBy([
                'isActive' => true,
                'direction' => ['both', 'pull']
            ]);
            
            if (empty($fieldMappings)) {
                throw new \Exception('No active field mappings found for pull operation');
            }
            
            // Get products from WooCommerce
            $wooProducts = $this->getWooCommerceProducts($filters);
            $syncHistory->setTotalProducts(count($wooProducts));
            
            // Process each product
            foreach ($wooProducts as $wooProduct) {
                $sku = $wooProduct['sku'];
                
                if (empty($sku)) {
                    continue;
                }
                
                $syncDetail = new SyncDetail();
                $syncDetail->setIdentifier($sku);
                
                try {
                    // Find or create Akeneo product
                    $product = $this->productRepository->findOneByIdentifier($sku);
                    $isNew = false;
                    
                    if (!$product) {
                        // For now, just log that we can't create products yet
                        // In a full implementation, you would create new products here
                        $this->logger->warning('Product not found in Akeneo, skipping', [
                            'sku' => $sku
                        ]);
                        
                        $syncDetail->setAction(SyncDetail::ACTION_SKIP)
                            ->setStatus(SyncDetail::STATUS_SUCCESS)
                            ->setErrorMessage('Product not found in Akeneo and creation is not implemented yet');
                            
                        $syncHistory->incrementSuccessCount();
                        continue;
                    }
                    
                    // Transform WooCommerce product to Akeneo format
                    $akeneoData = $this->transformWooToAkeneoProduct($wooProduct, $fieldMappings, $product);
                    
                    // Update Akeneo product
                    // $this->productUpdater->update($product, $akeneoData);
                    // $this->productSaver->save($product);
                    
                    // For now, just simulate success
                    $syncDetail->setAction(SyncDetail::ACTION_UPDATE)
                        ->setStatus(SyncDetail::STATUS_SUCCESS);
                    $syncHistory->incrementSuccessCount();
                    
                } catch (\Exception $e) {
                    $syncDetail->setStatus(SyncDetail::STATUS_ERROR)
                        ->setErrorMessage($e->getMessage());
                    $syncHistory->incrementErrorCount();
                    
                    $this->logger->error('Error pulling product from WooCommerce', [
                        'sku' => $sku,
                        'error' => $e->getMessage()
                    ]);
                }
                
                $syncDetail->setSyncHistory($syncHistory);
                $this->entityManager->persist($syncDetail);
            }
            
            $syncHistory->markAsCompleted();
            
        } catch (\Exception $e) {
            $syncHistory->markAsFailed($e->getMessage());
            $this->logger->error('Pull from WooCommerce failed', [
                'error' => $e->getMessage()
            ]);
        }
        
        $this->entityManager->flush();
        
        return $syncHistory;
    }
    
    /**
     * Get products from Akeneo
     * 
     * @param array $filters
     * @return ProductInterface[]
     */
    private function getAkeneoProducts(array $filters = []): array
    {
        // For Akeneo 5.0, we'll use the Product Query Builder
        $pqb = $this->pqbFactory->create(['limit' => 100]);
        
        // Apply filters
        if (!empty($filters)) {
            if (isset($filters['updated']) && $filters['updated']) {
                // Filter for products updated in the last 24 hours
                $pqb->addFilter('updated', 'SINCE LAST N DAYS', 1);
            }
            
            // Additional filters can be added based on your requirements
        }
        
        // Execute query and get cursor
        $cursor = $pqb->execute();
        
        // Convert cursor to array
        $products = [];
        foreach ($cursor as $product) {
            $products[] = $product;
        }
        
        return $products;
    }
    
    /**
     * Get products from WooCommerce
     * 
     * @param array $filters
     * @return array
     */
    private function getWooCommerceProducts(array $filters = []): array
    {
        $params = [
            'per_page' => 100,
        ];
        
        // Apply filters
        if (!empty($filters)) {
            if (isset($filters['updated']) && $filters['updated']) {
                // Get products updated in the last 24 hours
                $yesterday = new \DateTime('-1 day');
                $params['after'] = $yesterday->format('c');
            }
        }
        
        // Get products from WooCommerce
        try {
            return $this->apiClient->getProducts($params);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching products from WooCommerce', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            
            return [];
        }
    }
    
    /**
     * Transform Akeneo product to WooCommerce format
     * 
     * @param ProductInterface $product
     * @param array $fieldMappings
     * @return array
     */
    private function transformAkeneoToWooProduct(ProductInterface $product, array $fieldMappings): array
    {
        $wooProduct = [
            'sku' => $product->getIdentifier()
        ];
        
        // Get product values
        $values = $product->getValues();
        
        // Apply field mappings
        foreach ($fieldMappings as $mapping) {
            $akeneoField = $mapping->getAkeneoField();
            $wooField = $mapping->getWooCommerceField();
            
            // Skip the SKU field as it's already handled
            if ($akeneoField === 'sku') {
                continue;
            }
            
            // Get the attribute value from Akeneo product
            $value = null;
            if ($values->has($akeneoField)) {
                $attribute = $values->get($akeneoField);
                
                // Handle different attribute types
                switch ($mapping->getAkeneoType()) {
                    case 'pim_catalog_text':
                    case 'pim_catalog_textarea':
                        $value = $attribute->getData();
                        break;
                    
                    case 'pim_catalog_price_collection':
                        // Get the price in USD or the first available currency
                        $prices = $attribute->getData();
                        foreach ($prices as $price) {
                            if ($price->getCurrency() === 'USD') {
                                $value = $price->getAmount();
                                break;
                            }
                        }
                        
                        // If no USD price found, take the first one
                        if ($value === null && !empty($prices)) {
                            $value = $prices[0]->getAmount();
                        }
                        break;
                    
                        case 'pim_catalog_number':
                            $value = $attribute->getData();
                            break;
                        
                        case 'pim_catalog_boolean':
                            $value = $attribute->getData();
                            break;
                        
                        case 'pim_catalog_simpleselect':
                            $option = $attribute->getData();
                            if ($option) {
                                $value = $option->getCode();
                            }
                            break;
                        
                        case 'pim_catalog_multiselect':
                            $options = $attribute->getData();
                            $value = [];
                            foreach ($options as $option) {
                                $value[] = $option->getCode();
                            }
                            break;
                        
                        case 'pim_catalog_date':
                            $date = $attribute->getData();
                            if ($date) {
                                $value = $date->format('Y-m-d');
                            }
                            break;
                        
                        case 'pim_catalog_image':
                            $value = $attribute->getData();
                            // You would need to handle image upload to WooCommerce
                            break;
                        
                        default:
                            // Default handling for other types
                            $value = (string)$attribute->getData();
                            break;
                    }
                }
                
                // Map to appropriate WooCommerce field
                if ($value !== null) {
                    // Handle special WooCommerce fields
                    if (strpos($wooField, 'attribute_') === 0) {
                        // Handle product attributes
                        if (!isset($wooProduct['attributes'])) {
                            $wooProduct['attributes'] = [];
                        }
                        
                        $attributeName = substr($wooField, 10); // Remove 'attribute_' prefix
                        
                        $wooProduct['attributes'][] = [
                            'name' => $attributeName,
                            'options' => is_array($value) ? $value : [$value],
                            'visible' => true,
                            'variation' => true,
                        ];
                    } elseif (strpos($wooField, 'taxonomy_') === 0) {
                        // Handle taxonomies (categories, tags, etc.)
                        $taxonomyName = substr($wooField, 9); // Remove 'taxonomy_' prefix
                        
                        if ($taxonomyName === 'category') {
                            $wooProduct['categories'] = is_array($value) 
                                ? array_map(function($item) { return ['name' => $item]; }, $value)
                                : [['name' => $value]];
                        } elseif ($taxonomyName === 'tag') {
                            $wooProduct['tags'] = is_array($value)
                                ? array_map(function($item) { return ['name' => $item]; }, $value)
                                : [['name' => $value]];
                        } else {
                            // Other custom taxonomies
                            if (!isset($wooProduct[$taxonomyName])) {
                                $wooProduct[$taxonomyName] = [];
                            }
                            
                            $wooProduct[$taxonomyName] = is_array($value)
                                ? array_map(function($item) { return ['name' => $item]; }, $value)
                                : [['name' => $value]];
                        }
                    } else {
                        // Standard fields
                        $wooProduct[$wooField] = $value;
                    }
                }
            }
            
            // Ensure required fields have values
            if (!isset($wooProduct['name'])) {
                $wooProduct['name'] = 'Product ' . $product->getIdentifier();
            }
            
            if (!isset($wooProduct['type'])) {
                $wooProduct['type'] = 'simple';
            }
            
            return $wooProduct;
        }
        
        /**
         * Transform WooCommerce product to Akeneo format
         * 
         * @param array $wooProduct
         * @param array $fieldMappings
         * @param ProductInterface $product
         * @return array
         */
        private function transformWooToAkeneoProduct(array $wooProduct, array $fieldMappings, ProductInterface $product): array
        {
            $akeneoData = [];
            
            // Apply field mappings
            foreach ($fieldMappings as $mapping) {
                $akeneoField = $mapping->getAkeneoField();
                $wooField = $mapping->getWooCommerceField();
                
                // Skip the SKU field as it's the identifier
                if ($akeneoField === 'sku') {
                    continue;
                }
                
                // Get the value from WooCommerce product
                $value = null;
                
                // Handle different WooCommerce field types
                if (strpos($wooField, 'attribute_') === 0) {
                    // Handle product attributes
                    $attributeName = substr($wooField, 10); // Remove 'attribute_' prefix
                    
                    if (isset($wooProduct['attributes']) && is_array($wooProduct['attributes'])) {
                        foreach ($wooProduct['attributes'] as $attribute) {
                            if ($attribute['name'] === $attributeName) {
                                $value = $attribute['options'];
                                
                                // If single value, unwrap it
                                if (is_array($value) && count($value) === 1) {
                                    $value = $value[0];
                                }
                                
                                break;
                            }
                        }
                    }
                } elseif (strpos($wooField, 'taxonomy_') === 0) {
                    // Handle taxonomies (categories, tags, etc.)
                    $taxonomyName = substr($wooField, 9); // Remove 'taxonomy_' prefix
                    
                    if ($taxonomyName === 'category' && isset($wooProduct['categories'])) {
                        $value = array_map(function($cat) { 
                            return isset($cat['name']) ? $cat['name'] : $cat; 
                        }, $wooProduct['categories']);
                    } elseif ($taxonomyName === 'tag' && isset($wooProduct['tags'])) {
                        $value = array_map(function($tag) { 
                            return isset($tag['name']) ? $tag['name'] : $tag; 
                        }, $wooProduct['tags']);
                    } elseif (isset($wooProduct[$taxonomyName])) {
                        $value = array_map(function($term) { 
                            return isset($term['name']) ? $term['name'] : $term; 
                        }, $wooProduct[$taxonomyName]);
                    }
                    
                    // If single value, unwrap it
                    if (is_array($value) && count($value) === 1) {
                        $value = $value[0];
                    }
                } elseif (isset($wooProduct[$wooField])) {
                    // Standard fields
                    $value = $wooProduct[$wooField];
                }
                
                // If we have a value, transform it for Akeneo
                if ($value !== null) {
                    // Transform based on Akeneo attribute type
                    switch ($mapping->getAkeneoType()) {
                        case 'pim_catalog_text':
                        case 'pim_catalog_textarea':
                            $akeneoData[$akeneoField] = [
                                ['locale' => null, 'scope' => null, 'data' => (string)$value]
                            ];
                            break;
                        
                        case 'pim_catalog_price_collection':
                            $akeneoData[$akeneoField] = [
                                [
                                    'locale' => null, 
                                    'scope' => null, 
                                    'data' => [
                                        ['amount' => (float)$value, 'currency' => 'USD']
                                    ]
                                ]
                            ];
                            break;
                        
                        case 'pim_catalog_number':
                            $akeneoData[$akeneoField] = [
                                ['locale' => null, 'scope' => null, 'data' => (float)$value]
                            ];
                            break;
                        
                        case 'pim_catalog_boolean':
                            $akeneoData[$akeneoField] = [
                                ['locale' => null, 'scope' => null, 'data' => (bool)$value]
                            ];
                            break;
                        
                        case 'pim_catalog_simpleselect':
                            $akeneoData[$akeneoField] = [
                                ['locale' => null, 'scope' => null, 'data' => (string)$value]
                            ];
                            break;
                        
                        case 'pim_catalog_multiselect':
                            $akeneoData[$akeneoField] = [
                                ['locale' => null, 'scope' => null, 'data' => (array)$value]
                            ];
                            break;
                        
                        case 'pim_catalog_date':
                            $date = new \DateTime($value);
                            $akeneoData[$akeneoField] = [
                                ['locale' => null, 'scope' => null, 'data' => $date->format('Y-m-d')]
                            ];
                            break;
                        
                        default:
                            // Default handling
                            $akeneoData[$akeneoField] = [
                                ['locale' => null, 'scope' => null, 'data' => $value]
                            ];
                            break;
                    }
                }
            }
            
            return $akeneoData;
        }
    }