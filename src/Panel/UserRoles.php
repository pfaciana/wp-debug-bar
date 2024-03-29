<?php

namespace DebugBar\Panel;

class UserRoles extends \Debug_Bar_Panel
{
	use \DebugBar\Traits\FormatTrait;
	use \DebugBar\Traits\LayoutTrait;

	public $_icon = 'dashicons-admin-users';
	public $_panel_id;
	public $_capability = 'edit_others_posts';
	protected $all_capabilities = [];
	protected $roles = [];

	public function render ()
	{
		$this->setCapabilities();
		$this->addTab( 'Roles', [ $this, 'getRoles' ] );
		$this->addTab( 'Capabilities', [ $this, 'getCapabilities' ] );
		$this->showTabs( $this->_panel_id );
	}

	protected function setCapabilities ()
	{
		global $wp_roles;

		$this->all_capabilities = [];
		foreach ( array_reverse( $wp_roles->roles, TRUE ) as $role => $data ) {
			$capabilities = array_keys( $data['capabilities'] );
			rsort( $capabilities );
			foreach ( $capabilities as $capability ) {
				if ( !in_array( $capability, $this->all_capabilities ) ) {
					array_unshift( $this->all_capabilities, $capability );
				}
			}
		}
	}

	protected function getRoles ()
	{
		global $wp_roles;

		foreach ( array_reverse( $wp_roles->roles, TRUE ) as $role => $data ) {
			$role = [ 'name' => $role, 'label' => $data['name'] ];
			foreach ( $this->all_capabilities as $capability ) {
				$role[$capability] = array_key_exists( $capability, $data['capabilities'] ) ? $data['capabilities'][$capability] : FALSE;
			}
			$this->roles[] = $role;
		}

		$rolesConfig = [
			[ 'title' => 'name', 'field' => 'name', ] + $this->tabulatorConfigs['string'] + $this->tabulatorConfigs['frozen'],
			[ 'title' => 'label', 'field' => 'label', ] + $this->tabulatorConfigs['string'] + $this->tabulatorConfigs['frozen'],
		];
		foreach ( $this->all_capabilities as $capability ) {
			$rolesConfig[] = [ 'title' => str_replace( '_', ' ', $capability ), 'field' => $capability ] + $this->tabulatorConfigs['boolean'];
		}

		?>
		<h3>Roles</h3>
		<div id="roles-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var roles = <?= json_encode( array_values( $this->roles ?? [] ) ) ?>;

				if (roles.length) {
					T.Create("#roles-table", {
						data: roles,
						layout: 'fitDataStretch',
						columns: <?= json_encode( $rolesConfig ?? [] ) ?>,
					}, '<?= RWD_DEBUG_BAR_NAMESPACE ?>');
				}
			});
		</script>
		<?php
	}

	protected function getCapabilities ()
	{
		$capabilities = [];
		foreach ( $this->all_capabilities as $capability ) {
			$capabilities[] = [ 'name' => $capability ] + array_column( $this->roles, $capability, 'name' );
		}

		$capabilitiesConfig = [ [ 'title' => 'name', 'field' => 'name', ] + $this->tabulatorConfigs['string'] + $this->tabulatorConfigs['frozen'] ];
		foreach ( array_reverse( $this->roles ) as $role ) {
			$capabilitiesConfig[] = [ 'title' => str_replace( '_', ' ', $role['name'] ), 'field' => $role['name'] ] + $this->tabulatorConfigs['boolean'];
		}

		?>
		<h3>Capabilities</h3>
		<div id="capabilities-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var capabilities = <?= json_encode( array_values( $capabilities ?? [] ) ) ?>;

				if (capabilities.length) {
					T.Create("#capabilities-table", {
						data: capabilities,
						paginationSize: true,
						columns: <?= json_encode( $capabilitiesConfig ?? [] ) ?>,
					}, '<?= RWD_DEBUG_BAR_NAMESPACE ?>');
				}
			});
		</script>
		<?php
	}
}