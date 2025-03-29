<?php
// src/MugfulMuse/WooCommerceConnectorBundle/Controller/WooConnectorController.php
namespace MugfulMuse\WooCommerceConnectorBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;
use MugfulMuse\WooCommerceConnectorBundle\Service\WooCommerceApiClient;
use MugfulMuse\WooCommerceConnectorBundle\Service\SyncService;
use MugfulMuse\WooCommerceConnectorBundle\Service\SettingsManager;

/**
 * WooCommerce Connector Controller
 */
class WooConnectorController
{
    /** @var WooCommerceApiClient */
    private $apiClient;
    
    /** @var SyncService */
    private $syncService;
    
    /** @var SettingsManager */
    private $settingsManager;
    
    /** @var EngineInterface */
    private $templating;
    
    /** @var TranslatorInterface */
    private $translator;
    
    /**
     * Constructor
     *
     * @param WooCommerceApiClient $apiClient
     * @param SyncService $syncService
     * @param SettingsManager $settingsManager
     * @param EngineInterface $templating
     * @param TranslatorInterface $translator
     */
    public function __construct(
        WooCommerceApiClient $apiClient,
        SyncService $syncService,
        SettingsManager $settingsManager,
        EngineInterface $templating,
        TranslatorInterface $translator
    ) {
        $this->apiClient = $apiClient;
        $this->syncService = $syncService;
        $this->settingsManager = $settingsManager;
        $this->templating = $templating;
        $this->translator = $translator;
    }
    
    /**
     * Dashboard action
     *
     * @return Response
     */
    public function dashboardAction()
    {
        $settings = $this->settingsManager->getConnectionSettings();
        
        return $this->templating->renderResponse(
            'MugfulMuseWooCommerceConnectorBundle::dashboard.html.twig',
            [
                'settings' => $settings
            ]
        );
    }
    
    /**
     * Test connection action
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testConnectionAction(Request $request)
    {
        if ($request->isMethod('POST')) {
            $storeUrl = $request->request->get('store_url');
            $consumerKey = $request->request->get('consumer_key');
            $consumerSecret = $request->request->get('consumer_secret');
            
            $this->apiClient->setConnectionParams($storeUrl, $consumerKey, $consumerSecret);
        } else {
            $settings = $this->settingsManager->getConnectionSettings();
            
            $this->apiClient->setConnectionParams(
                $settings['store_url'],
                $settings['consumer_key'],
                $settings['consumer_secret']
            );
        }
        
        $success = $this->apiClient->testConnection();
        
        return new JsonResponse([
            'success' => $success,
            'message' => $success 
                ? $this->translator->trans('mugfulmuse_woocommerce.connection.success')
                : $this->translator->trans('mugfulmuse_woocommerce.connection.error')
        ]);
    }
    
    /**
     * Save settings action
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveSettingsAction(Request $request)
    {
        $storeUrl = $request->request->get('store_url');
        $consumerKey = $request->request->get('consumer_key');
        $consumerSecret = $request->request->get('consumer_secret');
        
        try {
            $this->settingsManager->saveConnectionSettings(
                $storeUrl, 
                $consumerKey, 
                $consumerSecret
            );
            
            return new JsonResponse([
                'success' => true,
                'message' => $this->translator->trans('mugfulmuse_woocommerce.settings.success')
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->translator->trans('mugfulmuse_woocommerce.settings.error', [
                    '%error%' => $e->getMessage()
                ])
            ], 400);
        }
    }
    
    /**
     * Push to WooCommerce action
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function pushAction(Request $request)
    {
        $filters = $request->request->get('filters', []);
        $username = $this->getUsername();
        
        try {
            $syncHistory = $this->syncService->pushToWooCommerce($filters, $username);
            
            return new JsonResponse([
                'success' => true,
                'message' => $this->translator->trans('mugfulmuse_woocommerce.push.success', [
                    '%count%' => $syncHistory->getSuccessCount(),
                    '%total%' => $syncHistory->getTotalProducts()
                ]),
                'syncId' => $syncHistory->getId()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->translator->trans('mugfulmuse_woocommerce.push.error', [
                    '%error%' => $e->getMessage()
                ])
            ], 400);
        }
    }
    
    /**
     * Pull from WooCommerce action
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function pullAction(Request $request)
    {
        $filters = $request->request->get('filters', []);
        $username = $this->getUsername();
        
        try {
            $syncHistory = $this->syncService->pullFromWooCommerce($filters, $username);
            
            return new JsonResponse([
                'success' => true,
                'message' => $this->translator->trans('mugfulmuse_woocommerce.pull.success', [
                    '%count%' => $syncHistory->getSuccessCount(),
                    '%total%' => $syncHistory->getTotalProducts()
                ]),
                'syncId' => $syncHistory->getId()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $this->translator->trans('mugfulmuse_woocommerce.pull.error', [
                    '%error%' => $e->getMessage()
                ])
            ], 400);
        }
    }
    
    /**
     * Get current username
     *
     * @return string
     */
    private function getUsername()
    {
        // In Akeneo 5.0, we should use the TokenStorage service
        $user = $this->getUser();
        
        if ($user) {
            return $user->getUsername();
        }
        
        return 'anonymous';
    }
    
    /**
     * Get the current user
     * 
     * @return \Akeneo\UserManagement\Component\Model\UserInterface|null
     */
    private function getUser()
    {
        // For simplicity, return null
        // In a real implementation, inject the token storage service
        return null;
    }
}