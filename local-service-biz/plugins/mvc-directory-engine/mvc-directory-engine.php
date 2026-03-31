<?php
/**
 * Plugin Name: MVC Directory Engine
 * Description: Core mapping loader, validator, helper functions, and admin upload page for the LocalServiceBiz directory engine.
 * Version: 1.0.0
 * Author: MVC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MVC_Directory_Engine {
	const VERSION                  = '1.0.0';
	const OPTION_MAPPING_ROWS      = 'mvc_directory_engine_mapping_rows';
	const OPTION_MAPPING_INDEX     = 'mvc_directory_engine_mapping_index';
	const OPTION_MAPPING_META      = 'mvc_directory_engine_mapping_meta';
	const NONCE_ACTION_UPLOAD      = 'mvc_directory_engine_upload_mapping';
	const NONCE_ACTION_CLEAR       = 'mvc_directory_engine_clear_mapping';
	const MENU_SLUG                = 'mvc-directory-engine';
	const CPT_BUSINESS             = 'businesses';
	const TAX_INDUSTRY             = 'industry_cat';
	const TAX_SERVICE              = 'service_type';
	const TAX_CITY                 = 'city_cat';
	const OPTION_PAGE_ROLES = 'mvc_directory_engine_page_roles';
	const PAGE_ROLE_HOME              = 'home';
	const PAGE_ROLE_INDUSTRY_OVERVIEW = 'industry_overview';
	const PAGE_ROLE_SERVICE_OVERVIEW  = 'service_overview';
	const PAGE_ROLE_CITY_OVERVIEW     = 'city_overview';
	const PAGE_ROLE_BUSINESS_OVERVIEW = 'business_overview';
	const TAX_IMAGE_USAGE = 'image_usage';
	const IMAGE_USAGE_GALLERY = 'gallery';
	const IMAGE_USAGE_HERO    = 'hero';
	const IMAGE_USAGE_SQUARE    = 'square';
	const IMAGE_USAGE_LOGO    = 'logo';
	const IMAGE_USAGE_ICON    = 'icon';
	const IMAGE_USAGE_EXCLUDE = 'exclude';

	/** @var MVC_Directory_Engine|null */
	private static $instance = null;

	/** @var array<string,mixed>|null */
	private $mapping_index = null;

	/** @var array<int,array<string,string>>|null */
	private $mapping_rows = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_post_mvc_directory_engine_upload_mapping', array( $this, 'handle_mapping_upload' ) );
		add_action( 'admin_post_mvc_directory_engine_clear_mapping', array( $this, 'handle_mapping_clear' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
add_action( 'admin_post_mvc_directory_engine_save_page_roles', array( $this, 'handle_save_page_roles' ) );
	}

	/**
	 * Public Helpers
	 */

	public function get_mapping_rows() {
		if ( null === $this->mapping_rows ) {
			$rows = get_option( self::OPTION_MAPPING_ROWS, array() );
			$this->mapping_rows = is_array( $rows ) ? $rows : array();
		}

		return $this->mapping_rows;
	}

	public function get_mapping_index() {
		if ( null === $this->mapping_index ) {
			$index = get_option( self::OPTION_MAPPING_INDEX, array() );
			$this->mapping_index = is_array( $index ) ? $index : array();
		}

		return $this->mapping_index;
	}

	public function get_mapping_meta() {
		$meta = get_option( self::OPTION_MAPPING_META, array() );
		return is_array( $meta ) ? $meta : array();
	}

	public function normalize_string( $value ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		$value = wp_strip_all_tags( $value );
		$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$value = strtolower( trim( $value ) );
		$value = preg_replace( '/[\x{2018}\x{2019}\x{201A}\x{201B}]/u', "'", $value );
		$value = preg_replace( '/[\x{201C}\x{201D}\x{201E}\x{201F}]/u', '"', $value );
		$value = preg_replace( '/[^a-z0-9\s\-_&\/]/', ' ', $value );
		$value = preg_replace( '/[\s\-_]+/', ' ', $value );
		return trim( (string) $value );
	}

	public function normalize_slug( $value ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		$value = sanitize_title( $value );
		return trim( $value );
	}

	public function get_service_mapping( $service_slug ) {
		$service_slug = $this->normalize_slug( $service_slug );
		if ( '' === $service_slug ) {
			return null;
		}

		$index = $this->get_mapping_index();
		return isset( $index['services'][ $service_slug ] ) ? $index['services'][ $service_slug ] : null;
	}

	public function get_industry_services( $industry_slug ) {
		$industry_slug = $this->normalize_slug( $industry_slug );
		if ( '' === $industry_slug ) {
			return array();
		}

		$index = $this->get_mapping_index();
		return isset( $index['industries'][ $industry_slug ] ) && is_array( $index['industries'][ $industry_slug ] )
			? $index['industries'][ $industry_slug ]
			: array();
	}

public function get_page_roles() {
	$roles = get_option( self::OPTION_PAGE_ROLES, array() );
	return is_array( $roles ) ? $roles : array();
}



	public function service_belongs_to_industry( $service_slug, $industry_slug ) {
		$service_slug  = $this->normalize_slug( $service_slug );
		$industry_slug = $this->normalize_slug( $industry_slug );

		if ( '' === $service_slug || '' === $industry_slug ) {
			return false;
		}

		$mapping = $this->get_service_mapping( $service_slug );
		if ( empty( $mapping ) || empty( $mapping['industry_slug'] ) ) {
			return false;
		}

		return $mapping['industry_slug'] === $industry_slug;
	}

	public function validate_business_assignment( $industry_slugs, $service_slugs ) {
		$industry_slugs = is_array( $industry_slugs ) ? array_filter( array_map( array( $this, 'normalize_slug' ), $industry_slugs ) ) : array();
		$service_slugs  = is_array( $service_slugs ) ? array_filter( array_map( array( $this, 'normalize_slug' ), $service_slugs ) ) : array();

		$result = array(
			'is_valid'          => true,
			'valid_services'    => array(),
			'invalid_services'  => array(),
			'unknown_services'  => array(),
			'messages'          => array(),
		);

		if ( empty( $industry_slugs ) ) {
			$result['is_valid'] = false;
			$result['messages'][] = 'No industry slugs were provided.';
			return $result;
		}

		foreach ( $service_slugs as $service_slug ) {
			$mapping = $this->get_service_mapping( $service_slug );

			if ( empty( $mapping ) ) {
				$result['is_valid'] = false;
				$result['unknown_services'][] = $service_slug;
				$result['messages'][] = sprintf( 'Service slug "%s" was not found in the mapping file.', $service_slug );
				continue;
			}

			if ( in_array( $mapping['industry_slug'], $industry_slugs, true ) ) {
				$result['valid_services'][] = $service_slug;
				continue;
			}

			$result['is_valid'] = false;
			$result['invalid_services'][] = array(
				'service_slug'     => $service_slug,
				'expected_industry'=> $mapping['industry_slug'],
				'provided_industry'=> $industry_slugs,
			);
			$result['messages'][] = sprintf(
				'Service slug "%1$s" belongs to industry "%2$s", not the provided industry set.',
				$service_slug,
				$mapping['industry_slug']
			);
		}

		return $result;
	}

	public function assign_business_terms_safely( $post_id, $industry_slugs, $service_slugs, $city_slugs = array() ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 || self::CPT_BUSINESS !== get_post_type( $post_id ) ) {
			return new WP_Error( 'invalid_business_post', 'The provided post is not a valid businesses post.' );
		}

		$industry_slugs = is_array( $industry_slugs ) ? array_values( array_unique( array_filter( array_map( array( $this, 'normalize_slug' ), $industry_slugs ) ) ) ) : array();
		$service_slugs  = is_array( $service_slugs ) ? array_values( array_unique( array_filter( array_map( array( $this, 'normalize_slug' ), $service_slugs ) ) ) ) : array();
		$city_slugs     = is_array( $city_slugs ) ? array_values( array_unique( array_filter( array_map( array( $this, 'normalize_slug' ), $city_slugs ) ) ) ) : array();

		$validation = $this->validate_business_assignment( $industry_slugs, $service_slugs );
		if ( ! $validation['is_valid'] ) {
			return new WP_Error(
				'invalid_term_assignment',
				'One or more services failed validation.',
				$validation
			);
		}

		$industry_term_ids = $this->get_term_ids_by_slugs( self::TAX_INDUSTRY, $industry_slugs );
		$service_term_ids  = $this->get_term_ids_by_slugs( self::TAX_SERVICE, $validation['valid_services'] );
		$city_term_ids     = $this->get_term_ids_by_slugs( self::TAX_CITY, $city_slugs );

		if ( is_wp_error( $industry_term_ids ) ) {
			return $industry_term_ids;
		}
		if ( is_wp_error( $service_term_ids ) ) {
			return $service_term_ids;
		}
		if ( is_wp_error( $city_term_ids ) ) {
			return $city_term_ids;
		}

		wp_set_object_terms( $post_id, $industry_term_ids, self::TAX_INDUSTRY, false );
		wp_set_object_terms( $post_id, $service_term_ids, self::TAX_SERVICE, false );

		if ( ! empty( $city_term_ids ) ) {
			wp_set_object_terms( $post_id, $city_term_ids, self::TAX_CITY, false );
		}

		return array(
			'post_id'            => $post_id,
			'industry_term_ids'  => $industry_term_ids,
			'service_term_ids'   => $service_term_ids,
			'city_term_ids'      => $city_term_ids,
			'validation'         => $validation,
		);
	}

	private function get_term_ids_by_slugs( $taxonomy, $slugs ) {
		$slugs = is_array( $slugs ) ? array_values( array_unique( array_filter( $slugs ) ) ) : array();
		if ( empty( $slugs ) ) {
			return array();
		}

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new WP_Error( 'invalid_taxonomy', sprintf( 'Taxonomy "%s" does not exist.', $taxonomy ) );
		}

		$term_ids = array();

		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( ! $term || is_wp_error( $term ) ) {
				return new WP_Error(
					'missing_term',
					sprintf( 'Could not find term slug "%1$s" in taxonomy "%2$s".', $slug, $taxonomy )
				);
			}

			$term_ids[] = (int) $term->term_id;
		}

		return $term_ids;
	}

	/**
	 * Admin UI
	 */

	public function register_admin_menu() {
		add_menu_page(
			'MVC Directory Engine',
			'Directory Engine',
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_admin_page' ),
			'dashicons-networking',
			58
		);
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$meta         = $this->get_mapping_meta();
		$rows         = $this->get_mapping_rows();
		$row_count    = count( $rows );
		$updated_at   = ! empty( $meta['updated_at'] ) ? $meta['updated_at'] : '';
		$file_name    = ! empty( $meta['file_name'] ) ? $meta['file_name'] : '';
		$sample_rows  = array_slice( $rows, 0, 10 );
		$page_roles = $this->get_page_roles();
		$all_pages  = get_pages( array(
			'sort_column' => 'post_title',
			'sort_order'  => 'ASC',
		) );		?>
		<div class="wrap">
			<h1>MVC Directory Engine</h1>
			<p>Upload the master service-to-industry mapping CSV. Required columns: <code>service_slug</code>, <code>industry_slug</code>. Optional columns: <code>term_name</code>, <code>parent_name</code>.</p>

			<div style="background:#fff;border:1px solid #ccd0d4;padding:16px 20px;margin:16px 0;max-width:1100px;">
				<h2 style="margin-top:0;">Current Mapping Status</h2>
				<p><strong>Total mappings:</strong> <?php echo esc_html( number_format_i18n( $row_count ) ); ?></p>
				<p><strong>Last upload:</strong> <?php echo $updated_at ? esc_html( $updated_at ) : 'Not uploaded yet'; ?></p>
				<p><strong>Source file:</strong> <?php echo $file_name ? esc_html( $file_name ) : '—'; ?></p>
			</div>

			<div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;max-width:1200px;">
				<div style="background:#fff;border:1px solid #ccd0d4;padding:16px 20px;flex:1;min-width:320px;">
					<h2 style="margin-top:0;">Upload Mapping CSV</h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
						<?php wp_nonce_field( self::NONCE_ACTION_UPLOAD ); ?>
						<input type="hidden" name="action" value="mvc_directory_engine_upload_mapping" />
						<p>
							<input type="file" name="mapping_csv" accept=".csv,text/csv" required />
						</p>
						<p>
							<button type="submit" class="button button-primary">Upload Mapping</button>
						</p>
					</form>
				</div>

				<div style="background:#fff;border:1px solid #ccd0d4;padding:16px 20px;flex:1;min-width:320px;">
					<h2 style="margin-top:0;">Clear Current Mapping</h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( self::NONCE_ACTION_CLEAR ); ?>
						<input type="hidden" name="action" value="mvc_directory_engine_clear_mapping" />
						<p>This removes the stored mapping rows and index from WordPress options.</p>
						<p>
							<button type="submit" class="button">Clear Mapping</button>
						</p>
					</form>
				</div>
			</div>
<div style="background:#fff;border:1px solid #ccd0d4;padding:16px 20px;margin-top:24px;max-width:1200px;">
	<h2 style="margin-top:0;">Overview Page Roles</h2>
	<p>Select which WordPress Pages should act as your general overview pages. These pages do not need taxonomy terms.</p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'mvc_directory_engine_save_page_roles' ); ?>
		<input type="hidden" name="action" value="mvc_directory_engine_save_page_roles" />

		<table class="form-table" role="presentation">
			<tbody>
				<?php
				$role_fields = array(
					self::PAGE_ROLE_HOME              => 'Home Page',
					self::PAGE_ROLE_INDUSTRY_OVERVIEW => 'Industry Overview Page',
					self::PAGE_ROLE_SERVICE_OVERVIEW  => 'Service Overview Page',
					self::PAGE_ROLE_CITY_OVERVIEW     => 'City Overview Page',
					self::PAGE_ROLE_BUSINESS_OVERVIEW => 'Local Businesses Overview Page',
				);

				foreach ( $role_fields as $role_key => $label ) :
					$current_page_id = ! empty( $page_roles[ $role_key ] ) ? absint( $page_roles[ $role_key ] ) : 0;
					?>
					<tr>
						<th scope="row">
							<label for="mvc-page-role-<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $label ); ?></label>
						</th>
						<td>
							<select id="mvc-page-role-<?php echo esc_attr( $role_key ); ?>" name="page_roles[<?php echo esc_attr( $role_key ); ?>]">
								<option value="0">— Select a Page —</option>
								<?php foreach ( $all_pages as $page ) : ?>
									<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $current_page_id, (int) $page->ID ); ?>>
										<?php echo esc_html( $page->post_title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p>
			<button type="submit" class="button button-primary">Save Page Roles</button>
		</p>
	</form>
</div>
<div style="background:#fff;border:1px solid #ccd0d4;padding:16px 20px;margin-top:24px;max-width:1200px;">
	<h2 style="margin-top:0;">VA Image Usage Guide</h2>
	<p>Use this guide when uploading images to the Media Library.</p>

	<table class="widefat striped" style="max-width:1000px;">
		<thead>
			<tr>
				<th style="width:180px;">Image Usage</th>
				<th>When to Use It</th>
				<th>Will It Show in Random Images?</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><strong>Gallery</strong></td>
				<td>Standard images for page galleries and normal dynamic image blocks.</td>
				<td>Yes</td>
			</tr>
			<tr>
				<td><strong>Hero</strong></td>
				<td>Strong wide banner image suitable for hero sections. Can also be used in hero image fallback logic.</td>
				<td>Yes, for hero output</td>
			</tr>
<tr>
	<td><strong>Square</strong></td>
	<td>Square-format images for business profile sections or other square-only display areas.</td>
	<td>No, only in square-specific output</td>
</tr>
			<tr>
				<td><strong>Logo</strong></td>
				<td>Business logo or branding mark only.</td>
				<td>No</td>
			</tr>
			<tr>
				<td><strong>Icon</strong></td>
				<td>Small icon, badge, symbol, or graphic element.</td>
				<td>No</td>
			</tr>
			<tr>
				<td><strong>Exclude</strong></td>
				<td>Any asset that should never appear in random image pools.</td>
				<td>No</td>
			</tr>
		</tbody>
	</table>

	<div style="margin-top:16px;padding:12px 14px;background:#f6f7f7;border-left:4px solid #2271b1;max-width:1000px;">
		<p style="margin:0 0 8px;"><strong>Required VA Workflow</strong></p>
		<p style="margin:0 0 6px;">1. Upload image</p>
		<p style="margin:0 0 6px;">2. Confirm file name is correct</p>
		<p style="margin:0 0 6px;">3. Assign Industry, Service, City, and Business if applicable</p>
		<p style="margin:0 0 6px;">4. Assign exactly one Image Usage value</p>
		<p style="margin:0;">5. Save</p>
	</div>
</div>
			<?php if ( ! empty( $sample_rows ) ) : ?>
				<div style="background:#fff;border:1px solid #ccd0d4;padding:16px 20px;margin-top:24px;max-width:1200px;overflow:auto;">
					<h2 style="margin-top:0;">Sample Rows</h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th>service_slug</th>
								<th>industry_slug</th>
								<th>term_name</th>
								<th>parent_name</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $sample_rows as $row ) : ?>
								<tr>
									<td><?php echo esc_html( isset( $row['service_slug'] ) ? $row['service_slug'] : '' ); ?></td>
									<td><?php echo esc_html( isset( $row['industry_slug'] ) ? $row['industry_slug'] : '' ); ?></td>
									<td><?php echo esc_html( isset( $row['term_name'] ) ? $row['term_name'] : '' ); ?></td>
									<td><?php echo esc_html( isset( $row['parent_name'] ) ? $row['parent_name'] : '' ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function handle_mapping_upload() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have permission to do that.' );
		}

		check_admin_referer( self::NONCE_ACTION_UPLOAD );

		if ( empty( $_FILES['mapping_csv'] ) || empty( $_FILES['mapping_csv']['tmp_name'] ) ) {
			$this->redirect_with_notice( 'error', 'No CSV file was uploaded.' );
		}

		$file = $_FILES['mapping_csv'];

		if ( ! empty( $file['error'] ) ) {
			$this->redirect_with_notice( 'error', 'The uploaded file could not be processed.' );
		}

		$parsed = $this->parse_mapping_csv( $file['tmp_name'] );
		if ( is_wp_error( $parsed ) ) {
			$this->redirect_with_notice( 'error', $parsed->get_error_message() );
		}

		update_option( self::OPTION_MAPPING_ROWS, $parsed['rows'], false );
		update_option( self::OPTION_MAPPING_INDEX, $parsed['index'], false );
		update_option(
			self::OPTION_MAPPING_META,
			array(
				'file_name'  => sanitize_file_name( isset( $file['name'] ) ? $file['name'] : '' ),
				'updated_at' => current_time( 'mysql' ),
				'row_count'  => count( $parsed['rows'] ),
			),
			false
		);

		$this->mapping_rows  = null;
		$this->mapping_index = null;

		$this->redirect_with_notice( 'success', sprintf( 'Mapping uploaded successfully. %d rows loaded.', count( $parsed['rows'] ) ) );
	}

	public function handle_mapping_clear() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have permission to do that.' );
		}

		check_admin_referer( self::NONCE_ACTION_CLEAR );

		delete_option( self::OPTION_MAPPING_ROWS );
		delete_option( self::OPTION_MAPPING_INDEX );
		delete_option( self::OPTION_MAPPING_META );

		$this->mapping_rows  = null;
		$this->mapping_index = null;

		$this->redirect_with_notice( 'success', 'Mapping cleared successfully.' );
	}

	private function parse_mapping_csv( $file_path ) {
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return new WP_Error( 'file_not_readable', 'The uploaded CSV file could not be read.' );
		}

		$handle = fopen( $file_path, 'r' );
		if ( ! $handle ) {
			return new WP_Error( 'file_open_failed', 'Unable to open the uploaded CSV file.' );
		}

		$header = fgetcsv( $handle );
		if ( false === $header || empty( $header ) ) {
			fclose( $handle );
			return new WP_Error( 'csv_empty', 'The CSV file is empty.' );
		}

		$header_map = array();
		foreach ( $header as $index => $column_name ) {
			$normalized = $this->normalize_csv_header( $column_name );
			if ( '' !== $normalized ) {
				$header_map[ $normalized ] = $index;
			}
		}

		$required = array( 'service_slug', 'industry_slug' );
		foreach ( $required as $required_column ) {
			if ( ! array_key_exists( $required_column, $header_map ) ) {
				fclose( $handle );
				return new WP_Error( 'csv_missing_columns', sprintf( 'Required column "%s" is missing from the CSV.', $required_column ) );
			}
		}

		$rows  = array();
		$index = array(
			'services'   => array(),
			'industries' => array(),
		);
		$line_number = 1;

		while ( ( $data = fgetcsv( $handle ) ) !== false ) {
			$line_number++;
			if ( $this->csv_row_is_empty( $data ) ) {
				continue;
			}

			$service_slug = isset( $data[ $header_map['service_slug'] ] ) ? $this->normalize_slug( $data[ $header_map['service_slug'] ] ) : '';
			$industry_slug = isset( $data[ $header_map['industry_slug'] ] ) ? $this->normalize_slug( $data[ $header_map['industry_slug'] ] ) : '';
			$term_name = isset( $header_map['term_name'] ) && isset( $data[ $header_map['term_name'] ] ) ? sanitize_text_field( $data[ $header_map['term_name'] ] ) : '';
			$parent_name = isset( $header_map['parent_name'] ) && isset( $data[ $header_map['parent_name'] ] ) ? sanitize_text_field( $data[ $header_map['parent_name'] ] ) : '';

			if ( '' === $service_slug || '' === $industry_slug ) {
				fclose( $handle );
				return new WP_Error( 'csv_invalid_row', sprintf( 'Row %d is missing a required service_slug or industry_slug.', $line_number ) );
			}

			if ( isset( $index['services'][ $service_slug ] ) && $index['services'][ $service_slug ]['industry_slug'] !== $industry_slug ) {
				fclose( $handle );
				return new WP_Error(
					'csv_conflicting_mapping',
					sprintf(
						'Row %1$d conflicts with an earlier row: service_slug "%2$s" is already mapped to industry "%3$s".',
						$line_number,
						$service_slug,
						$index['services'][ $service_slug ]['industry_slug']
					)
				);
			}

			$row = array(
				'service_slug'  => $service_slug,
				'industry_slug' => $industry_slug,
				'term_name'     => $term_name,
				'parent_name'   => $parent_name,
			);

			$rows[] = $row;
			$index['services'][ $service_slug ] = $row;

			if ( ! isset( $index['industries'][ $industry_slug ] ) ) {
				$index['industries'][ $industry_slug ] = array();
			}

			$index['industries'][ $industry_slug ][] = $service_slug;
		}

		fclose( $handle );

		if ( empty( $rows ) ) {
			return new WP_Error( 'csv_no_rows', 'No valid mapping rows were found in the CSV.' );
		}

		foreach ( $index['industries'] as $industry_slug => $service_slugs ) {
			$service_slugs = array_values( array_unique( array_filter( $service_slugs ) ) );
			sort( $service_slugs );
			$index['industries'][ $industry_slug ] = $service_slugs;
		}

		return array(
			'rows'  => $rows,
			'index' => $index,
		);
	}

	private function normalize_csv_header( $header ) {
		$header = is_scalar( $header ) ? (string) $header : '';
		$header = strtolower( trim( $header ) );
		$header = preg_replace( '/[^a-z0-9]+/', '_', $header );
		return trim( (string) $header, '_' );
	}

	private function csv_row_is_empty( $row ) {
		if ( ! is_array( $row ) ) {
			return true;
		}

		foreach ( $row as $value ) {
			if ( '' !== trim( (string) $value ) ) {
				return false;
			}
		}

		return true;
	}

	private function redirect_with_notice( $type, $message ) {
		$type    = in_array( $type, array( 'success', 'error' ), true ) ? $type : 'success';
		$message = rawurlencode( $message );
		$url     = add_query_arg(
			array(
				'page'       => self::MENU_SLUG,
				'mvc_notice' => $type,
				'message'    => $message,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}


public function handle_save_page_roles() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have permission to do that.' );
	}

	check_admin_referer( 'mvc_directory_engine_save_page_roles' );

	$submitted = isset( $_POST['page_roles'] ) && is_array( $_POST['page_roles'] )
		? wp_unslash( $_POST['page_roles'] )
		: array();

	$allowed_roles = array(
		self::PAGE_ROLE_HOME,
		self::PAGE_ROLE_INDUSTRY_OVERVIEW,
		self::PAGE_ROLE_SERVICE_OVERVIEW,
		self::PAGE_ROLE_CITY_OVERVIEW,
		self::PAGE_ROLE_BUSINESS_OVERVIEW,
	);

	$clean = array();

	foreach ( $allowed_roles as $role_key ) {
		$page_id = isset( $submitted[ $role_key ] ) ? absint( $submitted[ $role_key ] ) : 0;
		if ( $page_id > 0 && 'page' === get_post_type( $page_id ) ) {
			$clean[ $role_key ] = $page_id;
		}
	}

	update_option( self::OPTION_PAGE_ROLES, $clean, false );

	$this->redirect_with_notice( 'success', 'Overview page roles saved successfully.' );
}

	public function render_admin_notices() {
		if ( ! is_admin() || empty( $_GET['page'] ) || self::MENU_SLUG !== $_GET['page'] ) {
			return;
		}

		if ( empty( $_GET['mvc_notice'] ) || empty( $_GET['message'] ) ) {
			return;
		}

		$type    = sanitize_key( wp_unslash( $_GET['mvc_notice'] ) );
		$message = sanitize_text_field( wp_unslash( $_GET['message'] ) );
		$class   = 'success' === $type ? 'notice notice-success' : 'notice notice-error';

		echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( rawurldecode( $message ) ) . '</p></div>';
	}
}


