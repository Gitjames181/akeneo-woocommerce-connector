<?php
// src/MugfulMuse/WooCommerceConnectorBundle/Entity/SyncHistory.php
namespace MugfulMuse\WooCommerceConnectorBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass="MugfulMuse\WooCommerceConnectorBundle\Repository\SyncHistoryRepository")
 * @ORM\Table(name="mugfulmuse_woo_sync_history")
 */
class SyncHistory
{
    const TYPE_PUSH = 'push';
    const TYPE_PULL = 'pull';
    
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @ORM\Column(type="string", length=10)
     */
    private $type;
    
    /**
     * @ORM\Column(type="string", length=20)
     */
    private $status;
    
    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $filters = [];
    
    /**
     * @ORM\Column(type="datetime")
     */
    private $startedAt;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $completedAt;
    
    /**
     * @ORM\Column(type="integer")
     */
    private $totalProducts = 0;
    
    /**
     * @ORM\Column(type="integer")
     */
    private $successCount = 0;
    
    /**
     * @ORM\Column(type="integer")
     */
    private $errorCount = 0;
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $username;
    
    /**
     * @ORM\OneToMany(targetEntity="SyncDetail", mappedBy="syncHistory", cascade={"persist", "remove"})
     */
    private $details;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $errorMessage;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->startedAt = new \DateTime();
        $this->status = self::STATUS_PENDING;
        $this->details = new ArrayCollection();
    }
    
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Set type
     *
     * @param string $type
     * @return SyncHistory
     */
    public function setType($type)
    {
        $this->type = $type;
        
        return $this;
    }
    
    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * Set status
     *
     * @param string $status
     * @return SyncHistory
     */
    public function setStatus($status)
    {
        $this->status = $status;
        
        return $this;
    }
    
    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Set filters
     *
     * @param array $filters
     * @return SyncHistory
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
        
        return $this;
    }
    
    /**
     * Get filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }
    
    /**
     * Set startedAt
     *
     * @param \DateTime $startedAt
     * @return SyncHistory
     */
    public function setStartedAt($startedAt)
    {
        $this->startedAt = $startedAt;
        
        return $this;
    }
    
    /**
     * Get startedAt
     *
     * @return \DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }
    
    /**
     * Set completedAt
     *
     * @param \DateTime $completedAt
     * @return SyncHistory
     */
    public function setCompletedAt($completedAt)
    {
        $this->completedAt = $completedAt;
        
        return $this;
    }
    
    /**
     * Get completedAt
     *
     * @return \DateTime
     */
    public function getCompletedAt()
    {
        return $this->completedAt;
    }
    
    /**
     * Set totalProducts
     *
     * @param integer $totalProducts
     * @return SyncHistory
     */
    public function setTotalProducts($totalProducts)
    {
        $this->totalProducts = $totalProducts;
        
        return $this;
    }
    
    /**
     * Get totalProducts
     *
     * @return integer
     */
    public function getTotalProducts()
    {
        return $this->totalProducts;
    }
    
    /**
     * Set successCount
     *
     * @param integer $successCount
     * @return SyncHistory
     */
    public function setSuccessCount($successCount)
    {
        $this->successCount = $successCount;
        
        return $this;
    }
    
    /**
     * Get successCount
     *
     * @return integer
     */
    public function getSuccessCount()
    {
        return $this->successCount;
    }
    
    /**
     * Set errorCount
     *
     * @param integer $errorCount
     * @return SyncHistory
     */
    public function setErrorCount($errorCount)
    {
        $this->errorCount = $errorCount;
        
        return $this;
    }
    
    /**
     * Get errorCount
     *
     * @return integer
     */
    public function getErrorCount()
    {
        return $this->errorCount;
    }
    
    /**
     * Increment success count
     *
     * @return SyncHistory
     */
    public function incrementSuccessCount()
    {
        $this->successCount++;
        
        return $this;
    }
    
    /**
     * Increment error count
     *
     * @return SyncHistory
     */
    public function incrementErrorCount()
    {
        $this->errorCount++;
        
        return $this;
    }
    
    /**
     * Set username
     *
     * @param string $username
     * @return SyncHistory
     */
    public function setUsername($username)
    {
        $this->username = $username;
        
        return $this;
    }
    
    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * Set errorMessage
     *
     * @param string $errorMessage
     * @return SyncHistory
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
        
        return $this;
    }
    
    /**
     * Get errorMessage
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
    
    /**
     * Add detail
     *
     * @param SyncDetail $detail
     * @return SyncHistory
     */
    public function addDetail(SyncDetail $detail)
    {
        $this->details[] = $detail;
        $detail->setSyncHistory($this);
        
        return $this;
    }
    
    /**
     * Remove detail
     *
     * @param SyncDetail $detail
     */
    public function removeDetail(SyncDetail $detail)
    {
        $this->details->removeElement($detail);
    }
    
    /**
     * Get details
     *
     * @return Collection|SyncDetail[]
     */
    public function getDetails()
    {
        return $this->details;
    }
    
    /**
     * Get duration in seconds
     *
     * @return int
     */
    public function getDuration()
    {
        if (null === $this->completedAt) {
            return 0;
        }
        
        return $this->completedAt->getTimestamp() - $this->startedAt->getTimestamp();
    }
    
    /**
     * Mark as running
     *
     * @return SyncHistory
     */
    public function markAsRunning()
    {
        $this->status = self::STATUS_RUNNING;
        
        return $this;
    }
    
    /**
     * Mark as completed
     *
     * @return SyncHistory
     */
    public function markAsCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new \DateTime();
        
        return $this;
    }
    
    /**
     * Mark as failed
     *
     * @param string $errorMessage
     * @return SyncHistory
     */
    public function markAsFailed($errorMessage = null)
    {
        $this->status = self::STATUS_FAILED;
        $this->completedAt = new \DateTime();
        
        if ($errorMessage) {
            $this->errorMessage = $errorMessage;
        }
        
        return $this;
    }
}