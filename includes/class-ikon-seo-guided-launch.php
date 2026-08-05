<?php

defined( 'ABSPATH' ) || exit;

/**
 * Connects Auto Discovery, strategy confirmation, workflow activation,
 * bounded read-only tasks and the Closed-Loop Operating Plan.
 */
final class Ikon_SEO_Guided_Launch {
	const OPTION_KEY = 'ikon_seo_guided_launch_v1';
	const VERSION    = '1.0';

	private $auto_discovery;
	private $discovery_review;
	private $strategy;
	private $automation;
	private $closed_loop;
	private $history;
	private $logger;

	public function __construct(
		Ikon_SEO_Auto_Discovery $auto_discovery,
		Ikon_SEO_Discovery_Review $discovery_review,
		Ikon_SEO_Strategy $strategy,
		Ikon_SEO_Automation $automation,
		Ikon_SEO_Closed_Loop $closed_loop,
		Ikon_SEO_Workspace_History $history,
		Ikon_SEO_Logger $logger
	) {
		$this->auto_discovery = $auto_discovery;
		$this->discovery_review = $discovery_review;
		$this->strategy       = $strategy;
		$this->automation     = $automation;
		$this->closed_loop    = $closed_loop;
		$this->history        = $history;
		$this->logger         = $logger;
	}