require_once plugin_dir_path( __FILE__ ) . 'includes/class-mvc-de-media-taxonomies.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-mvc-de-page-context.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-mvc-de-image-query.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-mvc-de-image-alt.php';


MVC_Directory_Engine::instance();
MVC_DE_Media_Taxonomies::init();


/**
 * Helper wrapper functions for theme/plugin use.
 */

function mvc_directory_engine() {
	return MVC_Directory_Engine::instance();
}

function mvc_de_normalize_string( $value ) {
	return mvc_directory_engine()->normalize_string( $value );
}

function mvc_de_normalize_slug( $value ) {
	return mvc_directory_engine()->normalize_slug( $value );
}

function mvc_de_get_service_mapping( $service_slug ) {
	return mvc_directory_engine()->get_service_mapping( $service_slug );
}

function mvc_de_get_industry_services( $industry_slug ) {
	return mvc_directory_engine()->get_industry_services( $industry_slug );
}

function mvc_de_service_belongs_to_industry( $service_slug, $industry_slug ) {
	return mvc_directory_engine()->service_belongs_to_industry( $service_slug, $industry_slug );
}

function mvc_de_validate_business_assignment( $industry_slugs, $service_slugs ) {
	return mvc_directory_engine()->validate_business_assignment( $industry_slugs, $service_slugs );
}

