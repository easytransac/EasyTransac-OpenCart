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

namespace Opencart\Admin\Controller\Extension\OcPaymentEasytransac\Payment;

use Opencart\System\Engine\Controller;

class Easytransac extends Controller
{
    public function index(): void
    {
        $this->load->language('extension/oc_payment_easytransac/payment/easytransac');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment')
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/oc_payment_easytransac/payment/easytransac', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['save'] = $this->url->link('extension/oc_payment_easytransac/payment/easytransac.save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');

        //$data['payment_easytransac_response'] = $this->config->get('payment_easytransac_response');

        $data['payment_easytransac_approved_status_id'] = $this->config->get('payment_easytransac_approved_status_id');
        $data['payment_easytransac_failed_status_id'] = $this->config->get('payment_easytransac_failed_status_id');
        $data['payment_easytransac_order_status_id'] = $this->config->get('payment_easytransac_order_status_id');

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['payment_easytransac_geo_zone_id'] = $this->config->get('payment_easytransac_geo_zone_id');

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $data['payment_easytransac_status'] = $this->config->get('payment_easytransac_status');
        $data['payment_easytransac_sort_order'] = $this->config->get('payment_easytransac_sort_order');

        $data['payment_easytransac_et_api_key'] = $this->config->get('payment_easytransac_et_api_key');
        $data['payment_easytransac_et_api_key_test'] = $this->config->get('payment_easytransac_et_api_key_test');
        $data['payment_easytransac_et_api_live'] = $this->config->get('payment_easytransac_et_api_live');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['payment_easytransac_et_notification_url'] = 'https://' . $_SERVER['SERVER_NAME'] . '/index.php?route=extension/oc_payment_easytransac/payment/easytransac.notification';

        $this->response->setOutput($this->load->view('extension/oc_payment_easytransac/payment/easytransac', $data));
    }

    public function save(): void
    {
        $this->load->language('extension/oc_payment_easytransac/payment/easytransac');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/oc_payment_easytransac/payment/easytransac')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('setting/setting');

            $this->model_setting_setting->editSetting('payment_easytransac', $this->request->post);

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install(): void
    {
        if ($this->user->hasPermission('modify', 'extension/payment')) {
            $this->load->model('extension/oc_payment_easytransac/payment/easytransac');

            $this->model_extension_oc_payment_easytransac_payment_easytransac->install();
        }
    }

    public function uninstall(): void
    {
        if ($this->user->hasPermission('modify', 'extension/payment')) {
            $this->load->model('extension/oc_payment_easytransac/payment/easytransac');

            $this->model_extension_oc_payment_easytransac_payment_easytransac->uninstall();
        }
    }

}
