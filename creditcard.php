<?php



defined('_JEXEC') or die;



use Joomla\CMS\Factory;



use Joomla\CMS\Language\Text;



use Joomla\CMS\Plugin\CMSPlugin;



use Joomla\CMS\Uri\Uri;



use Joomla\Registry\Registry;







$document = Factory::getDocument();



// Load Bootstrap CSS



$document->addStyleSheet('https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css');



$input = Factory::getApplication()->input;



class plgGuruPaymentCreditCard extends CMSPlugin



{



    public function __construct(&$subject, $config)



    {



        parent::__construct($subject, $config);



        $this->loadLanguage();



    }

   

    // PAYMENT INITIATE



    public function onSendPayment(&$post)



    {

    

        if ($post['processor'] != 'creditcard') {

            return false;

        }

        // ADD COLUMN TO ORDER TABLE FOR REQUEST ID AUTHENTICATION

        $db = Factory::getDbo();

        $query = $db->getQuery(true);



        $tableName = '#__guru_order';

        $columnName = 'creditcard_request_id';



        // Check if the column exists

        $columns = $db->getTableColumns($tableName);

        if (!isset($columns[$columnName])) {

    

            $addColumn = 'ALTER TABLE ' . $db->quoteName($tableName) . ' ADD COLUMN ' . $db->quoteName($columnName) . ' VARCHAR(255)';



            try {

                $db->setQuery($addColumn);

                $db->execute();



                // Factory::getApplication()->enqueueMessage('Column added successfully: ' . $columnName, 'message');

            } catch (Exception $e) {

                // Factory::getApplication()->enqueueMessage('Error adding column: ' . $e->getMessage(), 'error');

            }

        } else {

            // Factory::getApplication()->enqueueMessage('Column already exists: ' . $columnName, 'message');

        }



        $language = JFactory::getLanguage();



        // GET CURRENT LNG TAG 

        $currentLanguage = $language->getTag();

        $primaryLanguage = substr($currentLanguage, 0, 2);

        //Factory::getApplication()->enqueueMessage($primaryLanguage, 'error');

        $params = new Registry($post['params']);

        $CCARD_KEY = $params->get('creditcard_key');

        $errorPageUrl = $params->get('creditcard_errorUrl');

        

        //Factory::getApplication()->enqueueMessage($CCARD_KEY, 'error');

        if (!$CCARD_KEY) {

            Factory::getApplication()->enqueueMessage(Text::_('PLG_CREDITCARD_MISSING_FIELDS_ERROR'), 'error');

            return;

        }

        if (!$errorPageUrl) {

            Factory::getApplication()->enqueueMessage(Text::_('PLG_CREDITCARD_MISSING_ERROR_URL'), 'error');

            return;

        }

        // URL TO INITIALIZE CREDIT CARD PAYMENTS

        $url = 'https://ifthenpay.com/api/creditcard/init/' . $CCARD_KEY;

        

        // GET CHECKOUT DATA

        $link_params = [

            'option' => $post['option'],

            'controller' => $post['controller'],

            'task' => $post['task'],

            'processor' => $post['processor'],

            'order_id' => @$post['order_id'],

            'sid' => @$post['sid'],

            'Itemid' => isset($post['Itemid']) ? $post['Itemid'] : '0',

        ];

        

        $callback_url = JURI::base() . 'index.php?' . $this->BindArray2Url($link_params) . '&pay=ipn';

        $cart_page_url = JURI::base() . 'gurubuy';

        // Factory::getApplication()->enqueueMessage("<pre>" . $callback_url . "</pre>", 'error');

        // Factory::getApplication()->enqueueMessage("<pre>" . $cart_page_url . "</pre>", 'error');

        

        // GET ORDER AMOUNT

        $db = Factory::getDbo();

        $query = $db->getQuery(true)

            ->select(['userid', 'amount', 'amount_paid'])

            ->from($db->quoteName('#__guru_order'))

            ->where($db->quoteName('id') . ' = ' . (int)$link_params['order_id']);

        $db->setQuery($query);

        $order_details = $db->loadAssoc();

        // Factory::getApplication()->enqueueMessage(print_r($order_details, true), 'error');

        

        $amount = $order_details['amount'];

        //Factory::getApplication()->enqueueMessage("<pre>" . $amount . "</pre>", 'error');

        

        // API REQUEST

        if (isset($CCARD_KEY, $url, $amount, $link_params['order_id'], $callback_url, $errorPageUrl, $cart_page_url)) {

            // POST DATA

            $postData = json_encode([

                'orderId' => $link_params['order_id'],

                'amount' => $amount,

                'successUrl' => $callback_url,

                'errorUrl' => $errorPageUrl,

                'cancelUrl' => $cart_page_url,

                'language' => $primaryLanguage

            ]);

        

            $curl = curl_init();

        

            curl_setopt_array($curl, [

                CURLOPT_URL => $url,

                CURLOPT_RETURNTRANSFER => true,

                CURLOPT_ENCODING => '',

                CURLOPT_MAXREDIRS => 10,

                CURLOPT_TIMEOUT => 0,

                CURLOPT_FOLLOWLOCATION => true,

                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

                CURLOPT_CUSTOMREQUEST => 'POST',

                CURLOPT_POSTFIELDS => $postData,

                CURLOPT_HTTPHEADER => [

                    'Content-Type: application/json',

                ],

            ]);

        

            $response = curl_exec($curl);

        

            if ($response === false) {

                Factory::getApplication()->enqueueMessage('cURL Error: ' . curl_error($curl), 'error');

            } else {

                $apiData = json_decode($response, true);

                if(isset($apiData)){

                    $Status = $apiData['Status'];

                    $Message = $apiData['Message'];

                    if ($Status === "0" && $Message === "Success") {

                        $PaymentUrl = $apiData['PaymentUrl'];

                        $RequestId = $apiData['RequestId'];

                        // JFactory::getApplication()->enqueueMessage($RequestId, 'error');

                        // Save RequestId to the database against the order ID

                        $db = Factory::getDbo();

                        $query = $db->getQuery(true);



                        $tableName = '#__guru_order';

                        $columnName = 'creditcard_request_id';



                        // Check if the column exists

                        $columns = $db->getTableColumns($tableName);

                        if (isset($columns[$columnName])) {



                            $query->clear()

                                ->update($db->quoteName($tableName))

                                ->set($db->quoteName($columnName) . ' = ' . $db->quote($RequestId))

                                ->where($db->quoteName('id') . ' = ' . (int)$link_params['order_id']);



                            try {

                                $db->setQuery($query);

                                $db->execute();



                                // Check if any rows were affected

                                $rowsAffected = $db->getAffectedRows();

                                if ($rowsAffected > 0) {

                                    // JFactory::getApplication()->enqueueMessage('Credit card request ID updated successfully.', 'message');

                                } else {

                                    // JFactory::getApplication()->enqueueMessage('No rows updated. Check your conditions.', 'warning');

                                }

                            } catch (Exception $e) {

                                // JFactory::getApplication()->enqueueMessage('Error updating creditcard_request_id: ' . $e->getMessage(), 'error');

                            }

                        } else {

                            // JFactory::getApplication()->enqueueMessage('Column does not exist: ' . $columnName, 'error');

                        }



                    

                        // REDIRECTION HTML

                        $html = '<div style="text-align: center;" id="ccard-main-container">';

                        $html .= '<div class="logo__ccard-ifthenpay">';

                        $html .= '<img src="' . Uri::base(true) . '/plugins/gurupayment/creditcard/creditcard-logo.png" alt="Logo">';

                        $html .= '<p style="font-size: 20px; padding-top: 18px;">' . Text::_('PLG_CREDITCARD_REDIRECT') . '</p>';

                        // LOADER

                        $html .= '<div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);">';

                        $html .= '<div class="spinner-border text-primary" role="status">';

                        $html .= '<span class="sr-only">Loading...</span>';

                        $html .= '</div>';

                        $html .= '</div>';

                        $html .= '</div>';

                        $html .= '</div>';

                    

                        // REDIRECT TO PAYMENT PAGE

                        $html .= '<script type="text/javascript">';

                        $html .= 'setTimeout(function() { window.location.href = "' . $PaymentUrl . '"; }, 3000);'; // 3000 ms delay

                        $html .= '</script>';

                    

                        return $html;

                    }else{

                        Factory::getApplication()->enqueueMessage($Message, 'error');

			            return;

                    }

                    

                    

                }

            }

        

            curl_close($curl);

        }else{

            Factory::getApplication()->enqueueMessage(Text::_('PLG_CREDITCARD_ERROR_PARAMS'), 'error');

			return;

        }

    }