function mvc_de_assign_business_terms_safely( $post_id, $industry_slugs, $service_slugs, $city_slugs = array() ) {
	return mvc_directory_engine()->assign_business_terms_safely( $post_id, $industry_slugs, $service_slugs, $city_slugs );
}

function mvc_de_get_current_page_context() {
	return MVC_DE_Page_Context::get_current_context();
}

function mvc_de_get_current_industry_slugs() {
	$context = mvc_de_get_current_page_context();
	return ! empty( $context['industries'] ) && is_array( $context['industries'] ) ? $context['industries'] : array();
}

function mvc_de_get_current_service_slugs() {
	$context = mvc_de_get_current_page_context();
	return ! empty( $context['services'] ) && is_array( $context['services'] ) ? $context['services'] : array();
}

function mvc_de_get_current_city_slugs() {
	$context = mvc_de_get_current_page_context();
	return ! empty( $context['cities'] ) && is_array( $context['cities'] ) ? $context['cities'] : array();
}

function mvc_de_get_current_business_slugs() {
	$context = mvc_de_get_current_page_context();
	return ! empty( $context['businesses'] ) && is_array( $context['businesses'] ) ? $context['businesses'] : array();
}


function mvc_de_get_dynamic_image_alt( $attachment_id, $context = array() ) {
	return MVC_DE_Image_Alt::get_dynamic_alt( $attachment_id, $context );
}

