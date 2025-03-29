<?php
// src/MugfulMuse/WooCommerceConnectorBundle/Entity/FieldMapping.php
namespace MugfulMuse\WooCommerceConnectorBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="MugfulMuse\WooCommerceConnectorBundle\Repository\FieldMappingRepository")
 * @ORM\Table(name="mugfulmuse_woo_field_mapping")
 */
class FieldMapping
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $akeneoField;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $wooCommerceField;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $akeneoType;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $wooCommerceType;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $transformationOptions = [];

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive = true;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $direction = 'both'; // 'both', 'push', 'pull'

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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
     * Set akeneoField
     *
     * @param string $akeneoField
     * @return FieldMapping
     */
    public function setAkeneoField($akeneoField)
    {
        $this->akeneoField = $akeneoField;

        return $this;
    }

    /**
     * Get akeneoField
     *
     * @return string
     */
    public function getAkeneoField()
    {
        return $this->akeneoField;
    }

    /**
     * Set wooCommerceField
     *
     * @param string $wooCommerceField
     * @return FieldMapping
     */
    public function setWooCommerceField($wooCommerceField)
    {
        $this->wooCommerceField = $wooCommerceField;

        return $this;
    }

    /**
     * Get wooCommerceField
     *
     * @return string
     */
    public function getWooCommerceField()
    {
        return $this->wooCommerceField;
    }

    /**
     * Set akeneoType
     *
     * @param string $akeneoType
     * @return FieldMapping
     */
    public function setAkeneoType($akeneoType)
    {
        $this->akeneoType = $akeneoType;

        return $this;
    }

    /**
     * Get akeneoType
     *
     * @return string
     */
    public function getAkeneoType()
    {
        return $this->akeneoType;
    }

    /**
     * Set wooCommerceType
     *
     * @param string $wooCommerceType
     * @return FieldMapping
     */
    public function setWooCommerceType($wooCommerceType)
    {
        $this->wooCommerceType = $wooCommerceType;

        return $this;
    }

    /**
     * Get wooCommerceType
     *
     * @return string
     */
    public function getWooCommerceType()
    {
        return $this->wooCommerceType;
    }

    /**
     * Set transformationOptions
     *
     * @param array $transformationOptions
     * @return FieldMapping
     */
    public function setTransformationOptions($transformationOptions)
    {
        $this->transformationOptions = $transformationOptions;

        return $this;
    }

    /**
     * Get transformationOptions
     *
     * @return array
     */
    public function getTransformationOptions()
    {
        return $this->transformationOptions;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return FieldMapping
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set direction
     *
     * @param string $direction
     * @return FieldMapping
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;

        return $this;
    }

    /**
     * Get direction
     *
     * @return string
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return FieldMapping
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

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return FieldMapping
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

    /**
     * @ORM\PreUpdate
     */
    public function updateTimestamp()
    {
        $this->updatedAt = new \DateTime();
    }
}