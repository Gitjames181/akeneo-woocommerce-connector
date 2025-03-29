<?php
// src/MugfulMuse/WooCommerceConnectorBundle/Service/WooCommerceApiClient.php
namespace MugfulMuse\WooCommerceConnectorBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * WooCommerce API Client Service
 */
class WooCommerceApiClient
{
    /** @var string */
    private $storeUrl;

    /** @var string */
    private $consumerKey;

    /** @var string */
    private $consumerSecret;

    /** @var Client */
    private $httpClient;

    /** @var LoggerInterface */
    private $logger;

    /**
     * Constructor
     *
     * @param string $storeUrl
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param LoggerInterface $logger
     */
    public function __construct(string $storeUrl = null, string $consumerKey = null, string $consumerSecret = null, LoggerInterface $logger)
    {
        $this->storeUrl = $storeUrl ? rtrim($storeUrl, '/') : null;
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->logger = $logger;
        $this->httpClient = new Client([
            'timeout' => 30,
        ]);
    }

    /**
     * Set connection parameters
     * 
     * @param string $storeUrl
     * @param string $consumerKey
     * @param string $consumerSecret
     */
    public function setConnectionParams(string $storeUrl, string $consumerKey, string $consumerSecret)
    {
        $this->storeUrl = rtrim($storeUrl, '/');
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
    }

    /**
     * Get products from WooCommerce
     *
     * @param array $params
     * @return array
     */
    public function getProducts(array $params = []): array
    {
        return $this->get('/wp-json/wc/v3/products', $params);
    }

