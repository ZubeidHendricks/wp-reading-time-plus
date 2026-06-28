<?php
/**
 * Uninstall cleanup.
 *
 * @package ReadingTimePlus
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'reading-time-plus_options' );
