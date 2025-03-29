<?php

namespace MugfulMuse\WooCommerceConnectorBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Akeneo\Platform\Bundle\UIBundle\Form\Type\NavigationMenuType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuExtension extends AbstractTypeExtension
{
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // If you're adding form fields, do it here
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        // Add your WooCommerce menu item
        $view->vars['tabs'][] = [
            'code' => 'woocommerce-tab',
            'label' => 'WooCommerce',
            'isActive' => false,
            'position' => 100, // Adjust position as needed
            'items' => [
                [
                    'code' => 'woocommerce',
                    'label' => 'Dashboard',
                    'route' => 'mugfulmuse_woocommerce_connector_dashboard',
                    'isActive' => false,
                ],
                [
                    'code' => 'woocommerce-mapping',
                    'label' => 'Attribute Mapping',
                    'route' => 'mugfulmuse_woocommerce_connector_mapping_index',
                    'isActive' => false,
                ],
                [
                    'code' => 'woocommerce-history',
                    'label' => 'Sync History',
                    'route' => 'mugfulmuse_woocommerce_connector_history',
                    'isActive' => false,
                ],
            ],
        ];
    }

    /**
     * This is the required method that was missing
     */
    public static function getExtendedTypes(): iterable
    {
        return [NavigationMenuType::class];
    }
}