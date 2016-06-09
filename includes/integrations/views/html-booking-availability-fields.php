<?php
	$intervals = array();

	$intervals['months'] = array(
		'1'  => __( 'January', 'woocommerce-product-vendors' ),
		'2'  => __( 'February', 'woocommerce-product-vendors' ),
		'3'  => __( 'March', 'woocommerce-product-vendors' ),
		'4'  => __( 'April', 'woocommerce-product-vendors' ),
		'5'  => __( 'May', 'woocommerce-product-vendors' ),
		'6'  => __( 'June', 'woocommerce-product-vendors' ),
		'7'  => __( 'July', 'woocommerce-product-vendors' ),
		'8'  => __( 'August', 'woocommerce-product-vendors' ),
		'9'  => __( 'September', 'woocommerce-product-vendors' ),
		'10' => __( 'October', 'woocommerce-product-vendors' ),
		'11' => __( 'November', 'woocommerce-product-vendors' ),
		'12' => __( 'December', 'woocommerce-product-vendors' )
	);

	$intervals['days'] = array(
		'1' => __( 'Monday', 'woocommerce-product-vendors' ),
		'2' => __( 'Tuesday', 'woocommerce-product-vendors' ),
		'3' => __( 'Wednesday', 'woocommerce-product-vendors' ),
		'4' => __( 'Thursday', 'woocommerce-product-vendors' ),
		'5' => __( 'Friday', 'woocommerce-product-vendors' ),
		'6' => __( 'Saturday', 'woocommerce-product-vendors' ),
		'7' => __( 'Sunday', 'woocommerce-product-vendors' )
	);

	for ( $i = 1; $i <= 53; $i ++ ) {
		$intervals['weeks'][ $i ] = sprintf( __( 'Week %s', 'woocommerce-product-vendors' ), $i );
	}

	if ( ! isset( $availability['type'] ) ) {
		$availability['type'] = 'custom';
	}

	if ( ! isset( $availability['priority'] ) ) {
		$availability['priority'] = 10;
	}

	$hide = '';

	// check to see if a vendor is logged in
	if ( ! $add_row && WC_Product_Vendors_Utils::is_vendor() && ( empty( $availability['vendor'] ) || absint( WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ) ) !== (int) $availability['vendor'] ) ) {
		$hide = ' style="display:none;"';
	}

	$vendor = ! empty( $availability['vendor'] ) ? esc_attr( $availability['vendor'] ) : '';

	if ( $add_row && WC_Product_Vendors_Utils::is_vendor() ) {
		$vendor = WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' );
	}
