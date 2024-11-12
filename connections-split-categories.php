<?php
/**
 * An extension for the Connections Business Directory which splits parent categories into their own metaboxes.
 *
 * @package   Connections Business Directory Split Categories
 * @category  Extension
 * @author    Steven A. Zahm
 * @license   GPL-2.0+
 * @link      http://connections-pro.com
 * @copyright 2023 Steven A. Zahm
 *
 * @wordpress-plugin
 * Plugin Name:       Connections Business Directory Split Categories
 * Plugin URI:        http://connections-pro.com
 * Description:       An extension for the Connections Business Directory which splits parent categories into their own metaboxes.
 * Version:           1.1
 * Author:            Steven A. Zahm
 * Author URI:        http://connections-pro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       connections-split-categories
 * Domain Path:       /languages
 */

use Connections_Directory\Content_Blocks\Entry\Categories as Entry_Categories;
use Connections_Directory\Utility\_escape;

if ( ! class_exists( 'Connections_Split_Categories' ) ) {

	final class Connections_Split_Categories {

		const VERSION = '1.1';

		/**
		 * Stores the instance of this class.
		 *
		 * @var $instance Connections_Split_Categories
		 *
		 * @access private
		 * @static
		 * @since  1.0
		 */
		private static $instance;

		/**
		 * A dummy constructor to prevent the class from being loaded more than once.
		 *
		 * @access public
		 * @since  1.0
		 */
		public function __construct() { /* Do nothing here */ }

		/**
		 * The main plugin instance.
		 *
		 * @access  private
		 * @static
		 * @since   1.0
		 * @return object self
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Connections_Split_Categories ) ) {

				self::$instance = new Connections_Split_Categories;

				/*
				 * Register the settings tabs shown on the Settings admin page tabs, sections and fields.
				 */
				//add_filter( 'cn_register_settings_tabs', array( __CLASS__, 'registerSettingsTab' ) );
				add_filter( 'cn_register_settings_sections', array( __CLASS__, 'registerSettingsSections' ) );
				add_filter( 'cn_register_settings_fields', array( __CLASS__, 'registerSettingsFields' ) );

				// Register CSS and JavaScript.
				//add_action( 'init', array( __CLASS__ , 'registerScripts' ) );

				// Register the category metaboxes.
				add_action( 'cn_metabox', array( __CLASS__, 'metabox' ) );

				// Register the callback for the category checklist.
				add_action( 'cn_meta_field-category_checklist', array( __CLASS__, 'field' ), 10, 3 );

				// Add filter to remove the split categories from the Form add-on custom category select.
				add_filter( 'Connections_Directory/Form/Metabox/Category/Options', array( __CLASS__, 'formAddon' ) );

				// Since we're using a custom field, we need to add our own sanitization method.
				add_filter( 'cn_meta_sanitize_field-category_checklist', array( __CLASS__, 'sanitize') );

				// Register the repeatable category select settings field.
				add_action( 'cn_settings_field-split-category-repeatable', array( __CLASS__, 'settingsFieldRepeatableCategory' ), 10, 3 );

				// When exporting the categories, split them into separate columns.
				add_filter( 'cn_csv_export_fields_config', array( __CLASS__, 'csvExportConfig' ) );

				// Remove the split categories from cnOutput::getCategoryBlock().
				add_filter( 'cn_entry_output_category_item', array( __CLASS__, 'removeCategoryItem' ), 10, 6 );

				// Add the content block options to the admin settings page.
				// This is also required so it'll be rendered by $entry->getContentBlock( 'category-id-{$id}' ).
				add_filter( 'cn_content_blocks', array( __CLASS__, 'registerContentBlockOptions') );

				// Register the content block so they can be displayed.
				add_action( 'cn_action_list_before', array( __CLASS__, 'registerContentBlock' ) );
			}

			return self::$instance;
		}

		//public static function registerScripts() {
		//
		//	// If SCRIPT_DEBUG is set and TRUE load the non-minified JS files, otherwise, load the minified files.
		//	$min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
		//
		//	wp_register_script(
		//		'cn-split-categories',
		//		plugin_dir_url( __FILE__ ) . "assets/js/cn-split-categories$min.js",
		//		array( 'jquery' ),
		//		self::VERSION,
		//		TRUE
		//	);
		//}

		/**
		 * Register the settings sections.
		 *
		 * @access private
		 * @since  1.0
		 *
		 * @param  array $sections
		 *
		 * @return array The settings sections options array.
		 */
		public static function registerSettingsSections( $sections ) {

			$sections[] = array(
				'plugin_id' => 'connections_split_categories',
				'tab'       => 'advanced',
				'id'        => 'general',
				'position'  => 5,
				'title'     => __( 'Split Categories', 'connections-split-categories' ),
				'callback'  => '',
				'page_hook' => 'connections_page_connections_settings',
			);

			return $sections;
		}

		/**
		 * Register the settings fields.
		 *
		 * @access private
		 * @since  1.0
		 *
		 * @param array $fields
		 *
		 * @return array The settings fields options array.
		 */
		public static function registerSettingsFields( $fields ) {

			$settings = 'connections_page_connections_settings';

			$fields[] = array(
				'plugin_id' => 'connections_split_categories',
				'id'        => 'display_category_metabox',
				'position'  => 10,
				'page_hook' => $settings,
				'tab'       => 'advanced',
				'section'   => 'general',
				'title'     => __( 'Categories metabox?', 'connections-split-categories' ),
				'desc'      => __( 'Whether or not to display the Categories metabox when adding or editing an entry.', 'connections-split-categories' ),
				'help'      => __( 'Check this option if you wish to hide the display of the Categories metabox.', 'connections-split-categories' ),
				'type'      => 'checkbox',
				'default'   => 1,
			);

			$fields[] = array(
				'plugin_id' => 'connections_split_categories',
				'id'        => 'categories',
				'position'  => 20,
				'page_hook' => $settings,
				'tab'       => 'advanced',
				'section'   => 'general',
				'title'     => __( 'Select', 'connections-split-categories' ),
				'desc'      => __( 'Select the parent categories to split into their own metabox.', 'connections-split-categories' ),
				//'help'      => __( 'Check this option if you wish to hide the display of the Categories metabox.', 'connections-split-categories' ),
				'type'      => 'split-category-repeatable',
				'default'   => array(),
			);

			return $fields;
		}

		/**
		 * @param string $name
		 * @param array  $value
		 * @param array  $field
		 */
		public static function settingsFieldRepeatableCategory( $name, $value, $field ) {

			$html = '';

			if ( isset( $field['desc'] ) && 0 < strlen( $field['desc'] ) ) {

				$html .= sprintf( '<div class="description"> %1$s</div>', esc_html( $field['desc'] ) );
			}

			$html .= cnTemplatePart::walker(
				'term-select-enhanced',
				array(
					'type'               => 'multiselect',
					'enhanced'           => FALSE,
					'default'            => '',
					'placeholder_option' => FALSE,
					'show_select_all'    => FALSE,
					'hide_empty'         => 0,
					'hide_if_empty'      => FALSE,
					'name'               => $name,
					'orderby'            => 'name',
					'taxonomy'           => 'category',
					'selected'           => $value,
					'hierarchical'       => TRUE,
					'return'             => TRUE,
				)
			);

			echo $html;
		}

		/**
		 * Return an array of term objects.
		 *
		 * @access protected
		 * @since  1.0
		 *
		 * @return array
		 */
		protected static function getSplitCategories() {

			// Need to use core WP settings API instead of Connections Settings API due to the latter not being loaded in time.
			$options = get_option( 'connections_split_categories_general', array() );
			$terms   = array();

			if ( is_array( $options ) && array_key_exists( 'categories', $options ) &&
			     is_array( $options['categories'] ) && ! empty( $options['categories'] )
			) {

				foreach ( $options['categories'] as $id ) {

					/** @var cnTerm_Object $term */
					$term = cnTerm::get( $id );

					if ( $term instanceof cnTerm_Object ) {

						$terms[] = $term;
					}
				}
			}

			return $terms;
		}

		/**
		 * Whether or not to display the core Categories metabox.
		 *
		 * @access protected
		 * @since  1.0
		 *
		 * @return bool
		 */
		protected static function getDisplayCategoryMetabox() {

			// Need to use core WP settings API instead of Connections Settings API due to the latter not being loaded in time.
			$options = get_option( 'connections_split_categories_general', array() );
			$display = TRUE;

			if ( is_array( $options ) &&
			     ! ( array_key_exists( 'display_category_metabox', $options ) || '1' == $options['display_category_metabox'] )
			) {

				$display = FALSE;
			}

			return $display;
		}

		/**
		 * Setup the CSV export to split the defined categories into their own column in the CSV file.
		 *
		 * @access protected
		 * @since  1.0
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		public static function csvExportConfig( $fields ) {

			foreach ( self::getSplitCategories() as $term ) {

				$fields[] = array(
					'field'    => 'category',
					'child_of' => $term->term_id,
					'type'     => 6,
					'fields'   => NULL,
					'table'    => CN_TERMS_TABLE,
					'types'    => NULL,
				);
			}

			return $fields;
		}

		/**
		 * Exclude split categories on the Form add-on custom category select.
		 *
		 * @param array $options
		 *
		 * @return array
		 */
		public static function formAddon( $options ) {

			$exclude = array();

			foreach ( self::getSplitCategories() as $term ) {

				$exclude[] = $term->term_id;
			}

			$options['exclude_tree'] = $exclude;

			return $options;
		}

		public static function metabox() {

			$exclude = array();

			foreach ( self::getSplitCategories() as $term ) {

				cnMetaboxAPI::add(
					array(
						'id'       => "category-id-{$term->term_id}",
						'title'    => $term->name,
						'context'  => 'side',
						'priority' => 'core',
						'fields'   => array(
							array(
								'id'         => "category-id-{$term->term_id}",
								'type'       => 'category_checklist',
								'category'   => $term->term_id,
							),
						),
					)
				);

				$exclude[] = $term->term_id;
			}

			if ( ! self::getDisplayCategoryMetabox() ) {

				cnMetaboxAPI::remove( 'categorydiv' );

			} else {

				if ( is_admin() ) {

					$pageHooks = apply_filters( 'cn_admin_default_metabox_page_hooks', array( 'connections_page_connections_add', 'connections_page_connections_manage' ) );

					// Define the core pages and use them by default if no page where defined.
					// Check if doing AJAX because the page hooks are not defined when doing an AJAX request which cause undefined property errors.
					$pages = defined('DOING_AJAX') && DOING_AJAX ? array() : $pageHooks;

				} else {

					$pages = array( 'public' );
				}

				// Remove and add the category metabox excluding the categories which were split out into their own metabox.
				cnMetaboxAPI::remove( 'categorydiv' );
				cnMetaboxAPI::add(
					array(
						'id'           => 'categorydiv',
						'title'        => __( 'Categories', 'connections' ),
						'exclude_tree' => $exclude,
						'pages'        => $pages,
						'context'      => 'side',
						'priority'     => 'core',
						'callback'     => array( 'cnEntryMetabox', 'category' ),
					)
				);
			}

		}

		public static function sanitize( $value ) {

			return $value;
		}

		/**
		 * @param array   $field
		 * @param array   $value
		 * @param cnEntry $entry
		 */
		public static function field( $field, $value, $entry ) {

			$atts = array(
				'child_of' => absint( $field['category'] ),
				'selected' => cnTerm::getRelationships( $entry->getID(), 'category', array( 'fields' => 'ids' ) ),
			);

			?>
			<div class="categorydiv" id="taxonomy-category-<?php echo $atts['child_of'] ; ?>">
				<div id="category-id-<?php echo $atts['child_of'] ; ?>" class="tabs-panel" style="max-height: 300px; overflow-y: scroll;">
					<?php cnTemplatePart::walker( 'term-checklist', $atts ); ?>
				</div>
			</div>
			<?php
		}

		/**
		 * Callback for the `cn_content_blocks` filter.
		 *
		 * Add the custom meta as an option in the content block settings in the admin.
		 * This is required for the output to be rendered by $entry->getContentBlock().
		 *
		 * @internal
		 * @since 1.0
		 * @since 1.1 Register the "As Image Grid" options.
		 *
		 * @param array $blocks An associative array containing the registered content block settings options.
		 *
		 * @return array
		 */
		public static function registerContentBlockOptions( $blocks ) {

			$hasEnhancedCategories = class_exists( 'Connections_Categories' );

			foreach ( self::getSplitCategories() as $term ) {

				$blocks["category-id-{$term->term_id}"] = $term->name;

				if ( $hasEnhancedCategories ) {

					$name = is_admin() ? sprintf( __( '%s as Image Grid', 'connections-split-categories' ), $term->name ) : $term->name;

					$blocks["category-id-{$term->term_id}-as-image-grid"] = $name;
				}
			}

			return $blocks;
		}

		/**
		 * Callback for the `cn_action_list_before` filter.
		 *
		 * Add the action that'll be run when calling $entry->getContentBlock( 'category-id-{$term->term_id}' )
		 * from within a template.
		 *
		 * @internal
		 * @since 1.0
		 * @since 1.1 Register the "As Image Grid" action hooks.
		 */
		public static function registerContentBlock() {

			$hasEnhancedCategories = class_exists( 'Connections_Categories' );

			foreach ( self::getSplitCategories() as $term ) {

				add_action( "cn_entry_output_content-category-id-{$term->term_id}", array( __CLASS__, 'contentBlock' ), 10, 3 );

				if ( $hasEnhancedCategories ) {

					add_action(
						"cn_entry_output_content-category-id-{$term->term_id}-as-image-grid",
						array(
							__CLASS__,
							'contentBlockAsImageGrid',
						),
						10,
						3
					);

				}
			}
		}

		/**
		 * Callback for the `cn_entry_output_content-{id}` filter in @see cnOutput::getCategoryBlock()
		 *
		 * Renders the Facilities content block.
		 * Modelled after the @see cnOutput::getCategoryBlock()
		 *
		 * @access  private
		 * @since   1.0
		 * @static
		 *
		 * @param  cnOutput   $entry
		 * @param  array      $atts The shortcode atts array passed from the calling action.
		 * @param  cnTemplate $template
		 */
		public static function contentBlock( $entry, $atts, $template ) {

			$matches = array();

			if ( preg_match( '#(\d+)$#', current_action(), $matches ) ) {

				// Remove the filter from cnOutput::getCategoryBlock().
				remove_filter( 'cn_entry_output_category_item', array( __CLASS__, 'removeCategoryItem' ), 10 );

				$entry->getCategoryBlock(
					array(
						'child_of' => $matches[1],
						'type'     => 'list',
						'label'    => '',
					)
				);

				// Add the filter which removes the split categories from cnOutput::getCategoryBlock().
				add_filter( 'cn_entry_output_category_item', array( __CLASS__, 'removeCategoryItem' ), 10, 6 );
			}
		}

		/**
		 * Callback for the `cn_entry_output_content-{id}` filter in @see cnOutput::getCategoryBlock()
		 *
		 * Renders the Facilities content block.
		 * Modelled after the @see cnOutput::getCategoryBlock()
		 *
		 * @internal
		 * @since 1.1
		 *
		 * @param cnOutput   $entry
		 * @param array      $atts The shortcode atts array passed from the calling action.
		 * @param cnTemplate $template
		 */
		public static function contentBlockAsImageGrid( $entry, $atts, $template ) {

			$hasEnhancedCategories = class_exists( 'Connections_Categories' );
			$matches               = array();

			if ( preg_match( '#(\d+)-as-image-grid$#', current_action(), $matches ) && $hasEnhancedCategories ) {

				// Remove the filter from cnOutput::getCategoryBlock().
				remove_filter( 'cn_entry_output_category_item', array( __CLASS__, 'removeCategoryItem' ), 10 );

				/**
				 * @param string[] $class An array of class names.
				 *
				 * @return string[]
				 */
				$classCallback = static function( array $class ) {

					$class[] = 'cn-category-image-container';

					return $class;
				};

				/**
				 * @param string           $html       The item HTML.
				 * @param cnTerm_Object    $term       The current term.
				 * @param int              $count      The number of category terms attached to an entry.
				 * @param int              $i          The current category iteration.
				 * @param array            $properties The properties of the current Entry Categories Content Block.
				 * @param Entry_Categories $block      An instance of the Entry Categories Content Block.
				 *
				 * @return string
				 */
				$itemCallback = static function( string $html, cnTerm_Object $term, int $count, int $i, array $properties, Entry_Categories $block ) use ( $entry ) {

					global $wp_rewrite;

					$name  = '<span class="cn-term-name">' . esc_html( $term->name ) . '</span>';
					$image = Connections_Categories::getImageHTML( $term->term_id );
					$text  = '';

					if ( $block->get( 'link' ) ) {

						$rel = is_object( $wp_rewrite ) && $wp_rewrite->using_permalinks() ? 'rel="category tag"' : 'rel="category"';

						$url = cnTerm::permalink(
							$term,
							'category',
							array(
								'force_home' => $entry->directoryHome['force_home'],
								'home_id'    => $entry->directoryHome['page_id'],
							)
						);

						$text .= '<a href="' . $url . '" ' . $rel . '>' . $image . $name . '</a>';

					} else {

						$text .= $image . $name;
					}

					return sprintf(
						'<%1$s class="%2$s">%3$s</%1$s>',
						_escape::tagName( $block->get( 'item_tag' ) ),
						// The `cn_category` class is named with an underscore for backward compatibility.
						_escape::classNames( "cn-category-name cn_category cn-category-{$term->term_id} cn-category-{$term->slug} cn-category-image-block" ),
						// `$text` is escaped.
						$text
					);
				};

				add_filter( 'cn_entry_output_category_items_class', $classCallback );
				add_filter( 'cn_entry_output_category_item', $itemCallback, 10, 6 );

				$entry->getCategoryBlock(
					array(
						'child_of' => $matches[1],
						'type' => 'list',
						'label' => '',
					)
				);

				remove_filter( 'cn_entry_output_category_item', $itemCallback );
				remove_filter( 'cn_entry_output_category_items_class', $classCallback );

				// Add the filter which removes the split categories from cnOutput::getCategoryBlock().
				add_filter( 'cn_entry_output_category_item', array( __CLASS__, 'removeCategoryItem' ), 10, 6 );
			}
		}

		/**
		 * Callback for the `cn_entry_output_category_item` filter.
		 *
		 * Remove the split categories from cnOutput::getCategoryBlock().
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 *
		 * @param string        $html
		 * @param cnTerm_Object $category
		 * @param int           $count
		 * @param int           $i
		 * @param array         $atts
		 * @param cnEntry       $entry
		 *
		 * @return string
		 */
		public static function removeCategoryItem( $html, $category, $count, $i, $atts, $entry ) {

			$terms = self::getSplitCategories();

			foreach ( $terms as $term ) {

				if ( $category->term_id == $term->term_id ||
				     cnTerm::isAncestorOf( $term->term_id, $category->term_id, 'category' )
				) {

					return '';
				}
			}

			return $html;
		}
	}

	/**
	 * Start up the extension.
	 *
	 * @access                public
	 * @since                 1.0
	 * @return mixed (object)|(bool)
	 */
	function Connections_Split_Categories() {

		if ( class_exists( 'connectionsLoad' ) ) {

			return Connections_Split_Categories::instance();

		} else {

			add_action(
				'admin_notices',
				function() {
					echo '<div id="message" class="error"><p><strong>ERROR:</strong> Connections must be installed and active in order use Connections Split Categories.</p></div>';
				}
			);

			return FALSE;
		}
	}

	/**
	 * We'll load the extension on `plugins_loaded` so we know Connections will be loaded and ready first.
	 */
	add_action( 'plugins_loaded', 'Connections_Split_Categories' );
}
