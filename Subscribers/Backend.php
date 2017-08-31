<?php

namespace KskCustomPriceCalc\Subscribers;

use Doctrine\ORM\EntityRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use KskCustomPriceCalc\Models\CurrencySettings;
use Shopware\Components\Model\ModelManager;
use Shopware_Controllers_Backend_Config;

/**
 * Class Backend
 * @package KskCustomPriceCalc\Subscribers
 */
class Backend implements SubscriberInterface
{
    const PARAM_ACTIVE = 'active';

    const PARAM_ALWAYS_UP = 'alwaysUp';

    const PARAM_PRECISION = 'precision';

    const PARAM_SUBTRAHEND = 'subtrahend';

    /**
     * @var string
     */
    private $pluginDir;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var EntityRepository
     */
    private $currencySettingsRepository;

    /**
     * Backend constructor.
     * @param $pluginDir string
     * @param ModelManager $modelManager
     */
    public function __construct($pluginDir, ModelManager $modelManager)
    {
        $this->pluginDir = $pluginDir;
        $this->modelManager = $modelManager;

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
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Config' => 'onPostDispatchSecureBackendConfig',
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function onPostDispatchSecureBackendConfig(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Backend_Config $controller */
        $controller = $args->get('subject');

        $templateDir = implode(DIRECTORY_SEPARATOR, [$this->pluginDir, 'Resources', 'views', 'backend', 'config']);
        $templates = [
            implode(DIRECTORY_SEPARATOR, [$templateDir, 'view', 'form', 'currency.js']),
            implode(DIRECTORY_SEPARATOR, [$templateDir, 'model', 'form', 'currency.js']),
        ];

        foreach ($templates as $template) {
            $controller->View()->extendsTemplate($template);
        }

        if ($controller->Request()->getActionName() === 'saveValues') {
            if (($currencyId = (int) $controller->Request()->getParam('id')) === null) {
                return;
            }
            $active = (bool) $controller->Request()->getParam(static::PARAM_ACTIVE, false);
            $alwaysUp = (bool) $controller->Request()->getParam(static::PARAM_ALWAYS_UP, false);
            $precision = (float) $controller->Request()->getParam(static::PARAM_PRECISION, 10);
            $subtrahend = (float) $controller->Request()->getParam(static::PARAM_SUBTRAHEND, 1);

            $this->updateCurrencySettings($currencyId, $active, $alwaysUp, $precision, $subtrahend);
        }

        if ($controller->Request()->getActionName() === 'getList'
            && $controller->Request()->getParam('_repositoryClass') === 'currency') {

            $data = $controller->View()->getAssign('data');

            foreach ($data as &$currency) {
                $currency = $this->extendCurrency($currency);
            }

            $controller->View()->assign('data', $data);
        }
    }

    /**
     * @param $currencyId
     * @param $active
     * @param $precision
     * @param $subtrahend
     */
    private function updateCurrencySettings($currencyId, $active, $alwaysUp, $precision, $subtrahend)
    {
        /** @var CurrencySettings $currencySettings */
        $currencySettings = $this->currencySettingsRepository->findOneBy(['currencyId' => $currencyId]);

        if ($currencySettings === null) {
            $currencySettings = new CurrencySettings();
        }

        $currencySettings->setCurrencyId($currencyId);
        $currencySettings->setActive($active);
        $currencySettings->setAlwaysUp($alwaysUp);
        $currencySettings->setPrecision($precision);
        $currencySettings->setSubtrahend($subtrahend);

        $this->modelManager->persist($currencySettings);
        $this->modelManager->flush();
    }

    /**
     * @param $currency array
     */
    private function extendCurrency(array $currency)
    {
        /** @var CurrencySettings $currencySettings */
        $currencySettings = $this->currencySettingsRepository->findOneBy(['currencyId' => $currency['id']]);

        if ($currencySettings === null) {
            $currency[static::PARAM_ACTIVE] = false;
            $currency[static::PARAM_ALWAYS_UP] = false;
            $currency[static::PARAM_PRECISION] = CurrencySettings::DEFAULT_VALUE_PRECISION;
            $currency[static::PARAM_SUBTRAHEND] = CurrencySettings::DEFAULT_VALUE_SUBTRAHEND;
        } else {
            $currency[static::PARAM_ACTIVE] = $currencySettings->getActive();
            $currency[static::PARAM_ALWAYS_UP] = $currencySettings->getAlwaysUp();
            $currency[static::PARAM_PRECISION] = $currencySettings->getPrecision();
            $currency[static::PARAM_SUBTRAHEND] = $currencySettings->getSubtrahend();
        }

        return $currency;
    }
}