function mvc_de_get_images( $limit = 5, $args = array() ) {
	return MVC_DE_Image_Query::get_images( $limit, $args );
}

function mvc_de_get_hero_image( $args = array() ) {
	return MVC_DE_Image_Query::get_hero_image( $args );
}

function mvc_de_get_square_images( $limit = 4, $args = array() ) {
	$limit   = max( 1, absint( $limit ) );
	$context = mvc_de_get_current_page_context();

	$defaults = array(
		'orderby' => 'rand',
	);

	$args = wp_parse_args( $args, $defaults );

	$usage_taxonomy = MVC_Directory_Engine::TAX_IMAGE_USAGE;
	$usage_term     = MVC_Directory_Engine::IMAGE_USAGE_SQUARE;

	// Priority 1: business + square
	if ( ! empty( $context['businesses'] ) ) {
		$images = get_posts( array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'numberposts'    => $limit,
			'orderby'        => $args['orderby'],
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'business_cat',
					'field'    => 'slug',
					'terms'    => $context['businesses'],
				),
				array(
					'taxonomy' => $usage_taxonomy,
					'field'    => 'slug',
					'terms'    => array( $usage_term ),
					'operator' => 'IN',
				),
			),
		) );

		if ( ! empty( $images ) ) {
			return $images;
		}
	}

	// Priority 2: city + square
	if ( ! empty( $context['cities'] ) ) {
		$images = get_posts( array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'numberposts'    => $limit,
			'orderby'        => $args['orderby'],
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => MVC_Directory_Engine::TAX_CITY,
					'field'    => 'slug',
					'terms'    => $context['cities'],
				),
				array(
					'taxonomy' => $usage_taxonomy,
					'field'    => 'slug',
					'terms'    => array( $usage_term ),
					'operator' => 'IN',
				),
			),
		) );

		if ( ! empty( $images ) ) {
			return $images;
		}
	}

	// Priority 3: industry + square
	if ( ! empty( $context['industries'] ) ) {
		$images = get_posts( array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'numberposts'    => $limit,
			'orderby'        => $args['orderby'],
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => MVC_Directory_Engine::TAX_INDUSTRY,
					'field'    => 'slug',
					'terms'    => $context['industries'],
				),
				array(
					'taxonomy' => $usage_taxonomy,
					'field'    => 'slug',
					'terms'    => array( $usage_term ),
					'operator' => 'IN',
				),
			),
		) );

		if ( ! empty( $images ) ) {
			return $images;
		}
	}

	return array();
}


