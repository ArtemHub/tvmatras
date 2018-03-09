<?php

require_once (_PS_MODULE_DIR_ . 'tvmatras/classes/TvMatrasExporter.class.php');
require_once (_PS_MODULE_DIR_ . 'tvmatras/classes/TvMatrasResponse.class.php');
require_once (_PS_MODULE_DIR_ . 'tvmatras/classes/TvMatrasUtility.class.php');

class AdminTvMatrasIndexController extends ModuleAdminController
{
    /** @var TvMatrasResponse response */
    private $response;

    public $context;

    /** @var TvMatrasUtility response */
    private $utility;

    private $timeout = 100000;
    private $submitValue = 'submitImportPrice';

    private $basePath = _PS_MODULE_DIR_ . 'tvmatras/';
    private $logPath = _PS_MODULE_DIR_ . 'tvmatras/log/';
    private $exportPath = _PS_MODULE_DIR_ . 'tvmatras/export/';

    private $step_limit = 10;

    public function init()
    {
        parent::init();

        $this->response = new TvMatrasResponse();
        $this->utility = new TvMatrasUtility($this);

        if($hash = Tools::getValue('download')) {
            $this->utility->outputFile($hash);
            exit();
        }

        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();

        $this->addJS($this->basePath . 'js/temp.js');
        $this->addCSS($this->basePath . 'css/style.css');

        $smarty = $this->context->smarty;
        $smarty->assign(array(
            'output' => $this->renderForm()
        ));

        $content = $smarty->fetch($this->basePath . 'views/templates/admin/index.tpl');
        $this->context->smarty->assign(array('content' => $content));
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => 'Импорт',
                    'icon' => 'icon-upload'
                ),
                'input' => array(
                    array(
                        'type' => 'file',
                        'name' => 'uploadFilePrice',
                        'desc' => 'Загрузите .csv файл с прайсом',
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'uploadFileToken'
                    ),
                ),
                'submit' => array(
                    'title' => 'Загрузить',
                    'class' => 'btn btn-default pull-right')
            ),
        );

        $helper = new HelperForm();
        $helper->fields_value['uploadFileToken'] = Tools::getAdminTokenLite('AdminTvMatrasIndex');
        $helper->submit_action = $this->submitValue;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        return $helper->generateForm(array($fields_form));
    }

    public function ajaxProcessExportPrice()
    {
        TvMatrasExporter::Export();
        $products = TvMatrasExporter::$products;

        $path = $this->exportPath . md5(time()) . '.csv';
        $file = fopen($path, 'w');

        $title = $this->utility->convertToWin('Код товара;Название;Цена;Производитель;Ссылка на товар' . "\n\r");
        fwrite($file, $title);

        foreach ($products as $product) {
            $name = $product['name'] . ((!empty($product['attr_name'])) ? ' ' . $product['attr_name'] : '');

            fputcsv($file, array(
                (string)$product['sku'],
                $this->utility->convertToWin($name),
                (int)$product['price'],
                $this->utility->convertToWin($product['manufacturer']),
                $product['link'],
            ), ';');
        }

        fclose($file);

        $this->response->setSuccess(true);
        $this->response->setUrl($this->utility->makeDownloadFileUrl($path));
        $this->sendResponse();
    }

    public function ajaxProcessUploadFilePrice()
    {
        if(!isset($_FILES) || !isset($_FILES['price'])) {
            $this->response->setMsg('Error on file upload: Import file not found.');
            $this->sendResponse();
        }
        else if($_FILES['price']['type'] != 'text/csv') {
            $this->response->setMsg('Error on file upload: File must be a CSV.');
            $this->sendResponse();
        }

        $path = $this->basePath . 'tmp/' . md5(time()) . '.csv';
        if(!move_uploaded_file($_FILES['price']['tmp_name'], $path)) {
            $this->response->setMsg('Error on file upload: Possible file upload attack.');
            $this->sendResponse();
        }

        $total = $this->utility->getLinesCount($path) - 1; // remover title line from count

        $this->response->setSuccess(true);
        $this->response->setData(array(
            'total' => $total,
            'file' => $path,
            'stepLimit' => $this->step_limit,
            'steps' => ceil($total/$this->step_limit),
            'step' => 0
        ));
        $this->sendResponse();
    }

    public function ajaxProcessImportPrice()
    {
        $total = (int) $_POST['total'];
        $step = (int) $_POST['step'];
        $file = $_POST['file'];

        $this->response->setSuccess(true);
        $this->response->setLog($this->readFile($file, $step));
        $this->response->setData(array(
            'total' => $total,
            'file' => $file,
            'stepLimit' => $this->step_limit,
            'steps' => ceil($total/$this->step_limit),
            'step' => $step
        ));

        $this->sendResponse();
    }

    public function ajaxProcessClean()
    {
        $this->utility->cleanDir($dir = $this->basePath . 'tmp/');

        $path = $this->logPath . basename($_POST['file']);
        $url = $this->utility->makeDownloadFileUrl($path);
        
        $this->response->setSuccess(true);
        $this->response->setUrl($url);

        $this->sendResponse();
    }

    private function readFile($path, $step)
    {
        $log = array(
            'total' => 0,
            'fail' => 0,
            'pass' => 0,
            'sku' => array()
        );

        $file = new SplFileObject($path);

        if($step == 1) {
            $start = $i = 1;
            $end = $this->step_limit;
        }
        else {
            $start = $i = (($step - 1) * $this->step_limit) + 1;
            $end = $start + $this->step_limit - 1;
        }

        while (!$file->eof() && ($i <= $end)) {
            $file->seek($i);
            $data = explode(';', $file->current());

            if(!empty($data[0])) {
                if($success = $this->setProductPrice($data[0], $data[2])) {
                    ++$log['pass'];
                }
                else {
                    ++$log['fail'];
                    $log['sku'][] = 'Line: ' . $i . ' | SKU^' . $data[0];
                }
                $this->writeLog($path, $data, $success);
            }

            ++$i;
            usleep($this->timeout);
        }

        $log['total'] = $i - $start;

        return $log;
    }

    private function setProductPrice($sku, $price)
    {
        if($id = $this->utility->findProductAttributeIdBySku($sku)) {
            $combination = new Combination($id);
            $product_price = Product::getPriceStatic($combination->id_product, false, false);

            if($product_price !== 0) {
                if($price < $product_price) {
                    $price = $product_price - $price;
                }
                else {
                    $price = $price - $product_price;
                }
            }

            $combination->price = (float) $price;

            if(!$combination->save()) {
                return false;
            }
        }
        else if($id = $this->utility->findProductIdBySku($sku)) {
            if(!Db::getInstance()->update('product_shop', array('price' => (float) $price), 'id_product=' . $id)) {
                return false;
            }
        }
        else {
            return false;
        }

        return $price;
    }


    private function writeLog($path, $data, $success)
    {
        $status = ($success) ? 'ОК' : 'Ошибка';
        $string = $data[0] . ';' . $status . ';' . $success . "\r\n";
        $string = $this->utility->convertToWin($string);

        $this->utility->writeToFile($this->logPath . basename($path), $string);
    }

    private function sendResponse() {
        $this->response->render();
        echo $this->response->output();
        exit();
    }

}