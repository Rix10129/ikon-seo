<?php

defined( 'ABSPATH' ) || exit;

/** Coordinates structured-data and media governance. */
final class Ikon_SEO_Structured_Media_Governance {
	const CRON_HOOK = 'ikon_seo_governance_weekly';

	private $schema_governance;
	private $media_governance;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Schema_Governance $schema_governance,
		Ikon_SEO_Media_Governance $media_governance,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->schema_governance = $schema_governance;
		$this->media_governance  = $media_governance;
		$this->history           = $history;
		$this->logger            = $logger;
	}

	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'scheduled_run' ) );
		add_action( 'save_post', array( $this, 'mark_post_stale' ), 30, 3 );
		add_action( 'add_attachment', array( $this, 'mark_attachment_stale' ) );
		add_action( 'edit_attachment', array( $this, 'mark_attachment_stale' ) );
	}

	public function scheduled_run() {
		$settings = Ikon_SEO_Plugin::settings();
		if ( empty( $settings['structured_media_governance_enabled'] ) ) {
			return;
		}
		$this->schema_governance->audit_batch( absint( $settings['schema_governance_batch_size'] ?? 10 ), false, 0, 'scheduled' );
		$this->media_governance->audit_batch( absint( $settings['media_governance_batch_size'] ?? 50 ), false, 0, 'scheduled' );
		$this->cleanup();
	}

	public function mark_post_stale( $post_id, $post, $update ) {
		global $wpdb;
		if ( ! $post instanceof WP_Post || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'publish' !== $post->post_status ) {
			return;
		}
		$table = $this->schema_governance->table();
		if ( $this->table_exists( $table ) ) {
			$wpdb->update( $table, array( 'checked_at' => null ), array( 'post_id' => absint( $post_id ) ) );
		}
	}

	public function mark_attachment_stale( $attachment_id ) {
		global $wpdb;
		$table = $this->media_governance->table();
		if ( $this->table_exists( $table ) ) {
			$wpdb->update( $table, array( 'checked_at' => null ), array( 'attachment_id' => absint( $attachment_id ) ) );
		}
	}

	public function status() {
		return array(
			'enabled' => ! empty( Ikon_SEO_Plugin::settings()['structured_media_governance_enabled'] ),
			'schema'  => $this->schema_governance->status(),
			'media'   => $this->media_governance->status(),
			'safe_boundary' => array(
				'changes_schema' => false,
				'changes_media'  => false,
				'changes_pages'  => false,
			),
		);
	}

	public function report( $limit = 100 ) {
		return array(
			'status' => $this->status(),
			'schema' => $this->schema_governance->report( $limit ),
			'media'  => $this->media_governance->report( $limit ),
			'generated_at' => current_time( 'mysql', true ),
		);
	}

	public function sync( array $payload, $user_id = 0 ) {
		$command = sanitize_key( $payload['command'] ?? 'read' );
		switch ( $command ) {
			case 'audit_schema_batch':
				return $this->schema_governance->audit_batch( absint( $payload['limit'] ?? 10 ), ! empty( $payload['force'] ), $user_id, 'workspace' );
			case 'audit_schema_url':
				return $this->schema_governance->audit_url( sanitize_text_field( $payload['url'] ?? '' ), $user_id, 'workspace' );
			case 'audit_media_batch':
				return $this->media_governance->audit_batch( absint( $payload['limit'] ?? 50 ), ! empty( $payload['force'] ), $user_id, 'workspace' );
			case 'audit_media_item':
				return $this->media_governance->audit_attachment( absint( $payload['attachment_id'] ?? 0 ), $user_id, 'workspace' );
			case 'save_media_rights':
				return $this->media_governance->save_rights( absint( $payload['attachment_id'] ?? 0 ), (array) ( $payload['rights'] ?? array() ), $user_id );
			case 'run_all':
				return array(
					'schema' => $this->schema_governance->audit_batch( absint( $payload['schema_limit'] ?? 10 ), ! empty( $payload['force'] ), $user_id, 'workspace' ),
					'media'  => $this->media_governance->audit_batch( absint( $payload['media_limit'] ?? 50 ), ! empty( $payload['force'] ), $user_id, 'workspace' ),
				);
			case 'save_settings':
				return $this->save_settings( $payload, $user_id );
			case 'cleanup':
				return $this->cleanup();
			case 'read':
			default:
				return $this->report( absint( $payload['report_limit'] ?? 100 ) );
		}
	}

	public function save_settings( array $payload, $user_id = 0 ) {
		$settings = Ikon_SEO_Plugin::settings();
		$settings['structured_media_governance_enabled'] = ! empty( $payload['enabled'] ) ? 1 : 0;
		$settings['schema_governance_batch_size'] = max( 1, min( 100, absint( $payload['schema_batch_size'] ?? $settings['schema_governance_batch_size'] ?? 10 ) ) );
		$settings['schema_governance_stale_days'] = max( 1, min( 365, absint( $payload['schema_stale_days'] ?? $settings['schema_governance_stale_days'] ?? 30 ) ) );
		$settings['media_governance_batch_size'] = max( 1, min( 500, absint( $payload['media_batch_size'] ?? $settings['media_governance_batch_size'] ?? 50 ) ) );
		$settings['media_governance_stale_days'] = max( 1, min( 365, absint( $payload['media_stale_days'] ?? $settings['media_governance_stale_days'] ?? 30 ) ) );
		$settings['media_governance_large_file_kb'] = max( 100, min( 10000, absint( $payload['large_file_kb'] ?? $settings['media_governance_large_file_kb'] ?? 500 ) ) );
		$settings['media_governance_alt_max_chars'] = max( 60, min( 300, absint( $payload['alt_max_chars'] ?? $settings['media_governance_alt_max_chars'] ?? 160 ) ) );
		$settings['media_governance_require_source_records'] = ! empty( $payload['require_source_records'] ) ? 1 : 0;
		$settings['media_governance_file_hashes'] = array_key_exists( 'file_hashes', $payload ) ? ( ! empty( $payload['file_hashes'] ) ? 1 : 0 ) : absint( $settings['media_governance_file_hashes'] ?? 1 );
		$settings['governance_retention_days'] = max( 30, min( 730, absint( $payload['retention_days'] ?? $settings['governance_retention_days'] ?? 180 ) ) );
		update_option( Ikon_SEO_Plugin::OPTION_KEY, $settings, false );

		if ( $user_id ) {
			$this->history->add(
				array(
					'category' => 'configuration',
					'status'   => 'completed',
					'title'    => 'Structured Data and Media Governance policy updated',
					'summary'  => 'Audit batching, stale-evidence, file-size, alt-text and source-record policies were updated.',
					'details'  => array(
						'enabled' => (bool) $settings['structured_media_governance_enabled'],
						'schema_batch_size' => $settings['schema_governance_batch_size'],
						'media_batch_size' => $settings['media_governance_batch_size'],
					),
				),
				'governance',
				$user_id
			);
		}
		return array( 'saved' => true, 'status' => $this->status() );
	}

	public function cleanup() {
		return array(
			'schema' => $this->schema_governance->cleanup(),
			'media'  => $this->media_governance->cleanup(),
		);
	}

	public function schema_governance() {
		return $this->schema_governance;
	}

	public function media_governance() {
		return $this->media_governance;
	}

	private function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}
}