function mvc_de_get_square_image_for_business( $business_post_id ) {
	$business_post_id = absint( $business_post_id );
	if ( $business_post_id <= 0 ) {
		return null;
	}

	// 1. Try business term first.
	$business_terms = wp_get_post_terms( $business_post_id, 'business_cat', array(
		'fields' => 'slugs',
	) );

	if ( ! is_wp_error( $business_terms ) && ! empty( $business_terms ) ) {
		$images = get_posts( array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'numberposts'    => 1,
			'orderby'        => 'rand',
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'business_cat',
					'field'    => 'slug',
					'terms'    => $business_terms,
				),
				array(
					'taxonomy' => MVC_Directory_Engine::TAX_IMAGE_USAGE,
					'field'    => 'slug',
					'terms'    => array( MVC_Directory_Engine::IMAGE_USAGE_SQUARE ),
					'operator' => 'IN',
				),
			),
		) );

		if ( ! empty( $images ) ) {
			return $images[0];
		}
	}

	// 2. Fallback to city.
	$city_terms = wp_get_post_terms( $business_post_id, MVC_Directory_Engine::TAX_CITY, array(
		'fields' => 'slugs',
	) );

	if ( ! is_wp_error( $city_terms ) && ! empty( $city_terms ) ) {
		$images = get_posts( array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'numberposts'    => 1,
			'orderby'        => 'rand',
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => MVC_Directory_Engine::TAX_CITY,
					'field'    => 'slug',
					'terms'    => $city_terms,
				),
				array(
					'taxonomy' => MVC_Directory_Engine::TAX_IMAGE_USAGE,
					'field'    => 'slug',
					'terms'    => array( MVC_Directory_Engine::IMAGE_USAGE_SQUARE ),
					'operator' => 'IN',
				),
			),
		) );

		if ( ! empty( $images ) ) {
			return $images[0];
		}
	}

	// 3. Fallback to industry.
	$industry_terms = wp_get_post_terms( $business_post_id, MVC_Directory_Engine::TAX_INDUSTRY, array(
		'fields' => 'slugs',
	) );

	if ( ! is_wp_error( $industry_terms ) && ! empty( $industry_terms ) ) {
		$images = get_posts( array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'numberposts'    => 1,
			'orderby'        => 'rand',
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy' => MVC_Directory_Engine::TAX_INDUSTRY,
					'field'    => 'slug',
					'terms'    => $industry_terms,
				),
				array(
					'taxonomy' => MVC_Directory_Engine::TAX_IMAGE_USAGE,
					'field'    => 'slug',
					'terms'    => array( MVC_Directory_Engine::IMAGE_USAGE_SQUARE ),
					'operator' => 'IN',
				),
			),
		) );

		if ( ! empty( $images ) ) {
			return $images[0];
		}
	}

	return null;
}

