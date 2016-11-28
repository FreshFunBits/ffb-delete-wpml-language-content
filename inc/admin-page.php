<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create the admin page under "Tools", named "Delete WPML Language Content by FreshFunBits"
 *
 * @author   FreshFunBits
 * @since    0.1
 */
class FFB_Delete_WPML_Admin_Page {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'add_js_file' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );

	}

	public function add_js_file( $hook ) {
		if ( $hook != 'tools_page_ffb-delete-wpml-language-content' ) {
			return;
		}
		wp_enqueue_script( 'ffb_delete_wpml', plugins_url( '/assets/ajax.js', dirname( __FILE__ ) ), array(), '20161127' );

		wp_localize_script( 'ffb_delete_wpml', 'ffb_delete_wpml_obj', array(
			'ajax_loading_img' => plugins_url( '/assets/loading.gif', dirname( __FILE__ ) ),
			// It is common practice to comma after
		) );                // the last array item for easier maintenance

	}

	public function register_admin_page() {
		add_submenu_page(
			'tools.php',
			__( 'Delete WPML Language Content by FreshFunBits', 'freshfunbits' ),
			__( 'Delete WPML Language Content', 'freshfunbits' ),
			'manage_options',
			'ffb-delete-wpml-language-content',
			array( $this, 'admin_page_html' )
		);
	}

	public function admin_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>

        <div class="wrap">
            <h1><?= esc_html( get_admin_page_title() ); ?></h1>

			<?php
			if ( ! FFB_Delete_WPML::is_wpml_installed() ) {
				echo '<div class="message error"><p>';
				_e( 'Yay! WPML has not been installed on your site. You do not need to use this plugin!', 'freshfunbits' );
				echo '</p></div>';
				echo '</div><!-- #wrap ->';

				return;
			}
			?>

            <h2><?php _e( 'Before running the action!!!', 'freshfunbits' ) ?></h2>

            <ol>
                <li><?php _e( 'Make a database backup of your site before proceeding this.', 'freshfunbits' ) ?></li>
                <li><?php _e( 'If your site has so much to-be-deleted content, it is recommended to disable all other plugins.', 'freshfunbits' ) ?></li>
                <li><?php _e( 'Delete or deactive this plugin after finishing this action.', 'freshfunbits' ) ?></li>
            </ol>

            <form name="ffb_delete_language">
                <input type="hidden" id="ffb_nonce" name="ffb_nonce"
                       value="<?php echo wp_create_nonce( 'ffb_delete_language' ) ?>">
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row"><label
                                    for="items_per_batch"><?php _e( 'Items deleted per batch', 'freshfunbits' ) ?></label>
                        </th>
                        <td>
                            <input name="items_per_batch" type="number" step="1" min="1" id="items_per_batch" value="30"
                                   class="small-text"> posts/terms
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label
                                    for="deleted_lang"><?php _e( 'Content language will be deleted', 'freshfunbits' ) ?></label>
                        </th>
                        <td>
                            <select name="deleted_lang" id="deleted_lang">
                                <option value=""
                                        selected="selected"><?php _e( '- Select -', 'freshfunbits' ) ?></option>
								<?php
								foreach ( FFB_Delete_WPML::get_languages() as $language ) {
									echo sprintf( '<option value="%1$s">%1$s</option>', $language );
								}
								?>
                            </select>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <p class="submit"><input type="button" name="submit" id="ffb_submit" class="button button-primary"
                                         value="<?php _e( 'Delete this language content', 'freshfunbits' ); ?>"></p>
                <p class="ffb_status" id="ffb_status"></p>
                <p class="ffb_message" id="ffb_message"></p>
            </form>
        </div><!-- #wrap ->
        <?php
	}

}