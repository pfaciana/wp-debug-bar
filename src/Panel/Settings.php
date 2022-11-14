<?php

namespace DebugBar\Panel;

class Settings extends \Debug_Bar_Panel
{
	public $_visible = TRUE;
	public $_icon = 'dashicons-admin-settings';
	protected $_can_disable = FALSE;

	public $_title = 'Setting';
	public $_submenu = [
		[
			'title'      => 'Enable All Panels',
			'capability' => 'read',
			'attrs'      => [
				'class'             => 'rwd-debug-panels-activate-all',
				'data-activate-all' => TRUE,
			],
		],
		[
			'title'      => 'Disable All Panels',
			'capability' => 'read',
			'attrs'      => [
				'class'             => 'rwd-debug-panels-activate-all',
				'data-activate-all' => FALSE,
			],
		],
		[
			'title' => 'Clear UI Settings',
			'attrs' => 'rwd-debug-clear-ui-settings',
		],
	];

	public function init ()
	{
		add_filter( 'debug_bar_enable', [ $this, 'debug_bar_enable' ], 10, 6 );
		add_action( 'wp_ajax_rwd_debug_bar_panels_status', [ $this, 'rwd_debug_bar_panels_status' ] );
		add_action( 'wp_ajax_rwd_debug_bar_panels_min_role', [ $this, 'rwd_debug_bar_panels_min_role' ] );
	}

	public function debug_bar_enable1 ( $enable, $is_admin_bar_showing, $is_user_logged_in, $is_super_admin, $doing_ajax, $is_wp_login )
	{
		if ( ( $is_admin_bar_showing || $doing_ajax ) && !$is_user_logged_in && !$is_wp_login ) {
			return TRUE;
		}

		return $enable;
	}

	public function debug_bar_enable ( $enable )
	{
		return $enable;
	}

	public function render ()
	{
		?>
		<h3 class="panel-header">Settings Page</h3>

		<h4>User Actions</h4>

		<ul>
			<?php if ( current_user_can( 'manage_options' ) ) :
				$min_user_role = get_option( 'rwd_debug_bar_min_role', 'edit_posts' ); ?>
				<li>
					<label for="min_user_role"><b>Minimum User Capability to view DebugBar (by user role)</b></label>
					<br>
					<select id="min_user_role" name="user_role">
						<?php foreach ( $this->user_roles as $capability => $role ) : ?>
							<option <?php selected( $min_user_role, $capability ); ?> value="<?= $capability ?>"><?= $role ?> (<?= $capability ?>)</option>
						<?php endforeach; ?>
					</select>
					<hr>
				</li>
			<?php endif; ?>
			<?php if ( current_user_can( 'read' ) ) : ?>
				<li>
					<button class="rwd-debug-panels-activate-all" data-activate-all="1">Enable All Panels</button>
				</li>
				<li>
					<button class="rwd-debug-panels-activate-all" data-activate-all="0">Disable All Panels</button>
				</li>
			<?php endif; ?>
			<li>
				<button class="rwd-debug-clear-ui-settings">Clear UI Settings</button>
			</li>
		</ul>
		<?php
		$this->render_js();
	}

	protected function render_js ()
	{
		?>
		<script>
			(function ($, window, document, undefined) {
				$(function () {
					var ajaxUrl = '<?= admin_url( 'admin-ajax.php' ) ?>';

					function getAllPanels() {
						var panels = [];

						$('.rwd-debug-menu-link').each(function () {
							panels.push($(this).closest('[data-panel]').data('panel'));
						});

						return panels;
					}

					$('#min_user_role').on('change', function () {
						var $this = $(this);

						$.ajax(ajaxUrl, {
							method: 'POST',
							data: {
								action: 'rwd_debug_bar_panels_min_role',
								user_role: $this.val(),
							},
							success: function (response) {
								console.log(response);
							}
						});
						return false;
					});

					$('.rwd-debug-panels-activate-all').on('click', function () {
						var $this = $(this), activate = $this.data('activate-all');

						$.ajax(ajaxUrl, {
							method: 'POST',
							data: {
								action: 'rwd_debug_bar_panels_status',
								activate: activate,
								panels: (activate == '1' ? 'all' : getAllPanels())
							},
							success: function (response) {
								var active = response == '1';
								$('.rwd-debug-bar-side-menu .rwd-debug-panel-action').attr('data-activate', active ? 0 : 1);
								$('.rwd-debug-bar-side-menu .rwd-debug-panel-action').find('i.fa').toggleClass('fa-toggle-off', !active);
								$('.rwd-debug-bar-side-menu .rwd-debug-panel-action').find('i.fa').toggleClass('fa-toggle-on', active);
							}
						});
						return false;
					});

					$('.rwd-debug-clear-ui-settings').on('click', function () {
						var isAdmin = $('body').hasClass('wp-admin'),
							hiddenKey = isAdmin ? 'rwdDebugBarAdminHidden' : 'rwdDebugBarHidden',
							keys = [
								'rwdDebugBarTop',
								'rwdDebugBarLeft',
								'rwdDebugBarPosition',
								'rwdDebugBarPanel',
								'rwdDebugBarState',
								'rwdDebugBarHidden',
								'rwdDebugBarAdminHidden',
							];

						$.each(keys, function (key, value) {
							localStorage.removeItem(value);
						});

						localStorage[hiddenKey] = '0';

						return false;
					});

				});
			}(jQuery, window, document));
		</script>
		<?php
	}

	public function rwd_debug_bar_panels_status ()
	{
		if ( !isset( $_REQUEST['activate'] ) || empty( $_REQUEST['panels'] ) ) {
			http_response_code( 400 );
			wp_die();
		}

		if ( !is_user_logged_in() ) {
			echo 'You are a guest, you must log in first!';
			wp_die();
		}

		$user_id  = get_current_user_id();
		$activate = !!$_REQUEST['activate'];
		$panels   = is_array( $_REQUEST['panels'] ) ? $_REQUEST['panels'] : (array) filter_var( $_REQUEST['panels'], FILTER_SANITIZE_STRING );

		$disabled_panels = json_decode( get_user_meta( $user_id, 'rwd_debug_bar_disabled_panels', TRUE ) ?: '[]' );

		$disabled_panels = array_unique( $activate ? ( $panels === [ 'all' ] ? [] : array_diff( $disabled_panels, $panels ) ) : array_merge( $disabled_panels, $panels ) );

		update_user_meta( $user_id, 'rwd_debug_bar_disabled_panels', json_encode( array_values( $disabled_panels ) ) );

		echo $activate ? '1' : '0';
		wp_die();
	}

	protected $user_roles = [
		'all'            => 'Guests',
		'read'           => 'Subscriber',
		'edit_posts'     => 'Contributor',
		'publish_posts'  => 'Author',
		'publish_pages'  => 'Editor',
		'manage_options' => 'Admin',
	];

	public function rwd_debug_bar_panels_min_role ()
	{
		if ( !isset( $_REQUEST['user_role'] ) ) {
			http_response_code( 400 );
			wp_die();
		}

		if ( !is_user_logged_in() || !current_user_can( 'manage_options' ) ) {
			http_response_code( 400 );
			wp_die();
		}

		$role = filter_var( $_REQUEST['user_role'], FILTER_SANITIZE_STRING );

		if ( !in_array( $role, array_keys( $this->user_roles ) ) ) {
			http_response_code( 400 );
			wp_die();
		}

		echo update_option( 'rwd_debug_bar_min_role', $role ) ? '1' : '0';
		wp_die();
	}
}