function mvc_de_reset_seen_images() {
	MVC_DE_Image_Query::reset_seen_images();
}


/**
 * Service inheritance and effective service resolution.
 */

if ( ! function_exists( 'mvc_de_get_post_assigned_term_slugs' ) ) {
	function mvc_de_get_post_assigned_term_slugs( $post_id, $taxonomy ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 || ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_the_terms( $post_id, $taxonomy );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		$slugs = array();
		foreach ( $terms as $term ) {
			if ( ! empty( $term->slug ) ) {
				$slugs[] = sanitize_title( $term->slug );
			}
		}

		$slugs = array_values( array_unique( array_filter( $slugs ) ) );
		sort( $slugs );
		return $slugs;
	}
}

if ( ! function_exists( 'mvc_de_get_direct_child_service_slugs' ) ) {
	function mvc_de_get_direct_child_service_slugs( $service_slug ) {
		$service_slug = mvc_de_normalize_slug( $service_slug );
		if ( '' === $service_slug ) {
			return array();
		}

		$term = get_term_by( 'slug', $service_slug, MVC_Directory_Engine::TAX_SERVICE );
		if ( ! $term || is_wp_error( $term ) ) {
			return array();
		}

		$children = get_terms(
			array(
				'taxonomy'   => MVC_Directory_Engine::TAX_SERVICE,
				'hide_empty' => false,
				'parent'     => (int) $term->term_id,
				'fields'     => 'all',
			)
		);

		if ( empty( $children ) || is_wp_error( $children ) ) {
			return array();
		}

		$slugs = array();
		foreach ( $children as $child ) {
			if ( ! empty( $child->slug ) ) {
				$slugs[] = sanitize_title( $child->slug );
			}
		}

		$slugs = array_values( array_unique( array_filter( $slugs ) ) );
		sort( $slugs );
		return $slugs;
	}
}

if ( ! function_exists( 'mvc_de_get_industry_child_service_slugs' ) ) {
	function mvc_de_get_industry_child_service_slugs( $industry_slug ) {
		$industry_slug = mvc_de_normalize_slug( $industry_slug );
		if ( '' === $industry_slug ) {
			return array();
		}

		$service_slugs = mvc_de_get_industry_services( $industry_slug );
		if ( empty( $service_slugs ) ) {
			return array();
		}

		$resolved = array();
		foreach ( $service_slugs as $service_slug ) {
			$children = mvc_de_get_direct_child_service_slugs( $service_slug );
			if ( ! empty( $children ) ) {
				$resolved = array_merge( $resolved, $children );
			} else {
				$resolved[] = mvc_de_normalize_slug( $service_slug );
			}
		}

		$resolved = array_values( array_unique( array_filter( $resolved ) ) );
		sort( $resolved );
		return $resolved;
	}
}

if ( ! function_exists( 'mvc_de_get_effective_service_slugs' ) ) {
	function mvc_de_get_effective_service_slugs( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			return array();
		}

		$industry_slugs = mvc_de_get_post_assigned_term_slugs( $post_id, MVC_Directory_Engine::TAX_INDUSTRY );
		$service_slugs  = mvc_de_get_post_assigned_term_slugs( $post_id, MVC_Directory_Engine::TAX_SERVICE );
		$effective      = array();

		if ( empty( $service_slugs ) && ! empty( $industry_slugs ) ) {
			foreach ( $industry_slugs as $industry_slug ) {
				$effective = array_merge( $effective, mvc_de_get_industry_child_service_slugs( $industry_slug ) );
			}
			$effective = array_values( array_unique( array_filter( $effective ) ) );
			sort( $effective );
			return $effective;
		}

		foreach ( $service_slugs as $service_slug ) {
			$children = mvc_de_get_direct_child_service_slugs( $service_slug );
			if ( ! empty( $children ) ) {
				$effective = array_merge( $effective, $children );
			} else {
				$effective[] = mvc_de_normalize_slug( $service_slug );
			}
		}

		if ( ! empty( $industry_slugs ) ) {
			$effective = array_values( array_filter( $effective, function( $service_slug ) use ( $industry_slugs ) {
				$mapping = mvc_de_get_service_mapping( $service_slug );
				return ! empty( $mapping['industry_slug'] ) && in_array( $mapping['industry_slug'], $industry_slugs, true );
			} ) );
		}

		$effective = array_values( array_unique( array_filter( $effective ) ) );
		sort( $effective );
		return $effective;
	}
}

if ( ! function_exists( 'mvc_de_get_effective_service_term_ids' ) ) {
	function mvc_de_get_effective_service_term_ids( $post_id ) {
		$slugs = mvc_de_get_effective_service_slugs( $post_id );
		if ( empty( $slugs ) ) {
			return array();
		}

		$term_ids = array();
		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, MVC_Directory_Engine::TAX_SERVICE );
			if ( $term && ! is_wp_error( $term ) ) {
				$term_ids[] = (int) $term->term_id;
			}
		}

		$term_ids = array_values( array_unique( array_filter( $term_ids ) ) );
		sort( $term_ids );
		return $term_ids;
	}
}

if ( ! function_exists( 'mvc_de_post_matches_service' ) ) {
	function mvc_de_post_matches_service( $post_id, $service_slug ) {
		$service_slug = mvc_de_normalize_slug( $service_slug );
		if ( '' === $service_slug ) {
			return false;
		}

		$effective = mvc_de_get_effective_service_slugs( $post_id );
		return in_array( $service_slug, $effective, true );
	}
}






