## Master Slate Product Option 

Here are some solutions that come to mind

## 1. Use export/import to excel. 
You can use a module that exports all the products or filtered product to an excel file where you can easily update the values and upload back in.

[Export/Import Pro][1]

## 2. Use a batch product editor module  
Use a module that basically does the same as the export/import only via the admin interface.

[Batch product editor][2]

## 3. Use a master product to update slave products
This is the option you wrote in your question. Basically create a master product that when updated, updates the other products. I made a module for you called ms_product_option (master slave product option)

here are the steps required.
1. add controller with path `admin/controller/extension/module/ms_product_option.php` .

```php
<?php
class ControllerExtensionModuleMSProductOption extends Controller {
    private $error = array();
    private $codename = 'ms_product_option';

    public function index() {
        $this->load->language('extension/module/ms_product_option');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');


        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_ms_product_option', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->load->model('setting/event');
            $this->model_setting_event->deleteEventByCode($this->codename);

            if($this->request->post['module_ms_product_option_status']){
                $this->model_setting_event->addEvent($this->codename, 'admin/model/catalog/product/editProduct/after', 'extension/module/ms_product_option/model_catalog_product_editProduct_after');
                $this->model_setting_event->addEvent($this->codename, 'admin/view/catalog/product_form/before', 'extension/module/ms_product_option/view_catalog_product_form_before');
            }

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/ms_product_option', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/ms_product_option', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        if (isset($this->request->post['module_ms_product_option_status'])) {
            $data['module_ms_product_option_status'] = $this->request->post['module_ms_product_option_status'];
        } else {
            $data['module_ms_product_option_status'] = $this->config->get('module_ms_product_option_status');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/ms_product_option', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/ms_product_option')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function install(){
        $this->load->model('extension/module/ms_product_option');
        $this->model_extension_module_ms_product_option->installDatabase();
    }

    public function uninstall(){
        $this->load->model('extension/module/ms_product_option');
        $this->model_extension_module_ms_product_option->deleteDatabase();

        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode($this->codename);
    }

    //OC event to trigger update of slave products
    public function model_catalog_product_editProduct_after(&$route, &$data, &$output){
        $this->load->model('extension/module/ms_product_option');
        //check if master product
        if($this->model_extension_module_ms_product_option->isProductMaster($data[0])){
            $master_product_id = $data[0];
            //trigger update of slaves

            $this->model_extension_module_ms_product_option->updateProductsOptionsFromProductMaster($master_product_id);
        }else{
            $product_id = $data[0];
            $this->model_extension_module_ms_product_option->deleteProductFromProductMaster($product_id);
            if(!empty($data[1]['master_product_id'])){
                $master_product_id = $data[1]['master_product_id'];

                $this->model_extension_module_ms_product_option->addProductToProductMaster($product_id, $master_product_id);
            }
        }

        
    }
    public function view_catalog_product_form_before(&$route, &$data){
        if($data && isset($this->request->get['product_id'])){
            $product_id = $this->request->get['product_id'];
            $this->load->model('extension/module/ms_product_option');

            $product_info = $this->model_extension_module_ms_product_option->getMasterProductOfProduct($product_id);

            if($product_info){
                $data['master_product_id'] = $product_info['master_product_id'];
            }
        }
    }
    
}


```



2. add model with path `admin/model/extension/module/ms_product_option.php`

```php
<?php
class ModelExtensionModuleMSProductOption extends Model {

    public function installDatabase() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ms_product_option` (
          `product_id` int(11) NOT NULL,
          `master_product_id` int(11) NOT NULL,
          PRIMARY KEY (`product_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
    }

    public function deleteDatabase() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ms_product_option`");

    }

    public function isProductMaster($master_product_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ms_product_option` WHERE master_product_id = '" . (int)$master_product_id . "'");
        if($query->rows){
            return true;
        }else{
            return false;
        }
    }

    public function addProductToProductMaster($product_id, $master_product_id) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "ms_product_option` SET product_id = '" . (int)$product_id . "', master_product_id = '" . (int)$master_product_id . "'");
    }

    public function deleteProductFromProductMaster($product_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "ms_product_option WHERE product_id = '" . (int)$product_id . "'");
    }

    public function getProductsOfProductMaster($master_product_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ms_product_option` msp2p LEFT JOIN " . DB_PREFIX . "product_description pd ON (msp2p.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND msp2p.master_product_id = '".(int) $master_product_id. "'");
        return $query->rows;
    }

    public function getMasterProductOfProduct($product_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ms_product_option` WHERE product_id = '".(int) $product_id. "'");
        return $query->row;
    }

    public function updateProductsOptionsFromProductMaster($master_product_id) {
        //get product master options
        $this->load->model('catalog/product');
        $data['product_option'] = $this->model_catalog_product->getProductOptions($master_product_id);

        //get list of products connected to product master
        $products = $this->getProductsOfProductMaster($master_product_id);

        //update options
        foreach($products as $product){
            $product_id = $product['product_id'];
            if (isset($data['product_option'])) {
                foreach ($data['product_option'] as $product_option) {
                    if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                        if (isset($product_option['product_option_value'])) {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

                            $product_option_id = $this->db->getLastId();

                            foreach ($product_option['product_option_value'] as $product_option_value) {
                                $this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
                            }
                        }
                    } else {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
                    }
                }
            }
        }
    }
}
```

3. add language file with path `admin/language/en-gb/extension/module/ms_product_option.php`

```php
<?php
// Heading
$_['heading_title']    = '<span style="color:#449DD0; font-weight:bold">MS Product Option</span><span style="font-size:12px; color:#999"> by <a href="http://www.opencart.com/index.php?route=extension/extension&filter_username=Dreamvention" style="font-size:1em; color:#999" target="_blank">Dreamvention</a></span>';

