<?php

namespace DebugBar\Panel;

class PostTypes extends \Debug_Bar_Panel
{
	use \DebugBar\Traits\FormatTrait;
	use \DebugBar\Traits\LayoutTrait;

	public $_icon = 'dashicons-admin-post';
	public $_panel_id;
	public $_capability = 'edit_others_posts';
	protected $scalarPostTypes = [
		'name'                  => 'string',
		'label'                 => 'string',
		'public'                => 'boolean',
		'hierarchical'          => 'boolean',
		'exclude_from_search'   => 'boolean',
		'publicly_queryable'    => 'boolean',
		'show_ui'               => 'boolean',
		'show_in_menu'          => 'boolean',
		'show_in_nav_menus'     => 'boolean',
		'show_in_admin_bar'     => 'boolean',
		'map_meta_cap'          => 'boolean',
		'has_archive'           => 'boolean',
		'can_export'            => 'boolean',
		'delete_with_user'      => 'boolean',
		'template_lock'         => 'boolean',
		'show_in_rest'          => 'boolean',
		'rewrite'               => 'boolean',
		'rest_base'             => 'string',
		'rest_namespace'        => 'string',
		'rest_controller_class' => 'string',
		'_edit_link'            => 'string',
		'menu_icon'             => 'string',
		'menu_position'         => 'integer',
		'query_var'             => 'string',
		'capability_type'       => 'string',
		'description'           => 'string',
	];

	protected $scalarTaxonomies = [
		'name'                  => 'string',
		'label'                 => 'string',
		'public'                => 'boolean',
		'publicly_queryable'    => 'boolean',
		'hierarchical'          => 'boolean',
		'show_ui'               => 'boolean',
		'show_in_menu'          => 'boolean',
		'show_in_nav_menus'     => 'boolean',
		'show_tagcloud'         => 'boolean',
		'show_in_quick_edit'    => 'boolean',
		'show_admin_column'     => 'boolean',
		'show_in_rest'          => 'boolean',
		'rest_base'             => 'string',
		'rest_namespace'        => 'string',
		'rest_controller_class' => 'string',
		'update_count_callback' => 'string',
		'default_term'          => 'string',
		'query_var'             => 'string',
		'description'           => 'string',
	];

	protected $scalarPostStatuses = [
		'name'                      => 'string',
		'label'                     => 'string',
		'visibility'                => 'list',
		'publicly_queryable'        => 'boolean',
		'exclude_from_search'       => 'boolean',
		'show_in_admin_status_list' => 'boolean',
		'show_in_admin_all_list'    => 'boolean',
		'_builtin'                  => 'boolean',
	];

	protected $scalarImageSizes = [
		'name'   => 'string',
		'width'  => 'integer',
		'height' => 'integer',
		'crop'   => 'boolean',
	];

	protected $ignore = [
		'cap',
		'taxonomies',
		'template',
		'supports',
		'labels',
		'register_meta_box_cb',
		'meta_box_cb',
		'meta_box_sanitize_cb',
		'_builtin',
		'rest_controller',
		'object_type',
		'rewrite',
		'sort',
		'args',
	];

	protected $frozen = [ 'name', 'label' ];

	public function render ()
	{
		$this->addTab( 'Post Types', [ $this, 'getPostTypes' ] );
		$this->addTab( 'Taxonomies', [ $this, 'getTaxonomies' ] );
		$this->addTab( 'Pairings', [ $this, 'getPairings' ] );
		$this->addTab( 'Post Status', [ $this, 'getPostStatuses' ] );
		$this->addTab( 'Image Sizes', [ $this, 'getImageSizes' ] );
		$this->showTabs( $this->_panel_id );
	}

