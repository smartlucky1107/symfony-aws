<?php

namespace App\Model\RapidOrder;

use App\Entity\CurrencyPair;
use App\Model\RapidOrder\RapidPlannedOrder;

use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;

class RapidOrder implements RapidOrderInterface
{
    /**
     * @var int
     *
     * @SWG\Property(description="Type of the order", example="1", enum={1,2})
     * @Serializer\Groups({"output"})
     */
    private $type;

    /**
     * @var string
     *
     * @SWG\Property(description="Amount of the base currency", example="0.005")
     * @Serializer\Groups({"output"})
     */
    private $amount;

    /**
     * @var CurrencyPair
     *
     * @SWG\Property(ref=@Model(type=CurrencyPair::class, groups={"output"}))
     * @Serializer\Groups({"output"})
     */
    private $currencyPair;

    /**
     * @var RapidOrderWallet
     *
     * @SWG\Property(ref=@Model(type=RapidOrderWallet::class, groups={"output"}))
     * @Serializer\Groups({"output"})
     */
    private $wallet;

    /**
     * @var RapidMarketOrder|null
     *
     * @SWG\Property(ref=@Model(type=RapidMarketOrder::class, groups={"output"}))
     * @Serializer\Groups({"output"})
     */
    private $marketOrder;

    /**
     * @var RapidPlannedOrder[]|null
     *
     * @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=RapidPlannedOrder::class, groups={"output"})))
     * @Serializer\Groups({"output"})
     */
    private $checkoutOrders;

    /**
     * RapidOrder constructor.
     * @param int $type
     * @param string $amount
     * @param CurrencyPair $currencyPair
     * @param RapidOrderWallet $wallet
     * @param RapidMarketOrder|null $marketOrder
     */
    public function __construct(int $type, string $amount, CurrencyPair $currencyPair, RapidOrderWallet $wallet, RapidMarketOrder $marketOrder = null)
    {
        $this->type = $type;
        $this->amount = $amount;
        $this->currencyPair = $currencyPair;
        $this->wallet = $wallet;
        $this->marketOrder = $marketOrder;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RapidOrder
     */
    public function setType(int $type): RapidOrder
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @param string $amount
     * @return RapidOrder
     */
    public function setAmount(string $amount): RapidOrder
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return CurrencyPair
     */
    public function getCurrencyPair(): CurrencyPair
    {
        return $this->currencyPair;
    }

    /**
     * @param CurrencyPair $currencyPair
     * @return RapidOrder
     */
    public function setCurrencyPair(CurrencyPair $currencyPair): RapidOrder
    {
        $this->currencyPair = $currencyPair;
        return $this;
    }

    /**
     * @return RapidOrderWallet
     */
    public function getWallet(): RapidOrderWallet
    {
        return $this->wallet;
    }

    /**
     * @param RapidOrderWallet $wallet
     * @return RapidOrder
     */
    public function setWallet(RapidOrderWallet $wallet): RapidOrder
    {
        $this->wallet = $wallet;
        return $this;
    }

    /**
     * @return RapidMarketOrder|null
     */
    public function getMarketOrder(): ?RapidMarketOrder
    {
        return $this->marketOrder;
    }

    /**
     * @param RapidMarketOrder|null $marketOrder
     */
    public function setMarketOrder(?RapidMarketOrder $marketOrder): void
    {
        $this->marketOrder = $marketOrder;
    }

    /**
     * @return RapidPlannedOrder[]|null
     */
    public function getCheckoutOrders(): ?array
    {
        return $this->checkoutOrders;
    }

    /**
     * @param RapidPlannedOrder[]|null $checkoutOrders
     */
    public function setCheckoutOrders(?array $checkoutOrders): void
    {
        $this->checkoutOrders = $checkoutOrders;
    }

    /**
     * @return RapidPlannedOrder[]|null
     */
    public function getPlannedOrders(): ?array
    {
        return $this->checkoutOrders;
    }

    /**
     * @param RapidPlannedOrder[]|null $checkoutOrders
     * @return RapidOrder
     */
    public function setPlannedOrders(?array $checkoutOrders): RapidOrder
    {
        $this->checkoutOrders = $checkoutOrders;
        return $this;
    }

    /**
     * @param RapidPlannedOrder $rapidPlannedOrder
     * @return RapidOrder
     */
    public function addPlannedOrder(RapidPlannedOrder $rapidPlannedOrder) : RapidOrder
    {
        $this->checkoutOrders[] = $rapidPlannedOrder;
        return $this;
    }
}