	public function report() {
		$state      = get_option( self::OPTION_KEY, array() );
		$state      = is_array( $state ) ? $state : array();
		$discovery  = $this->auto_discovery->report();
		$strategy   = $this->strategy->get();
		$workflow   = $this->automation->summary( 25 );
		$operating  = $this->closed_loop->report( 25 );
		$review     = $this->discovery_review->report();
		$conflicts  = (array) ( $review['conflicts'] ?? array() );
		$readiness  = (array) ( $strategy['readiness'] ?? array() );
		$minimum_readiness = class_exists( 'Ikon_SEO_Portfolio_Governance' ) ? Ikon_SEO_Portfolio_Governance::minimum_strategy_readiness( 70 ) : 70;
		$counts     = (array) ( $workflow['counts'] ?? array() );
		$op_status  = (array) ( $operating['status'] ?? array() );
		$op_summary = (array) ( $operating['summary'] ?? array() );

		$discovery_complete = ! empty( $discovery['generated_at'] );
		$fact_review_ready   = ! empty( $review['ready'] );
		$strategy_ready      = ! empty( $strategy['configured'] ) && absint( $readiness['score'] ?? 0 ) >= $minimum_readiness && $fact_review_ready;
		$acknowledgement_current = ! empty( $state['conflicts_acknowledged'] ) && sanitize_text_field( $state['discovery_generated_at'] ?? '' ) === sanitize_text_field( $discovery['generated_at'] ?? '' );
		$conflicts_reviewed  = 0 === absint( $review['unresolved_conflicts'] ?? count( $conflicts ) ) || $acknowledgement_current;
		$workflow_created    = ! empty( $workflow['workflows'] );
		$safe_audits_run     = absint( $counts['completed'] ?? 0 ) > 0 || absint( $state['safe_tasks_processed'] ?? 0 ) > 0;
		$plan_created        = ! empty( $op_status['last_plan_refresh'] ) || absint( $op_summary['recommendations'] ?? 0 ) > 0;

		$stages = array(
			array(
				'key'         => 'discovery',
				'label'       => 'Website discovery',
				'description' => 'Research the existing website and collect evidence-backed profile and strategy suggestions.',
				'complete'    => $discovery_complete,
				'weight'      => 15,
				'url'         => admin_url( 'admin.php?page=ikon-seo&tab=auto-discovery' ),
			),
			array(
				'key'         => 'strategy',
				'label'       => 'Business confirmation',
				'description' => 'Confirm or reject uncertain facts, resolve changed evidence, then save the strategy readiness score required by the active governance policy.',
				'complete'    => $strategy_ready,
				'weight'      => 25,
				'url'         => admin_url( 'admin.php?page=ikon-seo&tab=discovery-review' ),
			),
			array(
				'key'         => 'conflicts',
				'label'       => 'Conflict review',
				'description' => 'Resolve or explicitly acknowledge conflicting phone, email, language, currency or plugin evidence.',
				'complete'    => $conflicts_reviewed,
				'weight'      => 10,
				'url'         => admin_url( 'admin.php?page=ikon-seo&tab=discovery-review' ),
			),
			array(
				'key'         => 'workflow',
				'label'       => 'Recommended workflow',
				'description' => 'Create the mode-specific workflow with dependencies, ownership, due dates and approval gates.',
				'complete'    => $workflow_created,
				'weight'      => 15,
				'url'         => admin_url( 'admin.php?page=ikon-seo&tab=workflow-automation' ),
			),
			array(
				'key'         => 'safe_audits',
				'label'       => 'Initial safe audits',
				'description' => 'Run bounded read-only inventory, crawl, technical, search, analytics or local evidence tasks.',
				'complete'    => $safe_audits_run,
				'weight'      => 15,
				'url'         => admin_url( 'admin.php?page=ikon-seo&tab=workflow-automation' ),
			),
			array(
				'key'         => 'operating_plan',
				'label'       => 'Operating Plan',
				'description' => 'Consolidate current evidence into a prioritised, approval-controlled list of recommendations.',
				'complete'    => $plan_created,
				'weight'      => 20,
				'url'         => admin_url( 'admin.php?page=ikon-seo&tab=closed-loop' ),
			),
		);

		$score = 0;
		foreach ( $stages as $stage ) {
			if ( ! empty( $stage['complete'] ) ) {
				$score += absint( $stage['weight'] );
			}
		}

		return array(
			'version'       => self::VERSION,
			'score'         => min( 100, $score ),
			'status'        => 100 === $score ? 'activated' : ( $score >= 55 ? 'in_progress' : 'setup_required' ),
			'stages'        => $stages,
			'next_actions'  => $this->next_actions( $discovery, $strategy, $workflow, $operating, $state ),
			'discovery'     => array(
				'generated_at'       => sanitize_text_field( $discovery['generated_at'] ?? '' ),
				'pages_reviewed'     => absint( $discovery['summary']['pages_reviewed'] ?? 0 ),
				'needs_confirmation' => absint( $discovery['summary']['needs_confirmation'] ?? 0 ),
				'conflicts'          => count( $conflicts ),
				'fact_review_ready'  => $fact_review_ready,
				'confirmed_facts'    => absint( $review['counts']['confirmed'] ?? 0 ),
				'edited_facts'       => absint( $review['counts']['edited'] ?? 0 ),
				'outdated_facts'     => absint( $review['counts']['outdated'] ?? 0 ),
			),
			'strategy'      => array(
				'mode'            => sanitize_key( $strategy['mode'] ?? '' ),
				'mode_label'      => sanitize_text_field( $strategy['mode_label'] ?? '' ),
				'readiness_score' => absint( $readiness['score'] ?? 0 ),
				'readiness_level' => sanitize_key( $readiness['level'] ?? 'incomplete' ),
				'minimum_required'=> $minimum_readiness,
			),
			'workflow'      => array(
				'created'           => $workflow_created,
				'recommended'       => sanitize_key( $workflow['recommended_template'] ?? '' ),
				'completed_tasks'   => absint( $counts['completed'] ?? 0 ),
				'ready_tasks'       => absint( $counts['ready'] ?? 0 ),
				'approval_tasks'    => absint( $counts['pending_approval'] ?? 0 ),
				'failed_tasks'      => absint( $counts['failed'] ?? 0 ),
			),
			'operating_plan'=> array(
				'last_refresh'    => sanitize_text_field( $op_status['last_plan_refresh'] ?? '' ),
				'recommendations' => absint( $op_summary['recommendations'] ?? 0 ),
			),
			'last_run'      => array(
				'run_at'               => sanitize_text_field( $state['last_run_at'] ?? '' ),
				'workflow_created'     => ! empty( $state['workflow_created'] ),
				'safe_tasks_processed' => absint( $state['safe_tasks_processed'] ?? 0 ),
				'plan_items_generated' => absint( $state['plan_items_generated'] ?? 0 ),
				'errors'               => array_values( array_map( 'sanitize_text_field', (array) ( $state['errors'] ?? array() ) ) ),
			),
			'conflicts_acknowledged' => $acknowledgement_current,
			'safety'        => array(
				'publishes_content'  => false,
				'changes_live_pages' => false,
				'changes_redirects'  => false,
				'changes_indexation' => false,
				'external_writes'    => false,
			),
			'generated_at'  => current_time( 'mysql', true ),
		);
	}

