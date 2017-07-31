<?php

namespace KskCustomPriceCalc\Subscribers;

use Doctrine\ORM\EntityRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use KskCustomPriceCalc\Models\CurrencySettings;
use Shopware\Bundle\StoreFrontBundle\Gateway\DBAL\Hydrator\TaxHydrator;
use Shopware\Bundle\StoreFrontBundle\Service\Core\ContextService;
use Shopware\Bundle\StoreFrontBundle\Struct\Customer\Group;
use Shopware\Bundle\StoreFrontBundle\Struct\ProductContextInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\Tax;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Tax\Tax as TaxModel;

/**
 * Class PriceCalculation
 * @package KskCustomPriceCalc\Subscribers
 */
class PriceCalculation implements SubscriberInterface
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var TaxHydrator
     */
    private $taxHydrator;

    /**
     * @var ContextService
     */
    private $contextService;

    /**
     * @var EntityRepository
     */
    private $currencySettingsRepository;

    /**
     * PriceCalculation constructor.
     * @param ModelManager $modelManager
     * @param TaxHydrator $taxHydrator
     * @param ContextService $contextService
     */
    public function __construct(ModelManager $modelManager, TaxHydrator $taxHydrator, ContextService $contextService)
    {

        $this->modelManager = $modelManager;
        $this->taxHydrator = $taxHydrator;
        $this->contextService = $contextService;
        $this->currencySettingsRepository = $this->modelManager->getRepository(CurrencySettings::class);
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (position defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     * <code>
     * return array(
     *     'eventName0' => 'callback0',
     *     'eventName1' => array('callback1'),
     *     'eventName2' => array('callback2', 10),
     *     'eventName3' => array(
     *         array('callback3_0', 5),
     *         array('callback3_1'),
     *         array('callback3_2')
     *     )
     * );
     *
     * </code>
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_StoreFrontBundle_PriceCalculationService_Filter_Price' => 'filterPrice',
            'Shopware_Modules_Basket_getPriceForUpdateArticle_FilterPrice' => 'onUpdatePrice',
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     * @return float|int
     */
    public function filterPrice(Enlight_Event_EventArgs $args)
    {
        $price = $args->getReturn();
        /** @var Tax $tax */
        $tax = $args->get('tax');
        /** @var ProductContextInterface $context */
        $context = $args->get('context');
        /** @var Group $customerGroup */
        $customerGroup = $args->get('customerGroup');

        return $this->calculateCustomPrice($price, $tax, $context, $customerGroup);
    }

    /**
     * @param Enlight_Event_EventArgs $args
     * @return array
     */
    public function onUpdatePrice(Enlight_Event_EventArgs $args)
    {
        /** @var array $return */
        $return = $args->getReturn();

        $taxModel = $this->modelManager->getRepository(TaxModel::class)->findOneBy(['tax' => $return['tax']]);
        $tax = $this->taxHydrator->hydrate([
            '__tax_id' => $taxModel->getId(),
            '__tax_description' => $taxModel->getName(),
            '__tax_tax' => $taxModel->getTax(),
        ]);

        /** @var ProductContextInterface $context */
        $context = $this->contextService->getShopContext();
        $customerGroup = $context->getCurrentCustomerGroup();

        $price = $return['price'] * $context->getCurrency()->getFactor();
        $price = $this->calculateCustomPrice($price, $tax, $context, $customerGroup);
        $return['price'] = $price / $context->getCurrency()->getFactor();

        return $return;
    }

    /**
     * @param $price
     * @param Tax $tax
     * @param ProductContextInterface $context
     * @param Group $customerGroup
     * @return float|int
     */
    private function calculateCustomPrice($price, Tax $tax, ProductContextInterface $context, Group $customerGroup)
    {
        $currency = $context->getCurrency();

        /** @var CurrencySettings $currencySettings */
        if (($currencySettings = $this->currencySettingsRepository->findOneBy(['currencyId' => $currency->getId()])) === null) {
            return $price;
        }

        if ($currencySettings->getActive() !== true) {
            return $price;
        }

        $precision = $currencySettings->getPrecision();
        $roundingPrecision = log($precision, 10) * (-1);

        $displayPrice = ($customerGroup->displayGrossPrices()) ? $price * (100 + $tax->getTax()) / 100 : $price;

        $newPrice = round($displayPrice, $roundingPrecision, PHP_ROUND_HALF_UP);

        if ($currencySettings->getAlwaysUp() && $newPrice < $displayPrice) {
            $newPrice = round($displayPrice + $precision / 2, $roundingPrecision, PHP_ROUND_HALF_UP);
        }

        $newPrice = $newPrice - $currencySettings->getSubtrahend();

        if ($customerGroup->displayGrossPrices()) {
            $newPrice = $newPrice / (100 + $tax->getTax()) * 100;
        }

        return ($newPrice <= 0) ? $price : $newPrice;
    }
}
