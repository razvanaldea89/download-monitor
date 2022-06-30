<?php

/**
 * DLM_Reports_Page class
 */
class DLM_Reports_Page {

	/**
	 * Navigation tabs
	 *
	 * @var mixed
	 */
	public $tabs;

	/**
	 * Setup hooks
	 */
	public function setup() {

		// menu item.
		if ( DLM_Logging::is_logging_enabled() ) {
			add_filter( 'dlm_admin_menu_links', array( $this, 'add_admin_menu' ), 30 );
		}

		// Set this action on order for other plugins/themes to tap into our tabs.
		add_action( 'admin_init', array( $this, 'set_tabs' ) );
		add_action( 'dlm_page_header_links', array( $this, 'header_reports_settings' ) );

	}

	/**
	 * Set our insights page navigation tabs
	 *
	 * @return void
	 * @since 4.5.0
	 *
	 */
	public function set_tabs() {

		$this->tabs = apply_filters( 'dlm_insights_navigation', array(
			'general_info' => array(
				'tab_label'   => esc_html__( 'Overview', 'download-monitor' ),
				// Label to be displayed on tab nav.
				'description' => esc_html__( 'General information about your downloads', 'download-monitor' ),
				// Description to be displayed on tab nav.
				'callback'    => array( $this, 'general_info' ),
				// The callback to display the content.
				'priority'    => 10,
				// Tab priority.
			),
		) );

		$this->tabs['user_reports'] = array(
			'tab_label'   => esc_html__( 'User reports', 'download-monitor' ),
			// Label to be displayed on tab nav.
			'description' => esc_html__( 'Reports based on user activity', 'download-monitor' ),
			// Description to be displayed on tab nav.
			'callback'    => array( $this, 'user_reports' ),
			// The callback to display the content.
			'priority'    => 20,
			// Tab priority.
		);

		uasort( $this->tabs, array( 'DLM_Admin_Helper', 'sort_data_by_priority' ) );
	}

	/**
	 * Add settings menu item
	 *
	 * @param mixed $links The links for the menu.
	 *
	 * @return array
	 */
	public function add_admin_menu( $links ) {

		// Reports page page.
		$links[] = array(
			'page_title' => __( 'Reports', 'download-monitor' ),
			'menu_title' => __( 'Reports', 'download-monitor' ),
			'capability' => 'dlm_view_reports',
			'menu_slug'  => 'download-monitor-reports',
			'function'   => array( $this, 'view' ),
			'priority'   => 50,
		);

		return $links;
	}

	/**
	 * Date range filter element
	 */
	private function date_range_button() {

		$to_date = new DateTime( current_time( 'mysql' ) );
		$to_date->setTime( 0, 0, 0 );
		$to   = $to_date->format( 'Y-m-d' );
		$from = $to_date->modify( '-1 month' )->format( 'Y-m-d' );

		$end   = new DateTime( $to );
		$start = new DateTime( $from );
		?>
		<div class="dlm-reports-header-date-selector" id="dlm-date-range-picker">
			<label><?php echo esc_html__( 'Date Range', 'download-monitor' ); ?></label>
			<span class="dashicons dashicons-calendar-alt dlm-chart-icon"></span>
			<span
				class="date-range-info"><?php echo esc_html( $start->format( 'M d, Y' ) ) . ' - ' . esc_html( $end->format( 'M d, Y' ) ); ?></span>
			<span class="dlm-arrow"></span>
		</div>
		<?php
	}

