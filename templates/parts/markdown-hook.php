<?php
/**
 * Markdown Hook Template.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Documentor
 */

echo $eol;

$summary = $hook->get_summary();

if ( ! empty( $summary ) ) {
	echo $summary, $eol;
	echo $eol;
}

$description = $hook->get_description();

if ( ! empty( $description ) ) {
	echo $description, $eol;
	echo $eol;
}

$doc_block = $hook->get_doc_block();

if ( ! empty( $doc_block ) ) {
	$param_tags = $doc_block->getTagsByName( 'param' ); 

	echo '**Arguments**', $eol;

	if ( \count( $param_tags ) > 0 ) {
		echo $eol;

		echo 'Argument | Type | Description', $eol;
		echo '-------- | ---- | -----------', $eol;

		foreach ( $param_tags as $param_tag ) {
			$type        = $param_tag->getType();
			$description = $param_tag->getDescription();

			\printf(
				'%s | %s | %s',
				\sprintf( '`%s`', $param_tag->getVariableName() ),
				empty( $type ) ? '' : \sprintf( '`%s`', \addcslashes( $type, '|' ) ),
				strtr(
					( null === $description ) ? '' : \addcslashes( $description, '|' ),
					array(
						"\r\n" => '<br>',
						"\r"   => '<br>',
						"\n"   => '<br>',
					)
				)
			);

			echo $eol;
		}
	} else {
		echo PHP_EOL . 'No arguments.' . PHP_EOL;
	}

	echo $eol;
}

/**
 * Changelog.
 *
 * @link https://developer.wordpress.org/reference/hooks/activated_plugin/#changelog
 * @link https://github.com/phpDocumentor/ReflectionDocBlock/blob/5.2.2/src/DocBlock/Tags/Since.php
 */
$changelog = $hook->get_changelog();

if ( null !== $changelog && \count( $changelog ) > 0 ) {
	echo '**Changelog**', $eol;

	echo $eol;

	echo 'Version | Description', $eol;
	echo '------- | -----------', $eol;

	foreach ( $changelog as $item ) {
		\printf(
			'%s | %s',
			\sprintf( '`%s`', $item->get_version() ),
			$item->get_description()
		);

		echo $eol;
	}

	echo $eol;
}
