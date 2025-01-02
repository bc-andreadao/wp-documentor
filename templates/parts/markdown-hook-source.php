<?php
/**
 * Markdown Hook Source Template - Shows only the source location of a hook.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Documentor
 */

printf(
    'Source: %s, %s',
    \sprintf(
        '[%s](%s)',
        $hook->get_file()->getPathname(),
        $documentor->relative($hook->get_file())
    ),
    \sprintf(
        '[line %s](%s)',
        $hook->get_start_line(),
        \sprintf(
            '%s#L%d-L%d',
            $documentor->relative($hook->get_file()),
            $hook->get_start_line(),
            $hook->get_end_line()
        )
    )
);

echo $eol;
echo $eol; 