?>
<tr<?php echo $hide; ?>>
	<td class="sort">&nbsp;</td>
	<td><input type="hidden" name="wc_booking_availability_vendor[]" value="<?php echo esc_attr( $vendor ); ?>" />
		<div class="select wc_booking_availability_type">
			<select name="wc_booking_availability_type[]">
				<option value="custom" <?php selected( $availability['type'], 'custom' ); ?>><?php esc_html_e( 'Date range', 'woocommerce-product-vendors' ); ?></option>
				<option value="months" <?php selected( $availability['type'], 'months' ); ?>><?php esc_html_e( 'Range of months', 'woocommerce-product-vendors' ); ?></option>
				<option value="weeks" <?php selected( $availability['type'], 'weeks' ); ?>><?php esc_html_e( 'Range of weeks', 'woocommerce-product-vendors' ); ?></option>
				<option value="days" <?php selected( $availability['type'], 'days' ); ?>><?php esc_html_e( 'Range of days', 'woocommerce-product-vendors' ); ?></option>
				<optgroup label="<?php esc_attr_e( 'Time Ranges', 'woocommerce-product-vendors' ); ?>">
					<option value="time" <?php selected( $availability['type'], 'time' ); ?>><?php esc_html_e( 'Time Range (all week)', 'woocommerce-product-vendors' ); ?></option>
					<option value="time:range" <?php selected( $availability['type'], 'time:range' ); ?>><?php esc_html_e( 'Date Range with time', 'woocommerce-product-vendors' ); ?></option>
					<?php foreach ( $intervals['days'] as $key => $label ) : ?>
						<option value="time:<?php echo $key; ?>" <?php selected( $availability['type'], 'time:' . $key ) ?>><?php echo $label; ?></option>
					<?php endforeach; ?>
				</optgroup>
			</select>
		</div>
	</td>
	<td style="border-right:0;">
	<div class="bookings-datetime-select-from">
		<div class="select from_day_of_week">
			<select name="wc_booking_availability_from_day_of_week[]">
				<?php foreach ( $intervals['days'] as $key => $label ) : ?>
					<option value="<?php echo $key; ?>" <?php selected( isset( $availability['from'] ) && $availability['from'] == $key, true ) ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select from_month">
			<select name="wc_booking_availability_from_month[]">
				<?php foreach ( $intervals['months'] as $key => $label ) : ?>
					<option value="<?php echo $key; ?>" <?php selected( isset( $availability['from'] ) && $availability['from'] == $key, true ) ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select from_week">
			<select name="wc_booking_availability_from_week[]">
				<?php foreach ( $intervals['weeks'] as $key => $label ) : ?>
					<option value="<?php echo $key; ?>" <?php selected( isset( $availability['from'] ) && $availability['from'] == $key, true ) ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="from_date">
			<?php
			$from_date = '';
			if ( 'custom' === $availability['type'] && ! empty( $availability['from'] ) ) {
				$from_date = $availability['from'];
			} else if ( 'time:range' === $availability['type'] && ! empty( $availability['from_date'] ) ) {
				$from_date = $availability['from_date'];
			}
			?>
			<input type="text" class="date-picker" name="wc_booking_availability_from_date[]" value="<?php echo esc_attr( $from_date ); ?>" />
		</div>
		<div class="from_time">
			<input type="time" class="time-picker" name="wc_booking_availability_from_time[]" value="<?php if ( strrpos( $availability['type'], 'time' ) === 0 && ! empty( $availability['from'] ) ) echo $availability['from'] ?>" placeholder="HH:MM" />
		</div>
	</div>
	</td>
	<td style="border-right:0;" class="bookings-to-label-row">
		<p><?php _e( 'to', 'woocommerce-product-vendors' ); ?></p>
		<p class="bookings-datetimerange-second-label"><?php _e( 'to', 'woocommerce-product-vendors' ); ?></p>
	</td>
	<td>
	<div class='bookings-datetime-select-to'>
		<div class="select to_day_of_week">
			<select name="wc_booking_availability_to_day_of_week[]">
				<?php foreach ( $intervals['days'] as $key => $label ) : ?>
					<option value="<?php echo $key; ?>" <?php selected( isset( $availability['to'] ) && $availability['to'] == $key, true ) ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select to_month">
			<select name="wc_booking_availability_to_month[]">
				<?php foreach ( $intervals['months'] as $key => $label ) : ?>
					<option value="<?php echo $key; ?>" <?php selected( isset( $availability['to'] ) && $availability['to'] == $key, true ) ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select to_week">
			<select name="wc_booking_availability_to_week[]">
				<?php foreach ( $intervals['weeks'] as $key => $label ) : ?>
					<option value="<?php echo $key; ?>" <?php selected( isset( $availability['to'] ) && $availability['to'] == $key, true ) ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="to_date">
			<?php
			$to_date = '';
			if ( 'custom' === $availability['type'] && ! empty( $availability['to'] ) ) {
				$to_date = $availability['to'];
			} else if ( 'time:range' === $availability['type'] && ! empty( $availability['to_date'] ) ) {
				$to_date = $availability['to_date'];
			}
			?>
			<input type="text" class="date-picker" name="wc_booking_availability_to_date[]" value="<?php echo esc_attr( $to_date ); ?>" />
		</div>

		<div class="to_time">
			<input type="time" class="time-picker" name="wc_booking_availability_to_time[]" value="<?php if ( strrpos( $availability['type'], 'time' ) === 0 && ! empty( $availability['to'] ) ) echo $availability['to']; ?>" placeholder="HH:MM" />
		</div>
	</div>
	</td>
	<td>
		<div class="select">
			<select name="wc_booking_availability_bookable[]">
				<option value="no" <?php selected( isset( $availability['bookable'] ) && $availability['bookable'] == 'no', true ) ?>><?php _e( 'No', 'woocommerce-product-vendors' ) ;?></option>
				<option value="yes" <?php selected( isset( $availability['bookable'] ) && $availability['bookable'] == 'yes', true ) ?>><?php _e( 'Yes', 'woocommerce-product-vendors' ) ;?></option>
			</select>
		</div>
	</td>
	<td>
	<div class="priority">
		<input type="number" name="wc_booking_availability_priority[]" value="<?php echo esc_attr( $availability['priority'] ); ?>" placeholder="10" />
	</div>
	</td>
	<td>
	<?php if ( ! empty( $availability['vendor'] ) && current_user_can( 'manage_options' ) ) {
		$vendor = get_term( $availability['vendor'], WC_PRODUCT_VENDORS_TAXONOMY );

		echo $vendor->name;
	} ?>
	</td>
	<td class="remove">&nbsp;</td>
</tr>
