<?php
// src/MugfulMuse/WooCommerceConnectorBundle/Repository/FieldMappingRepository.php
namespace MugfulMuse\WooCommerceConnectorBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Field Mapping Repository
 */
class FieldMappingRepository extends EntityRepository
{
    /**
     * Find active mappings for direction
     *
     * @param string $direction 'push', 'pull' or 'both'
     * @return array
     */
    public function findActiveForDirection($direction)
    {
        $qb = $this->createQueryBuilder('m');
        
        $qb->where('m.isActive = :active')
           ->setParameter('active', true);
        
        if ($direction === 'push') {
            $qb->andWhere('m.direction IN (:directions)')
               ->setParameter('directions', ['both', 'push']);
        } elseif ($direction === 'pull') {
            $qb->andWhere('m.direction IN (:directions)')
               ->setParameter('directions', ['both', 'pull']);
        }
        
        return $qb->getQuery()->getResult();
    }
}