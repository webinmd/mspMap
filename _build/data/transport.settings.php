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
		'value' => '',
        'area' => 'mspmap_main',
	),
	'key' => array(
		'xtype' => 'textfield',
		'value' => '',
        'area' => 'mspmap_main',
	),
	'pass' => array(
		'xtype' => 'text-password',
		'value' => '',
        'area' => 'mspmap_main',
	),
	'success_id' => array(
		'xtype' => 'numberfield',
		'value' => 0,
        'area' => 'mspmap_main',

	),
	'failure_id' => array(
		'xtype' => 'numberfield',
		'value' => 0,
        'area' => 'mspmap_main',
	),
    'test_mode' => array(
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'mspmap_main',
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