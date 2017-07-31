<?php

namespace KskCustomPriceCalc;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Enlight_Event_EventArgs;
use KskCustomPriceCalc\Models\CurrencySettings;
use KskCustomPriceCalc\Services\PriceCalculationService;
use Shopware\Bundle\StoreFrontBundle\Gateway\PriceGroupDiscountGatewayInterface;
use Shopware\Components\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;

/**
 * Class KskCustomPriceCalc
 * @package KskCustomPriceCalc
 */
class KskCustomPriceCalc extends Plugin
{
    const ARRAY_STORE_KEY = 'key';

    const ARRAY_STORE_VALUE = 'value';

    const TABLE_NAME_CURRENCIES = 's_core_currencies';

    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        $this->createSchema();
    }

    public function update(UpdateContext $context)
    {
        $this->createSchema();
    }

    private function createSchema()
    {
        try {
            /** @var ModelManager $modelManager */
            $modelManager = $this->container->get('models');

            $tool = new SchemaTool($modelManager);
            $classes = [
                $modelManager->getClassMetadata(CurrencySettings::class)
            ];
            $tool->createSchema($classes);
        } catch (ToolsException $exception) {
            /** @var Logger $pluginLogger */
            $pluginLogger = $this->container->get('pluginlogger');
            $pluginLogger->error($exception->getMessage());
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Bootstrap_AfterInitResource_shopware_storefront.price_calculation_service' => 'decoratePriceCalculationService',
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function decoratePriceCalculationService(Enlight_Event_EventArgs $args)
    {
        /** @var PriceGroupDiscountGatewayInterface $priceGroupDiscountGateway */
        $priceGroupDiscountGateway = $this->container->get('shopware_storefront.price_group_discount_gateway');

        $this->container->set('shopware_storefront.price_calculation_service', new PriceCalculationService(
            $priceGroupDiscountGateway
        ));
    }
}
