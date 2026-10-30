<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Credit card validation configuration.
 *
 * Options for each credit card:
 *
 * - length - All the allowed card number lengths, in a comma separated string
 * - prefix - The digits the card needs to start with, in regex format
 * - luhn   - Enable or disable card number validation by the Luhn algorithm
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */

/**
 * Default credit card configuration
 */
$config = 
[
	'default' => 
	[
		'length' => '13,14,15,16,17,18,19',
		'prefix' => '',
		'luhn'   => TRUE
	],
	'american express' => 
	[
		'length' => '15',
		'prefix' => '3[47]',
		'luhn'   => TRUE
	],
	'diners club' => 
	[
		'length' => '14,16',
		'prefix' => '36|55|30[0-5]',
		'luhn'   => TRUE
	],
	'discover' => 
	[
		'length' => '16',
		'prefix' => '6(?:5|011)',
		'luhn'   => TRUE,
	],
	'jcb' => 
	[
		'length' => '15,16',
		'prefix' => '3|1800|2131',
		'luhn'   => TRUE
	],
	'maestro' => 
	[
		'length' => '16,18',
		'prefix' => '50(?:20|38)|6(?:304|759)',
		'luhn'   => TRUE
	],
	'mastercard' => 
	[
		'length' => '16',
		'prefix' => '5[1-5]',
		'luhn'   => TRUE
	],
	'visa' => 
	[
		'length' => '13,16',
		'prefix' => '4',
		'luhn'   => TRUE
	],
];