    /**
     * Get a specific product by ID
     *
     * @param int $productId
     * @return array|null
     */
    public function getProduct(int $productId): ?array
    {
        try {
            return $this->get('/wp-json/wc/v3/products/' . $productId);
        } catch (\Exception $e) {
            if ($e->getCode() === Response::HTTP_NOT_FOUND) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Find a product by SKU
     *
     * @param string $sku
     * @return array|null
     */
    public function findProductBySku(string $sku): ?array
    {
        $products = $this->get('/wp-json/wc/v3/products', ['sku' => $sku]);
        
        if (empty($products)) {
            return null;
        }
        
        return $products[0];
    }

    /**
     * Create a product in WooCommerce
     *
     * @param array $productData
     * @return array
     */
    public function createProduct(array $productData): array
    {
        return $this->post('/wp-json/wc/v3/products', $productData);
    }

    /**
     * Update a product in WooCommerce
     *
     * @param int $productId
     * @param array $productData
     * @return array
     */
    public function updateProduct(int $productId, array $productData): array
    {
        return $this->put('/wp-json/wc/v3/products/' . $productId, $productData);
    }

    /**
     * Get product categories
     *
     * @param array $params
     * @return array
     */
    public function getCategories(array $params = []): array
    {
        return $this->get('/wp-json/wc/v3/products/categories', $params);
    }

    /**
     * Get product attributes
     *
     * @param array $params
     * @return array
     */
    public function getAttributes(array $params = []): array
    {
        return $this->get('/wp-json/wc/v3/products/attributes', $params);
    }

    /**
     * Test the connection to WooCommerce API
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            if (!$this->storeUrl || !$this->consumerKey || !$this->consumerSecret) {
                return false;
            }
            
            $this->get('/wp-json/wc/v3/system_status');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('WooCommerce connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get API information
     *
     * @return array
     */
    public function getApiInfo(): array
    {
        return $this->get('/wp-json');
    }

    /**
     * Perform a GET request
     *
     * @param string $endpoint
     * @param array $params
     * @return array
     */
    private function get(string $endpoint, array $params = []): array
    {
        try {
            $url = $this->storeUrl . $endpoint;
            $this->logger->debug('GET request to WooCommerce API: ' . $url);
            
            $response = $this->httpClient->get($url, [
                'auth' => [$this->consumerKey, $this->consumerSecret],
                'query' => $params,
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }
    }

    /**
     * Perform a POST request
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    private function post(string $endpoint, array $data): array
    {
        try {
            $url = $this->storeUrl . $endpoint;
            $this->logger->debug('POST request to WooCommerce API: ' . $url);
            
            $response = $this->httpClient->post($url, [
                'auth' => [$this->consumerKey, $this->consumerSecret],
                'json' => $data,
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }
    }

    /**
     * Perform a PUT request
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    private function put(string $endpoint, array $data): array
    {
        try {
            $url = $this->storeUrl . $endpoint;
            $this->logger->debug('PUT request to WooCommerce API: ' . $url);
            
            $response = $this->httpClient->put($url, [
                'auth' => [$this->consumerKey, $this->consumerSecret],
                'json' => $data,
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }
    }

    /**
     * Handle request exceptions
     *
     * @param RequestException $e
     * @throws \Exception
     */
    private function handleRequestException(RequestException $e): void
    {
        $this->logger->error('WooCommerce API error: ' . $e->getMessage());
        
        $response = $e->getResponse();
        if (null !== $response) {
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            
            $this->logger->error('Response status: ' . $statusCode);
            $this->logger->error('Response body: ' . $body);
            
            $errorData = json_decode($body, true);
            
            if (isset($errorData['message'])) {
                throw new \Exception('WooCommerce API error: ' . $errorData['message'], $statusCode);
            }
        }
        
        throw $e;
    }

    /**
     * Discover available fields in WooCommerce
     *
     * @return array
     */
    public function discoverFields(): array
    {
        $fields = [
            'standard' => [
                'id' => ['type' => 'integer', 'readonly' => true],
                'name' => ['type' => 'string'],
                'slug' => ['type' => 'string'],
                'permalink' => ['type' => 'string', 'readonly' => true],
                'date_created' => ['type' => 'date-time', 'readonly' => true],
                'date_modified' => ['type' => 'date-time', 'readonly' => true],
                'type' => ['type' => 'string'],
                'status' => ['type' => 'string'],
                'featured' => ['type' => 'boolean'],
                'catalog_visibility' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'short_description' => ['type' => 'string'],
                'sku' => ['type' => 'string'],
                'price' => ['type' => 'string', 'readonly' => true],
                'regular_price' => ['type' => 'string'],
                'sale_price' => ['type' => 'string'],
                'date_on_sale_from' => ['type' => 'date-time'],
                'date_on_sale_to' => ['type' => 'date-time'],
                'on_sale' => ['type' => 'boolean', 'readonly' => true],
                'purchasable' => ['type' => 'boolean', 'readonly' => true],
                'total_sales' => ['type' => 'integer', 'readonly' => true],
                'virtual' => ['type' => 'boolean'],
                'downloadable' => ['type' => 'boolean'],
                'downloads' => ['type' => 'array'],
                'download_limit' => ['type' => 'integer'],
                'download_expiry' => ['type' => 'integer'],
                'tax_status' => ['type' => 'string'],
                'tax_class' => ['type' => 'string'],
                'manage_stock' => ['type' => 'boolean'],
                'stock_quantity' => ['type' => 'integer'],
                'stock_status' => ['type' => 'string'],
                'backorders' => ['type' => 'string'],
                'backorders_allowed' => ['type' => 'boolean', 'readonly' => true],
                'backordered' => ['type' => 'boolean', 'readonly' => true],
                'sold_individually' => ['type' => 'boolean'],
                'weight' => ['type' => 'string'],
                'dimensions' => ['type' => 'object'],
                'shipping_required' => ['type' => 'boolean', 'readonly' => true],
                'shipping_taxable' => ['type' => 'boolean', 'readonly' => true],
                'shipping_class' => ['type' => 'string'],
                'shipping_class_id' => ['type' => 'integer', 'readonly' => true],
                'reviews_allowed' => ['type' => 'boolean'],
                'average_rating' => ['type' => 'string', 'readonly' => true],
                'rating_count' => ['type' => 'integer', 'readonly' => true],
                'related_ids' => ['type' => 'array', 'readonly' => true],
                'upsell_ids' => ['type' => 'array'],
                'cross_sell_ids' => ['type' => 'array'],
                'parent_id' => ['type' => 'integer'],
                'purchase_note' => ['type' => 'string'],
                'categories' => ['type' => 'array'],
                'tags' => ['type' => 'array'],
                'images' => ['type' => 'array'],
                'attributes' => ['type' => 'array'],
                'default_attributes' => ['type' => 'array'],
                'variations' => ['type' => 'array', 'readonly' => true],
                'grouped_products' => ['type' => 'array'],
                'menu_order' => ['type' => 'integer'],
                'meta_data' => ['type' => 'array'],
            ],
            'attributes' => [],
            'taxonomies' => [],
            'meta_fields' => []
        ];
        
        // Discover product attributes
        try {
            $attributes = $this->getAttributes();
            foreach ($attributes as $attribute) {
                $fields['attributes'][$attribute['id']] = [
                    'name' => $attribute['name'],
                    'slug' => $attribute['slug'],
                    'type' => 'attribute',
                    'options' => $this->getAttributeTerms($attribute['id'])
                ];
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to discover WooCommerce attributes: ' . $e->getMessage());
        }
        
        // Discover taxonomies
        try {
            $categories = $this->getCategories(['per_page' => 100]);
            $fields['taxonomies']['categories'] = [
                'name' => 'Categories',
                'type' => 'taxonomy',
                'terms' => array_map(function ($category) {
                    return [
                        'id' => $category['id'],
                        'name' => $category['name'],
                        'slug' => $category['slug'],
                    ];
                }, $categories)
            ];
            
            // Get tags
            $tags = $this->get('/wp-json/wc/v3/products/tags', ['per_page' => 100]);
            $fields['taxonomies']['tags'] = [
                'name' => 'Tags',
                'type' => 'taxonomy',
                'terms' => array_map(function ($tag) {
                    return [
                        'id' => $tag['id'],
                        'name' => $tag['name'],
                        'slug' => $tag['slug'],
                    ];
                }, $tags)
            ];
        } catch (\Exception $e) {
            $this->logger->warning('Failed to discover WooCommerce taxonomies: ' . $e->getMessage());
        }
        
        return $fields;
    }

    /**
     * Get attribute terms
     *
     * @param int $attributeId
     * @return array
     */
    public function getAttributeTerms(int $attributeId): array
    {
        $terms = $this->get('/wp-json/wc/v3/products/attributes/' . $attributeId . '/terms', ['per_page' => 100]);
        
        return array_map(function ($term) {
            return [
                'id' => $term['id'],
                'name' => $term['name'],
                'slug' => $term['slug'],
            ];
        }, $terms);
    }
}