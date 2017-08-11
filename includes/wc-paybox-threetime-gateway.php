<?php

class WC_Paybox_Threetime_Gateway extends WC_Paybox_Abstract_Gateway {

    protected $defaultTitle = 'Paybox 3 times payment';
    protected $defaultDesc = 'xxxx';
    protected $type = 'threetime';

    public function __construct() {
        // Some properties
        $this->id = 'x';
        $this->method_title = __('Paybox 3 times', 'paybox');
        $this->has_fields = false;
        $this->icon = apply_filters('woocommerce_gateway_icon', plugins_url('paybox-by-verifone-integration/images/logo-small.png'));

        parent::__construct();
    }

    /**
     * Check If The Gateway Is Available For Use
     *
     * @access public
     * @return bool
     */
    public function is_available() {
        if (!parent::is_available()) {
            return false;
        }
        $minimal = $this->_config->getAmount();
        if (empty($minimal)) {
            return true;
        }
        $total = WC()->cart->total;
        $minimal = floatval($minimal);
        return $total >= $minimal;
    }

}
