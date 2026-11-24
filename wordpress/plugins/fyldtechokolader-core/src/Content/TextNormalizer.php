<?php
/**
 * Plain-text normalization for API values.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Content;

final class TextNormalizer {
	public static function plain( string $value ): string {
		return trim(
			strip_tags(
				html_entity_decode(
					$value,
					ENT_QUOTES | ENT_HTML5,
					'UTF-8'
				)
			)
		);
	}
}
