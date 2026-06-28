<?php
/**
 * Plugin Name:       Reading Time Plus
 * Plugin URI:        https://zubeidhendricks.dev/wp-plugins/reading-time-plus
 * Description:        Add an accurate "X min read" estimate to your posts automatically, or anywhere with a shortcode or block-friendly function.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.2
 * Author:            Zubeid Hendricks
 * Author URI:        https://zubeidhendricks.dev
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       reading-time-plus
 *
 * @package ReadingTimePlus
 */

defined( 'ABSPATH' ) || exit;

define( 'READING_TIME_PLUS_VERSION', '1.0.0' );

require_once __DIR__ . '/includes/factory-core.php';

/**
 * Reading Time Plus.
 */
final class ReadingTimePlus extends ZubFactory_Plugin {

	protected function configure() {
		$this->slug    = 'reading-time-plus';
		$this->title   = 'Reading Time Plus';
		$this->version = READING_TIME_PLUS_VERSION;
	}

	protected function settings_fields() {
		return array(
			'wpm'      => array(
				'label'   => __( 'Reading speed (words per minute)', 'reading-time-plus' ),
				'type'    => 'number',
				'default' => 200,
				'desc'    => __( 'Average adult reading speed is 200–250 wpm.', 'reading-time-plus' ),
			),
			'label'    => array(
				'label'   => __( 'Label', 'reading-time-plus' ),
				'type'    => 'text',
				'default' => '{time} min read',
				'desc'    => __( 'Use {time} for the number of minutes.', 'reading-time-plus' ),
			),
			'position' => array(
				'label'   => __( 'Auto-display', 'reading-time-plus' ),
				'type'    => 'select',
				'options' => array(
					'before' => __( 'Before post content', 'reading-time-plus' ),
					'after'  => __( 'After post content', 'reading-time-plus' ),
					'none'   => __( 'Don’t auto-display (shortcode only)', 'reading-time-plus' ),
				),
				'default' => 'before',
			),
			'types'    => array(
				'label'   => __( 'Show on', 'reading-time-plus' ),
				'type'    => 'select',
				'options' => array(
					'post' => __( 'Posts only', 'reading-time-plus' ),
					'all'  => __( 'Posts and pages', 'reading-time-plus' ),
				),
				'default' => 'post',
			),
			'images'   => array(
				'label'    => __( 'Image time', 'reading-time-plus' ),
				'type'     => 'checkbox',
				'cb_label' => __( 'Add 12s per image to the estimate', 'reading-time-plus' ),
				'pro'      => true,
			),
		);
	}

	protected function hooks() {
		add_shortcode( 'reading_time', array( $this, 'shortcode' ) );
		add_filter( 'the_content', array( $this, 'auto_display' ), 20 );
	}

	/** Compute minutes for a post's content. */
	public function minutes( $content ) {
		$words = str_word_count( wp_strip_all_tags( $content ) );
		$wpm   = max( 50, (int) $this->option( 'wpm', 200 ) );
		$mins  = (int) ceil( $words / $wpm );

		if ( ZubFactory_Upsell::is_pro( $this->slug ) && $this->option( 'images', 0 ) ) {
			$images = substr_count( strtolower( $content ), '<img' );
			$mins  += (int) ceil( ( $images * 12 ) / 60 );
		}
		return max( 1, $mins );
	}

	/** Render the badge text. */
	private function render( $minutes ) {
		$label = $this->option( 'label', '{time} min read' );
		$text  = str_replace( '{time}', $minutes, $label );
		return '<span class="reading-time-plus">' . esc_html( $text ) . '</span>';
	}

	/** [reading_time] shortcode. */
	public function shortcode( $atts ) {
		$post = get_post();
		if ( ! $post ) {
			return '';
		}
		return $this->render( $this->minutes( $post->post_content ) );
	}

	/** Prepend/append to the content automatically. */
	public function auto_display( $content ) {
		$position = $this->option( 'position', 'before' );
		if ( 'none' === $position || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$allowed = 'all' === $this->option( 'types', 'post' ) ? array( 'post', 'page' ) : array( 'post' );
		if ( ! in_array( get_post_type(), $allowed, true ) ) {
			return $content;
		}

		$badge = '<p class="reading-time-plus-wrap">' . $this->render( $this->minutes( $content ) ) . '</p>';

		return 'after' === $position ? $content . $badge : $badge . $content;
	}
}

add_action(
	'plugins_loaded',
	function () {
		( new ReadingTimePlus( __FILE__ ) )->boot();
	}
);