add_shortcode( 'mvc_images', function( $atts ) {

	$atts = shortcode_atts( array(
	'limit'         => 5,
	'size'          => 'medium',
	'layout'        => 'grid',
	'columns'       => 3,
	'class'         => '',
	'show_caption'  => 'no',
	'link_to_file'  => 'no',
	'exclude_seen'  => 'no',
	'mark_seen'     => 'yes',
), $atts, 'mvc_images' );

$exclude_seen = in_array( strtolower( $atts['exclude_seen'] ), array( 'yes', 'true', '1' ), true );
$mark_seen    = in_array( strtolower( $atts['mark_seen'] ), array( 'yes', 'true', '1' ), true );


	$limit        = max( 1, absint( $atts['limit'] ) );
	$size         = sanitize_key( $atts['size'] );
	$layout       = sanitize_key( $atts['layout'] );
	$columns      = max( 1, min( 6, absint( $atts['columns'] ) ) );
	$custom_class = sanitize_html_class( $atts['class'] );
	$show_caption = in_array( strtolower( $atts['show_caption'] ), array( 'yes', 'true', '1' ), true );
	$link_to_file = in_array( strtolower( $atts['link_to_file'] ), array( 'yes', 'true', '1' ), true );

	$images = mvc_de_get_images( $limit, array(
	'exclude_seen' => $exclude_seen,
	'mark_seen'    => $mark_seen,
	'mode'         => 'default',
) );


	if ( empty( $images ) ) {
		return '';
	}

	$wrapper_classes = array(
		'mvc-images',
		'mvc-images-layout-' . $layout,
		'mvc-images-columns-' . $columns,
	);

	if ( ! empty( $custom_class ) ) {
		$wrapper_classes[] = $custom_class;
	}

	ob_start();

	// Inline CSS for now. Later we can move this to an enqueued stylesheet.
	static $mvc_images_css_printed = false;
	if ( ! $mvc_images_css_printed ) {
		$mvc_images_css_printed = true;
		?>
		<style>
			.mvc-images {
				width: 100%;
			}
			.mvc-images-layout-grid {
				display: grid;
				gap: 16px;
			}
			.mvc-images-columns-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
			.mvc-images-columns-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
			.mvc-images-columns-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
			.mvc-images-columns-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
			.mvc-images-columns-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
			.mvc-images-columns-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }

			.mvc-images-layout-hero {
				display: block;
			}

			.mvc-image-item {
				margin: 0;
			}

			.mvc-image-item img {
				display: block;
				width: 100%;
				height: auto;
				border-radius: 8px;
			}

			.mvc-images-layout-grid .mvc-image-item img {
				aspect-ratio: 4 / 3;
				object-fit: cover;
			}

			.mvc-images-layout-hero .mvc-image-item img {
				width: 100%;
				aspect-ratio: 16 / 9;
				object-fit: cover;
			}

			.mvc-image-caption {
				margin-top: 8px;
				font-size: 14px;
				line-height: 1.4;
			}

			@media (max-width: 1024px) {
				.mvc-images-columns-4,
				.mvc-images-columns-5,
				.mvc-images-columns-6 {
					grid-template-columns: repeat(3, minmax(0, 1fr));
				}
			}

			@media (max-width: 767px) {
				.mvc-images-layout-grid {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}
			}

			@media (max-width: 479px) {
				.mvc-images-layout-grid {
					grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
				}
			}
		</style>
		<?php
	}

	echo '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '">';

	foreach ( $images as $img ) {
	$attachment_id = (int) $img->ID;
	$context       = mvc_de_get_current_page_context();
	$dynamic_alt   = mvc_de_get_dynamic_image_alt( $attachment_id, $context );

	if ( empty( $dynamic_alt ) ) {
		$dynamic_alt = 'Local service business image';
	}

	$image_html = wp_get_attachment_image( $attachment_id, $size, false, array(
		'class' => 'mvc-image',
		'alt'   => esc_attr( $dynamic_alt ),
	) );

	if ( empty( $image_html ) ) {
		continue;
	}

	$caption = wp_get_attachment_caption( $attachment_id );

	echo '<figure class="mvc-image-item">';

	if ( $link_to_file ) {
		$file_url = wp_get_attachment_url( $attachment_id );
		if ( $file_url ) {
			echo '<a href="' . esc_url( $file_url ) . '">';
			echo $image_html;
			echo '</a>';
		} else {
			echo $image_html;
		}
	} else {
		echo $image_html;
	}

	if ( $show_caption && ! empty( $caption ) ) {
		echo '<figcaption class="mvc-image-caption">' . esc_html( $caption ) . '</figcaption>';
	}

	echo '</figure>';
}

	echo '</div>';

	return ob_get_clean();
});


add_shortcode( 'mvc_hero_image', function( $atts ) {

	$atts = shortcode_atts( array(
		'size'         => 'full',
		'class'        => '',
		'link_to_file' => 'no',
		'mark_seen'    => 'yes',
	), $atts, 'mvc_hero_image' );

	$size         = sanitize_key( $atts['size'] );
	$custom_class = sanitize_html_class( $atts['class'] );
	$link_to_file = in_array( strtolower( $atts['link_to_file'] ), array( 'yes', 'true', '1' ), true );
	$mark_seen    = in_array( strtolower( $atts['mark_seen'] ), array( 'yes', 'true', '1' ), true );

	$image = mvc_de_get_hero_image( array(
		'exclude_seen' => false,
		'mark_seen'    => $mark_seen,
		'mode'         => 'hero',
	) );

	if ( empty( $image ) || empty( $image->ID ) ) {
		return '';
	}

	$attachment_id = (int) $image->ID;
	$context       = mvc_de_get_current_page_context();
	$dynamic_alt   = mvc_de_get_dynamic_image_alt( $attachment_id, $context );

	if ( empty( $dynamic_alt ) ) {
		$dynamic_alt = 'Local service business image';
	}

	$image_html = wp_get_attachment_image( $attachment_id, $size, false, array(
		'class' => 'mvc-hero-image',
		'alt'   => esc_attr( $dynamic_alt ),
	) );

	if ( empty( $image_html ) ) {
		return '';
	}

	ob_start();

	static $mvc_hero_css_printed = false;
	if ( ! $mvc_hero_css_printed ) {
		$mvc_hero_css_printed = true;
		?>
		<style>
			.mvc-hero-image-wrap {
				width: 100%;
			}
			.mvc-hero-image-wrap img {
				display: block;
				width: 100%;
				height: auto;
				aspect-ratio: 16 / 9;
				object-fit: cover;
				border-radius: 10px;
			}
		</style>
		<?php
	}

	$classes = array( 'mvc-hero-image-wrap' );
	if ( ! empty( $custom_class ) ) {
		$classes[] = $custom_class;
	}

	echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';

	if ( $link_to_file ) {
		$file_url = wp_get_attachment_url( $attachment_id );
		if ( $file_url ) {
			echo '<a href="' . esc_url( $file_url ) . '">';
			echo $image_html;
			echo '</a>';
		} else {
			echo $image_html;
		}
	} else {
		echo $image_html;
	}

	echo '</div>';

	return ob_get_clean();
});


