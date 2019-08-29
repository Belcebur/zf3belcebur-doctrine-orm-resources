<?php

namespace ZF3Belcebur\DoctrineORMResources\EntityTrait;


use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
trait Coordinates
{

    /**
     * @var float
     *
     * @ORM\Column(name="latitude", type="decimal", precision=10, scale=7, nullable=false, options={"default":0})
     */
    protected $latitude = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="longitude", type="decimal", precision=10, scale=7, nullable=false, options={"default":0})
     */
    protected $longitude = 0;

    /**
     * @return float
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * @param float $latitude
     *
     * @return self
     */
    public function setLatitude($latitude): self
    {
        $this->latitude = (float)$latitude;
        return $this;
    }

    /**
     * @return float
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * @param float $longitude
     *
     * @return self
     */
    public function setLongitude($longitude): self
    {
        $this->longitude = (float)$longitude;
        return $this;
    }


    public function setLatitudeLongitude($latitude, $longitude): self
    {
        $this->latitude = (float)$latitude;
        $this->longitude = (float)$longitude;

        return $this;
    }

}