// Text
$_['text_extension']   = 'Extensions';
$_['text_success']     = 'Success: You have modified MS Product Option module!';
$_['text_edit']        = 'Edit MS Product Option Module';

// Entry
$_['entry_status']     = 'Status';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify MS Product Option module!';
```
4. add twig file with path `admin/view/template/extension/module/ms_product_option.twig`

```twig
{{ header }}{{ column_left }}
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-module" data-toggle="tooltip" title="{{ button_save }}" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ cancel }}" data-toggle="tooltip" title="{{ button_cancel }}" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1>{{ heading_title }}</h1>
      <ul class="breadcrumb">
        {% for breadcrumb in breadcrumbs %}
        <li><a href="{{ breadcrumb.href }}">{{ breadcrumb.text }}</a></li>
        {% endfor %}
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    {% if error_warning %}
    <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> {{ error_warning }}
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    {% endif %}
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> {{ text_edit }}</h3>
      </div>
      <div class="panel-body">
        <form action="{{ action }}" method="post" enctype="multipart/form-data" id="form-module" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-status">{{ entry_status }}</label>
            <div class="col-sm-10">
              <select name="module_ms_product_option_status" id="input-status" class="form-control">
                {% if module_ms_product_option_status %}
                <option value="1" selected="selected">{{ text_enabled }}</option>
                <option value="0">{{ text_disabled }}</option>
                {% else %}
                <option value="1">{{ text_enabled }}</option>
                <option value="0" selected="selected">{{ text_disabled }}</option>
                {% endif %}
              </select>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
{{ footer }}
```

5. add ocmod file with path `system/ms_product_option.ocmod.xml`

```xml
<?xml version="1.0" encoding="utf-8"?>
<modification>
    <name>ms_product_option</name>
    <code>ms_product_option</code>
    <description>MS Product Option</description>
    <version>1.0.0</version>
    <author>Dreamvention</author>
    <link>http://dreamvention.ee</link>
    <file path="admin/view/template/catalog/product_form.twig">
        <operation error="skip">
            <search><![CDATA[<div class="tab-pane" id="tab-option">]]></search>
            <add position="after"><![CDATA[
            <!-- //ms_product_option.xml 1 -->
            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-model">Master Product</label>
                <div class="col-sm-10">
                  <input type="text" name="master_product_id" value="{{ master_product_id }}" placeholder="master product id" id="input-model" class="form-control" />
                  </div>
              </div>
            ]]></add>
        </operation>
    </file>
</modification>
```

6. (optional) add a shopunity json file if you are using https://shopunity.net 

```json
{
    "codename": "ms_product_option",
    "version": "1.0.0",
    "name": "MS Product Option",
    "description": "Create Master products and connect slave products. When updating options in saster product, you will automatically update slave products.",
    "index": "extension/d_shopunity",
    "author": {
        "name": "Dreamvention",
        "email": "info@dreamvention.com",
        "url": "https://dreamvention.ee/"
    },
    "opencart_version": [
        "3.0.0.0",
        "3.0.1.1",
        "3.0.1.2",
        "3.0.2.0"
    ],
    "type": "module",
    "license": {
        "type": "free",
        "url": "https://shopunity.net/licenses/free"
    },
    "install": {
        "url": "extension/module/install&extension=ms_product_option",
        "xml": "system/library/d_shopunity/install/ms_product_option.xml"
    },
    "uninstall": {
        "url": "extension/module/uninstall&extension=ms_product_option"
    },
    "support": {
        "email": "support@dreamvention.com",
        "url": "https://dreamvention.ee/support"
    },
    "required":{
        "d_opencart_patch": ">=1.0.0"
    },
    "files": [
        "system/library/d_shopunity/extension/ms_product_option.json",

        "admin/controller/extension/module/ms_product_option.php",
        "admin/model/extension/module/ms_product_option.php",
        "admin/language/en-gb/extension/module/ms_product_option.php",
        "admin/view/template/extension/module/ms_product_option.twig",

        "system/library/d_shopunity/install/ms_product_option.xml"
    ],
    "changelog": [
        {
            "version":"1.0.0",
            "change": "Initial commit"
        }
    ]
}
```

  [1]: https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=31314
  [2]: https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=32157
