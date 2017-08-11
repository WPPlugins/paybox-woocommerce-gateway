<?php

abstract class WC_Paybox_Abstract_Gateway extends WC_Payment_Gateway {

    protected $_config;
    protected $_paybox;
    protected $_orderClass = 'WC_Order';
    private $logger;
    public $plugin_id = 'paybox_';

    public function __construct() {
        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'api_call'));

        // Logger for debug if needed
        if (WC()->debug === 'yes') {
            $this->logger = WC()->logger();
        }

        $this->method_description = '<center><img src="' . plugins_url('paybox-by-verifone-integration/images/logo.png') . '"/></center>';

        // Load the settings
        $this->init_settings();

        $this->_config = new Paybox_Config($this->settings, $this->defaultTitle, $this->defaultDesc);
        $this->init_form_fields();

        $this->_paybox = new Payboxclass($this->_config);
//
        $this->title = $this->_config->getTitle();
        $this->description = $this->_config->getDescription();
    }

    /**
     * save_hmackey
     * Used to save the settings field of the custom type HSK
     * @param  array $field
     * @return void
     */
    public function process_admin_options() {
        $crypto = new PayboxEncrypt();
        if (isset($_POST["paybox_standard_hmackey"]))
            $_POST["paybox_standard_hmackey"] = $crypto->encrypt($_POST["paybox_standard_hmackey"]);
        else
            $_POST["paybox_x_hmackey"] = $crypto->encrypt($_POST["paybox_x_hmackey"]);
        parent::process_admin_options();
    }

    public function admin_options() {
        $crypt = new PayboxEncrypt();
        if (isset($this->settings['hmackey'])) {
            $this->settings['hmackey'] = $crypt->decrypt($this->settings['hmackey']);
        }
        parent::admin_options();
    }

    public function init_form_fields() {
        $this->form_fields = $this->_config->init_form_fields($this->type);
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
        return $total >= $minimal;
    }

    /**
     * Process the payment, redirecting user to Paybox.
     *
     * @param int $order_id The order ID
     * @return array TODO
     */
    public function process_payment($orderId) {
        $order = new WC_Order($orderId);

        $message = $this->_paybox->_messages['info']['paybox-customer-redirect'];
        $this->_paybox->addOrderNote($order, $message);

        return array(
            'result' => 'success',
            'redirect' => add_query_arg('order-pay', $order->id, add_query_arg('key', $order->order_key, $order->get_checkout_order_received_url())),
        );
    }

    public function receipt_page($orderId) {
        $order = new WC_Order($orderId);

        $urls = array(
            'PBX_ANNULE' => network_site_url(add_query_arg('wc-api', get_class($this), add_query_arg('status', 'cancel'))),
            'PBX_EFFECTUE' => network_site_url(add_query_arg('wc-api', get_class($this), add_query_arg('status', 'success'))),
            'PBX_REFUSE' => network_site_url(add_query_arg('wc-api', get_class($this), add_query_arg('status', 'failed'))),
            'PBX_REPONDRE_A' => network_site_url(add_query_arg('wc-api', get_class($this), add_query_arg('status', 'ipn'))),
        );

        $params = $this->_paybox->buildSystemParams($order, $this->type, $urls);

        try {
            $url = $this->_paybox->getSystemUrl();
        } catch (Exception $e) {
            echo "<p>" . $e->getMessage() . "</p>";
            echo "<form><center><button onClick='history.go(-1);return true;'>" . __('Back...', 'paybox') . "</center></button></form>";
            exit;
        }
        $debug = $this->_config->isDebug();

        $debugViewMsg = $this->_paybox->_messages['info']['paybox-debug-view'];
        $redirectButtonMsg = $this->_paybox->_messages['info']['paybox-redirect-button'];
        $continueMsg = $this->_paybox->_messages['info']['paybox-continue'];
        ?>
        <form id="pbxep_form" method="post" action="<?php echo esc_url($url); ?>" enctype="application/x-www-form-urlencoded">
            <?php if ($debug): ?>
                <p>
                    <?php echo $debugViewMsg; ?>
                </p>
            <?php else: ?>
                <p>
                    <?php echo $redirectButtonMsg; ?>
                </p>
                <script type="text/javascript">
                    window.setTimeout(function () {
                        document.getElementById('pbxep_form').submit();
                    }, 1);
                </script>
            <?php endif; ?>
            <center><button><?php echo $continueMsg; ?></button></center>
            <?php
            $type = $debug ? 'text' : 'hidden';
            foreach ($params as $name => $value):
                $name = esc_attr($name);
                $value = esc_attr($value);
                if ($debug):
                    echo '<p><label for="' . $name . '">' . $name . '</label>';
                endif;
                echo '<input type="' . $type . '" id="' . $name . '" name="' . $name . '" value="' . $value . '" />';
                if ($debug):
                    echo '</p>';
                endif;
            endforeach;
            ?>
        </form>
        <?php
    }

    public function api_call() {
        if (!isset($_GET['status'])) {
            header('Status: 404 Not found', true, 404);
            die();
        }

        switch ($_GET['status']) {
            case 'cancel':
                return $this->_paybox->on_payment_canceled($this->_orderClass);
                break;

            case 'failed':
                return $this->_paybox->on_payment_failed($this->_orderClass);
                break;

            case 'ipn':
                return $this->_paybox->on_ipn($this->_orderClass, $this->type);
                break;

            case 'success':
                return $this->_paybox->on_payment_succeed($this->_orderClass);
                break;

            default:
                header('Status: 404 Not found', true, 404);
                die();
        }
    }

    public function showDetails($order){
        $this->_paybox->showDetails($order->id, $this->_orderClass, $this->type);
    }
}