	/**
	 * The settings for the Reports page
	 *
	 * @return void
	 */
	private function page_settings() {
		$reports_settings = apply_filters( 'dlm_reports_settings', array(
			// Option to clear the cache. Functionality already present
			/*'dlm_clear_api_cache'     => array(
				'label'   => 'Clear reports cache',
				'default' => false,
			),*/
		) );

		if ( empty( $reports_settings ) ) {
			return;
		}
		?>
		<div id="dlm-toggle-settings" class="dashicons dashicons-admin-generic">
			<div class="dlm-toggle-settings__settings reports-block">
				<?php
				foreach ( $reports_settings as $key => $value ) {
					?>
					<div>
						<div class="wpchill-toggle">
							<input class="wpchill-toggle__input" type="checkbox"
							       name="<?php echo esc_attr( $key ); ?>" <?php checked( get_option( $key ), 'on' ); ?>
							       value="on">
							<div class="wpchill-toggle__items">
								<span class="wpchill-toggle__track"></span>
								<span class="wpchill-toggle__thumb"></span>
								<svg class="wpchill-toggle__off" width="6" height="6" aria-hidden="true" role="img"
								     focusable="false" viewBox="0 0 6 6">
									<path
										d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
								</svg>
								<svg class="wpchill-toggle__on" width="2" height="6" aria-hidden="true" role="img"
								     focusable="false" viewBox="0 0 2 6">
									<path d="M0 0h2v6H0z"></path>
								</svg>
							</div>
						</div>
						<label
							for="dlm_reports_page[<?php echo esc_attr( $key ); ?>]"> <?php echo esc_html( $value['label'] ); ?></label>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Insights page header
	 *
	 * @return void
	 */
	public function insights_header() {
		?>
		<div class="dlm-insights-header">
			<div class="dlm-insights-navigation">
				<?php
				$this->insights_navigation();
				?>
			</div>
			<div class="dlm-insights-datepicker dlm-reports-actions">
				<?php
				do_action( 'dlm_insights_header' );
				$this->date_range_button();
				$this->page_settings();
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Insights page navigation
	 *
	 * @return void
	 */
	public function insights_navigation() {

		if ( empty( $this->tabs ) || count( $this->tabs ) <= 1 ) {
			return;
		}

		echo '<ul class="dlm-insights-tab-navigation">';

		foreach ( $this->tabs as $key => $tab ) {

			$active = '';

			if ( 'general_info' === $key ) {
				$active = 'active';
			}

			?>
			<li id="<?php echo esc_attr( $key ); ?>"
			    class="dlm-insights-tab-navigation__element <?php echo esc_attr( $active ); ?>">
				<label class="dlm-insights-tab-navigation__label"><?php echo esc_html( $tab['tab_label'] ); ?></label>
			</li>
			<?php
		}

		echo '</ul>';
	}

	/**
	 * Insights page general info content
	 *
	 * @return void
	 */
	public function general_info() {
		?>
		<div class="dlm-reports-wrapper">
			<div class="dlm-reports-block dlm-reports-block-summary" id="total_downloads_summary">
				<ul>
					<li id="total" class="reports-block">
						<label><?php esc_html_e( 'Total Downloads', 'download-monitor' ); ?>
							<div class="wpchill-tooltip"><i>[?]</i>
								<div
									class="wpchill-tooltip-content"><?php esc_html_e( 'Number of downloads between the selected date range.', 'download-monitor' ); ?></div>
							</div>
						</label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
					<li id="average" class="reports-block">
						<label><?php esc_html_e( 'Daily Average Downloads', 'download-monitor' ); ?>
							<div class="wpchill-tooltip"><i>[?]</i>
								<div
									class="wpchill-tooltip-content"><?php esc_html_e( 'Average number of downloads between the selected date range.', 'download-monitor' ); ?></div>
							</div>
						</label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
					<!--
					<li id="popular"><label><?php esc_html_e( 'Most Popular Download', 'download-monitor' ); ?></label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
				-->
					<li id="today" class="reports-block">
						<label><?php esc_html_e( 'Today Downloads', 'download-monitor' ); ?></label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span>
					</li>
				</ul>
			</div>

			<div class="total_downloads_chart-wrapper">
				<canvas class="dlm-reports-block-chart" id="total_downloads_chart"></canvas>
			</div>
		</div>


		<div id="total_downloads_table_wrapper" class="empty">
			<div class="total_downloads_table_header">
				<h3><?php esc_html_e( 'Top downloads', 'donwload-monitor' ); ?></h3>
			</div><!--/.total_downloads_table_header-->

			<div class="total_downloads_table_filters">
				<div class="total_downloads_table_filters_id">ID</div>
				<div class='total_downloads_table_filters_title'>Title</div>
				<div class='total_downloads_table_filters_status_completed'>Completed</div>
				<div class='total_downloads_table_filters_status_failed'>Failed</div>
				<div class='total_downloads_table_filters_status_redirected'>Redirected</div>
				<div class='total_downloads_table_filters_status_Downloads'>Downloads</div>


			</div><!--/.total_downloads_table_filters-->


			<div class="dlm-reports-block dlm-reports-block-table" id="total_downloads_table" data-page="0">
				<div class="dlm-reports-placeholder-no-data"><?php esc_html_e( 'NO DATA', 'download-monitor' ); ?></div>
			</div>

			<div id="downloads-block-navigation">
				<button class="hidden dashicons dashicons-arrow-left-alt2" disabled="disabled"
				        title="<?php esc_html_e( 'Previous 15 downloads', 'download-monitor' ); ?>"></button>
				<button class="hidden dashicons dashicons-arrow-right-alt2" data-action="load-more"
				        title="<?php esc_html_e( 'Next 15 downloads', 'download-monitor' ); ?>"></button>
			</div>
		</div>
		<!-- <div id="total_downloads_summary_wrapper" class="reports-block half-reports-block">
			<h3><?php esc_html_e( 'Downloads summary', 'donwload-monitor' ); ?>
				<div class="wpchill-tooltip"><i>[?]</i>
					<div
						class="wpchill-tooltip-content"><?php esc_html_e( 'The most accessed Downloads.', 'download-monitor' ); ?></div>
				</div>
			</h3>
			<div class="half-reports-block">
				<label><?php echo esc_html__( 'Logged in downloads: ', 'download-monitor' ); ?></label>
				<span class="dlm-reports-logged-in"><?php esc_html_e( 'NO DATA', 'download-monitor' ); ?></span>
			</div>
			<div class="half-reports-block">
				<label><?php echo esc_html__( 'Logged out downloads:', 'download-monitor' ); ?></label>
				<span class="dlm-reports-logged-out"><?php esc_html_e( 'NO DATA', 'download-monitor' ); ?></span>
			</div>
		</div>
		-->

		<div id="total_downloads_table_wrapper2" class="empty">
			<div class='total_downloads_table_header'>
				<h3><?php esc_html_e( 'Downloads', 'donwload-monitor' ); ?></h3>
				<span class="total_downloads_table_exportcsv"><i class="dashicons dashicons-database-export"></i>export to csv</span>
			</div><!--/.total_downloads_table_header-->

			<div class="total_downloads_table_filters">
				<div class="total_downloads_table_filters_id">ID</div>
				<div class='total_downloads_table_filters_title'>Title</div>
				<div class='total_downloads_table_filters_completed_downloads'>Completed</div>
				<div class='total_downloads_table_filters_failed_downloads'>Failed</div>
				<div class='total_downloads_table_filters_redirected_downloads'>Redirected</div>
				<div class='total_downloads_table_filters_total_downloads'>Downloads</div>
				<div class='total_downloads_table_filters_total_downloads'>Logged-in</div>
				<div class='total_downloads_table_filters_total_downloads'>Not logged in</div>
				<div class='total_downloads_table_filters_total_downloads'>% of total</div>
				<div class='total_downloads_table_filters_total_downloads'>Content Locking</div>
			</div><!--/.total_downloads_table_filters-->

			<div class='total_downloads_table_entries'>
				<div>1</div>
				<div><a href="#">Login_v1</a></div>
				<div>150,000</div>
				<div>1,000</div>
				<div>250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>15%</div>
				<div>1300</div>
			</div><!--/.total_downloads_table_entries-->
			<div class='total_downloads_table_entries'>
				<div>2</div>
				<div><a href='#'>Login_v1</a></div>
				<div>150,000</div>
				<div>1,000</div>
				<div>250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>18%</div>
				<div>1300</div>
			</div><!--/.total_downloads_table_entries-->
			<div class='total_downloads_table_entries'>
				<div>3</div>
				<div><a href='#'>Login_v1</a></div>
				<div>150,000</div>
				<div>1,000</div>
				<div>250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>23%</div>
				<div>1300</div>
			</div><!--/.total_downloads_table_entries-->
			<div class='total_downloads_table_entries'>
				<div>4</div>
				<div><a href='#'>Login_v1</a></div>
				<div>150,000</div>
				<div>1,000</div>
				<div>250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>21%</div>
				<div>1300</div>
			</div><!--/.total_downloads_table_entries-->
			<div class='total_downloads_table_entries'>
				<div>5</div>
				<div><a href='#'>Login_v1</a></div>
				<div>150,000</div>
				<div>1,000</div>
				<div>250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>1%</div>
				<div>1300</div>
			</div><!--/.total_downloads_table_entries-->
			<div class='total_downloads_table_entries'>
				<div>6</div>
				<div><a href='#'>Login_v1</a></div>
				<div>150,000</div>
				<div>1,000</div>
				<div>250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>2.3%</div>
				<div>1300</div>
			</div><!--/.total_downloads_table_entries-->
			<div class='total_downloads_table_entries'>
				<div>7</div>
				<div><a href='#'>Login_v1</a></div>
				<div>150,000</div>
				<div>1,000</div>
				<div>250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>3.5%</div>
				<div>1300</div>
			</div><!--/.total_downloads_table_entries-->
			<div class='total_downloads_table_entries'>
				<div>8</div>
				<div><a href='#'>Login_v1</a></div>
				<div>150,000</div>
				<div>1,000</div>
				<div>250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>99%</div>
				<div>1300</div>
			</div><!--/.total_downloads_table_entries-->
			<div class='total_downloads_table_entries'>
				<div>9</div>
				<div><a href='#'>Login_v1</a></div>
				<div>150,000</div>
				<div>1,000</div>
				<div>250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>151,250</div>
				<div>67%</div>
				<div>1300</div>
			</div><!--/.total_downloads_table_entries-->
			<div class='total_downloads_table_entries'>
				<div>10</div>
				<div><a href='#'>Login_v1</a></div>
				<div>290,000</div>
				<div>3,000</div>
				<div>1250</div>
				<div>14,250</div>
				<div>14,250</div>
				<div>140,250</div>
				<div>0.9%</div>
				<div>1300</div>
			</div><!--/.total_downloads_table_entries-->

			<div class="total_downloads_table_footer">
				<div class='woocommerce-pagination__page-arrows'><span
						class='woocommerce-pagination__page-arrows-label' role='status'
						aria-live='polite'>Page 1 of 6</span>
					<div class='woocommerce-pagination__page-arrows-buttons'>
						<button type='button' disabled='' class='components-button woocommerce-pagination__link'
						        aria-label='Previous Page'>
							<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='24' height='24'
							     aria-hidden='true' focusable='false'>
								<path d='M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z'></path>
							</svg>
						</button>
						<button type='button' class='components-button woocommerce-pagination__link is-active'
						        aria-label='Next Page'>
							<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='24' height='24'
							     aria-hidden='true' focusable='false'>
								<path d='M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z'></path>
							</svg>
						</button>
					</div>
				</div>
				<div class='woocommerce-pagination__page-picker'><label for='woocommerce-pagination-page-picker-267'
				                                                        class='woocommerce-pagination__page-picker-label'>Go
						to page<input id='woocommerce-pagination-page-picker-267'
						              class='woocommerce-pagination__page-picker-input' aria-invalid='false'
						              type='number' min='1' max='6' value='1'></label></div>
				<div class='woocommerce-pagination__per-page-picker'>
					<div class='components-base-control css-1wzzj1a ej5x27r4'>
						<div class='components-base-control__field css-1t5ousf ej5x27r3'>
							<div data-wp-c16t='true' data-wp-component='Flex'
							     class='components-flex components-select-control em5sgkm7 css-r7p7v1 em57xhy0'>
								<div data-wp-c16t='true' data-wp-component='FlexItem'
								     class='components-flex-item em5sgkm3 css-l7qly em57xhy0'><label
										data-wp-c16t='true' data-wp-component='Text'
										for='inspector-select-control-15'
										class='components-truncate components-text components-input-control__label em5sgkm4 css-7tja0k em57xhy0'>Rows
										per page</label></div>
								<div class='components-input-control__container css-1t6thiu em5sgkm6'><select
										class='components-select-control__input css-1opxiwx e1mv6sxx1'
										id='inspector-select-control-15'>
										<option value='25'>25</option>
										<option value='50'>50</option>
										<option value='75'>75</option>
										<option value='100'>100</option>
									</select><span class='components-input-control__suffix css-jgf79h em5sgkm0'><div
											class='css-1j3xh4d e1mv6sxx0'><svg viewBox='0 0 24 24'
								                                               xmlns='http://www.w3.org/2000/svg'
								                                               width='18' height='18'
								                                               aria-hidden='true' focusable='false'><path
													d='M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z'></path></svg></div></span>
									<div aria-hidden='true'
									     class='components-input-control__backdrop css-29yhbg em5sgkm2'></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>


		<?php
	}

	/**
	 * Insights page general info content
	 *
	 * @return void
	 */
	public function user_reports() {
		?>
		<div class="dlm-reports-wrapper">
			<div class="dlm-reports-block dlm-reports-block-summary" id="user_downloads_summary">
				<ul>
					<li id="logged_in" class="reports-block">
						<label><?php esc_html_e( 'Logged in downloads', 'download-monitor' ); ?>
							<div class="wpchill-tooltip"><i>[?]</i>
								<div
									class="wpchill-tooltip-content"><?php esc_html_e( 'Total number of downloads made by logged in users.', 'download-monitor' ); ?></div>
							</div>
						</label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
					<li id="logged_out" class="reports-block">
						<label><?php esc_html_e( 'Logged out/visitor downloads', 'download-monitor' ); ?>
							<div class="wpchill-tooltip"><i>[?]</i>
								<div
									class="wpchill-tooltip-content"><?php esc_html_e( 'Total number of downloads made by logged out users or visitors.', 'download-monitor' ); ?></div>
							</div>
						</label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span></li>
					<li id="most_active_user" class="reports-block">
						<label><?php esc_html_e( 'Most active user', 'download-monitor' ); ?></label><span><?php esc_html_e( 'No data', 'download-monitor' ); ?></span>
					</li>
				</ul>
			</div>
		</div>


		<div id="users_downloads_table_wrapper" class="empty">
			<div class="user-downloads-filters">
				<h3 class="user-downloads-filters__heading"><?php echo esc_html__( 'Filter logs by:', 'download-monitor' ); ?></h3>
				<select id="dlm-filter-by-status" class="user-downloads-filters__filter" data-type="download_status">
					<option value=""><?php echo esc_html__( 'Filter by status', 'download-monitor' ); ?></option>
					<option value="completed"><?php echo esc_html__( 'Completed', 'download-monitor' ); ?></option>
					<option value="redirected"><?php echo esc_html__( 'Redirected', 'download-monitor' ); ?></option>
					<option value="failed"><?php echo esc_html__( 'Failed', 'download-monitor' ); ?></option>
				</select>
				<select id="dlm-filter-by-user" class="user-downloads-filters__filter" data-type="user_id">
					<option value=""><?php echo esc_html__( 'Filter by user', 'download-monitor' ); ?></option>
				</select>
			</div>
			<div class="dlm-reports-block dlm-reports-block-table reports-block" id="users_download_log" data-page="0">
				<div class="dlm-reports-placeholder-no-data"><?php esc_html_e( 'NO DATA', 'download-monitor' ); ?></div>
			</div>
			<div id="user-downloads-block-navigation">
				<button class="hidden dashicons dashicons-arrow-left-alt2" disabled="disabled"
				        title="<?php esc_html_e( 'Previous 15', 'download-monitor' ); ?>"></button>
				<button class="hidden dashicons dashicons-arrow-right-alt2" data-action="load-more"
				        title="<?php esc_html_e( 'Next 15', 'download-monitor' ); ?>"></button>
			</div>
		</div>

		<?php
	}

	/**
	 * Insights page tab content
	 *
	 * @return void
	 */
	public function insights_content() {

		foreach ( $this->tabs as $key => $tab ) {

			if ( ! isset( $tab['callback'] ) ) {
				continue;
			}

			$active = '';

			if ( 'general_info' === $key ) {
				$active = 'active';
			}

			ob_start();
			call_user_func( $tab['callback'] );
			$response = ob_get_clean();

			// $response should be escaped in callback function.
			echo '<div id="tab_' . esc_attr( $key ) . '" class="dlm-insights-tab-navigation__content ' . esc_attr( $active ) . '" data-id="' . esc_attr( $key ) . '">' . $response . '</div>'; //phpcs:ignore

		}

	}

	/**
	 * Display page
	 */
	public function view() {

		if ( DLM_DB_Upgrader::do_upgrade() ) {
			/* Upgrade DB View */ ?>
			<div class="wrap">
				<hr class="wp-header-end">
				<div class="main">
					<h3><?php esc_html_e( 'Please upgrade the database in order to further use Download Monitor\'s Reports page.', 'download-monitor' ); ?></h3>
				</div>
			</div>
			<?php
		} else {
			/* Display page */ ?>
			<div class="wrap dlm-reports wp-clearfix">
				<hr class="wp-header-end">
				<div id="icon-edit" class="icon32 icon32-posts-dlm_download"></div>
				<?php $this->insights_header(); ?>
				<?php do_action( 'dlm_reports_page_start' ); ?>
				<?php $this->insights_content(); ?>
				<?php do_action( 'dlm_reports_page_end' ); ?>
			</div>
			<?php
		}
	}

	/**
	 * The reports settings
	 *
	 * @return void
	 */
	public function header_reports_settings() {

		$settings = apply_filters( 'dlm_reports_settings', array(
			'dlm_user_reports' => array(
				'label'       => esc_html__( 'Enable user reports', 'donwload-monitor' ),
				'description' => esc_html__( 'Toggle to enable or disable the user reports section', 'download-monitor' ),
				'default'     => '1',
				'type'        => 'checkbox',
			),
		) );
	}

}
