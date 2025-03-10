<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo '<h2>Change Admin</h2>';


global $wpdb;
$get_current_user_id = get_current_user_id();
$users = $wpdb->get_results("
    SELECT u.ID, u.display_name 
    FROM wp_users u
    JOIN wp_usermeta m ON u.ID = m.user_id
    WHERE m.meta_key = 'email_verified' 
    AND m.meta_value = 'true' 
    AND u.ID != $get_current_user_id
");

echo '<select id="userDropdown">';
    echo '<option value="">Select a User</option>';
    foreach ($users as $user) {
        echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . '</option>'; 
    }
echo '</select>';

echo '<button id="changeRoleBtn" class="us-btn-style_1">Change Role & Logout</button>';


add_action('wp_ajax_change_user_role_logout', 'change_user_role_logout');
add_action('wp_ajax_nopriv_change_user_role_logout', 'change_user_role_logout');

function change_user_role_logout() {
    // Security check
    check_ajax_referer('change_role_nonce', 'security');

    // Get current user ID and selected user ID
    $selected_user_id = isset($_POST['selected_user_id']) ? intval($_POST['selected_user_id']) : 0;

    // Change the role of the selected user (Example: 'editor')
    $user = new WP_User($selected_user_id);


    // Make sure to end execution after sending JSON response
    wp_send_json_success(['message' => 'successfully.']);
    wp_die(); // Always add this after wp_send_json_*
}
?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("changeRoleBtn").addEventListener("click", function() {
        var userDropdown = document.getElementById("userDropdown");
        var selectedUserId = userDropdown.value;
        var securityNonce = "<?php echo wp_create_nonce('change_role_nonce'); ?>";

        console.log("Selected User ID:", selectedUserId);

        if (!selectedUserId) {
            alert("Please select a user.");
            return;
        }

        var formData = new FormData();
        formData.append("action", "change_user_role_logout");
        formData.append("security", securityNonce);
        formData.append("selected_user_id", selectedUserId);

        fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
                method: "POST",
                body: formData,
            })
            .then(response => {
                console.log("HTTP Response Status:", response.status);
                return response.text(); // Read raw text to debug issues
            })
            .then(text => {
                console.log("Raw Response:", text); // Log raw response
                let data;
                try {
                    data = JSON.parse(text); // Try to parse JSON
                } catch (error) {
                    throw new Error("Invalid JSON response: " + text);
                }

                console.log("Parsed JSON:", data);
                if (data.success) {
                    alert(data.data?.message || "Role changed, logging out...");
                } else {
                    alert(data.data?.message || "Error occurred!");
                }
            })
            .catch(error => {
                console.error("AJAX Error:", error);
                alert("Something went wrong. Please try again.");
            });
    });
});
</script>