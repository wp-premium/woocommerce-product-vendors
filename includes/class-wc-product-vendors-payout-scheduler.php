<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Payout Scheduler Class.
 *
 * Processes the cron schedule for commission payouts.
 *
 * @category Payout
 * @package  WooCommerce Product Vendors/Payout Scheduler
 * @version  2.0.0
 */
class WC_Product_Vendors_Payout_Scheduler {
	protected $commission;
	protected $frequency;
	protected $log;

	/**
	 * Constructor
	 *
	 * @access private
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct( WC_Product_Vendors_Commission $commission ) {
		add_filter( 'cron_schedules', array( $this, 'add_new_intervals' ) );

		add_action( 'init', array( $this, 'run_schedule' ) );

		// the scheduled cron will trigger this event
		add_action( 'wcpv_scheduled_payment', array( $this, 'do_payment' ) );

		// we need to update the schedule if settings changed
		add_action ( 'wcpv_save_vendor_settings', array( $this, 'update_schedule' ) );

		$this->commission = $commission;
		$this->frequency = WC_Product_Vendors_Utils::payout_schedule_frequency();
		$this->log = new WC_Logger();

		return true;
	}

	/**
	 * Adds new intervals for cron
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $intervals
	 * @return array $intervals
	 */
	public function add_new_intervals( $intervals ) {
		$intervals['weekly']   = array( 'interval' => WEEK_IN_SECONDS, 'display' => __( 'Weekly', 'woocommerce-product-vendors' ) );
		$intervals['biweekly'] = array( 'interval' => ( DAY_IN_SECONDS * 14 ), 'display' => __( 'Bi-Weekly', 'woocommerce-product-vendors' ) );
		$intervals['monthly']  = array( 'interval' => ( DAY_IN_SECONDS * 30 ), 'display' => __( 'Monthly', 'woocommerce-product-vendors' ) );

		return $intervals;
	}

	/**
	 * Update the cron schedule if settings changed
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function update_schedule() {
		// grab new copy of frequency setting
		$frequency = WC_Product_Vendors_Utils::payout_schedule_frequency();

		// we need to clear the scheduled event if setting is changed to manual
		if ( 'manual' === $frequency ) {
			wp_clear_scheduled_hook( 'wcpv_scheduled_payment' );
		}

		// we need to clear the schedule event and reset it to new schedule
		if ( $frequency !== $this->frequency ) {
			wp_clear_scheduled_hook( 'wcpv_scheduled_payment' );

			$this->run_schedule();
		}

		return true;
	}

	/**
	 * Runs the schedule after vendor settings are saved
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function run_schedule() {
		// grab new copy of frequency setting
		$frequency = WC_Product_Vendors_Utils::payout_schedule_frequency();

		if ( 'manual' === $frequency ) {
			return;
		}

		if ( ! wp_next_scheduled( 'wcpv_scheduled_payment' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), $frequency, 'wcpv_scheduled_payment' );
		}

		return true;
	}

	/**
	 * Process commission payments
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function do_payment() {
		// no need to process if set to manual
		if ( 'manual' === $this->frequency ) {
			return;
		}

		$unpaid_commission_ids = $this->commission->get_unpaid_commission_ids();

		try {
			$results = $this->commission->pay( $unpaid_commission_ids );
		} catch ( Exception $e ) {
			$this->log->add( 'wcpv-masspay', $e->getMessage() );
		}		

		return true;
	}
}

new WC_Product_Vendors_Payout_Scheduler( new WC_Product_Vendors_Commission( new WC_Product_Vendors_PayPal_MassPay ) );
