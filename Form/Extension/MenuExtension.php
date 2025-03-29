<?php
// src/MugfulMuse/WooCommerceConnectorBundle/Form/Extension/MenuExtension.php
namespace MugfulMuse\WooCommerceConnectorBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

/**
 * Menu Extension to add WooCommerce to Akeneo menu
 */
class MenuExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            
            if (!isset($data['system']) || !is_array($data['system'])) {
                $data['system'] = [];
            }
            
            $data['system']['woocommerce'] = [
                'route' => 'mugfulmuse_woocommerce_connector_dashboard',
                'label' => 'mugfulmuse_woocommerce.menu.woocommerce',
                'icon'  => 'icon-cart',
                'position' => 70,
            ];
            
            $event->setData($data);
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return ['pim_menu'];
    }
}