    // CALLBACK PROCESS



    public function onReceivePayment($post)



    {



        if ($post['processor'] != 'creditcard') {



            return 0;



        }



        $order = $post['order_id'];

        $requestId = $post['requestId'];



        $params = new Registry($post['params']);



        // GET ASSOCIATED REQUEST ID



        $db = Factory::getDbo();

        $query = $db->getQuery(true)

            ->select($db->quoteName('creditcard_request_id'))

            ->from($db->quoteName('#__guru_order'))

            ->where($db->quoteName('id') . ' = ' . (int)$order);

        

        $db->setQuery($query);

        $creditcard_request_id = $db->loadResult();



        // Check if received requestId matches stored creditcard_request_id

        if ($requestId != $creditcard_request_id) {

            return 0; 

        }



        // CHECK ORDER STATUS IN DATABASE



        $db = Factory::getDbo();



        $query = $db->getQuery(true)



            ->select(['userid', 'amount', 'amount_paid'])



            ->from($db->quoteName('#__guru_order'))



            ->where($db->quoteName('id') . ' = ' . (int) $order);



        $db->setQuery($query);



        $order_details = $db->loadAssoc();







        $customer_id = $order_details['userid'];



        $gross_amount = $order_details['amount'];







        if ($order_details['amount_paid'] != -1) {



            $gross_amount = $order_details['amount_paid'];



        }







        require_once(JPATH_SITE . '/components/com_guru/models/gurubuy.php');



        $guru_buy_model = new guruModelguruBuy();



        $submit_array = [



            'customer_id' => (int) $customer_id,



            'order_id' => (int) $order,



            'price' => $gross_amount



        ];



        // MARK THE ORDER PAID



        $guru_buy_model->proccessSuccess('guruBuy', $submit_array, false);



    }







    private function BindArray2Url($param)



    {



        $out = [];



        foreach ($param as $k => $v) {



            $out[] = "$k=$v";



        }



        return implode('&', $out);



    }



}



?>