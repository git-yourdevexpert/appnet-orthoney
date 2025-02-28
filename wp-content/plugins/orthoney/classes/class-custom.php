<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_Custom{
	/**
	 * Define class Constructor
	 **/
	public function __construct() {}

    public static function RecipientForm(){
        ?>
        <div id="recipient-manage-form" class="">
        <form method="POST" enctype="multipart/form-data">
        <input type="hidden" id="recipient_id" name="recipient_id" value="">
            <div>
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div>
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" required>
            </div>
            <div>
            <label for="address_1">Address 1:</label>
            <input type="text" id="address_1" name="address_1" required>
            </div>
            <div>
            <label for="address_2">Address 2:</label>
            <input type="text" id="address_2" name="address_2">
            </div>
            <div>
            <label for="city">City:</label>
            <input type="text" id="city" name="city" required>
            </div>
            <div>
            <label for="state">State:</label>
            <input type="text" id="state" name="state" required>
            </div>
            <div>
            <label for="country">Country:</label>
            <select id="country" name="country" required>
                <option value="">Select Country</option>
                <option value="US">United States</option>
                <option value="USA">USA</option>
                <option value="CA">Canada</option>
                <option value="UK">United Kingdom</option>
                <option value="AU">Australia</option>
            </select>
            </div>
            <div>
            <label for="zipcode">Zipcode:</label>
            <input type="text" id="zipcode" name="zipcode" required>
            </div>
            <div>
            <button type="submit">Submit</button>
            </div>
        </form>
        </div>
        <?php
    }

    public static function manageRecipientPopup(){
        ?>
        <div id="recipient-manage-popup" class="lity-hide black-mask full-popup">
            <h2>Recipient is edit</h2>
            <?php 
            echo self::RecipientForm();
            ?>
        </div>
        <?php
    }
    
    public static function getGroup() {
        ?>
        <div class="recipient-group-section">
            <div class="recipient-group-list">
                <?php 
                $groups = self::getGroupList();
                if(!empty($groups)){
                ?>
                <select name="groups-list" class="groups-list">
                    <option value="">Select Group</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?php echo esc_attr($group->id); ?>">
                            <?php echo esc_html($group->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
    
                <div class="edit-group-form-wrapper" style="display: none;">
                    <div class="edit-group-form" style="display: none;">
                        <?php echo self::getCreateGroupForm('edit'); ?>
                        <div class="response-msg"></div>
                    </div>
                </div>
                <?php } else{
                    echo 'No group exists. Please create a group first!';
                    ?>
                    <div class="recipient-group-form" style="display:none">
                    <?php echo self::getCreateGroupForm(); ?>
                    <div class="response-msg"></div>
            </div>
            <button class="createGroupFormButton">Create New Group</button>
                    <?php 
                } ?>
            </div>
        </div>
        <?php
    }
    
    /**
	 * Helper function that get Affiliates lists
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return array $groups is array with user data.
	 */
    public static function getAffiliateList($user_id = ''){
        global $wpdb;
        
        // Table name (sanitize the table name)
        $orm_affiliate_table = $wpdb->prefix . 'orm_affiliate';
        
        // Prepare the query based on whether user_id is provided
        if($user_id == ''){
            $query = "SELECT * FROM $orm_affiliate_table";  // No need to prepare if there's no dynamic value
            $groups = $wpdb->get_results($query);
        } else {
            $query = $wpdb->prepare("SELECT * FROM $orm_affiliate_table WHERE id = %d", $user_id); // Prepare the user_id part
            $groups = $wpdb->get_results($query);
        }
        
        return $groups;
    }

	/**
	 * Helper function that get group lists
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return array $groups is array with user data.
	 */

	public static function getGroupList($user_id = ''){
        $groups = array();
        global $wpdb;
        if($user_id == ''){
            // Get current user ID
            $user_id = get_current_user_id();
        }
        // Table name
        $recipient_group_table = $wpdb->prefix . 'recipient_group';

        if($user_id == ''){
            $groups = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM $recipient_group_table"));
        }else{
            $groups = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM $recipient_group_table WHERE user_id = %d
            ", $user_id));
        }
        
        return $groups;
    }
    
    public static function getuploadRecipientForm($edit = ''){
        echo '<form id="csv-upload-form" action="'.home_url("/dev-test").'"  enctype="multipart/form-data" method="get">
        <input type="hidden" name="recipient_group_id" id="recipient_group_id">
        <div>
            <label for="csv_file">Upload CSV File:</label><br>
            <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
        </div>
        <div>
            <label for="group_name">Add name for CSV:</label><br>
            <input type="text" placeholder="Add name here..." name="group_name" id="group_name">
        </div>
        <div>
            <label for="greeting">Add Greeting</label><br>
            <textarea placeholder="Add name here..." name="greeting" id="greeting" maxlength="250"></textarea>
            <div id="char-counter"><span>250</span> characters left</div>
        </div>
        <button type="submit">Upload</button>
    </form>
     <div id="progress-wrapper" style="display: none;">
            <progress id="progress-bar" value="0" max="100"></progress>
            <span id="progress-percentage">0%</span>
        </div><div id="message"></div>
        <div id="failCSVData"></div>
        <div id="successCSVData"></div>
        <div id="duplicateCSVData"></div>
        <div id="newCSVData"></div>';
    }
	/**
	 * Helper function that get form for the create and edit group
	 *
	 * @return string
	 */
	public static function getCreateGroupForm($edit = ''){
        $label = 'Create Group';
        $status = 'create';
        if($edit == 'edit'){
            $status = 'edit';
            $label = 'Edit Group';
        }
        echo '<form class="groupForm" data-formType="'.$status.'">
            <input type="text" name="group_name" class="group_name" placeholder="Enter group name" required />
            <input type="hidden" name="group_id" class="group_id" />
            <button type="button" name="create_group" class="createGroupButton">'.$label.'</button>
        </form>';
    }

}
new OAM_Custom();

/*
backup code for the old code
public static function getGroup(){
        ?>
        <div class="recipient-group-section">
            <div class="recipient-group-list">
                <?php $groups = self::getGroupList();
                echo '<select name="groups-list" class="groups-list">';
                echo '<option value="">Select Group</option>';
                foreach ($groups as $group) {
                    echo '<option value="' . esc_attr($group->id) . '">' . esc_html($group->name) . '</option>';
                }
                echo '</select>';
                ?>
                <div class="edit-group-form-wrapper" style="display:none">
                <div class="edit-group-form" style="display:none">
                    <?php echo self::getCreateGroupForm('edit'); ?>
                    <div class="response-msg"></div>
                </div>
                <button class="editGroupFormButton">Edit Group</button>
                <button class="uploadRecipientButton">Add Recipient using (SCV)</button>
                <!-- <button class="deleteGroupButton">Delete Group</button> -->
                </div>
            </div>
            <div class="recipient-group-form" style="display:none">
            <?php echo self::getCreateGroupForm(); ?>
            <div class="response-msg"></div>
            </div>
            <div class="upload-recipient-form" style="display:none">
                <?php echo self::getuploadRecipientForm(); ?>
            <div class="response-msg"></div>
            </div>
            <button class="createGroupFormButton">Create New Group</button>
        </div>
        <?php
    }
 */