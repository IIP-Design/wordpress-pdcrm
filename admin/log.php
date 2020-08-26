<?php
/**
 * The log page for the form capture plugin
 */
// enqueue the stylesheet for this page
function add_stylesheet() {
    wp_enqueue_style( 'capture-log-styles', plugins_url( 'css/style.css', __FILE__ ) );
}
add_action('admin_print_styles', 'add_stylesheet');

// json decoder to print the cells
function fc_json_decode( $json, $arr = false ) {
    $result = json_decode( $json, $arr );
    if ( $result ) {
        foreach ( $result as &$val ) {
            if ( is_string( $val ) ) {
                $nested = fc_json_decode( $val, $arr );
                if ( $nested ) {
                    $val = $nested;
                }
            }
        }
        return $result;
    }
    return $json;
}

function salesforce_form_capture_log() {
    $show_all = (array_key_exists( 'show_all', $_GET ) && $_GET['show_all']) ? 1 : 0;
	$month = isset($_GET['month']) ? $_GET['month'] : 1;
?>

<!-- begin markup for log page -->
    <div id="capture-log">
        <h1>Salesforce Form Captures Log</h1>

        <p>Number of Months Back</p>
        <form method="get" action='<?php echo admin_url("admin.php") ?>'>
            <input type="hidden" name="page" value="chief-sfc-log" />
            <!-- <input type="search" name="keyword" class="table-filter" data-table="order-table" placeholder="Search word or date" value="<?=isset($_GET['keyword']) ? $_GET['keyword'] : ''?>" /> -->

            <input type="number" min="1" name="month" class="table-filter" data-table="order-table" value="<?=$month?>">

            <a href="<?=admin_url('admin.php?page=chief-sfc-log&show_all=0' )?>" alt="Reset" class="button">Reset</a>
            <button type="submit" name="show_all" id="show_failures" class="button" value="0">Show Failures</button>
            <button type="submit" name="show_all" id="show_all" class="button" value="1">Show All</button>

            <a id="export" target="_blank" class="button" href="<?=admin_url('admin-post.php?action=sfc_export&month=' . $month . '&show_all=' . $show_all) ?>">Export</a>


        </form>

        <!-- begin markup to display table -->
        <div id="dataTable">

            <?php

            // begin db queries
            global $wpdb;

            // using url for conditional css for buttons
            echo '<style>#show_all, #show_failures {background: none; color: #0071a1; }';
            if ($show_all) {
	            echo '#show_all {background: #0071a1; color: #fff; }';
            } else {
	            echo '#show_failures {background: #0071a1; color: #fff; }';
            }
            echo '</style>';

            $query             = "SELECT * FROM wp_form_capture_data WHERE 1 ";
            if (!$show_all) {
                $query .= " AND fc_failure = 1 ";
            }
            // if (isset($_GET['keyword'])) {
            //     $kw = sanitize_text_field($_GET['keyword']);
            //     $query .= " AND (fc_request_data LIKE '%$kw%' OR fc_response LIKE '%$kw%' OR fc_submission_date LIKE '%$kw%')";
            // }
            if (isset($_GET['month'])) {
                $month = sanitize_text_field($_GET['month']);
                $query .= " AND(fc_submission_date >= DATE_SUB(NOW(), INTERVAL $month MONTH))";
            }

            $total_query       = "SELECT COUNT(1) FROM (${query}) AS combined_table";
            $total             = $wpdb->get_var( $total_query );
            $items_per_page    = 30;
            $page              = isset( $_GET['p'] ) ? abs( (int) $_GET['p'] ) : 1;
            $offset            = ( $page * $items_per_page ) - $items_per_page;
            $failure_result    = $wpdb->get_results( $query . " ORDER BY fc_submission_date DESC LIMIT ${offset}, ${items_per_page}" );
            $totalPage         = ceil($total / $items_per_page);

            echo '<table class="order-table table">';
            echo '<thead>
                    <th>Entry #</th>
                    <th>Form ID</th>
                    <th>Submission ID</th>
                    <th>Form Content</th>
                    <th>Response</th>
                    <th>Submission Date</th>
                    <th>Failures</th>
                </thead>';

            foreach ( $failure_result as $row ) {

                // fetch the data, make a variable, decode it to use nested json values in table
                // do the Form Content:
                $fc_request_data = $row->fc_request_data;
                $request_data = fc_json_decode($fc_request_data, true);
                if ( $request_data && is_array( $request_data ) && array_key_exists('body', $request_data) ) {
                    $request_data = $request_data['body'];
                }

                // do the Response Code:
                $fc_response = $row->fc_response;
                $response = fc_json_decode($fc_response, true);
                if ( $response && is_array( $response ) && array_key_exists('response', $response) ) {
                    $response = $response['response'];
                }

                $entry_link = '#';
                if ( $row->fc_form_id && $row->fc_submission_id ) {
                    $entry_link = admin_url("admin.php?page=formidable-entries&frm_action=show&id=$row->fc_submission_id&form=$row->fc_form_id&frm-full=1");
                }

                // make the table on the front-end
                echo '<tr>';
                echo '<td>' . $row->fc_id . '</td>';
                echo '<td>' . $row->fc_form_id . '</td>';
                echo '<td><a href="' . $entry_link . '">' . $row->fc_submission_id . '</a></td>';
                echo '<td>' . '<pre>' . print_r($request_data,1) . '</pre>' . '</td>';
                echo '<td><pre>' . print_r($response,1) . '</pre></td>';
                echo '<td>' . $row->fc_submission_date . '</td>';
                echo '<td class="failure">' . $row->fc_failure . '</td>';
                echo '</tr>';

            } // end foreach

            echo '<tr>
                            <th>Entry #</th>
                            <th>Form ID</th>
                            <th>Submission ID</th>
                            <th>Form Content</th>
                            <th>Response</th>
                            <th>Submission Date</th>
                            <th>Failures</th>
                        </tr>';

            echo '</table>';

            // add in pagination links
            if ($totalPage > 1){
                echo '<div class="paginate">'.paginate_links( array(
                'base'      => add_query_arg( 'p', '%#%' ),
                'format'    => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total'     => $totalPage,
                'current'   => $page,
                'before_page_number' => '',
                'after_page_number'  => '',
                )).'</div>';
            }

        ?>

        </div> <!-- #dataTable -->

    </div> <!-- end #capture-log -->

<?php } /* end salesforce_form_capture_log()  */?>