	protected function getPostTypes ()
	{
		global $wp_post_types;

		$postTypes = [];
		foreach ( $wp_post_types as $wp_post_type ) {
			$wp_post_type = (array) $wp_post_type;
			foreach ( $this->ignore as $key ) {
				unset( $wp_post_type[$key] );
			}
			$postTypes[] = $wp_post_type;
		}

		$postTypesConfig = [];
		foreach ( $this->scalarPostTypes as $field => $type ) {
			$postTypeConfig = [ 'title' => str_replace( '_', ' ', $field ), 'field' => $field, ] + $this->tabulatorConfigs[$type];
			if ( in_array( $field, $this->frozen ) ) {
				$postTypeConfig += $this->tabulatorConfigs['frozen'];
			}
			$postTypesConfig[] = $postTypeConfig;
		}

		?>

		<h3>Post Types</h3>
		<div id="post-types-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var postTypes = <?= json_encode( array_values( $postTypes ?? [] ) ) ?>;

				if (postTypes.length) {
					T.Create("#post-types-table", {
						data: postTypes,
						layout: 'fitDataStretch',
						columns: <?= json_encode( $postTypesConfig ?? [] ) ?>,
					});
				}
			});
		</script>
		<?php
	}

	protected function getTaxonomies ()
	{
		global $wp_taxonomies;

		$taxonomies = [];
		foreach ( $wp_taxonomies as $wp_taxonomy ) {
			$wp_taxonomy = (array) $wp_taxonomy;
			foreach ( $this->ignore as $key ) {
				unset( $wp_taxonomy[$key] );
			}
			$taxonomies[] = $wp_taxonomy;
		}

		$taxonomiesConfig = [];
		foreach ( $this->scalarTaxonomies as $field => $type ) {
			$taxonomyConfig = [ 'title' => str_replace( '_', ' ', $field ), 'field' => $field, ] + $this->tabulatorConfigs[$type];
			if ( in_array( $field, $this->frozen ) ) {
				$taxonomyConfig += $this->tabulatorConfigs['frozen'];
			}
			$taxonomiesConfig[] = $taxonomyConfig;
		}
		?>

		<h3>Taxonomies</h3>
		<div id="taxonomies-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var taxonomies = <?= json_encode( array_values( $taxonomies ?? [] ) ) ?>;

				if (taxonomies.length) {
					T.Create("#taxonomies-table", {
						data: taxonomies,
						layout: 'fitDataStretch',
						columns: <?= json_encode( $taxonomiesConfig ?? [] ) ?>,
					});
				}
			});
		</script>
		<?php
	}

	protected function getPairings ()
	{
		global $wp_post_types, $wp_taxonomies;

		$pairings = [];
		foreach ( $wp_post_types as $wp_post_type ) {
			$pairings[$wp_post_type->name] = get_object_taxonomies( $wp_post_type->name );
		}

		$taxonomyPairings        = [];
		$taxonomyPairingsCounter = [];

		foreach ( $wp_taxonomies as $wp_taxonomy ) {
			$pairing = [ 'name' => $wp_taxonomy->name, 'label' => $wp_taxonomy->label ];
			foreach ( $pairings as $post_type => $taxonomies ) {
				if ( $exists = in_array( $wp_taxonomy->name, $taxonomies ) ) {
					if ( !array_key_exists( $post_type, $taxonomyPairingsCounter ) ) {
						$taxonomyPairingsCounter[$post_type] = 0;
					}
					$taxonomyPairingsCounter[$post_type]++;
				}
				$pairing[$post_type] = $exists;
			}
			$taxonomyPairings[] = $pairing;
		}

		arsort( $taxonomyPairingsCounter );
		$taxonomyPairingsConfig = [];
		foreach ( [ 'name' => NULL, 'label' => NULL ] + $taxonomyPairingsCounter as $field => $count ) {
			if ( in_array( $field, $this->frozen ) ) {
				$pairingConfig = [ 'title' => str_replace( '_', ' ', $field ), 'field' => $field, ] + $this->tabulatorConfigs['string'] + $this->tabulatorConfigs['frozen'];
			}
			else {
				$pairingConfig = [ 'title' => $field, 'headerTooltip' => $wp_post_types[$field]->label, 'tooltip' => $wp_post_types[$field]->label, 'field' => $field, ] + $this->tabulatorConfigs['boolean'];
			}
			$taxonomyPairingsConfig[] = $pairingConfig;
		}

		?>

		<h3>Taxonomy Pairings</h3>
		<div id="taxonomy-pairings-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var taxonomyPairings = <?= json_encode( array_values( $taxonomyPairings ?? [] ) ) ?>;

				if (taxonomyPairings.length) {
					T.Create("#taxonomy-pairings-table", {
						data: taxonomyPairings,
						paginationSize: 10,
						columns: <?= json_encode( $taxonomyPairingsConfig ?? [] ) ?>,
					});
				}
			});
		</script>
		<?php

		$postTypePairings        = [];
		$postTypePairingsCounter = [];

		foreach ( $wp_post_types as $wp_post_type ) {
			$pairing = [ 'name' => $wp_post_type->name, 'label' => $wp_post_type->label ];
			foreach ( $wp_taxonomies as $wp_taxonomy ) {
				if ( $exists = in_array( $wp_taxonomy->name, $pairings[$wp_post_type->name] ) ) {
					if ( !array_key_exists( $wp_taxonomy->name, $postTypePairingsCounter ) ) {
						$postTypePairingsCounter[$wp_taxonomy->name] = 0;
					}
					$postTypePairingsCounter[$wp_taxonomy->name]++;
				}
				$pairing[$wp_taxonomy->name] = $exists;
			}
			$postTypePairings[] = $pairing;
		}

		arsort( $postTypePairingsCounter );
		$postTypePairingsConfig = [];
		foreach ( [ 'name' => NULL, 'label' => NULL ] + $postTypePairingsCounter as $field => $count ) {
			if ( in_array( $field, $this->frozen ) ) {
				$pairingConfig = [ 'title' => str_replace( '_', ' ', $field ), 'field' => $field, ] + $this->tabulatorConfigs['string'] + $this->tabulatorConfigs['frozen'];
			}
			else {
				$pairingConfig = [ 'title' => $field, 'headerTooltip' => $wp_taxonomies[$field]->label, 'tooltip' => $wp_taxonomies[$field]->label, 'field' => $field, ] + $this->tabulatorConfigs['boolean'];
			}
			$postTypePairingsConfig[] = $pairingConfig;
		}

		?>

		<h3>Post Type Pairings</h3>
		<div id="post-type-pairings-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var postTypePairings = <?= json_encode( array_values( $postTypePairings ?? [] ) ) ?>;

				if (postTypePairings.length) {
					T.Create("#post-type-pairings-table", {
						data: postTypePairings,
						paginationSize: 10,
						columns: <?= json_encode( $postTypePairingsConfig ?? [] ) ?>,
					});
				}
			});
		</script>
		<?php
	}

	protected function getPostStatuses ()
	{
		global $wp_post_statuses;

		$visibilities = [ 'public', 'protected', 'private', 'internal' ];

		$postStatuses = [];
		foreach ( $wp_post_statuses as $wp_post_status ) {
			$postStatus = [];

			foreach ( $this->scalarPostStatuses as $field => $type ) {
				if ( $field === 'visibility' ) {
					foreach ( $visibilities as $visibility ) {
						if ( $wp_post_status->$visibility ) {
							$postStatus[$field] = $visibility;
							break;
						}
					}
				}
				else {
					$postStatus[$field] = $wp_post_status->$field;
				}
			}
			$postStatuses[] = $postStatus;
		}

		$pairingsConfig = [];
		foreach ( $this->scalarPostStatuses as $field => $type ) {
			$pairingConfig = [ 'title' => str_replace( '_', ' ', $field ), 'field' => $field, ] + ( $type === 'boolean' ? [ 'width' => 130 ] : [] ) + $this->tabulatorConfigs[$type];
			if ( in_array( $field, $this->frozen ) ) {
				$pairingConfig += $this->tabulatorConfigs['frozen'];
			}
			$pairingsConfig[] = $pairingConfig;
		}

		?>

		<h3>Post Statuses</h3>
		<div id="post-status-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var postStatuses = <?= json_encode( array_values( $postStatuses ?? [] ) ) ?>;

				if (postStatuses.length) {
					T.Create("#post-status-table", {
						data: postStatuses,
						columns: <?= json_encode( $pairingsConfig ?? [] ) ?>,
					});
				}
			});
		</script>
		<?php
	}

	protected function getImageSizes ()
	{
		$sizes = wp_get_registered_image_subsizes();

		$imageSizes = [];
		foreach ( $sizes as $name => $size ) {
			$imageSize = [];
			foreach ( $this->scalarImageSizes as $field => $type ) {
				if ( $field === 'name' ) {
					$imageSize[$field] = $name;
				}
				else {
					$imageSize[$field] = $size[$field];
				}
			}
			$imageSizes[] = $imageSize;
		}

		$imageSizesConfig = [];
		foreach ( $this->scalarImageSizes as $field => $type ) {
			$imageSizeConfig = [ 'title' => str_replace( '_', ' ', $field ), 'field' => $field, ] + $this->tabulatorConfigs[$type];
			if ( in_array( $field, $this->frozen ) ) {
				$imageSizeConfig += $this->tabulatorConfigs['frozen'];
			}
			$imageSizesConfig[] = $imageSizeConfig;
		}

		?>

		<h3>Image Sizes</h3>
		<div id="image-sizes-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var imageSizes = <?= json_encode( array_values( $imageSizes ?? [] ) ) ?>;

				if (imageSizes.length) {
					T.Create("#image-sizes-table", {
						data: imageSizes,
						columns: <?= json_encode( $imageSizesConfig ?? [] ) ?>,
					});
				}
			});
		</script>
		<?php
	}
}