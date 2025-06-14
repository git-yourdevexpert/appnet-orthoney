<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>
<style>

    #sales-representative-affiliate-table_wrapper table.dataTable tbody th, table.dataTable tbody td:nth-child(2) {
        width: 150px !important;
        max-width:150px !important;
        word-break: break-word;
    }
    #sales-representative-affiliate-table_wrapper table.dataTable tbody th, table.dataTable tbody td:nth-child(1) {
        width: 40px !important;
        max-width: 40px !important;
        word-break: break-word;
    }
  
</style>
 <div class="affiliate-dashboard order-process-block">
     <h3>Assigned Organizations</h3>
<div class="orthoney-datatable-warraper">
    <table id="sales-representative-affiliate-table" class="display">
        <thead>
            <tr>
                <th>Code</th>  
                <th>Email</th>
                <th>Organization</th>
                <th>New Organization</th>
                <th>Status</th>
                <th>Season Status</th>
                <th>Price</th>
                <th>Commission</th>
                <th>Login</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
</div>
