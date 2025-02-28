<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


class OAM_Shortcode
{
    /**
	 * Define class Constructor
	 **/
	public function __construct() {
        add_shortcode('select_affiliate', array( $this, 'select_affiliate_handler' ) );
        add_shortcode('select_devs', array( $this, 'select_devs_handler' ) );
        add_shortcode('get_group', array( $this, 'get_group_handler' ) );
        
    }

    
    public function get_group_handler() {
        ob_start();

        $OAM_Custom = new OAM_Custom();
        $OAM_Custom->getGroup();
        return ob_get_clean();
    }
    public function select_devs_handler() {
        ob_start();

        if (! is_user_logged_in()) {
            echo "User is not login";
            return ob_get_clean();
        }
        
        $data = '';
        $oam_ajax = new OAM_Ajax();
        $OAM_Custom = new OAM_Custom();
        if(isset($_GET['recipient_group_id']) AND $_GET['recipient_group_id'] != ''){
            $data = $oam_ajax->orthoney_get_recipient_handler(get_current_user_id(),$_GET['recipient_group_id'] );
        }else{
            $data = $oam_ajax->orthoney_get_recipient_handler(get_current_user_id() );
        }
        
        $result = json_decode($data, true);
        if(!empty( $result)){
            if($result['success'] == 1){
            echo '<div class="recipient-group-section">';
            echo '<div id="failCSVData">'.$result['data']['failData'].'</div>';
            echo '<div id="successCSVData">'.$result['data']['successData'].'</div>';
            echo '<div id="duplicateCSVData">'.$result['data']['duplicateData'].'</div>';
            echo '<div id="newCSVData">'.$result['data']['newData'].'</div>';
            echo '</div>';
            echo $OAM_Custom->manageRecipientPopup();
                if(isset($_GET['recipient_group_id']) AND $_GET['recipient_group_id'] != ''){
                echo '<button class="editRecipient" data-popup="#recipient-manage-popup">Add new Recipient</button>';
                }
            }
        }
        
        return ob_get_clean();
    }
    
    public function select_affiliate_handler() {
        ob_start();
        $affiliate_id = 0; 
        if(isset($_POST['affiliate']) AND $_POST['affiliate'] != ''){
            $affiliate_id = $_POST['affiliate']; 
        }
        
        $affiliates = OAM_Custom::getAffiliateList();
        
        ?>
        <form id="select-affiliate" method="post">
            <label for="affiliate">Select Affiliate:</label>
            <select name="affiliate" id="affiliate" required>
              <?php 

              if(!empty($affiliates)){
                echo '<option value="0">' . esc_html('ORT Honey') . '</option>';
                foreach ($affiliates as $affiliate) {
                    $selected = '';
                    if($affiliate_id == $affiliate->id){
                        $selected = 'selected';
                    }
                    echo '<option '.$selected.'  value="' . esc_attr($affiliate->id) . '">' . esc_html($affiliate->name) . '</option>';
                }
                }
              ?>
            </select>
            <button type="submit" class="button button-primary">Select Affiliate</button>
        </form>
        <?php 
         if(isset($_POST['affiliate']) AND $_POST['affiliate'] != ''){
            ?>
            <div class="order-format-section">
                <div class="order-type-wrapper">
                    <button class="single-order button button-primary">Single Order</button>
                    <button class="order-with-recipient button button-primary">Order With Recipient</button>
                </div>
                <div class="single-order-wrapper" style="display:none">
                    <form action="<?php echo esc_url( wc_get_checkout_url() ); ?>" method="post">
                        <div>
                            <input type="hidden" name="product_id" value="12"> <!-- Product ID -->
                            <input type="hidden" name="affiliate" value="<?php echo esc_attr( $_POST['affiliate'] ?? '' ); ?>"> <!-- Affiliate Data -->
                            <input type="hidden" name="order_type" value="single-order">
                        </div>
                        <div>
                            <label for="quantity">Quantity:</label>
                            <input type="number" id="quantity" name="quantity" min="1" required>
                        </div>

                        <div>
                            <button type="submit">Order Place</button>
                        </div>
                    </form>
                </div>

                <div class="recipient-order-wrapper" style="display:none">
                    <button class="re-order">Re-Order</button>
                    <button class="upload-csv">Upload CSV</button>
                    <button class="existing-recipients">Order with existing recipients</button>
                    <div class="re-order-wrapper" style="display:none">
                        Working Pending
                    </div>
                    <div class="upload-csv-wrapper" style="display:none">
                        <div class="recipient-group-section">
                            <?php
                                echo OAM_Custom::getuploadRecipientForm();
                                echo OAM_Custom::manageRecipientPopup();
                                echo '<button class="editRecipient" data-popup="#recipient-manage-popup">Add new Recipient</button>';
                            ?>
                        </div>
                    </div>
                    <div class="existing-recipients-wrapper" style="display:none">
                        <?php
                        echo OAM_Custom::getGroup();
                        ?>
                    </div>
                </div>
            </div>
            <?php 
         }
        
        return ob_get_clean();
    }

}
new OAM_Shortcode();