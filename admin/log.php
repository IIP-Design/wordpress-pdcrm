<?php
/**
 * The log page for the form capture plugin
 */

// enqueue the stylesheet for this page
function add_stylesheet() {
    wp_enqueue_style( 'capture-log-styles', plugins_url( 'css/style.css', __FILE__ ) );
}
add_action('admin_print_styles', 'add_stylesheet');

// add in submenu for log
add_action('admin_menu', 'form_capture_settings');

function form_capture_settings() {
	add_submenu_page(
		'Salesforce Form Captures Log',
		'Salesforce Form Captures Log',
		'administrator',
		__FILE__,
		'salesforce_form_capture_log' ,
		plugins_url('/images/icon.png', __FILE__)
	);
}

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
    }
    return $result;
}

// begin markup to display on log page

function salesforce_form_capture_log() {

?>

    <div id="capture-log">
        <h1>Salesforce Form Captures Log</h1>

        <input type="search" class="light-table-filter" data-table="order-table" placeholder="Search">
        <button id="show-failures" type="button" class="button">Show Failures</button>
        <button id="btnExportToCsv" type="button" class="button">Export all to CSV</button>

        <!-- add in search filter -->
        <script>
            (function(document) {
                'use strict';

                var LightTableFilter = (function(Arr) {

                    var _input;

                    function _onInputEvent(e) {
                        _input = e.target;
                        var tables = document.getElementsByClassName(_input.getAttribute('data-table'));
                        Arr.forEach.call(tables, function(table) {
                            Arr.forEach.call(table.tBodies, function(tbody) {
                                Arr.forEach.call(tbody.rows, _filter);
                            });
                        });
                    }

                    function _filter(row) {
                        var text = row.textContent.toLowerCase(), val = _input.value.toLowerCase();
                        row.style.display = text.indexOf(val) === -1 ? 'none' : 'table-row';
                    }

                    return {
                        init: function() {
                            var inputs = document.getElementsByClassName('light-table-filter');
                            Arr.forEach.call(inputs, function(input) {
                                input.oninput = _onInputEvent;
                            });
                        }
                    };
                })(Array.prototype);

                document.addEventListener('readystatechange', function() {
                    if (document.readyState === 'complete') {
                        LightTableFilter.init();
                    }
                });

            })(document);
        </script>

        <?php settings_fields( 'salesforce-form-capture-plugin-settings-group' ); ?>
        <?php do_settings_sections( 'salesforce-form-capture-plugin-settings-group' ); ?>

        <?php

            function form_capture_table() {
                global $wpdb;

                $content .= '<table class="order-table table">';
                $content .= '<thead>
                                <th>Entry #</th>
                                <th>Form ID</th>
                                <th>Submission ID</th>
                                <th>Form Content</th>
                                <th>Response</th>
                                <th>Submission Date</th>
                                <th>Failures</th>
                            </thead>';

                $result = $wpdb->get_results( 'SELECT * FROM wp_form_capture_data ORDER BY fc_submission_date DESC' );



                foreach ( $result as $row ) {

                    // fetch the data, make a variable, decode it to use nested json values in table
                    // do the Form Content:
                    $fc_request_data = $row->fc_request_data;
                    $request_data = fc_json_decode($fc_request_data, true);
                    if ( $request_data && array_key_exists('body', $request_data) ) {
                        $request_data = $request_data['body'];
                    }

                    // do the Response Code:
                    $fc_response = $row->fc_response;
                    $response = fc_json_decode($fc_response, true);
                    if ( $response && array_key_exists('response', $response) ) {
                        $response = $response['response'];
                    }

                    $entry_link = '#';
                    if ( $row->fc_form_id && $row->fc_submission_id ) {
                        $entry_link = admin_url("admin.php?page=formidable-entries&frm_action=show&id=$row->fc_submission_id&form=$row->fc_form_id&frm-full=1");
                    }

                    // make the table on the front-end
                    $content .= '<tr>';
                    $content .= '<td>' . $row->fc_id . '</td>';
                    $content .= '<td>' . $row->fc_form_id . '</td>';
                    $content .= '<td><a href="' . $entry_link . '">' . $row->fc_submission_id . '</a></td>';
                    $content .= '<td>' . '<pre>' . print_r($request_data,1) . '</pre>' . '</td>';
                    $content .= '<td><pre>' . print_r($response,1) . '</pre></td>';
                    $content .= '<td>' . $row->fc_submission_date . '</td>';
                    $content .= '<td class="failure">' . $row->fc_failure . '</td>';
                    $content .= '</tr>';
                }
                $content .= '<tr>
                                <th>Entry #</th>
                                <th>Form ID</th>
                                <th>Submission ID</th>
                                <th>Form Content</th>
                                <th>Response</th>
                                <th>Submission Date</th>
                                <th>Failures</th>
                            </tr>';

                $content .= '</table>';

                return $content;

            }

        ?>

        <div id="dataTable"> <!-- use this for export -->

            <?php echo form_capture_table(); ?>

        </div>

        <!-- javascript to handle markup to csv -->

        <script>
            class TableCSVExporter {
                constructor (table, includeHeaders = true) {
                    this.table = table;
                    this.rows = Array.from(table.querySelectorAll("tr"));

                    if (!includeHeaders && this.rows[0].querySelectorAll("th").length) {
                        this.rows.shift();
                    }
                }

                convertToCSV () {
                    const lines = [];
                    const numCols = this._findLongestRowLength();

                    for (const row of this.rows) {
                        let line = "";

                        for (let i = 0; i < numCols; i++) {
                            if (row.children[i] !== undefined) {
                                line += TableCSVExporter.parseCell(row.children[i]);
                            }
                            line += (i !== (numCols - 1)) ? "," : "";
                        }
                        lines.push(line);
                    }
                    return lines.join("\n");
                }

                _findLongestRowLength () {
                    return this.rows.reduce((l, row) => row.childElementCount > l ? row.childElementCount : l, 0);
                }

                static parseCell (tableCell) {
                    let parsedValue = tableCell.textContent;
                    // Replace all double quotes with two double quotes
                    parsedValue = parsedValue.replace(/"/g, `""`);
                    // If value contains comma, new-line or double-quote, enclose in double quotes
                    parsedValue = /[",\n]/.test(parsedValue) ? `"${parsedValue}"` : parsedValue;
                    return parsedValue;
                }
            } // end TableCSVExporter class
        </script>

        <!-- create export -->

        <script>
            const dataTable = document.getElementById("dataTable");
            const btnExportToCsv = document.getElementById("btnExportToCsv");

            btnExportToCsv.addEventListener("click", () => {
                const exporter = new TableCSVExporter(dataTable);
                const csvOutput = exporter.convertToCSV();
                const csvBlob = new Blob([csvOutput], { type: "text/csv" });
                const blobUrl = URL.createObjectURL(csvBlob);
                const anchorElement = document.createElement("a");

                anchorElement.href = blobUrl;
                anchorElement.download = "table-export.csv";
                anchorElement.click();

                setTimeout(() => {
                    URL.revokeObjectURL(blobUrl);
                }, 500);
            });
        </script>

        <!-- use css to hide rows without errors -->
        <script>
            jQuery(document).ready(function() {
               jQuery("#show-failures").toggle(
                function() {
                    var cellid = 0;
                    jQuery('tr').find('td:eq(6):contains('+cellid+')').parent().css('display', 'none');
                    jQuery('#show-failures').text(function(i, oldText) {
                        return oldText === 'Show Failures' ? 'Show All' : oldText;
                    });
                },
                function() {
                    var cellid = 0;
                    jQuery('tr').find('td:eq(6):contains('+cellid+')').parent().css('display', 'table-row');
                    jQuery('#show-failures').text(function(i, oldText) {
                        return oldText === 'Show All' ? 'Show Failures' : oldText;
                    });
                });
            });
        </script>

    </div> <!-- end #capture-log -->

<?php } ?>