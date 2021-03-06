<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Query;

/**
 * Base class for SqlColumn, SqlString, SqlBool, and SqlFunction classes.
 *
 * These are used to validate and format sql expressions in Api4 select queries.
 *
 * @package Civi\Api4\Query
 */
abstract class SqlExpression {

  /**
   * @var array
   */
  protected $fields = [];

  /**
   * @var string|null
   */
  protected $alias;

  /**
   * The argument string.
   * @var string
   */
  protected $arg = '';

  /**
   * SqlFunction constructor.
   * @param string $arg
   * @param string|null $alias
   */
  public function __construct(string $arg, $alias = NULL) {
    $this->arg = $arg;
    $this->alias = $alias;
    $this->initialize();
  }

  abstract protected function initialize();

  /**
   * Converts a string to a SqlExpression object.
   *
   * E.g. the expression "SUM(foo)" would return a SqlFunctionSUM object.
   *
   * @param string $expression
   * @param bool $parseAlias
   * @param array $mustBe
   * @param array $cantBe
   * @return SqlExpression
   * @throws \API_Exception
   */
  public static function convert(string $expression, $parseAlias = FALSE, $mustBe = [], $cantBe = ['SqlWild']) {
    $as = $parseAlias ? strrpos($expression, ' AS ') : FALSE;
    $expr = $as ? substr($expression, 0, $as) : $expression;
    $alias = $as ? \CRM_Utils_String::munge(substr($expression, $as + 4)) : NULL;
    $bracketPos = strpos($expr, '(');
    $firstChar = substr($expr, 0, 1);
    $lastChar = substr($expr, -1);
    // Function
    if ($bracketPos && $lastChar === ')') {
      $fnName = substr($expr, 0, $bracketPos);
      if ($fnName !== strtoupper($fnName)) {
        throw new \API_Exception('Sql function must be uppercase.');
      }
      $className = 'SqlFunction' . $fnName;
      $expr = substr($expr, $bracketPos + 1, -1);
    }
    // String expression
    elseif ($firstChar === $lastChar && in_array($firstChar, ['"', "'"], TRUE)) {
      $className = 'SqlString';
    }
    elseif ($expr === 'NULL') {
      $className = 'SqlNull';
    }
    elseif ($expr === '*') {
      $className = 'SqlWild';
    }
    elseif (is_numeric($expr)) {
      $className = 'SqlNumber';
    }
    // If none of the above, assume it's a field name
    else {
      $className = 'SqlField';
    }
    $className = __NAMESPACE__ . '\\' . $className;
    if (!class_exists($className)) {
      throw new \API_Exception('Unable to parse sql expression: ' . $expression);
    }
    $sqlExpression = new $className($expr, $alias);
    foreach ($cantBe as $cant) {
      if (is_a($sqlExpression, __NAMESPACE__ . '\\' . $cant)) {
        throw new \API_Exception('Illegal sql expression.');
      }
    }
    if ($mustBe) {
      foreach ($mustBe as $must) {
        if (is_a($sqlExpression, __NAMESPACE__ . '\\' . $must)) {
          return $sqlExpression;
        }
      }
      throw new \API_Exception('Illegal sql expression.');
    }
    return $sqlExpression;
  }

  /**
   * Returns the field names of all sql columns that are arguments to this expression.
   *
   * @return array
   */
  public function getFields(): array {
    return $this->fields;
  }

  /**
   * Renders expression to a sql string, replacing field names with column names.
   *
   * @param array $fieldList
   * @return string
   */
  abstract public function render(array $fieldList): string;

  /**
   * Returns the alias to use for SELECT AS.
   *
   * @return string
   */
  public function getAlias(): string {
    return $this->alias ?? $this->fields[0] ?? \CRM_Utils_String::munge($this->arg);
  }

}
