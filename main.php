<?php
/*
 * Plugin Name: Delete WPML Language Content by FreshFunBits
 * Plugin URI: https://github.com/FreshFunBits/ffb-delete-wpml-language-content
 * Description: This small plugin removes any language content on your WPML site. It is useful when you no longer want the content of a specific language.
 * Author: FreshFunBits
 * Author URI: http://freshfunbits.com
 * Text Domain: freshfunbits
 * Domain Path: /languages
 * Version: 0.2
 * License:     GPLv2+
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The main class of the plugin
 *
 * @author   FreshFunBits
 * @since    0.1
 */
class FFB_Delete_WPML {

	/**
	 * Setup class.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		add_action( 'wp_ajax_ffb_delete_language', array( $this, 'run_deleting_ajax_action' ) );
		add_action( 'init', array( $this, 'i18n' ) );
	}

	/**
	 * Localize the plugin
	 * @since 0.1
	 */
	public function i18n() {
		load_plugin_textdomain( 'freshfunbits', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Run the deleting action
	 * @since 0.1
	 */
	public function run_deleting_ajax_action() {

		$return['next_batch'] = false;

		if ( ! ( isset( $_POST['ffb_nonce'] ) && wp_verify_nonce( $_POST['ffb_nonce'], 'ffb_delete_language' ) ) ) :
			$return ['err_message'] = __( 'Wrong nonce! Please refresh this page!', 'freshfunbits' );

		elseif ( ! self::is_wpml_installed() ) :
			$return ['err_message'] = __( 'Yay! WPML has not been installed on your site. You do not need to use this plugin!', 'freshfunbits' );

		elseif ( ! isset( $_POST['deleted_lang'] ) ):
			$return ['err_message'] = __( 'Please select a language!', 'freshfunbits' );

		else :
			$deleted_lang = $_POST['deleted_lang'];

			$items_per_batch = 30;
			if ( isset( $_POST['items_per_batch'] ) ) {
				$items_per_batch = (int) $_POST['items_per_batch'];
			}

			if ( in_array( $deleted_lang, $this->get_languages() ) ) {

				$this->delete_elements( $deleted_lang, $items_per_batch );

				$remaining_rows = $this->count_icl_translations();

				$message = sprintf( __( '%1$d remaining elements of the "%2$s" language...', 'freshfunbits' ), $remaining_rows, $deleted_lang );

				$return['message'] = $message;

				if ( $remaining_rows > 0 ) {
					$return['next_batch'] = true;
				}


			} else {

				$return ['err_message'] = __( 'Please select the correct language. The selected language does not exist.', 'freshfunbits' );
			}

		endif;

		wp_send_json( $return );

	}

	/**
	 * Check if WPML is installed
	 * @return bool
	 * @since 0.1
	 */
	public static function is_wpml_installed() {
		global $wpdb;
		$table = $wpdb->prefix . 'icl_translations';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) == $table ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get all languages of content
	 *
	 * @return array
	 * @since 0.1
	 */
	public static function get_languages() {

		global $wpdb;
		$table = $wpdb->prefix . 'icl_translations';

		$results = $wpdb->get_results(
			"
			SELECT DISTINCT language_code
			FROM $table
			",
			ARRAY_A
		);

		$languages = array();

		foreach ( $results as $key => $value ) {
			$languages[] = $value['language_code'];
		}

		return $languages;
	}

	/**
	 * Delete elements (posts and terms)
	 *
	 * @param string $language
	 * @param int $items
	 *
	 * @since 0.1
	 */
	public function delete_elements( $language, $items ) {
		$elements = $this->get_elements( $language, $items );
		foreach ( $elements as $element ) {

			// If the current element is a post
			if ( 'post' == substr( $element->element_type, 0, 4 ) ) {

				$postid       = $element->element_id;
				$force_delete = true; // Delete the post without putting it to the trash

				wp_delete_post( $postid, $force_delete );
			}

			// If the current element is a taxonomy term
			if ( 'tax' == substr( $element->element_type, 0, 3 ) ) {

				$term_taxonomy_id = $element->element_id;
				$term_object      = get_term_by( 'term_taxonomy_id', $term_taxonomy_id );
				$term_id          = $term_object->term_id;

				$taxonomy = substr( $element->element_type, 4 );

				wp_delete_term( $term_id, $taxonomy );

			}

			// Delete this element in the icl_translation table
			$this->delete_icl_translations_row( $element->translation_id );

		}


	}

	/**
	 * Get all elements (posts and terms) of a specific language
	 *
	 * @param string $language
	 * @param int $items
	 *
	 * @return array
	 * @since 0.1
	 */
	public function get_elements( $language, $items = 30 ) {

		global $wpdb;
		$table = $wpdb->prefix . 'icl_translations';

		$elements = $wpdb->get_results(
			"
			SELECT *
			FROM $table
			WHERE language_code = '$language'
			ORDER BY translation_id ASC
			LIMIT $items
			"
		);

		return $elements;

	}

	/**
	 * Delete a translation ID in the icl_translation table
	 *
	 * @param $translation_id
	 *
	 * @since 0.1
	 */
	public function delete_icl_translations_row( $translation_id ) {

		$translation_id = (int) $translation_id;
		global $wpdb;
		$table = $wpdb->prefix . 'icl_translations';

		$sql =
			"
			DELETE 
			FROM $table 
			WHERE translation_id = $translation_id
			";

		$wpdb->query( $sql );

	}

	/**
	 * Count the number of rows in the icl_translations table
	 * @return int
	 * @since 0.1
	 */
	public function count_icl_translations() {

		global $wpdb;
		$table = $wpdb->prefix . 'icl_translations';

		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );

		return (int) $count;

	}

	/* @todo Future Versions */
	// - Done: Change the name to "Delete WPML Language Content by FreshFunBits"
	// - Create the log
	// - Move posts to 'trash' instead of deleting them
	// - Deal with the case the to-be-deleted-language is the original language of posts
}

/*
 * Start the instance
 */
if ( is_admin() ) {

	// Load the main class
	new FFB_Delete_WPML();

	// Load the admin page
	include( 'inc/admin-page.php' );
	new FFB_Delete_WPML_Admin_Page();
}