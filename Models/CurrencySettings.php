<?php

namespace KskCustomPriceCalc\Models;

use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * Class CurrencySettings
 * @package KskCustomPriceCalc\Models
 * @ORM\Entity
 * @ORM\Table(name="ksk_custom_price_calc_currency_settings")
 */
class CurrencySettings extends ModelEntity
{
    const DEFAULT_VALUE_PRECISION = 10;

    const DEFAULT_VALUE_SUBTRAHEND = 1;
    
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="currency_id", type="integer", nullable=false)
     */
    private $currencyId = null;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @var bool
     *
     * @ORM\Column(name="always_up", type="boolean")
     */
    private $alwaysUp;

    /**
     * @var float
     * @ORM\Column(name="precision_value", type="float")
     */
    private $precision;

    /**
     * @var float
     * @ORM\Column(type="float")
     */
    private $subtrahend;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    /**
     * @param int $currencyId
     */
    public function setCurrencyId($currencyId)
    {
        $this->currencyId = $currencyId;
    }

    /**
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return bool
     */
    public function getAlwaysUp()
    {
        return $this->alwaysUp;
    }

    /**
     * @param bool $alwaysUp
     */
    public function setAlwaysUp($alwaysUp)
    {
        $this->alwaysUp = $alwaysUp;
    }

    /**
     * @return float
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @param float $precision
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;
    }

    /**
     * @return float
     */
    public function getSubtrahend()
    {
        return $this->subtrahend;
    }

    /**
     * @param float $subtrahend
     */
    public function setSubtrahend($subtrahend)
    {
        $this->subtrahend = $subtrahend;
    }
}
