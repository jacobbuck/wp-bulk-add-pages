<?php
/*
Plugin Name: Bulk Add Pages
Plugin URI: https://github.com/jacobbuck/wp-bulk-add-pages
Description: Quickly add multiple pages at once.
Version: 1.0
Author: Jacob Buck
Author URI: http://jacobbuck.co.nz/
*/

class Bulk_Add_Pages {

	function __construct () {

		add_action( 'admin_init', array( &$this, 'save_pages' ) );
		add_action( 'admin_menu', array( &$this, 'add_admin_menu_pages' ) );

	}

	function add_admin_menu_pages () {

		add_pages_page(
			__('Bulk Add Pages'),
			__('Bulk Add New'),
			'publish_pages',
			'bulk-add-pages',
			array( &$this, 'management_page_cb' )
		);

	}

	function management_page_cb () {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e('Bulk Add Pages'); ?></h2>
			<?php
			if ( isset( $_GET['bap_result'] ) ) {
				$result = intval( $_GET['bap_result'] );
				echo '<div class="updated settings-error" id="setting-error-settings_updated"><p><strong>';
				switch ( $result ) {
					case 0:
						echo __('No pages created');
						break;
					case 1:
						echo __('1 page created');
						break;
					default:
						echo str_replace( '%' , number_format_i18n( $result ),  __('% pages created') );
						break;
				}
				echo '</strong></p></div>';
			}
			?>
			<form action="<?php echo admin_url('/edit.php?post_type=page&page=bulk-add-pages'); ?>" method="post">
				<?php wp_nonce_field( plugin_basename( __FILE__ ), 'bap_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th class="row"><label for="bap_titles">Titles</label></th>
						<td>
							<p class="description"><?php _e('One title per line. Empty lines will be ignored.'); ?></p>
							<textarea name="bap_titles" rows="10" cols="50" id="bap_titles" class="large-text" style="resize:vertical"></textarea>
						</td>
					</tr>
					<tr>
						<th class="row"><label for="bap_parent_id">Parent</label></th>
						<td>
							<?php
							wp_dropdown_pages( array(
								'post_type'        => 'page',
								'name'             => 'bap_parent_id',
								'show_option_none' => __('(no parent)'),
								'sort_column'      => 'menu_order, post_title',
								'post_status'      => array( 'publish', 'pending', 'draft', 'private', 'future' )
							) );
							?>
						</td>
					</tr>
					<tr>
						<th class="row"><label for="bap_status">Status</label></th>
						<td>
							<select name="bap_status" id="bap_status">
								<option value="publish"><?php _e('Published'); ?></option>
								<option value="private"><?php _e('Private') ?></option>
								<option value="pending"><?php _e( 'Pending Review' ); ?></option>
								<option value="draft"><?php _e('Draft'); ?></option>
							</select>
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" name="bap_submit" id="bap_submit" class="button-primary" value="<?php _e('Add New Pages'); ?>"></p>
			</form>
		</div>
		<?php
	}

	function save_pages () {

		// Nonce Validation
		if ( empty( $_POST['bap_nonce'] ) || ! wp_verify_nonce( $_POST['bap_nonce'], plugin_basename( __FILE__ ) ) )
			return;

		// Check if user is allowed
		if ( ! current_user_can( 'import' ) )
			return;

		// Let's do this thing!
		if ( ! empty( $_POST['bap_titles'] ) ) {

			$success = 0;

			// Get titles
			$titles = explode( "\r\n", $_POST['bap_titles'] );

			// Get options
			$args = array(
				'parent' => empty( $_POST['bap_parent_id'] ) ? 0         : $_POST['bap_parent_id'],
				'status' => empty( $_POST['bap_status'] )    ? 'publish' : $_POST['bap_status']
			);

			// Insert each page
			foreach ( (array) $titles as $key => $title ) {

				if ( ! $title = trim( $title ) )
					continue;

				$post = array(
					'post_parent' => $args['parent'],
					'post_status' => $args['status'],
					'post_title'  => $title,
					'post_type'   => 'page'
				);

				$post = apply_filters( 'bap_insert_post', $post, $title, $args );

				if ( wp_insert_post( $post ) )
					$success += 1;

			}

			// Redirect once done
			wp_redirect( admin_url( '/edit.php?post_type=page&page=bulk-add-pages&bap_result=' . $success ) );
			exit;

		}

	}

}

$bulk_add_pages = new Bulk_Add_Pages;