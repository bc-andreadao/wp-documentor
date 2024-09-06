<?php
namespace Pronamic\WordPress\Documentor;

use \PhpParser\Node;
use \PhpParser\Node\Expr;
use \PhpParser\Node\Name;
use \PhpParser\Node\Identifier;

class TagPrinter {
    /**
     * Print PHP Parser expression.
     *
     * @throws \Exception Throws exception when expression cannot be transformed to string.
     * @param Node $expr PHP Parser expression.
     * @return string
     */
    public function print( Node $expr ) {
        /**
         * Handle Name nodes (e.g., class names, namespaces).
         */
        if ( $expr instanceof Name ) {
            return implode('\\', $expr->parts);
        }

        /**
         * Handle Identifier nodes (e.g., variable, function, method names).
         */
        if ( $expr instanceof Identifier ) {
            return $expr->name;
        }

        // String.
        if ( $expr instanceof \PhpParser\Node\Scalar\String_ ) {
            return $expr->value;
        }

        // Concat.
        if ( $expr instanceof \PhpParser\Node\Expr\BinaryOp\Concat ) {
            return $this->print( $expr->left ) . $this->print( $expr->right );
        }

        // Variable.
        if ( $expr instanceof \PhpParser\Node\Expr\Variable ) {
            return '{$' . $expr->name . '}';
        }

        // Encapsed.
        if ( $expr instanceof \PhpParser\Node\Scalar\Encapsed ) {
            return implode(
                '',
                \array_map(
                    [ $this, 'print' ],
                    $expr->parts
                )
            );
        }

        // Encapsed String Part.
        if ( $expr instanceof \PhpParser\Node\Scalar\EncapsedStringPart ) {
            return $expr->value;
        }

        // Function Call.
        if ( $expr instanceof \PhpParser\Node\Expr\FuncCall ) {
            return '' . $expr->name . '()';
        }

        // Method Call.
        if ( $expr instanceof \PhpParser\Node\Expr\MethodCall ) {
            // Currently not supported.
        }

        // Property Fetch.
        if ( $expr instanceof \PhpParser\Node\Expr\PropertyFetch ) {
            return '{$' . $expr->var->name . '->' . $expr->name . '}';
        }

        // Array Dim Fetch.
        if ( $expr instanceof \PhpParser\Node\Expr\ArrayDimFetch ) {
            $varName = $this->print( $expr->var ); // Print the array variable.
            $dim = $this->print( $expr->dim );     // Print the array index/key.
            return $varName . '[' . $dim . ']';     // Combine them into a string.
        }

        // Class Constant Fetch.
        if ( $expr instanceof \PhpParser\Node\Expr\ClassConstFetch ) {
            $className = $this->print( $expr->class ); // Print the class name.
            $constantName = $this->print( $expr->name ); // Print the constant name.
            return $className . '::' . $constantName; // Combine into class::constant format.
        }

        // Unsupported expression.
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