	public function activate( array $args = array(), $user_id = 0 ) {
		$discovery = $this->auto_discovery->report();
		if ( empty( $discovery['generated_at'] ) ) {
			return new WP_Error( 'ikon_seo_guided_launch_discovery', __( 'Run Auto Discovery before activating the strategy workflow.', 'ikon-seo' ) );
		}

		$strategy  = $this->strategy->get();
		$readiness = (array) ( $strategy['readiness'] ?? array() );
		$minimum_readiness = class_exists( 'Ikon_SEO_Portfolio_Governance' ) ? Ikon_SEO_Portfolio_Governance::minimum_strategy_readiness( 70 ) : 70;
		$review = $this->discovery_review->report();
		if ( empty( $review['ready'] ) ) {
			return new WP_Error( 'ikon_seo_guided_launch_fact_review', __( 'Resolve uncertain, outdated and conflicting discovery evidence before activation.', 'ikon-seo' ) );
		}
		if ( empty( $strategy['configured'] ) || absint( $readiness['score'] ?? 0 ) < $minimum_readiness ) {
			return new WP_Error( 'ikon_seo_guided_launch_strategy', sprintf( __( 'Confirm the Website Strategy and reach at least %d/100 readiness before activation.', 'ikon-seo' ), $minimum_readiness ) );
		}

		$state = get_option( self::OPTION_KEY, array() );
		$state = is_array( $state ) ? $state : array();
		$conflicts = (array) ( $review['conflicts'] ?? array() );
		$acknowledgement_current = ! empty( $state['conflicts_acknowledged'] ) && sanitize_text_field( $state['discovery_generated_at'] ?? '' ) === sanitize_text_field( $discovery['generated_at'] ?? '' );
		if ( absint( $review['unresolved_conflicts'] ?? count( $conflicts ) ) > 0 && ! $acknowledgement_current ) {
			return new WP_Error( 'ikon_seo_guided_launch_conflicts', __( 'Review or acknowledge the current Auto Discovery conflicts before activation.', 'ikon-seo' ) );
		}

		$create_workflow = ! array_key_exists( 'create_workflow', $args ) || ! empty( $args['create_workflow'] );
		$run_safe_tasks  = ! array_key_exists( 'run_safe_tasks', $args ) || ! empty( $args['run_safe_tasks'] );
		$build_plan      = ! array_key_exists( 'build_plan', $args ) || ! empty( $args['build_plan'] );
		$governance_batch = class_exists( 'Ikon_SEO_Portfolio_Governance' ) ? Ikon_SEO_Portfolio_Governance::max_safe_batch( 5 ) : 5;
		$task_batch      = max( 1, min( $governance_batch, absint( $args['task_batch'] ?? 3 ) ) );
		$errors          = array();
		$workflow_created = false;
		$safe_processed   = 0;
		$plan_generated   = 0;

		$workflow_summary = $this->automation->summary( 5 );
		if ( $create_workflow && empty( $workflow_summary['workflows'] ) ) {
			$workflow_result = $this->automation->create_workflow(
				$this->automation->recommended_template(),
				array(
					'owner_id'   => absint( $user_id ),
					'created_by' => absint( $user_id ),
				)
			);
			if ( is_wp_error( $workflow_result ) ) {
				$errors[] = $workflow_result->get_error_message();
			} else {
				$workflow_created = true;
			}
		}

		if ( $run_safe_tasks ) {
			$safe_result = $this->automation->run_safe_tasks( $task_batch, false );
			if ( is_wp_error( $safe_result ) ) {
				$errors[] = $safe_result->get_error_message();
			} else {
				$safe_processed = absint( $safe_result['processed'] ?? 0 );
			}
		}

		if ( $build_plan ) {
			$plan_result = $this->closed_loop->refresh_plan( false, 100, true, absint( $user_id ) );
			if ( is_wp_error( $plan_result ) ) {
				$errors[] = $plan_result->get_error_message();
			} else {
				$plan_generated = absint( $plan_result['stored'] ?? $plan_result['generated'] ?? 0 );
				foreach ( (array) ( $plan_result['errors'] ?? array() ) as $error ) {
					if ( $error ) {
						$errors[] = sanitize_text_field( $error );
					}
				}
			}
		}

		$state['version']              = self::VERSION;
		$state['last_run_at']          = current_time( 'mysql', true );
		$state['workflow_created']     = $workflow_created || ! empty( $workflow_summary['workflows'] );
		$state['safe_tasks_processed'] = absint( $state['safe_tasks_processed'] ?? 0 ) + $safe_processed;
		$state['plan_items_generated'] = $plan_generated;
		$state['errors']               = array_values( array_unique( array_filter( $errors ) ) );
		$state['updated_by']           = absint( $user_id );
		update_option( self::OPTION_KEY, $state, false );

		$this->history->add(
			array(
				'category' => 'strategy',
				'status'   => $errors ? 'open' : 'completed',
				'title'    => 'Guided strategy activation run',
				'summary'  => sprintf(
					'Workflow activation processed %d safe tasks and generated %d Operating Plan items.',
					$safe_processed,
					$plan_generated
				),
				'details'  => array(
					'workflow_created'     => $workflow_created,
					'safe_tasks_processed' => $safe_processed,
					'plan_items_generated' => $plan_generated,
					'errors'               => $errors,
				),
			),
			'guided_launch',
			absint( $user_id )
		);

		return $this->report();
	}

