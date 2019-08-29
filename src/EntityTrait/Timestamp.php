<?php

namespace ZF3Belcebur\DoctrineORMResources\EntityTrait;


use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
trait Timestamp
{

    /**
     * @var DateTime|null
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime", precision=0, scale=0, nullable=true, options={"default"="CURRENT_TIMESTAMP"}, unique=false)
     */
    protected $createdAt;

    /**
     * @var DateTime|null
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="modified_at", type="datetime", precision=0, scale=0, nullable=true, options={"default"="CURRENT_TIMESTAMP"}, unique=false)
     */
    protected $modifiedAt;


    /**
     * @return DateTime
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     *
     * @return self
     */
    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getModifiedAt(): ?DateTime
    {
        return $this->modifiedAt;
    }

    /**
     * @param DateTime $modifiedAt
     *
     * @return self
     */
    public function setModifiedAt(DateTime $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

}
