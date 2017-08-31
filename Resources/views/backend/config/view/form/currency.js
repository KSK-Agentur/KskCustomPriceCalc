
//{namespace name="backend/plugins/ksk_custom_price_calc/config/currency"}
//{block name="backend/config/view/main/table"}
//{$smarty.block.parent}

Ext.define('Shopware.apps.KskCustomPriceCalc.view.form.Currency', {
    override: 'Shopware.apps.Config.view.form.Currency',

    getFormItems: function() {
        var me = this,
            result = me.callParent(arguments);

        result.push({
            xtype: 'config-element-boolean',
            name: 'active',
            fieldLabel: '{s name="ActiveLabel"}{/s}',
            supportText: '{s name="ActiveSupportText"}{/s}'
        });
        result.push({
            xtype: 'config-element-boolean',
            name: 'alwaysUp',
            fieldLabel: '{s name="AlwaysUpLabel"}{/s}',
            supportText: '{s name="AlwaysUpSupportText"}{/s}'
        });
        result.push({
            xtype: 'combobox',
            name: 'precision',
            store: [
                [0.1, '{s name="PrecisionValue01"}{/s}'],
                [1, '{s name="PrecisionValue1"}{/s}'],
                [10, '{s name="PrecisionValue10"}{/s}'],
                [100, '{s name="PrecisionValue100"}{/s}'],
                [1000, '{s name="PrecisionValue1000"}{/s}']
            ],
            fieldLabel: '{s name="PrecisionLabel"}{/s}',
            supportText: '{s name="PrecisionSupportText"}{/s}'
        });
        result.push({
            xtype: 'config-element-number',
            name: 'subtrahend',
            decimalPrecision: 2,
            fieldLabel: '{s name="SubtrahendLabel"}{/s}',
            supportText: '{s name="SubtrahendSupportText"}{/s}'
        });

        return result;
    }
});
//{/block}
