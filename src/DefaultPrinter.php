<?php
/**
 * Default Printer
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Documentor
 */

namespace Pronamic\WordPress\Documentor;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * Default Printer
 *
 * @link    https://symfony.com/doc/current/components/console/helpers/table.html
 * @author  Remco Tolsma
 * @version 1.0.0
 * @since   1.0.0
 */
class DefaultPrinter {
	/**
	 * Constrcuct default printer.
	 *
	 * @param Documentor      $documentor Documentor.
	 * @param OutputInterface $output     Output.
	 */
	public function __construct( Documentor $documentor, OutputInterface $output ) {
		$this->documentor = $documentor;
		$this->output     = $output;

		$this->table = new Table( $output );
		$this->table->setHeaders( array( 'File', 'Tag', 'Summary' ) );

		foreach ( $documentor->get_hooks() as $hook ) {
			$filePath = $hook->get_file()->getPathname();
			$tagName = $hook->get_tag()->get_name();
			$summary = $hook->get_summary();

			$echoedLine = "File: $filePath, Tag: $tagName, Summary: $summary";

			foreach ( $hook->get_arguments() as $argument ) {
				$argumentName = $argument->get_name();
				$argumentType = $argument->get_type();
				$argumentDescription = $argument->get_description();

				$echoedLine .= ", Argument: $argumentName $argumentType $argumentDescription";
			}
		
			echo $echoedLine . PHP_EOL;
		
			$this->table->addRow( array( $filePath, $tagName, $summary ) );
			
		}
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	public function render() {
		$this->table->render();
	}
}
