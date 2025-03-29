<?php
// src/MugfulMuse/WooCommerceConnectorBundle/Repository/SyncHistoryRepository.php
namespace MugfulMuse\WooCommerceConnectorBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Sync History Repository
 */
class SyncHistoryRepository extends EntityRepository
{
    /**
     * Find recent sync history entries
     *
     * @param int $limit
     * @return array
     */
    public function findRecent($limit = 5)
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.startedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find history by type
     *
     * @param string $type
     * @param int $limit
     * @return array
     */
    public function findByType($type, $limit = null)
    {
        $qb = $this->createQueryBuilder('h')
            ->where('h.type = :type')
            ->setParameter('type', $type)
            ->orderBy('h.startedAt', 'DESC');
            
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        
        return $qb->getQuery()->getResult();
    }
}