<?php
// src/MugfulMuse/WooCommerceConnectorBundle/Entity/Setting.php
namespace MugfulMuse\WooCommerceConnectorBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="mugfulmuse_woo_setting",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 * )
 */
class Setting
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $key;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $value;
    
    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->updatedAt = new \DateTime();
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
     * Set key
     *
     * @param string $key
     * @return Setting
     */
    public function setKey($key)
    {
        $this->key = $key;
        
        return $this;
    }
    
    /**
     * Get key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
    
    /**
     * Set value
     *
     * @param mixed $value
     * @return Setting
     */
    public function setValue($value)
    {
        $this->value = is_array($value) ? json_encode($value) : $value;
        $this->updatedAt = new \DateTime();
        
        return $this;
    }
    
    /**
     * Get value
     *
     * @return mixed
     */
    public function getValue()
    {
        if ($this->value === null) {
            return null;
        }
        
        // Try to decode JSON values
        $decoded = json_decode($this->value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        return $this->value;
    }
    
    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Setting
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        
        return $this;
    }
    
    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}