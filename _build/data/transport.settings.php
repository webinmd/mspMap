<?php
/**
 * Loads system settings into build
 *
 * @package mspmap
 * @subpackage build
 */
$settings = array();

$tmp = array(
	'gateway_url' => array(
		'xtype' => 'textfield',
		'value' => ''
	),
	'key' => array(
		'xtype' => 'textfield',
		'value' => ''
	),
	'success_id' => array(
		'xtype' => 'numberfield',
		'value' => 0

	),
	'failure_id' => array(
		'xtype' => 'numberfield',
		'value' => 0
	),
    'test_mode' => array(
        'xtype' => 'combo-boolean',
        'value' => true
    )
);

foreach ($tmp as $k => $v) {
	/* @var modSystemSetting $setting */
	$setting = $modx->newObject('modSystemSetting');
	$setting->fromArray(array_merge(
		array(
			'key' => 'ms2_payment_mspmap_'.$k,
			'namespace' => 'minishop2',
			'area' => 'ms2_payment',
		), $v
	),'',true,true);

	$settings[] = $setting;
}

unset($tmp);
return $settings;