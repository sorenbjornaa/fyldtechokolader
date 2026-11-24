<?php
/**
 * Maps legacy chocolate fields to the canonical content model.
 *
 * @package FyldtechokoladerCore
 */

namespace Fyldtechokolader\Core\Migration;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

final class LegacyContentMapper {
	/**
	 * @return array{production_year: int, production_date: string|null, shell_slug: string, shell_name: string}
	 */
	public function map( int $post_id, string $legacy_date, string $legacy_shell ): array {
		$date       = $this->map_date( $post_id, $legacy_date );
		$shell_slug = $this->map_shell( $post_id, $legacy_shell );
		$shells     = self::shell_types();

		return array(
			'production_year' => $date['year'],
			'production_date' => $date['date'],
			'shell_slug'      => $shell_slug,
			'shell_name'      => $shells[ $shell_slug ],
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function shell_types(): array {
		return array(
			'hvid'      => 'Hvid chokolade',
			'lys'       => 'Lys/mælkechokolade',
			'moerk'     => 'Mørk chokolade',
			'dulcey'    => 'Dulcey',
			'uden-skal' => 'Uden chokoladeskal',
		);
	}

	/**
	 * @return array{year: int, date: string|null}
	 */
	private function map_date( int $post_id, string $legacy_date ): array {
		$legacy_date = trim( $legacy_date );

		if ( '' === $legacy_date ) {
			$known_years = array(
				109 => 2023,
				458 => 2025,
			);

			if ( isset( $known_years[ $post_id ] ) ) {
				return array(
					'year' => $known_years[ $post_id ],
					'date' => null,
				);
			}

			throw new InvalidArgumentException( 'Produktionsdato mangler for chokolade ' . $post_id . '.' );
		}

		if ( ! preg_match( '/^(20\d{2})(\d{2})(\d{2})$/', $legacy_date, $parts ) ) {
			throw new InvalidArgumentException( 'Ugyldig legacy-dato for chokolade ' . $post_id . '.' );
		}

		$year  = (int) $parts[1];
		$month = (int) $parts[2];
		$day   = (int) $parts[3];

		if ( ! checkdate( $month, $day, $year ) ) {
			throw new InvalidArgumentException( 'Legacy-datoen findes ikke for chokolade ' . $post_id . '.' );
		}

		return array(
			'year' => $year,
			'date' => sprintf( '%04d-%02d-%02d', $year, $month, $day ),
		);
	}

	private function map_shell( int $post_id, string $legacy_shell ): string {
		if ( 56 === $post_id ) {
			return 'moerk';
		}

		if ( 475 === $post_id ) {
			return 'uden-skal';
		}

		$normalized = strtolower( trim( html_entity_decode( $legacy_shell, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );

		if ( str_contains( $normalized, 'mørk' ) ) {
			return 'moerk';
		}

		if ( str_contains( $normalized, 'hvid' ) ) {
			return 'hvid';
		}

		if ( str_contains( $normalized, 'lys' ) || str_contains( $normalized, 'mælke' ) ) {
			return 'lys';
		}

		if ( str_contains( $normalized, 'dulce' ) ) {
			return 'dulcey';
		}

		throw new InvalidArgumentException( 'Ukendt skaltype for chokolade ' . $post_id . ': ' . $legacy_shell );
	}
}