	public function acknowledge_conflicts( $acknowledged, $user_id = 0 ) {
		$state = get_option( self::OPTION_KEY, array() );
		$state = is_array( $state ) ? $state : array();
		$discovery = $this->auto_discovery->report();
		$state['conflicts_acknowledged']    = (bool) $acknowledged;
		$state['discovery_generated_at']    = $acknowledged ? sanitize_text_field( $discovery['generated_at'] ?? '' ) : '';
		$state['conflicts_acknowledged_at'] = $acknowledged ? current_time( 'mysql', true ) : '';
		$state['conflicts_acknowledged_by'] = $acknowledged ? absint( $user_id ) : 0;
		update_option( self::OPTION_KEY, $state, false );

		return $this->report();
	}

	private function next_actions( array $discovery, array $strategy, array $workflow, array $operating, array $state ) {
		$actions = array();
		$review = $this->discovery_review->report();
		$conflicts = (array) ( $review['conflicts'] ?? array() );
		$readiness = (array) ( $strategy['readiness'] ?? array() );
		$minimum_readiness = class_exists( 'Ikon_SEO_Portfolio_Governance' ) ? Ikon_SEO_Portfolio_Governance::minimum_strategy_readiness( 70 ) : 70;

		if ( empty( $discovery['generated_at'] ) ) {
			$actions[] = array( 'title' => 'Research and configure this website', 'reason' => 'Auto Discovery has not been run.', 'url' => admin_url( 'admin.php?page=ikon-seo&tab=auto-discovery' ), 'priority' => 100 );
		}

		if ( ! empty( $discovery['generated_at'] ) && empty( $review['ready'] ) ) {
			$actions[] = array(
				'title'    => 'Review uncertain website facts',
				'reason'   => sprintf( '%d facts need confirmation, %d facts changed after rescan and %d conflicts remain unresolved.', absint( $review['counts']['needs_confirmation'] ?? 0 ), absint( $review['counts']['outdated'] ?? 0 ), absint( $review['unresolved_conflicts'] ?? 0 ) ),
				'url'      => admin_url( 'admin.php?page=ikon-seo&tab=discovery-review' ),
				'priority' => 98,
			);
		}

		if ( ! empty( $discovery['generated_at'] ) && ( empty( $strategy['configured'] ) || absint( $readiness['score'] ?? 0 ) < $minimum_readiness ) ) {
			$gap = (array) ( $readiness['gaps'][0] ?? array() );
			$actions[] = array(
				'title'    => sanitize_text_field( $gap['message'] ?? 'Confirm the Website Strategy' ),
				'reason'   => sprintf( 'Strategy readiness is %d/100 and the active policy requires %d/100; business decisions must be confirmed before controlled execution.', absint( $readiness['score'] ?? 0 ), $minimum_readiness ),
				'url'      => admin_url( 'admin.php?page=ikon-seo&tab=strategy' ),
				'priority' => 95,
			);
		}

		$acknowledgement_current = ! empty( $state['conflicts_acknowledged'] ) && sanitize_text_field( $state['discovery_generated_at'] ?? '' ) === sanitize_text_field( $discovery['generated_at'] ?? '' );
		if ( absint( $review['unresolved_conflicts'] ?? count( $conflicts ) ) > 0 && ! $acknowledgement_current ) {
			$actions[] = array( 'title' => 'Review detected conflicts', 'reason' => count( $conflicts ) . ' conflicting evidence item(s) still require resolution or acknowledgement.', 'url' => admin_url( 'admin.php?page=ikon-seo&tab=discovery-review' ), 'priority' => 92 );
		}

		if ( empty( $workflow['workflows'] ) ) {
			$actions[] = array( 'title' => 'Create the recommended workflow', 'reason' => 'No active mode-specific workflow exists yet.', 'url' => admin_url( 'admin.php?page=ikon-seo&tab=guided-launch' ), 'priority' => 88 );
		}

		$counts = (array) ( $workflow['counts'] ?? array() );
		if ( ! empty( $workflow['workflows'] ) && absint( $counts['completed'] ?? 0 ) < 1 ) {
			$actions[] = array( 'title' => 'Run the initial safe audit batch', 'reason' => 'No read-only workflow task has completed yet.', 'url' => admin_url( 'admin.php?page=ikon-seo&tab=guided-launch' ), 'priority' => 84 );
		}

		$op_status  = (array) ( $operating['status'] ?? array() );
		$op_summary = (array) ( $operating['summary'] ?? array() );
		if ( empty( $op_status['last_plan_refresh'] ) && 0 === absint( $op_summary['recommendations'] ?? 0 ) ) {
			$actions[] = array( 'title' => 'Generate the initial Operating Plan', 'reason' => 'Current evidence has not yet been consolidated into priorities.', 'url' => admin_url( 'admin.php?page=ikon-seo&tab=guided-launch' ), 'priority' => 80 );
		}

		foreach ( (array) ( $workflow['tasks'] ?? array() ) as $task ) {
			if ( count( $actions ) >= 8 ) {
				break;
			}
			$actions[] = array(
				'title'    => sanitize_text_field( $task['title'] ?? 'Review workflow task' ),
				'reason'   => sanitize_text_field( $task['description'] ?? 'Continue the approved workflow.' ),
				'url'      => admin_url( 'admin.php?page=ikon-seo&tab=workflow-automation' ),
				'priority' => absint( $task['priority'] ?? 60 ),
			);
		}

		foreach ( (array) ( $operating['recommendations'] ?? array() ) as $item ) {
			if ( count( $actions ) >= 10 ) {
				break;
			}
			$actions[] = array(
				'title'    => sanitize_text_field( $item['title'] ?? 'Review Operating Plan recommendation' ),
				'reason'   => sanitize_text_field( $item['rationale'] ?? 'Review the evidence and approve the recommended action.' ),
				'url'      => admin_url( 'admin.php?page=ikon-seo&tab=closed-loop' ),
				'priority' => absint( $item['priority'] ?? 50 ),
			);
		}

		usort(
			$actions,
			function( $a, $b ) {
				return absint( $b['priority'] ?? 0 ) <=> absint( $a['priority'] ?? 0 );
			}
		);

		$seen = array();
		$unique = array();
		foreach ( $actions as $action ) {
			$key = strtolower( $action['title'] );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$unique[] = $action;
			if ( count( $unique ) >= 5 ) {
				break;
			}
		}

		return $unique;
	}
}
