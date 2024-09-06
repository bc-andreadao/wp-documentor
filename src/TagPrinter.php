<?php
/**
 * Tag Printer
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Documentor
 */

namespace Pronamic\WordPress\Documentor;

use \PhpParser\Node\Expr;

/**
 * Tag Printer
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class TagPrinter {
	/**
	 * Print PHP Parser epxression.
	 *
	 * @throws \Exception Throws exception when epxression can not be transformed to string.
	 * @param Expr $expr PHP Parser epxression.
	 * @return string
	 */
	public function print( Expr $expr ) {
		/**
		 * String.
		 *
		 * @link https://github.com/nikic/PHP-Parser/blob/v4.10.4/lib/PhpParser/Node/Scalar/String_.php
		 */
		if ( $expr instanceof \PhpParser\Node\Scalar\String_ ) {
			return $expr->value;
		}

		/**
		 * Contat.
		 *
		 * @link https://github.com/nikic/PHP-Parser/blob/v4.10.4/lib/PhpParser/Node/Expr/BinaryOp/Concat.php
		 * @link https://github.com/nikic/PHP-Parser/blob/v4.10.4/lib/PhpParser/Node/Expr/BinaryOp.php
		 */
		if ( $expr instanceof \PhpParser\Node\Expr\BinaryOp\Concat ) {
			return $this->print( $expr->left ) . $this->print( $expr->right );
		}

		/**
		 * Variable.
		 *
		 * @link https://github.com/nikic/PHP-Parser/blob/v4.10.4/lib/PhpParser/Node/Expr/Variable.php
		 */
		if ( $expr instanceof \PhpParser\Node\Expr\Variable ) {
			return '{$' . $expr->name . '}';
		}

		/**
		 * Encapsed.
		 *
		 * @link https://github.com/nikic/PHP-Parser/blob/v4.10.4/lib/PhpParser/Node/Scalar/Encapsed.php
		 */
		if ( $expr instanceof \PhpParser\Node\Scalar\Encapsed ) {
			return implode(
				'',
				\array_map(
					__METHOD__,
					$expr->parts
				)
			);
		}

		/**
		 * Encapsed String Part.
		 *
		 * @link https://github.com/nikic/PHP-Parser/blob/v4.10.4/lib/PhpParser/Node/Scalar/EncapsedStringPart.php
		 */
		if ( $expr instanceof \PhpParser\Node\Scalar\EncapsedStringPart ) {
			return $expr->value;
		}

		/**
		 * Function Call.
		 *
		 * For example: `get_current_screen()`.
		 *
		 * @todo What todo with function call arguments?
		 * @link https://github.com/nikic/PHP-Parser/blob/v4.10.4/lib/PhpParser/Node/Expr/FuncCall.php
		 * @link https://github.com/WordPress/WordPress/blob/5.7/wp-admin/network/sites.php#L231-L232
		 * @link https://github.com/WordPress/WordPress/blob/5.7/wp-admin/network/site-themes.php#L124-L139
		 */
		if ( $expr instanceof \PhpParser\Node\Expr\FuncCall ) {
			return '' . $expr->name . '()';
		}

		/**
		 * Method Call.
		 *
		 * For example: `hook_{$this->test()}`.
		 *
		 * @todo What todo with method call arguments?
		 * @link https://github.com/nikic/PHP-Parser/blob/v4.10.4/lib/PhpParser/Node/Expr/MethodCall.php
		 */
		if ( $expr instanceof \PhpParser\Node\Expr\MethodCall ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
			// Currently not supported.
		}

		/**
		 * Property Fetch.
		 *
		 * For example: `get_current_screen()->id`.
		 *
		 * @link https://github.com/nikic/PHP-Parser/blob/v4.10.4/lib/PhpParser/Node/Expr/PropertyFetch.php
		 */
		if ( $expr instanceof \PhpParser\Node\Expr\PropertyFetch ) {
			return '{$' . $expr->var->name . '->' . $expr->name . '}';
		}

		/**
		 * Array Dim Fetch.
		 *
		 * For example: `$action['bc-action']`.
		 *
		 * @link https://github.com/nikic/PHP-Parser/blob/v4.10.4/lib/PhpParser/Node/Expr/ArrayDimFetch.php
		 */
		if ( $expr instanceof \PhpParser\Node\Expr\ArrayDimFetch ) {
			$varName = $this->print( $expr->var ); // Print the array variable.
			$dim = $this->print( $expr->dim );     // Print the array index/key.
			return $varName . '[' . $dim . ']';     // Combine them into a string.
		}

		/**
		 * Class Constant Fetch.
		 *
		 * For example: `Webhook_Cron_Tasks::UPDATE_PRODUCT`.
		 *
		 * @link https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/Node/Expr/ClassConstFetch.php
		 */
		if ( $expr instanceof \PhpParser\Node\Expr\ClassConstFetch ) {
			$className = $this->print( $expr->class ); // Print the class name.
			$constantName = $this->print( $expr->name ); // Print the constant name.
			return $className . '::' . $constantName; // Combine into class::constant format.
		}

		/**
		 * Unsupported expression.
		 *
		 * @link https://github.com/nikic/PHP-Parser/blob/master/doc/component/Pretty_printing.markdown
		 */
		$pretty_printer = new \PhpParser\PrettyPrinter\Standard();

		throw new \Exception(
			\sprintf(
				'Not supported hook tag expression `%s`: %s.',
				\get_class( $expr ),
				$pretty_printer->prettyPrintExpr( $expr )
			)
		);
	}
}
