<?php
/*
Plugin Name: MVC Taxonomy CSV Importer
Description: Reusable CSV importer/exporter for hierarchical WordPress taxonomies with parent/child support, export-all, and missing-description export.
Version: 1.2
Author: MVC
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MVC_Taxonomy_CSV_Importer {

	const OPTION_KEY   = 'mvc_tax_csv_importer_settings';
	const NONCE_ACTION = 'mvc_tax_csv_importer_run';
	const MENU_SLUG    = 'mvc-taxonomy-csv-importer';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'handle_form_submission' ] );
	}

	public function add_admin_menu() {
		add_management_page(
			'MVC Taxonomy Importer',
			'MVC Taxonomy Importer',
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_admin_page' ]
		);
	}

	public function get_saved_settings() {
		$defaults = [
			'taxonomy' => '',
			'csv_file' => '',
		];

		$saved = get_option( self::OPTION_KEY, [] );

		if ( ! is_array( $saved ) ) {
			$saved = [];
		}

		return wp_parse_args( $saved, $defaults );
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->get_saved_settings();
		$taxonomy = $settings['taxonomy'];
		$csv_file = $settings['csv_file'];
		?>
		<div class="wrap">
			<h1>MVC Taxonomy CSV Importer</h1>
			<p>Import taxonomy terms from CSV, assign parent/child automatically, export all terms, or export only terms missing descriptions.</p>

			<div style="background:#fff;border:1px solid #ccd0d4;padding:16px 20px;margin:20px 0;max-width:900px;">
				<h2 style="margin-top:0;">Required CSV Headers</h2>
				<code>term name, slug, parent name, short description</code>
				<p style="margin-bottom:0;">Rows can be in any order. Parents are assigned in a second pass.</p>
			</div>

			<form method="post">
				<?php wp_nonce_field( self::NONCE_ACTION, 'mvc_tax_importer_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="mvc_taxonomy_slug">Taxonomy Slug</label>
							</th>
							<td>
								<input
									name="mvc_taxonomy_slug"
									type="text"
									id="mvc_taxonomy_slug"
									value="<?php echo esc_attr( $taxonomy ); ?>"
									class="regular-text"
									placeholder="service_type"
								>
								<p class="description">Example: <code>service_type</code>, <code>industry_cat</code>, <code>city_cat</code></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="mvc_csv_file">CSV File Path</label>
							</th>
							<td>
								<input
									name="mvc_csv_file"
									type="text"
									id="mvc_csv_file"
									value="<?php echo esc_attr( $csv_file ); ?>"
									class="large-text"
									placeholder="/wp-content/uploads/hvac_taxonomy_services.csv"
								>
								<p class="description">
									Use a server path or WordPress-relative path.<br>
									Examples:<br>
									<code>/wp-content/uploads/hvac_taxonomy_services.csv</code><br>
									<code>wp-content/uploads/hvac_taxonomy_services.csv</code>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p>
					<button type="submit" name="mvc_tax_importer_action" value="save_and_run" class="button button-primary">
						Save Settings &amp; Run Import
					</button>
					<button type="submit" name="mvc_tax_importer_action" value="export_missing" class="button button-secondary">
						Export Terms Missing Descriptions
					</button>
					<button type="submit" name="mvc_tax_importer_action" value="export_all" class="button button-secondary">
						Export All Terms
					</button>
				</p>
			</form>

			<hr>

			<h2>How to Use</h2>
			<ol>
				<li>Upload your CSV into <code>/wp-content/uploads/...</code></li>
				<li>Enter the taxonomy slug</li>
				<li>Enter the CSV path for imports</li>
				<li>Run import, export missing descriptions, or export all terms</li>
			</ol>
		</div>
		<?php
	}

	public function handle_form_submission() {
		if (
			! is_admin() ||
			! current_user_can( 'manage_options' ) ||
			! isset( $_POST['mvc_tax_importer_action'] )
		) {
			return;
		}

		if (
			! isset( $_POST['mvc_tax_importer_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mvc_tax_importer_nonce'] ) ), self::NONCE_ACTION )
		) {
			wp_die( 'Security check failed.' );
		}

		$action   = sanitize_text_field( wp_unslash( $_POST['mvc_tax_importer_action'] ) );
		$taxonomy = isset( $_POST['mvc_taxonomy_slug'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['mvc_taxonomy_slug'] ) ) ) : '';
		$csv_file = isset( $_POST['mvc_csv_file'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['mvc_csv_file'] ) ) ) : '';

		update_option(
			self::OPTION_KEY,
			[
				'taxonomy' => $taxonomy,
				'csv_file' => $csv_file,
			]
		);

		if ( $action === 'export_missing' ) {
			$this->export_terms_csv( $taxonomy, true );
		}

		if ( $action === 'export_all' ) {
			$this->export_terms_csv( $taxonomy, false );
		}

		if ( $action !== 'save_and_run' ) {
			return;
		}

		$result = $this->run_import( $taxonomy, $csv_file );

		$redirect_url = add_query_arg(
			[
				'page'               => self::MENU_SLUG,
				'mvc_import_done'    => 1,
				'mvc_import_message' => rawurlencode( $result['message'] ),
				'mvc_import_status'  => $result['status'],
			],
			admin_url( 'tools.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	private function run_import( $taxonomy, $csv_file ) {
		if ( empty( $taxonomy ) ) {
			return [
				'status'  => 'error',
				'message' => 'Taxonomy slug is required.',
			];
		}

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return [
				'status'  => 'error',
				'message' => 'Taxonomy does not exist: ' . $taxonomy,
			];
		}

		$taxonomy_obj = get_taxonomy( $taxonomy );
		if ( ! $taxonomy_obj || empty( $taxonomy_obj->hierarchical ) ) {
			return [
				'status'  => 'error',
				'message' => 'This taxonomy is not hierarchical. Parent/child only works on hierarchical taxonomies.',
			];
		}

		if ( empty( $csv_file ) ) {
			return [
				'status'  => 'error',
				'message' => 'CSV file path is required.',
			];
		}

		$resolved_csv_file = $this->resolve_csv_path( $csv_file );

		if ( ! $resolved_csv_file || ! file_exists( $resolved_csv_file ) ) {
			return [
				'status'  => 'error',
				'message' => 'CSV file not found: ' . $csv_file,
			];
		}

		$rows = $this->read_csv( $resolved_csv_file );

		if ( is_wp_error( $rows ) ) {
			return [
				'status'  => 'error',
				'message' => $rows->get_error_message(),
			];
		}

		if ( empty( $rows ) ) {
			return [
				'status'  => 'error',
				'message' => 'No valid rows found in CSV.',
			];
		}

		$term_map        = [];
		$created         = 0;
		$updated         = 0;
		$parent_assigned = 0;
		$errors          = [];

		foreach ( $rows as $index => $row ) {
			$name        = $row['term name'] ?? '';
			$slug        = $row['slug'] ?? '';
			$description = $row['short description'] ?? '';

			if ( empty( $name ) ) {
				continue;
			}

			$existing = $this->find_existing_term( $taxonomy, $slug, $name );

			if ( $existing && ! is_wp_error( $existing ) ) {
				$term_id = is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;

				$result = wp_update_term(
					$term_id,
					$taxonomy,
					[
						'name'        => $name,
						'slug'        => $slug,
						'description' => $description,
					]
				);

				if ( is_wp_error( $result ) ) {
					$errors[] = 'Row ' . ( $index + 2 ) . ': failed updating "' . $name . '" - ' . $result->get_error_message();
					continue;
				}

				$updated++;
			} else {
				$result = wp_insert_term(
					$name,
					$taxonomy,
					[
						'slug'        => $slug,
						'description' => $description,
					]
				);

				if ( is_wp_error( $result ) ) {
					$errors[] = 'Row ' . ( $index + 2 ) . ': failed creating "' . $name . '" - ' . $result->get_error_message();
					continue;
				}

				$term_id = (int) $result['term_id'];
				$created++;
			}

			if ( ! empty( $slug ) ) {
				$term_map[ 'slug:' . $slug ] = $term_id;
			}
			$term_map[ 'name:' . $name ] = $term_id;
		}

		foreach ( $rows as $index => $row ) {
			$name        = $row['term name'] ?? '';
			$slug        = $row['slug'] ?? '';
			$parent_name = $row['parent name'] ?? '';

			if ( empty( $name ) || empty( $parent_name ) ) {
				continue;
			}

			$child_id = 0;

			if ( ! empty( $slug ) && ! empty( $term_map[ 'slug:' . $slug ] ) ) {
				$child_id = (int) $term_map[ 'slug:' . $slug ];
			} elseif ( ! empty( $term_map[ 'name:' . $name ] ) ) {
				$child_id = (int) $term_map[ 'name:' . $name ];
			}

			if ( empty( $child_id ) ) {
				$errors[] = 'Row ' . ( $index + 2 ) . ': child term not found for "' . $name . '"';
				continue;
			}

			$parent_id = 0;

			if ( ! empty( $term_map[ 'name:' . $parent_name ] ) ) {
				$parent_id = (int) $term_map[ 'name:' . $parent_name ];
			} else {
				$parent_existing = term_exists( $parent_name, $taxonomy );
				if ( $parent_existing && ! is_wp_error( $parent_existing ) ) {
					$parent_id = is_array( $parent_existing ) ? (int) $parent_existing['term_id'] : (int) $parent_existing;
					$term_map[ 'name:' . $parent_name ] = $parent_id;
				}
			}

			if ( empty( $parent_id ) ) {
				$errors[] = 'Row ' . ( $index + 2 ) . ': parent term not found for "' . $name . '" → "' . $parent_name . '"';
				continue;
			}

			if ( $child_id === $parent_id ) {
				$errors[] = 'Row ' . ( $index + 2 ) . ': "' . $name . '" cannot be its own parent.';
				continue;
			}

			$result = wp_update_term(
				$child_id,
				$taxonomy,
				[
					'parent' => $parent_id,
				]
			);

			if ( is_wp_error( $result ) ) {
				$errors[] = 'Row ' . ( $index + 2 ) . ': failed assigning parent for "' . $name . '" - ' . $result->get_error_message();
				continue;
			}

			$parent_assigned++;
		}

		$message = "Import finished. Created: {$created}. Updated: {$updated}. Parent assignments: {$parent_assigned}.";

		if ( ! empty( $errors ) ) {
			$message .= ' Issues: ' . implode( ' | ', array_slice( $errors, 0, 10 ) );
			if ( count( $errors ) > 10 ) {
				$message .= ' | Additional issues not shown: ' . ( count( $errors ) - 10 );
			}
		}

		return [
			'status'  => empty( $errors ) ? 'success' : 'warning',
			'message' => $message,
		];
	}

	private function find_existing_term( $taxonomy, $slug, $name ) {
		if ( ! empty( $slug ) ) {
			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				return [ 'term_id' => (int) $term->term_id ];
			}
		}

		if ( ! empty( $name ) ) {
			$term = term_exists( $name, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				return $term;
			}
		}

		return false;
	}

	private function resolve_csv_path( $csv_file ) {
		$csv_file = trim( $csv_file );

		if ( file_exists( $csv_file ) ) {
			return $csv_file;
		}

		if ( strpos( $csv_file, '/wp-content/' ) === 0 ) {
			$path = ABSPATH . ltrim( $csv_file, '/' );
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		if ( strpos( $csv_file, 'wp-content/' ) === 0 ) {
			$path = ABSPATH . $csv_file;
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		$upload_dir = wp_upload_dir();
		$basename   = basename( $csv_file );
		$path       = trailingslashit( $upload_dir['basedir'] ) . $basename;

		if ( file_exists( $path ) ) {
			return $path;
		}

		return false;
	}

	private function read_csv( $csv_file ) {
		$rows = [];

		$handle = fopen( $csv_file, 'r' );
		if ( ! $handle ) {
			return new WP_Error( 'csv_open_failed', 'Unable to open CSV file.' );
		}

		$header = fgetcsv( $handle );
		if ( ! $header ) {
			fclose( $handle );
			return new WP_Error( 'csv_header_missing', 'CSV header row is missing.' );
		}

		$header = array_map(
			function( $h ) {
				return strtolower( trim( $h ) );
			},
			$header
		);

		$required_headers = [ 'term name', 'slug', 'parent name', 'short description' ];
		foreach ( $required_headers as $required ) {
			if ( ! in_array( $required, $header, true ) ) {
				fclose( $handle );
				return new WP_Error( 'csv_header_invalid', 'Missing required CSV header: ' . $required );
			}
		}

		while ( ( $data = fgetcsv( $handle ) ) !== false ) {
			if ( count( $data ) !== count( $header ) ) {
				continue;
			}

			$row = array_combine( $header, $data );

			$row = array_map(
				function( $value ) {
					return is_string( $value ) ? trim( $value ) : $value;
				},
				$row
			);

			$rows[] = $row;
		}

		fclose( $handle );

		return $rows;
	}

	private function export_terms_csv( $taxonomy, $missing_only = false ) {
		if ( empty( $taxonomy ) ) {
			wp_die( 'Taxonomy slug is required.' );
		}

		if ( ! taxonomy_exists( $taxonomy ) ) {
			wp_die( 'Invalid taxonomy: ' . esc_html( $taxonomy ) );
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( is_wp_error( $terms ) ) {
			wp_die( 'Failed to retrieve terms.' );
		}

		$filename = $missing_only
			? $taxonomy . '-missing-descriptions.csv'
			: $taxonomy . '-all-terms.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );

		fputcsv( $output, [ 'term name', 'slug', 'parent name', 'short description' ] );

		foreach ( $terms as $term ) {
			$description = trim( (string) $term->description );

			if ( $missing_only && $description !== '' ) {
				continue;
			}

			$parent_name = '';
			if ( ! empty( $term->parent ) ) {
				$parent_term = get_term( $term->parent, $taxonomy );
				if ( $parent_term && ! is_wp_error( $parent_term ) ) {
					$parent_name = $parent_term->name;
				}
			}

			fputcsv(
				$output,
				[
					$term->name,
					$term->slug,
					$parent_name,
					$description,
				]
			);
		}

		fclose( $output );
		exit;
	}
}

new MVC_Taxonomy_CSV_Importer();

add_action( 'admin_notices', function() {
	if (
		! is_admin() ||
		! current_user_can( 'manage_options' ) ||
		! isset( $_GET['page'], $_GET['mvc_import_done'], $_GET['mvc_import_message'], $_GET['mvc_import_status'] ) ||
		$_GET['page'] !== MVC_Taxonomy_CSV_Importer::MENU_SLUG
	) {
		return;
	}

	$status  = sanitize_text_field( wp_unslash( $_GET['mvc_import_status'] ) );
	$message = sanitize_text_field( wp_unslash( $_GET['mvc_import_message'] ) );

	$class = 'notice notice-info';
	if ( $status === 'success' ) {
		$class = 'notice notice-success';
	} elseif ( $status === 'error' ) {
		$class = 'notice notice-error';
	} elseif ( $status === 'warning' ) {
		$class = 'notice notice-warning';
	}

	echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
} );