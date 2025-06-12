<?php
// store/partials/order_tabs.php
if(!isset($active_tab)) { $active_tab = 'pending'; }

// This partial requires $link and $restaurant_id to be defined before it's included.
$counts = ['pending' => 0, 'active' => 0];
if (isset($link) && isset($restaurant_id)) {
    $sql_counts = "
        SELECT 
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status IN ('preparing', 'out_for_delivery') THEN 1 ELSE 0 END) as active_count
        FROM orders
        WHERE restaurant_id = ?";
    if($stmt_counts = mysqli_prepare($link, $sql_counts)){
        mysqli_stmt_bind_param($stmt_counts, "i", $restaurant_id);
        mysqli_stmt_execute($stmt_counts);
        $result = mysqli_stmt_get_result($stmt_counts);
        $fetched_counts = mysqli_fetch_assoc($result);
        if ($fetched_counts) {
            $counts['pending'] = $fetched_counts['pending_count'];
            $counts['active'] = $fetched_counts['active_count'];
        }
        mysqli_stmt_close($stmt_counts);
    }
}
?>
<div class="mb-6">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="orders.php" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?php echo ($active_tab == 'pending') ? 'text-orange-600 border-orange-500' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                Pending <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5 ml-1"><?php echo $counts['pending']; ?></span>
            </a>
            <a href="orders_active.php" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?php echo ($active_tab == 'active') ? 'text-orange-600 border-orange-500' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                Active <span class="bg-blue-500 text-white text-xs rounded-full px-2 py-0.5 ml-1"><?php echo $counts['active']; ?></span>
            </a>
            <a href="orders_completed.php" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?php echo ($active_tab == 'completed') ? 'text-orange-600 border-orange-500' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                Completed
            </a>
        </nav>
    </div>
</div>
