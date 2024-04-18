<?php
/**
 * EasyTransac OpenCart module.
 *
 * @author Easytransac SAS
 * @copyright Copyright (c) 2022 Easytransac
 * @license Apache 2.0
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Opencart\Catalog\Controller\Extension\OcPaymentEasytransac\Payment;

require_once __DIR__ . '/../../../vendor/autoload.php';

use EasyTransac\Core\Services;
use EasyTransac\Entities\Customer;
use EasyTransac\Entities\Notification;
use EasyTransac\Entities\PaymentPageTransaction;
use EasyTransac\Requests\PaymentPage;
use Opencart\System\Engine\Controller;

class Easytransac extends Controller
{
    public function index(): string
    {
        $this->load->language('extension/oc_payment_easytransac/payment/easytransac');

        if (isset($this->session->data['payment_method'])) {
            $data['logged'] = $this->customer->isLogged();
            $data['subscription'] = $this->cart->hasSubscription();

            $data['months'] = [];

            foreach (range(1, 12) as $month) {
                $data['months'][] = date('m', mktime(0, 0, 0, $month, 1));
            }

            $data['years'] = [];

            foreach (range(date('Y'), date('Y', strtotime('+10 year'))) as $year) {
                $data['years'][] = $year;
            }

            $data['language'] = $this->config->get('config_language');

            return $this->load->view('extension/oc_payment_easytransac/payment/easytransac', $data);
        }

        return '';
    }

    public function notification(): void
    {
        $requestData = $_POST; //$this->request->all();

        if (empty($requestData)) {
            return;
        }

        if (!isset($requestData['Status']) || !isset($requestData['Message'])) {
            return;
        }

        $notif = new Notification();
        $notif->hydrate(json_decode(json_encode($requestData)));
        $notification = $notif->toArray();

        if ('captured' !== $requestData['Status'] || 'La transaction a réussi' !== $requestData['Message']) {
            $this->load->model('checkout/order');
            $this->model_checkout_order->addHistory($notification['OrderId'], $this->config->get('payment_easytransac_failed_status_id'), '', true);
            return;
        }

        $this->load->model('checkout/order');
        $this->model_checkout_order->addHistory($notification['OrderId'], $this->config->get('payment_easytransac_approved_status_id'), '', true);


    }

    public function confirm()
    {
        $this->load->language('extension/oc_payment_easytransac/payment/easytransac');

        $json = [];

        if (isset($this->session->data['order_id'])) {
            $order_id = $this->session->data['order_id'];
        } else {
            $order_id = 0;
        }

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        /*
        if (!$order_info) {
            $json['error']['warning'] = $this->language->get('error_order');
        }
        */

        $et_api_live = $this->config->get('payment_easytransac_et_api_live');

        if (empty($et_api_live)) {
            $et_api_key = $this->config->get('payment_easytransac_et_api_key_test');
        } else {
            $et_api_key = $this->config->get('payment_easytransac_et_api_key');
        }


        if (empty($et_api_key)) {
            $json['error']['warning'] = $this->language->get('error_payment_et_api_key');
        }

        if (!$json) {
            /*
            *
            * Credit Card charge code goes here
            *
            */
            $this->load->model('extension/oc_payment_easytransac/payment/easytransac');
            //$response = $this->model_extension_oc_payment_easytransac_payment_easytransac->charge($this->customer->getId(), $this->session->data['order_id'], $order_info['total'], 0);

            $montant = $order_info['total'];
            $montant = (float)$montant * 100;

            Services::getInstance()->setDebug(false);
            Services::getInstance()->provideAPIKey($et_api_key);

            // Création d'un token de retour
            try {
                $randomBytes = random_bytes(32);
            } catch (Exception $e) {
                $randomBytes = 'ultra$ecureramdOmpassphrase!23#_';
            }
            $accessToken = rtrim(strtr(base64_encode($randomBytes), '+/', '-_'), '=');

            // Set customer info
            $customer = (new Customer())
                ->setEmail($this->customer->getEmail())
                ->setLastname($this->customer->getLastname())
                ->setFirstname($this->customer->getFirstname());
            $returnUrl = 'https://opencart.inevents.fr/index.php';

            // Set easytransac config
            $transaction = (new PaymentPageTransaction())
                ->setAmount($montant)
                ->setClientIP($_SERVER['SERVER_ADDR'])
                ->setCustomer($customer)
                //->setLanguage($language)
                ->setOrderId($order_id)
                ->setOperationType('payment')
                ->setReturnUrl($returnUrl . '?route=checkout/success&access_token=' . $accessToken)
                ->setCancelUrl($returnUrl . '?route=checkout/failure&access_token=' . $accessToken)
                ->setDownPayment($returnUrl . '?access_token=' . $accessToken)
                ->setDescription('Paiement de ' . $montant . '€');

            $this->load->model('checkout/order');
            $this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_easytransac_order_status_id'), '', true);

            // Call payment page
            $paymentPage = new PaymentPage();
            $res = $paymentPage->execute($transaction);


            $result = [];

            if ($res->isSuccess()) {
                $result = $res->getContent()->toArray();
                return $this->response->redirect($result['PageUrl']);
            } else {
                $error = $response->getErrorMessage();
            }

        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

}
