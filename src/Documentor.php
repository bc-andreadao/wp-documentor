<?php
/**
 * Documentor
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Documentor
 */

namespace Pronamic\WordPress\Documentor;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\NodeConnectingVisitor;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Documentor
 *
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class Documentor {
	/**
	 * Hooks.
	 *
	 * @var Hook[]
	 */
	private $hooks;

	/**
	 * Prefixes.
	 *
	 * @var string[]
	 */
	public $prefixes;

	/**
	 * Document type 'actions' or 'filters'.
	 * 
	 * @var string
	 */
	public $type;

	/**
	 * Relative path.
	 * 
	 * @var string
	 */
	public $relative;

	/**
	 * Construct documentor.
	 */
	public function __construct() {
		$this->hooks    = array();
		$this->prefixes = array();
	}

	/**
	 * Get hooks.
	 *
	 * @return array
	 */
	public function get_hooks() {
		return $this->hooks;
	}

	/**
	 * Get actions.
	 *
	 * @return array
	 */
	public function get_actions() {
		return \array_filter(
			$this->hooks,
			function( $hook ) {
				return $hook->is_action();
			}
		);
	}

	/**
	 * Get filters.
	 *
	 * @return array
	 */
	public function get_filters() {
		return \array_filter(
			$this->hooks,
			function( $hook ) {
				return $hook->is_filter();
			}
		);
	}

	/**
	 * Get relative path.
	 *
	 * @param \SplFileInfo $file File.
	 * @return string
	 */
	public function relative( \SplFileInfo $file ) {
		$filesystem = new Filesystem();

		$end   = $file;
		$start = new \SplFileInfo( $this->relative );

		return \trim( $filesystem->makePathRelative( $end->getRealPath(), $start->getRealPath() ), '/' );
	}

	/**
	 * Check if the specified tag name should be parsed.
	 *
	 * @param string $name Tag name.
	 * @return bool True if should parse, false otherwise.
	 */
	private function should_parse_tag( $name ) {
		if ( 0 === \count( $this->prefixes ) ) {
			return true;
		}

		foreach ( $this->prefixes as $prefix ) {
			if ( \str_starts_with( $name, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get statement doc document.
	 * 
	 * @param Node $statement Statement.
	 * @return string|null
	 */
	private function get_statement_doc_comment( Node $statement ) {
		$doc_comment = $statement->getDocComment();

		if ( null !== $doc_comment ) {
			return $doc_comment;
		}

		$parent = $statement->getAttribute( 'parent' );

		/**
		 * Only check parent if it's an assignment, cast, or direct statement
		 * containing the hook call - not function definitions or other structures
		 */
		if ( 
			/**
			 * This handles a assignment before a hook function call:
			 * 
			 * ```php
			 * $some_variable = apply_filters( 'my_plugin_filter_1', $first_param, $second_param );
			 * ```
			 * 
			 * @link https://github.com/pronamic/wp-documentor/issues/11
			 */
			 $parent instanceof \PhpParser\Node\Expr\Assign ||

			/**
			 * This handles a cast before a hook function call:
			 * 
			 * ```php
			 * $should_do = (bool) apply_filters( 'should_we_do_it', true, $some_value );
			 * ```
			 * 
			 * @link https://github.com/pronamic/wp-documentor/issues/18
			 */ 
			 $parent instanceof \PhpParser\Node\Expr\Cast ||

			/**
			 * This handles a hook function call in a `if` statement (`\PhpParser\Node\Stmt\If_`):
			 * 
			 * ```php
			 * if ( (bool) apply_filters( 'some_condition_filter', $some_condition, $some_other_parameter ) )
			 * ```
			 * 
			 * And also a `return` statement (`PhpParser\Node\Stmt\Return_`):
			 * 
			 * ```php
			 * return apply_filters( 'test_issue_13_file_exclude_2', $first_param, $second_param );
			 * ```
			 */
			 $parent instanceof \PhpParser\Node\Stmt\If_ ||
			 $parent instanceof \PhpParser\Node\Stmt\Return_ ) {
			return $this->get_statement_doc_comment( $parent );
		}

		return null;
	}

	/**
	 * Parse.
	 *
	 * @throws \Exception Throws exception when parsing fails.
	 * @param \SplFileInfo $file File.
	 */
	public function parse( $file ) {
		$parser_factory = new ParserFactory();

		$parser = $parser_factory->create( ParserFactory::PREFER_PHP7 );

		$node_finder = new NodeFinder();

		$traverser = new NodeTraverser();

		$traverser->addVisitor( new NodeConnectingVisitor() );
		$traverser->addVisitor( new NamespaceResolver() );

		$tag_printer = new TagPrinter();

		$changelog_factory = new ChangelogFactory();

		/**
		 * File.
		 */
		$contents = $file->getContents();

		$statements = $parser->parse( $contents );

		$statements = $traverser->traverse( $statements );

		$statements = $node_finder->find(
			$statements,
			function( Node $node ) {
				if ( ! $node instanceof Node\Expr\FuncCall ) {
					return false;
				}

				/**
				 * Function call can be a name or an expression, for example: `$callback()`.
				 *
				 * @link https://github.com/nikic/PHP-Parser/blob/v4.10.4/lib/PhpParser/Node/Expr/FuncCall.php#L10-L11
				 */
				if ( ! $node->name instanceof Node\Name ) {
					return false;
				}

				return \in_array(
					\strval( $node->name ),
					array(
						'apply_filters',
						'apply_filters_ref_array',
						'apply_filters_deprecated',
						'do_action',
						'do_action_ref_array',
						'do_action_deprecated',
					),
					true
				);
			}
		);

		foreach ( $statements as $statement ) {
			$tag_arg = \array_shift( $statement->args );

			if ( null === $tag_arg ) {
				throw new \Exception( 'Tag argument missing from hook call.' );
			}

			$doc_comment = $this->get_statement_doc_comment( $statement );

			$doc_block = null;

			if ( null !== $doc_comment ) {
				$doc_block_factory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();

				$context_factory = new \phpDocumentor\Reflection\Types\ContextFactory();

				$context = $context_factory->createForNamespace( \strval( $statement->getAttribute( 'namespace' ) ), $file->getContents() );

				$doc_block = $doc_block_factory->create( (string) $doc_comment, $context );

				if ( $doc_block->hasTag( 'ignore' ) ) {
					continue;
				}
			}

			try {
				$tag_name = $tag_printer->print( $tag_arg->value );
			} catch ( \Exception $exception ) {
				throw new \Exception(
					\sprintf(
						'Could not convert tag argument value to a name in %s.',
						$file . '#' . $statement->getStartLine()
					),
					0,
					$exception
				);
			}

			if ( ! $this->should_parse_tag( $tag_name ) ) {
				continue;
			}

			$tag = new Tag( $tag_name, $tag_arg );

			$arguments = array();

			foreach ( $statement->args as $arg ) {
				$argument = new Argument( $arg );

				$arguments[] = $argument;
			}

			$hook = new Hook( $file, $statement, $tag, $arguments );

			$hook->set_doc_comment( $doc_comment );
			$hook->set_doc_block( $doc_block );

			if ( null !== $doc_block ) {
				$hook->set_changelog( $changelog_factory->create( $doc_block ) );

				foreach ( $hook->get_arguments() as $argument ) {
					$arg = $argument->get_php_parser_argument();

					$param_tags = \array_filter(
						$doc_block->getTagsByName( 'param' ),
						function( $tag ) use ( $arg ) {
							/**
							 * Documentor can only match named expression to a tag, currently no support for:
							 *
							 * ```php
							 * do_action_ref_array( $hook, $v['args'] );
							 * ```
							 *
							 * @link https://github.com/WordPress/WordPress/blob/5.7/wp-cron.php#L129-L138
							 * @link https://github.com/nikic/PHP-Parser/blob/v4.10.4/lib/PhpParser/Node/Expr/ArrayDimFetch.php
							 * @link https://github.com/nikic/PHP-Parser/blob/v4.10.4/lib/PhpParser/Node/Expr/Variable.php#L9-L10
							 */
							if ( ! \property_exists( $arg->value, 'name' ) ) {
								return false;
							}

							return $tag->getVariableName() === $arg->value->name;
						}
					);
					
					$param_tag = \reset( $param_tags );

					if ( false !== $param_tag ) {
						$argument->set_param_tag( $param_tag );
					}
				}
			}

			$this->hooks[] = $hook;
		}
	}
}
