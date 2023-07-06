<?php
require_once "AppiloBase.php";
class Appilo {
    public $plugin_file=__FILE__;
    public $responseObj;
    public $licenseMessage;
    public $showMessage=true;
    public $slug="appilo-license";
    function __construct() {
        $licenseKey=get_option("Appilo_lic_Key","");
        $liceEmail=get_option( "Appilo_lic_email","");
        AppiloBase::addOnDelete(function(){
            delete_option("Appilo_lic_Key");
        });
        if(AppiloBase::CheckWPPlugin($licenseKey,$liceEmail,$this->licenseMessage,$this->responseObj,__FILE__)){
            add_action( 'admin_menu', [$this,'ActiveAdminMenu'],99999);
            add_action( 'admin_post_Appilo_el_deactivate_license', [ $this, 'action_deactivate_license' ] );
            //$this->licenselMessage=$this->mess;
            //***Write you plugin's code here***

        }else{
            if(!empty($licenseKey) && !empty($this->licenseMessage)){
                $this->showMessage=true;
            }
            update_option("Appilo_lic_Key","") || add_option("Appilo_lic_Key","");
            add_action( 'admin_post_Appilo_el_activate_license', [ $this, 'action_activate_license' ] );
            add_action( 'admin_menu', [$this,'InactiveMenu']);
        }
    }
    function ActiveAdminMenu(){
        add_submenu_page(  'appilo-admin-menu', "License", "License", "activate_plugins",  "appilo-license", [$this,"Activated"] );

    }
    function InactiveMenu() {
        add_submenu_page(  'appilo-admin-menu', "License", "License", "activate_plugins",  "appilo-license", [$this,"LicenseForm"] );

    }
    function action_activate_license(){
        check_admin_referer( 'el-license' );
        $licenseKey=!empty($_POST['el_license_key'])?sanitize_text_field($_POST['el_license_key']):"";
        $licenseEmail=!empty($_POST['el_license_email'])?sanitize_email($_POST['el_license_email']):"";
        update_option("Appilo_lic_Key",$licenseKey) || add_option("Appilo_lic_Key",$licenseKey);
        update_option("Appilo_lic_email",$licenseEmail) || add_option("Appilo_lic_email",$licenseEmail);
        update_option('_site_transient_update_plugins','');
        wp_safe_redirect(admin_url( 'admin.php?page='.$this->slug));
    }
    function action_deactivate_license() {
        check_admin_referer( 'el-license' );
        $message="";
        if(AppiloBase::RemoveLicenseKey(__FILE__,$message)){
            update_option("Appilo_lic_Key","") || add_option("Appilo_lic_Key","");
            update_option('_site_transient_update_plugins','');
        }
        wp_safe_redirect(admin_url( 'admin.php?page='.$this->slug));
    }
    function Activated(){
        ?>
        <div class="wrap about-wrap appilo-wrap">
            <div class="appilo-system-stats">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="Appilo_el_deactivate_license"/>
                    <div class="el-license-container">
                        <h1 class="el-license-title"><?php _e("Appilo License",$this->slug);?> </h1>
                        <hr>
                        <ul class="el-license-info">
                            <li>
                                <div>
                                    <span class="el-license-info-title"><?php _e("Status",$this->slug);?></span>

                                    <?php if ( $this->responseObj->is_valid ) : ?>
                                        <span class="el-license-valid"><?php _e("Valid",$this->slug);?></span>
                                    <?php else : ?>
                                        <span class="el-license-valid"><?php _e("Invalid",$this->slug);?></span>
                                    <?php endif; ?>
                                </div>
                            </li>

                            <li>
                                <div>
                                    <span class="el-license-info-title"><?php _e("License Type",$this->slug);?></span>
                                    <?php echo $this->responseObj->license_title; ?>
                                </div>
                            </li>

                            <li>
                                <div>
                                    <span class="el-license-info-title"><?php _e("License Expired on",$this->slug);?></span>
                                    <?php echo $this->responseObj->expire_date;
                                    if(!empty($this->responseObj->expire_renew_link)){
                                        ?>
                                        <a target="_blank" class="el-blue-btn" href="<?php echo $this->responseObj->expire_renew_link; ?>">Renew</a>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </li>

                            <li>
                                <div>
                                    <span class="el-license-info-title"><?php _e("Support Expired on",$this->slug);?></span>
                                    <?php
                                    echo $this->responseObj->support_end;
                                    if(!empty($this->responseObj->support_renew_link)){
                                        ?>
                                        <a target="_blank" class="el-blue-btn" href="<?php echo $this->responseObj->support_renew_link; ?>">Renew</a>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </li>
                            <li>
                                <div>
                                    <span class="el-license-info-title"><?php _e("Your License Key",$this->slug);?></span>
                                    <span class="el-license-key"><?php echo esc_attr( substr($this->responseObj->license_key,0,9)."XXXXXXXX-XXXXXXXX".substr($this->responseObj->license_key,-9) ); ?></span>
                                </div>
                            </li>
                        </ul>
                        <div class="el-license-active-btn">
                            <?php wp_nonce_field( 'el-license' ); ?>
                            <?php submit_button('Deactivate'); ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    function LicenseForm() {
        ?>
        <div class="wrap about-wrap appilo-wrap">
            <div class="appilo-system-stats">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="Appilo_el_activate_license"/>
                    <div class="el-license-container">
                        <h1 class="el-license-title"> <?php _e("Appilo Licensing",$this->slug);?></h1>
                        <hr>
                        <?php
                        if(!empty($this->showMessage) && !empty($this->licenseMessage)){
                            ?>
                            <div class="notice notice-error is-dismissible">
                                <p><?php echo _e($this->licenseMessage,$this->slug); ?></p>
                            </div>
                            <?php
                        }
                        ?>

                        <div class="el-license-field">
                            <label for="el_license_key"><?php _e("License code",$this->slug);?></label>
                            <input type="text" class="regular-text code" name="el_license_key" size="50" placeholder="xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx" required="required">
                        </div>
                        <div class="el-license-field">
                            <label for="el_license_key"><?php _e("Email Address",$this->slug);?></label>
                            <?php
                            $purchaseEmail   = get_option( "Appilo_lic_email", get_bloginfo( 'admin_email' ));
                            ?>
                            <input type="text" class="regular-text code" name="el_license_email" size="50" value="<?php echo $purchaseEmail; ?>" placeholder="" required="required">
                            <div><small><?php _e("We will send update news of this product by this email address, don't worry, we hate spam",$this->slug);?></small></div>
                        </div>
                        <div class="el-license-active-btn">
                            <?php wp_nonce_field( 'el-license' ); ?>
                            <?php submit_button('Activate'); ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}

new Appilo();