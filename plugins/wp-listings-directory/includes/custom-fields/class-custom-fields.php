<?php
/**
 * Custom Fields
 *
 * @package    wp-listings-directory
 * @author     Habq
 * @license    GNU General Public License, version 3
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_Listings_Directory_Custom_Fields {
	
	public static function init() {
		// submit admin
		add_filter( 'wp-listings-directory-listing-fields-admin', array( __CLASS__, 'admin_custom_fields' ), 10 );

		// submit frontend
		add_filter( 'wp-listings-directory-listing-fields-front', array( __CLASS__, 'front_custom_fields' ), 100, 2 );

		// filter fields
		add_filter( 'wp-listings-directory-default-listing-filter-fields', array( __CLASS__, 'filter_custom_fields' ), 100 );
		
	}

	public static function filter_custom_fields($old_fields) {
		$fields = self::get_search_custom_fields($old_fields, true);
		$fields['center-location'] = array(
			'name' => __( 'Location', 'wp-listings-directory' ),
			'field_call_back' => array( 'WP_Listings_Directory_Abstract_Filter', 'filter_field_input_location'),
			'placeholder' => __( 'All Location', 'wp-listings-directory' ),
			'show_distance' => true,
			'toggle' => false,
			'for_post_type' => 'listing',
		);
		$fields['distance'] = array(
			'name' => __( 'Search Distance', 'wp-listings-directory' ),
			'field_call_back' => array( 'WP_Listings_Directory_Abstract_Filter', 'filter_field_input_distance'),
			'placeholder' => __( 'Distance', 'wp-listings-directory' ),
			'toggle' => false,
			'for_post_type' => 'listing',
		);
		return apply_filters( 'wp-listings-directory-types-filter-custom-fields', $fields, $old_fields);
	}

	public static function admin_custom_fields() {
		$init_fields = self::get_custom_fields(array(), true);
		$init_fields = apply_filters( 'wp-listings-directory-types-admin_custom_fields', $init_fields);
		
		$fields = array();
		$key_tab = 'tab-heading-start'.rand(100,1000);
		$tab_data = array(
			'id' => $key_tab,
			'icon' => 'dashicons-admin-home',
			'title'  => esc_html__( 'General', 'wp-listings-directory' ),
			'fields' => array(),
		);
		$i = 0;
		foreach ($init_fields as $key => $field) {
			if ( $i == 0 && (empty($field['type']) || $field['type'] !== 'title') ) {
				$fields[$key_tab] = $tab_data;
			} elseif ( !empty($field['type']) && $field['type'] == 'title' ) {
				$key_tab = $field['id'];
				$fields[$key_tab] = array(
					'id' => $key_tab,
					'icon' => !empty($field['icon']) ? $field['icon'] : '',
					'title'  => !empty($field['name']) ? $field['name'] : '',
					'fields' => array(),
				);
			}

			$fields[$key_tab]['fields'][] = $field;
			$i++;
		}
		
		// author fields
		$post_author_id = '';
		if ( !empty($_GET['post']) ) {
			$post_author_id = get_post_field( 'post_author', $_GET['post'] );
		}
		$author_key = 'tab-heading-author'.rand(100,1000);
		$fields[$author_key] = array(
			'id' => $author_key,
			'icon' => 'dashicons-admin-users',
			'title'  => esc_html__( 'Author', 'wp-listings-directory' ),
			'fields' => array(
				array(
					'name'          => __( 'Author', 'wp-listings-directory' ),
					'id'            => WP_LISTINGS_DIRECTORY_LISTING_PREFIX . 'posted_by',
					'type'          => 'user_ajax_search',
					'default'		=> $post_author_id
				)
			),
		);

		$box_options = array(
			'id'           => 'listing_metabox',
			'title'        => esc_html__( 'Listing Metabox', 'wp-listings-directory' ),
			'object_types' => array( 'listing' ),
			'show_names'   => true,
		);
		
		// Setup meta box
		$cmb = new_cmb2_box( $box_options );

		// Set tabs
		$cmb->add_field( [
			'id'   => '__tabs',
			'type' => 'tabs',
			'tabs' => array(
				'config' => $box_options,
				'layout' => 'vertical', // Default : horizontal
				'tabs'   => apply_filters('wp-listings-directory-admin-custom-fields', $fields),
			),
		] );

		return true;
	}

	public static function front_custom_fields($old_fields, $post_id) {
		$fields = self::get_custom_fields($old_fields, false, $post_id);
		
		return apply_filters( 'wp-listings-directory-types-submit_form_fields', $fields, $old_fields, $post_id);
	}

	public static function get_all_custom_fields($old_fields, $admin_field = false) {
		$prefix = WP_LISTINGS_DIRECTORY_LISTING_PREFIX;
		$fields = array();

		$custom_all_fields = WP_Listings_Directory_Fields_Manager::get_custom_fields_data();
		if (is_array($custom_all_fields) && sizeof($custom_all_fields) > 0) {

			$dtypes = WP_Listings_Directory_Fields_Manager::get_all_field_type_keys();
	        $available_types = WP_Listings_Directory_Fields_Manager::get_all_types_fields_available();
	        $required_types = WP_Listings_Directory_Fields_Manager::get_all_types_fields_required();
			$i = 1;

			foreach ($custom_all_fields as $key => $custom_field) {
				
				$fieldkey = !empty($custom_field['type']) ? $custom_field['type'] : '';
				if ( !empty($fieldkey) ) {
					$type = '';
					$required_values = WP_Listings_Directory_Fields_Manager::get_field_id($fieldkey, $required_types);
					$available_values = WP_Listings_Directory_Fields_Manager::get_field_id($fieldkey, $available_types);

					if ( !empty($required_values) ) {
						$field_data = wp_parse_args( $custom_field, $required_values);
						$fieldtype = isset($required_values['type']) ? $required_values['type'] : '';
						$fieldtype_type = 'required';
					} elseif ( !empty($available_values) ) {
						$field_data = wp_parse_args( $custom_field, $available_values);
						$fieldtype = isset($available_values['type']) ? $available_values['type'] : '';
						$fieldtype_type = 'available';
					} elseif ( in_array($fieldkey, $dtypes) ) {
						$fieldkey = isset($custom_field['key']) ? $custom_field['key'] : '';
						$fieldtype = isset($custom_field['type']) ? $custom_field['type'] : '';
						$fieldtype_type = 'custom';
						$field_data = $custom_field;
						if ( in_array($fieldtype, array('heading', 'file', 'url', 'email')) ) {
							continue;
						}
					}

					$id = str_replace(WP_LISTINGS_DIRECTORY_LISTING_PREFIX, '', $field_data['id']);
					$fields[$id] = self::render_field($field_data, $fieldkey, $fieldtype, $i, $admin_field, $fieldtype_type);
				}
				$i++;
			}
		} else {
			$fields = $old_fields;
		}

		return $fields;
	}

	public static function get_search_custom_fields($old_fields, $admin_field = true) {
		$prefix = WP_LISTINGS_DIRECTORY_LISTING_PREFIX;
		$fields = array();

		$custom_all_fields = WP_Listings_Directory_Fields_Manager::get_custom_fields_data();
		if (is_array($custom_all_fields) && sizeof($custom_all_fields) > 0) {

			$dtypes = WP_Listings_Directory_Fields_Manager::get_all_field_type_keys();
	        $available_types = WP_Listings_Directory_Fields_Manager::get_all_types_fields_available();
	        $required_types = WP_Listings_Directory_Fields_Manager::get_all_types_fields_required();
			$i = 1;

			foreach ($custom_all_fields as $key => $custom_field) {
				
				$fieldkey = !empty($custom_field['type']) ? $custom_field['type'] : '';
				if ( !empty($fieldkey) ) {
					$type = '';
					$required_values = WP_Listings_Directory_Fields_Manager::get_field_id($fieldkey, $required_types);
					$available_values = WP_Listings_Directory_Fields_Manager::get_field_id($fieldkey, $available_types);

					if ( !empty($required_values) ) {
						$field_data = wp_parse_args( $custom_field, $required_values);
						$fieldtype = isset($required_values['type']) ? $required_values['type'] : '';
						$fieldtype_type = 'required';
					} elseif ( !empty($available_values) ) {
						$field_data = wp_parse_args( $custom_field, $available_values);
						$fieldtype = isset($available_values['type']) ? $available_values['type'] : '';
						$fieldtype_type = 'available';
					} elseif ( in_array($fieldkey, $dtypes) ) {
						$fieldkey = isset($custom_field['key']) ? $custom_field['key'] : '';
						$fieldtype = isset($custom_field['type']) ? $custom_field['type'] : '';
						$fieldtype_type = 'custom';
						$field_data = $custom_field;
						if ( in_array($fieldtype, array('heading', 'file', 'url', 'email')) ) {
							continue;
						}
					}

					if ( !in_array($fieldkey, array( $prefix.'heading', $prefix.'featured_image', $prefix.'gallery', $prefix.'description', $prefix.'expiry_date', $prefix.'price_prefix', $prefix.'price_suffix', $prefix.'price_custom', $prefix.'lot_dimensions', $prefix.'video', $prefix.'map_location', $prefix.'featured_image', $prefix.'gallery', $prefix.'attachments', $prefix.'address', $prefix.'file' )) ) {

						$id = str_replace(WP_LISTINGS_DIRECTORY_LISTING_PREFIX, '', $field_data['id']);
						if ( $id == 'map_location' ) {
							$id = 'center-location';
						}
						$fields[$id] = self::render_field($field_data, $fieldkey, $fieldtype, $i, $admin_field, $fieldtype_type);

						if ( empty($fields[$id]['field_call_back']) ) {
							if ( !empty($field_data['field_call_back']) ) {
								$fields[$id]['field_call_back'] = $field_data['field_call_back'];
							} else {
								unset($fields[$id]);
							}
						}
					}
				}
				$i++;
			}

			// echo "<pre>".print_r($fields,1); die;
		} else {
			$fields = $old_fields;
		}

		return $fields;
	}

	public static function get_custom_fields($old_fields, $admin_field = true, $post_id = 0) {
		$prefix = WP_LISTINGS_DIRECTORY_LISTING_PREFIX;
		$fields = array();

		$package_id = 0;
		if ( !$admin_field ) {
			$package_id = self::get_package_id($post_id);
		}
		
		$custom_all_fields = WP_Listings_Directory_Fields_Manager::get_custom_fields_data();
		if (is_array($custom_all_fields) && sizeof($custom_all_fields) > 0) {

			$dtypes = WP_Listings_Directory_Fields_Manager::get_all_field_type_keys();
	        $available_types = WP_Listings_Directory_Fields_Manager::get_all_types_fields_available();
	        $required_types = WP_Listings_Directory_Fields_Manager::get_all_types_fields_required();
			$i = 1;
			foreach ($custom_all_fields as $key => $custom_field) {
				$check_package_field = true;
				if ( !$admin_field ) {
					$check_package_field = self::check_package_field($custom_field, $package_id);
				}

				$fieldkey = !empty($custom_field['type']) ? $custom_field['type'] : '';
				if ( !empty($fieldkey) && $check_package_field ) {
					$type = '';
					$required_values = WP_Listings_Directory_Fields_Manager::get_field_id($fieldkey, $required_types);
					$available_values = WP_Listings_Directory_Fields_Manager::get_field_id($fieldkey, $available_types);
					if ( !empty($required_values) ) {
						$field_data = wp_parse_args( $custom_field, $required_values);
						$fieldtype = isset($required_values['type']) ? $required_values['type'] : '';
					} elseif ( !empty($available_values) ) {
						$field_data = wp_parse_args( $custom_field, $available_values);
						$fieldtype = isset($available_values['type']) ? $available_values['type'] : '';
					} elseif ( in_array($fieldkey, $dtypes) ) {
						$fieldkey = isset($custom_field['key']) ? $custom_field['key'] : '';
						$fieldtype = isset($custom_field['type']) ? $custom_field['type'] : '';
						$field_data = $custom_field;
					}
					
					if ( !$admin_field && (!empty($field_data['show_in_submit_form']) || $fieldtype == 'heading') && $fieldkey !== $prefix.'featured' ) {
						$fields[] = self::render_field($field_data, $fieldkey, $fieldtype, $i);
					} elseif( $admin_field && (!empty($field_data['show_in_admin_edit']) || $fieldtype == 'heading') && !in_array($fieldkey, array( $prefix.'title', $prefix.'description', $prefix.'featured_image', $prefix.'location', $prefix.'category', $prefix.'type' ))) {

						$fields[] = self::render_field($field_data, $fieldkey, $fieldtype, $i, $admin_field);
					}
				}
				$i++;
			}
		} else {
			$fields = $old_fields;
		}
		return $fields;
	}

	public static function get_package_id($post_id) {
		
		$package_id = apply_filters('wp-listings-directory-get-listing-package-id', 0, $post_id);
		
		return apply_filters( 'wp-listings-directory-types-get_package_id', $package_id);
	}

	public static function check_package_field($field, $package_id) {
		$return = false;
		if ( empty($package_id) ) {
			$return = true;
		}
		if ( empty($field['show_in_package']) ) {
			$return = true;
		}
		if ( !empty($field['show_in_package']) ) {
			$package_display = !empty($field['package_display']) ? $field['package_display'] : array();
			if ( !empty($package_display) && is_array($package_display) && in_array($package_id, $package_display) ) {
				$return = true;
			}
		}
		
		return apply_filters( 'wp-listings-directory-types-check_package_field', $return, $field, $package_id);
	}

	public static function render_field($field_data, $fieldkey, $fieldtype, $priority, $admin_field = false, $fieldtype_type = '') {
		$name = stripslashes(isset($field_data['name']) ? $field_data['name'] : '');
		$id = isset($field_data['id']) ? $field_data['id'] : '';
        $placeholder = stripslashes(isset($field_data['placeholder']) ? $field_data['placeholder'] : '');
        $description = stripslashes(isset($field_data['description']) ? $field_data['description'] : '');
        $format = isset($field_data['format']) ? $field_data['format'] : '';
        $required = isset($field_data['required']) ? $field_data['required'] : '';
        $default = isset($field_data['default']) ? $field_data['default'] : '';

		$field = array(
			'name' => $name,
			'id' => $id,
			'type' => $fieldtype,
			'priority' => $priority,
			'description' => $description,
			'default' => $default,
			'attributes' => array()
		);
		if ( !empty($field_data['attributes']) ) {
			$field['attributes'] = $field_data['attributes'];
		}
		if ( $placeholder ) {
			$field['attributes']['placeholder'] = $placeholder;
			$field['placeholder'] = $placeholder;
		}
		if ( $required ) {
			$field['attributes']['required'] = 'required';
			$field['label_cb'] = array( 'WP_Listings_Directory_Mixes', 'required_add_label' );
		}
		if ( $fieldtype_type == 'custom' ) {
			$field['filter-name-prefix'] = 'filter-cfield';
		}
		switch ($fieldtype) {
			case 'wysiwyg':
			case 'textarea':
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_Listings_Directory_Abstract_Filter', 'filter_field_input');
				}
				break;
			case 'text':
				$field['type'] = 'text';
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_Listings_Directory_Abstract_Filter', 'filter_field_input');
				}
				break;
			case 'number':
				$field['type'] = 'text';
				$field['attributes']['type'] = 'number';
				$field['attributes']['min'] = 0;
				$field['attributes']['pattern'] = '\d*';
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_Listings_Directory_Abstract_Filter', 'filter_field_input');
				}
				break;
			case 'url':
				$field['type'] = 'text';
				$field['attributes']['type'] = 'url';
				$field['attributes']['pattern'] = 'https?://.+';
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_Listings_Directory_Abstract_Filter', 'filter_field_input');
				}
				break;
			case 'email':
				$field['type'] = 'text';
				$field['attributes']['type'] = 'email';
				$field['attributes']['pattern'] = '[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2, 4}$';
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_Listings_Directory_Abstract_Filter', 'filter_field_input');
				}
				break;
			case 'date':
				$field['type'] = 'text_date';
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_Listings_Directory_Abstract_Filter', 'filter_date_field_input');
					$field['filter-name-prefix'] = 'filter-cfielddate';
				}
				$field['date_format'] = 'Y-m-d';
				break;
			case 'checkbox':
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_Listings_Directory_Abstract_Filter', 'filter_field_checkbox');
				}
				break;
			case 'radio':
			case 'select':
				$doptions = !empty($field_data['options']) ? $field_data['options'] : array();
				$options = array();
				if ( !empty($placeholder) ) {
					$options = array('' => $placeholder);
				}
				if ( is_array($doptions) ) {
					$options = $doptions;
				} elseif ( !empty($doptions) ) {
					$doptions = explode("\n", str_replace("\r", "", stripslashes($doptions)));
					foreach ($doptions as $val) {
						$options[$val] = $val;
					}
				}
				$field['options'] = $options;
				if ( $fieldtype == 'select' ) {
					$field['type'] = 'pw_select';
				}
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_Listings_Directory_Abstract_Filter', 'filter_field_select');
				}
				break;
			case 'multiselect':
				$doptions = !empty($field_data['options']) ? $field_data['options'] : array();
				$options = array();
				if ( !empty($placeholder) ) {
					$options = array('' => $placeholder);
				}
				if ( is_array($doptions) ) {
					$options = $doptions;
				} elseif ( !empty($doptions) ) {
					$doptions = explode("\n", str_replace("\r", "", stripslashes($doptions)));
					foreach ($doptions as $val) {
						$options[$val] = $val;
					}
				}
				$field['options'] = $options;
				$field['type'] = 'pw_multiselect';
				if ( $fieldtype_type == 'custom' ) {
					$field['field_call_back'] = array( 'WP_Listings_Directory_Abstract_Filter', 'filter_field_select');
				}
				break;
			case 'file':
				$allow_types = !empty($field_data['allow_types']) ? $field_data['allow_types'] : array();
				$multiples = !empty($field_data['multiple_files']) ? $field_data['multiple_files'] : false;
				$ajax = !empty($field_data['ajax']) ? $field_data['ajax'] : false;

				
				$field['ajax'] = $ajax ? true : (is_user_logged_in() ? true : false);
				if ( $multiples ) {
					$field['file_limit'] = !empty($field_data['file_limit']) ? $field_data['file_limit'] : 10;
				}
				if ( !$admin_field ) {
					$field['type'] = 'wp_listings_directory_file';
					$field['file_multiple'] = $multiples ? true : false;

					if ( !empty($allow_types) ) {
						$allowed_mime_types = array();
						$all_mime_types = get_allowed_mime_types();
						$mime_types = array();
						foreach ($allow_types as $mime_type) {
							$tmime = explode('|', $mime_type);
							$mime_types = array_merge($mime_types, $tmime);

							if ( isset($all_mime_types[$mime_type]) ) {
								$allowed_mime_types[] = $all_mime_types[$mime_type];
							}
						}

						$field['mime_types'] = $mime_types;
						$field['allow_mime_types'] = $allowed_mime_types;
					}
				} else {
					if ( !$multiples ) {
						$field['type'] = 'file';
						$field['preview_size'] = 'thumbnail';
					} else {
						$field['type'] = 'file_list';
					}

					if ( !empty($allow_types) ) {
						$allowed_mime_types = array();
						$mime_types = get_allowed_mime_types();
						foreach ($allow_types as $mime_type) {
							if ( isset($mime_types[$mime_type]) ) {
								$allowed_mime_types[$mime_type] = $mime_types[$mime_type];
							}
						}
						$field['allow_mime_types'] = $allowed_mime_types;
						$field['query_args']['type'] = $allowed_mime_types;
					}
				}
				break;
			case 'wp_listings_directory_file':
				$allow_types = !empty($field_data['allow_types']) ? $field_data['allow_types'] : array();

				$multiples = !empty($field_data['multiple_files']) ? $field_data['multiple_files'] : false;
				$ajax = !empty($field_data['ajax']) ? $field_data['ajax'] : false;
				
				$field['ajax'] = $ajax ? true : false;
				if ( $multiples ) {
					$field['file_limit'] = !empty($field_data['file_limit']) ? $field_data['file_limit'] : 10;
				}
				if ( !$admin_field ) {
					$field['file_multiple'] = $multiples ? true : false;
					if ( !empty($allow_types) ) {
						$allowed_mime_types = array();
						$all_mime_types = get_allowed_mime_types();
						$mime_types = array();
						foreach ($allow_types as $mime_type) {
							$tmime = explode('|', $mime_type);
							$mime_types = array_merge($mime_types, $tmime);

							if ( isset($all_mime_types[$mime_type]) ) {
								$allowed_mime_types[] = $all_mime_types[$mime_type];
							}
						}
						$field['mime_types'] = $mime_types;
						$field['allow_mime_types'] = $allowed_mime_types;
					}

				} else {
					if ( !$multiples ) {
						$field['type'] = 'file';
						$field['preview_size'] = 'thumbnail';
					} else {
						$field['type'] = 'file_list';
					}

					if ( !empty($allow_types) ) {
						$allowed_mime_types = array();
						$mime_types = get_allowed_mime_types();
						foreach ($allow_types as $mime_type) {
							if ( isset($mime_types[$mime_type]) ) {
								$allowed_mime_types[$mime_type] = $mime_types[$mime_type];
							}
						}
						$field['allow_mime_types'] = $allowed_mime_types;
						$field['query_args']['type'] = $allowed_mime_types;
					}
				}
				break;
			case 'heading':
				$field['type'] = 'title';
				$field['icon'] = !empty($field_data['icon']) ? $field_data['icon'] : '';
				$field['number_columns'] = !empty($field_data['number_columns']) ? $field_data['number_columns'] : '';
			case 'pw_map':

				$field['split_values'] = isset($field_data['split_values']) ? $field_data['split_values'] : false;
			case 'repeater':
			case 'group':
				$subfields = array();
				if ( !empty($field_data['fields']) ) {
					foreach ($field_data['fields'] as $subf) {
						$subfield = $subf;
						if ( !empty($subfield['type']) && $subfield['type'] == 'wp_listings_directory_file' ) {
							if ( $admin_field ) {
								$subfield['type'] = 'file';
								$subfield['preview_size'] = 'thumbnail';
							}
							$subfields[] = $subfield;
						} elseif ( !empty($subfield['type']) && $subfield['type'] == 'file' ) {
							if ( !$admin_field ) {
								$subfield['type'] = 'wp_listings_directory_file';
								$ajax = !empty($subfield['ajax']) ? $subfield['ajax'] : false;
								
								$field['ajax'] = $ajax ? true : false;
								if ( $ajax ) {
									$field['file_limit'] = !empty($subfield['file_limit']) ? $subfield['file_limit'] : 10;
								}
								$field['file_multiple'] = !empty($subfield['file_multiple']) ? $subfield['file_multiple'] : false;
								$field['mime_types'] = !empty($subfield['mime_types']) ? $subfield['mime_types'] : array( 'gif', 'jpeg', 'jpg', 'png' );
							}
							$subfields[] = $subfield;
						} else {
							$subfields[] = $subfield;
						}
					}
				}
				$field['fields'] = $subfields;
				if ( !empty($field_data['options']) ) {
					$field['options'] = $field_data['options'];
				}
				break;
		}
    	
    	$prefix = WP_LISTINGS_DIRECTORY_LISTING_PREFIX;
    	switch ($fieldkey) {
			case $prefix.'description':
				$field['type'] = !empty($field_data['select_type']) ? $field_data['select_type'] : 'wysiwyg';
				if ( !empty($field_data['options']) ) {
					$field['options'] = $field_data['options'];
				}
			break;
			case $prefix.'location':
				$field['taxonomy'] = !empty($field_data['taxonomy']) ? $field_data['taxonomy'] : '';
				$location_type = wp_listings_directory_get_option('location_multiple_fields', 'yes');
				
				if ( $location_type === 'yes' ) {
					$field['type'] = 'wpld_taxonomy_location';
				} else {
					$field['type'] = 'pw_taxonomy_select';
				}
				// $field['type'] = !empty($field_data['type']) ? $field_data['type'] : 'pw_taxonomy_select';
			break;
			case $prefix.'type':
			case $prefix.'category':
			case $prefix.'feature':
			
				$field['type'] = !empty($field_data['select_type']) ? $field_data['select_type'] : 'pw_taxonomy_select';
				$field['taxonomy'] = !empty($field_data['taxonomy']) ? $field_data['taxonomy'] : '';
				break;
			case $prefix.'expiry_date':
				$field['date_format'] = !empty($field_data['date_format']) ? $field_data['date_format'] : 'Y-m-d';
				break;
			case $prefix.'price_range':
				
				$current_currency = WP_Listings_Directory_Price::get_current_currency();
				$multi_currencies = WP_Listings_Directory_Price::get_currencies_settings();

				if ( !empty($multi_currencies) && !empty($multi_currencies[$current_currency]) ) {
					$currency_args = $multi_currencies[$current_currency];
				}

				if ( !empty($currency_args) ) {
					$currency_symbol = !empty($currency_args['custom_symbol']) ? $currency_args['custom_symbol'] : '';
					if ( empty($currency_symbol) ) {
						$currency = !empty($currency_args['currency']) ? $currency_args['currency'] : 'USD';
						$currency_symbol = WP_Listings_Directory_Price::currency_symbol($currency);
					}
				}

				if ( empty($currency_symbol) ) {
					$currency_symbol = '$';
				}

		        $price_range = WP_Listings_Directory_Mixes::price_range_icons();
		        $opt_price_range = [];
		        foreach ($price_range as $key => $value) {
		            $opt_price_range[$key] =  str_repeat( $currency_symbol, $value['icon'] ).' - '.$value['label'];
		        }
		        $field['options'] = $opt_price_range;
				break;
		}

		return apply_filters( 'wp-listings-directory-types-render_field', $field, $field_data, $fieldkey, $fieldtype, $priority);
	}

}
WP_Listings_Directory_Custom_Fields::init();