add_shortcode( 'mvc_square_images', function( $atts ) {

	$atts = shortcode_atts( array(
		'limit'         => 4,
		'size'          => 'medium_large',
		'columns'       => 4,
		'class'         => '',
		'show_caption'  => 'no',
		'link_to_file'  => 'no',
	), $atts, 'mvc_square_images' );

	$limit        = max( 1, absint( $atts['limit'] ) );
	$size         = sanitize_key( $atts['size'] );
	$columns      = max( 1, min( 6, absint( $atts['columns'] ) ) );
	$custom_class = sanitize_html_class( $atts['class'] );
	$show_caption = in_array( strtolower( $atts['show_caption'] ), array( 'yes', 'true', '1' ), true );
	$link_to_file = in_array( strtolower( $atts['link_to_file'] ), array( 'yes', 'true', '1' ), true );

	$images = mvc_de_get_square_images( $limit );

	if ( empty( $images ) ) {
		return '';
	}

	ob_start();

	static $mvc_square_css_printed = false;
	if ( ! $mvc_square_css_printed ) {
		$mvc_square_css_printed = true;
		?>
		<style>
			.mvc-square-images {
				display: grid;
				gap: 16px;
				width: 100%;
			}
			.mvc-square-columns-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
			.mvc-square-columns-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
			.mvc-square-columns-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
			.mvc-square-columns-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
			.mvc-square-columns-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
			.mvc-square-columns-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }

			.mvc-square-image-item {
				margin: 0;
			}

			.mvc-square-image-item img {
				display: block;
				width: 100%;
				height: auto;
				aspect-ratio: 1 / 1;
				object-fit: cover;
				border-radius: 10px;
			}

			.mvc-square-image-caption {
				margin-top: 8px;
				font-size: 14px;
				line-height: 1.4;
			}

			@media (max-width: 1024px) {
				.mvc-square-columns-4,
				.mvc-square-columns-5,
				.mvc-square-columns-6 {
					grid-template-columns: repeat(3, minmax(0, 1fr));
				}
			}

			@media (max-width: 767px) {
				.mvc-square-images {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}
			}

			@media (max-width: 479px) {
				.mvc-square-images {
					grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
				}
			}
		</style>
		<?php
	}

	$wrapper_classes = array(
		'mvc-square-images',
		'mvc-square-columns-' . $columns,
	);

	if ( ! empty( $custom_class ) ) {
		$wrapper_classes[] = $custom_class;
	}

	echo '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '">';

	foreach ( $images as $img ) {
		$attachment_id = (int) $img->ID;
		$context       = mvc_de_get_current_page_context();
		$dynamic_alt   = mvc_de_get_dynamic_image_alt( $attachment_id, $context );

		if ( empty( $dynamic_alt ) ) {
			$dynamic_alt = 'Business profile image';
		}

		$image_html = wp_get_attachment_image( $attachment_id, $size, false, array(
			'class' => 'mvc-square-image',
			'alt'   => esc_attr( $dynamic_alt ),
		) );

		if ( empty( $image_html ) ) {
			continue;
		}

		$caption = wp_get_attachment_caption( $attachment_id );

		echo '<figure class="mvc-square-image-item">';

		if ( $link_to_file ) {
			$file_url = wp_get_attachment_url( $attachment_id );
			if ( $file_url ) {
				echo '<a href="' . esc_url( $file_url ) . '">';
				echo $image_html;
				echo '</a>';
			} else {
				echo $image_html;
			}
		} else {
			echo $image_html;
		}

		if ( $show_caption && ! empty( $caption ) ) {
			echo '<figcaption class="mvc-square-image-caption">' . esc_html( $caption ) . '</figcaption>';
		}

		echo '</figure>';
	}

	echo '</div>';

	return ob_get_clean();
});


//Debuging
//add_shortcode( 'mvc_test_context', function() {
//
//	if ( ! function_exists( 'mvc_de_get_current_page_context' ) ) {
//		return 'Context function not found.';
//	}
//
//	$context = mvc_de_get_current_page_context();
//
//	ob_start();
//
//	echo '<pre style="background:#000;color:#0f0;padding:15px;font-size:12px;line-height:1.5;">';
//	print_r( $context );
//	echo '</pre>';
//
//	return ob_get_clean();
//
//   });


add_shortcode( 'mvc_debug_image_usage', function( $atts ) {
	$atts = shortcode_atts( array(
		'id' => 0,
	), $atts, 'mvc_debug_image_usage' );

	$attachment_id = absint( $atts['id'] );
	if ( $attachment_id <= 0 ) {
		return 'No attachment ID provided.';
	}

	$taxonomies = array(
		'industry_cat',
		'service_type',
		'city_cat',
		'business_cat',
		'image_usage',
	);

	ob_start();

	echo '<pre style="background:#000;color:#0f0;padding:15px;font-size:12px;line-height:1.5;">';
	echo 'Attachment ID: ' . $attachment_id . "\n\n";

	foreach ( $taxonomies as $taxonomy ) {
		$terms = wp_get_object_terms( $attachment_id, $taxonomy, array(
			'fields' => 'slugs',
		) );

		echo $taxonomy . ': ';
		if ( is_wp_error( $terms ) ) {
			echo 'ERROR';
		} elseif ( empty( $terms ) ) {
			echo '(none)';
		} else {
			print_r( $terms );
		}
		echo "\n";
	}

	echo '</pre>';

	return ob_get_clean();
});


add_shortcode( 'mvc_debug_image_ids', function( $atts ) {
	$atts = shortcode_atts( array(
		'limit' => 10,
		'mode'  => 'default',
	), $atts, 'mvc_debug_image_ids' );

	$limit = max( 1, absint( $atts['limit'] ) );
	$mode  = sanitize_key( $atts['mode'] );

	$images = mvc_de_get_images( $limit, array(
		'exclude_seen' => false,
		'mark_seen'    => false,
		'mode'         => $mode,
	) );

	ob_start();

	echo '<pre style="background:#000;color:#0f0;padding:15px;font-size:12px;line-height:1.5;">';

	if ( empty( $images ) ) {
		echo 'No images found.';
	} else {
		foreach ( $images as $img ) {
			echo 'ID: ' . $img->ID . ' | Title: ' . get_the_title( $img->ID ) . "\n";
		}
	}

	echo '</pre>';

	return ob_get_clean();
});