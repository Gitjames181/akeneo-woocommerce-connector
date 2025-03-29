<?php
// src/MugfulMuse/WooCommerceConnectorBundle/Entity/SyncDetail.php
namespace MugfulMuse\WooCommerceConnectorBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="mugfulmuse_woo_sync_detail")
 */
class SyncDetail
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_SKIP = 'skip';
    
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="SyncHistory", inversedBy="details")
     * @ORM\JoinColumn(name="sync_history_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $syncHistory;
    
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $identifier;
    
    /**
     * @ORM\Column(type="string", length=20)
     */
    private $action;
    
    /**
     * @ORM\Column(type="string", length=20)
     */
    private $status;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $errorMessage;
    
    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
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
     * Set syncHistory
     *
     * @param SyncHistory $syncHistory
     * @return SyncDetail
     */
    public function setSyncHistory(SyncHistory $syncHistory)
    {
        $this->syncHistory = $syncHistory;
        
        return $this;
    }
    
    /**
     * Get syncHistory
     *
     * @return SyncHistory
     */
    public function getSyncHistory()
    {
        return $this->syncHistory;
    }
    
    /**
     * Set identifier
     *
     * @param string $identifier
     * @return SyncDetail
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        
        return $this;
    }
    
    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
    
    /**
     * Set action
     *
     * @param string $action
     * @return SyncDetail
     */
    public function setAction($action)
    {
        $this->action = $action;
        
        return $this;
    }
    
    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }
    
    /**
     * Set status
     *
     * @param string $status
     * @return SyncDetail
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
     * Set errorMessage
     *
     * @param string $errorMessage
     * @return SyncDetail
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return SyncDetail
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        
        return $this;
    }
    
    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}