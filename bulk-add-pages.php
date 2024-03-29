<?php
/*
Plugin Name: Bulk Add Pages
Plugin URI: https://github.com/jacobbuck/wp-bulk-add-pages
Description: Quickly add multiple pages at once.
Version: 1.1
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
				echo '<div class="updated" id="message"><p>';
				switch ( $result ) {
					case 0:
						_e('No pages created.');
						break;
					case 1:
						_e('1 page created.');
						break;
					default:
						echo str_replace( '%' , number_format_i18n( $result ),  __('% pages created.') );
						break;
				}
				echo '</p></div>';
			}
			?>
			<form action="<?php echo admin_url('edit.php?post_type=page&page=bulk-add-pages'); ?>" method="post">
				<?php wp_nonce_field( plugin_basename( __FILE__ ), 'bap_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th class="row"><strong><label for="bap_titles"><?php _e('Titles'); ?></label></strong></th>
						<td>
							<p class="description"><?php _e('One title per line. Empty lines will be ignored.'); ?></p>
							<textarea name="bap_titles" rows="10" cols="50" id="bap_titles" class="large-text" style="resize:vertical"></textarea>
						</td>
					</tr>
					<tr>
						<th class="row"><strong><label for="bap_author"><?php _e('Author'); ?></label></strong></th>
						<td>
							<?php
							global $user_ID;
							wp_dropdown_users( array(
								'who'              => 'authors',
								'name'             => 'bap_author',
								'selected'         => $user_ID,
								'include_selected' => true
							) );
							?>
						</td>
					</tr>
					<tr>
						<th class="row"><strong><label for="bap_parent"><?php _e('Parent'); ?></label></strong></th>
						<td>
							<?php
							wp_dropdown_pages( array(
								'post_type'        => 'page',
								'name'             => 'bap_parent',
								'show_option_none' => __('(no parent)'),
								'sort_column'      => 'menu_order, post_title',
								'post_status'      => array( 'publish', 'pending', 'draft', 'private', 'future' )
							) );
							?>
						</td>
					</tr>
					<tr>
						<th class="row"><strong><label for="bap_status"><?php _e('Status'); ?></label></strong></th>
						<td>
							<select name="bap_status" id="bap_status">
								<option value="publish"><?php _e('Published'); ?></option>
								<option value="private"><?php _e('Private') ?></option>
								<option value="pending"><?php _e( 'Pending Review' ); ?></option>
								<option value="draft"><?php _e('Draft'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th class="row"><strong><label for="bap_template"><?php _e('Template'); ?></label></strong></th>
						<td>
							<select name="bap_template" id="bap_template">
								<option value="default"><?php _e('Default Template'); ?></option>
								<?php page_template_dropdown(); ?>
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

		global $user_ID;

		// Nonce Validation
		if ( empty( $_POST['bap_nonce'] ) || ! wp_verify_nonce( $_POST['bap_nonce'], plugin_basename( __FILE__ ) ) )
			return;

		// Check if user is allowed
		if ( ! current_user_can('publish_pages') )
			return;

		$posts = array();

		if ( ! empty( $_POST['bap_titles'] ) ) {

			// Get titles
			$titles = explode( "\r\n", $_POST['bap_titles'] );

			// Get options
			$author   = empty( $_POST['bap_author'] )   ? $user_ID  : $_POST['bap_author'];
			$parent   = empty( $_POST['bap_parent'] )   ? 0         : $_POST['bap_parent'];
			$status   = empty( $_POST['bap_status'] )   ? 'publish' : $_POST['bap_status'];
			$template = empty( $_POST['bap_template'] ) ? 'default' : $_POST['bap_template'];

			// Insert each page
			foreach ( (array) $titles as $key => $title ) {

				if ( ! $title = trim( $title ) )
					continue;

				$post_data = array(
					'post_author' => $author,
					'post_parent' => $parent,
					'post_status' => $status,
					'post_title'  => $title,
					'post_type'   => 'page'
				);

				$post_id = wp_insert_post( apply_filters( 'bap_insert_post', $post_data ) );

				if ( 0 !== $post_id ) {

					array_push( $posts, $post_id );

					if ( 'default' !== $template )
						add_post_meta( $post_id, '_wp_page_template', $template );

				}

			}

			// Redirect once done
			wp_redirect( admin_url( '/edit.php?post_type=page&page=bulk-add-pages&bap_result=' . count( $posts ) ), 302 );
			exit;

		}

	}

}

$bulk_add_pages = new Bulk_Add_Pages;