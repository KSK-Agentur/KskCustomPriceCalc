<?php

namespace KskCustomPriceCalc;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Enlight_Event_EventArgs;
use KskCustomPriceCalc\Models\CurrencySettings;
use KskCustomPriceCalc\Services\PriceCalculationService;
use Shopware\Bundle\StoreFrontBundle\Gateway\PriceGroupDiscountGatewayInterface;
use Shopware\Bundle\StoreFrontBundle\Service\PriceCalculatorInterface;
use Shopware\Components\Logger;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

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

    /**
     * @param UpdateContext $context
     */
    public function update(UpdateContext $context)
    {
        $this->createSchema();
    }

    private function createSchema()
    {
        try {
            /** @var ModelManager $modelManager */
            $modelManager = $this->container->get('models');
            /** @var AbstractSchemaManager $schemaManager */
            $schemaManager = $modelManager->getConnection()->getSchemaManager();

            $tool = new SchemaTool($modelManager);
            $classes = [
                $modelManager->getClassMetadata(CurrencySettings::class),
            ];

            foreach($classes as $class) {
                if (!$schemaManager->tablesExist($class->getTableName())) {
                    $tool->createSchema([$class]);
                } else {
                    $tool->updateSchema([$class], true);
                }
            }
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
        try {
            // Shopware 5.3

            /** @var PriceCalculatorInterface $argument */
            $argument = $this->container->get('shopware_storefront.price_calculator');
        } catch (ServiceNotFoundException $exception) {
            // Shopware 5.2

            /** @var PriceGroupDiscountGatewayInterface $argument */
            $argument = $this->container->get('shopware_storefront.price_group_discount_gateway');
        }

        $this->container->set('shopware_storefront.price_calculation_service', new PriceCalculationService(
            $argument
        ));
    }
}
