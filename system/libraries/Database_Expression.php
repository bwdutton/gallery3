<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database expression.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Database_Expression_Core implements \Stringable {

	public function __construct(protected $expression)
    {
    }

	public function __toString(): string
	{
		return (string) $this->expression;
	}
}
