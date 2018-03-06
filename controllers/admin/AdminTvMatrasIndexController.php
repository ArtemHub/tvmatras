<?php

require_once (_PS_MODULE_DIR_ . 'tvmatras/classes/Exporter.php');

class AdminTvMatrasIndexController extends ModuleAdminController
{
    private $submitValue = 'submitImportPrice';
    private $basePath = _PS_MODULE_DIR_ . 'tvmatras/';

    private $step_limit = 10;

    public function init()
    {
        parent::init();
        if($hash = Tools::getValue('download')) {
            $this->outputFile($hash);
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

    public function outputFile($hash)
    {
        $file = base64_decode($hash);
        $filename = date('Ymd', time());

        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename={$filename}.csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        readfile($file);
    }

    public function ajaxProcessExportPrice()
    {
        $path = _PS_MODULE_DIR_ . 'tvmatras/export/' . md5(time()) . '.csv';
        Exporter::Export();

        $file = fopen($path, 'w');

        fputcsv($file, array(
            'Код товара',
            'Название',
            'Цена',
            'Производитель',
            'Ссылка на товар',
        ), ';');

        foreach (Exporter::$products as $product) {
            fputcsv($file, array(
                (string)$product['sku'],
                $product['name'] . ((!empty($product['attr_name'])) ? ' ' . $product['attr_name'] : ''),
                (int)$product['price'],
                $product['manufacturer'],
                $product['link'],
            ), ';');
        }

        fclose($file);

        $response['success'] = true;
        $response['url'] = $this->makeDownloadFileUrl($path);

        $this->sendResponse($response);
    }

    public function ajaxProcessUploadFilePrice()
    {
        $response = array(
            'success' => false,
            'error' => '',
            'data' => array()
        );

        if(!isset($_FILES) || !isset($_FILES['price'])) {
            $response['error'] = 'Error on file upload: Import file not found.';
            $this->sendResponse($response);
        }
        else if($_FILES['price']['type'] != 'text/csv') {
            $response['error'] = 'Error on file upload: File must be a CSV.';
            $this->sendResponse($response);
        }

        $file = $this->basePath . 'tmp/' . md5(time()) . '.csv';
        if(!move_uploaded_file($_FILES['price']['tmp_name'], $file)) {
            $response['error'] = 'Error on file upload: Possible file upload attack.';
            $this->sendResponse($response);
        }

        $total = $this->getRecordsCount($file);

        $response['data'] = array(
            'total' => $total,
            'file' => $file,
            'stepLimit' => $this->step_limit,
            'steps' => ceil($total/$this->step_limit),
            'step' => 0
        );
        $response['success'] = true;
        $this->sendResponse($response);
    }

    public function ajaxProcessImportPrice()
    {
        $response = array(
            'success' => true,
            'error' => '',
            'data' => array(),
            'log' => array()
        );

        $total = (int) $_POST['total'];
        $step = (int)$_POST['step'];
        $file = $_POST['file'];

        $response['log'] = $this->readFile($file, $step);

        $response['data'] = array(
            'total' => $total,
            'file' => $file,
            'stepLimit' => $this->step_limit,
            'steps' => ceil($total/$this->step_limit),
            'step' => $step
        );

        $this->sendResponse($response);
    }

    public function ajaxProcessClean()
    {
        $this->clearDir($dir = $this->basePath . 'tmp/');

        $response['success'] = true;
        $path = $this->basePath . 'log/' . basename($_POST['file']);
        $response['url'] = $this->context->link->getAdminLink('AdminTvMatrasIndex', true);
        $response['url'].= '&download='.base64_encode($path);
        $this->sendResponse($response);
    }

    private function readFile($path, $step)
    {
        $result = array(
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
                    ++$result['pass'];
                }
                else {
                    ++$result['fail'];
                    $result['sku'][] = 'Line: ' . $i . ' | SKU^' . $data[0];
                }
                $this->writeLog($path, $data, $success);
            }

            ++$i;
            usleep(100000);
        }

        $result['total'] = $i - $start;

        return $result;
    }

    private function setProductPrice($sku, $price)
    {
        if($data = $this->findProductAttributeBySku($sku)) {
            $product_price = Product::getPriceStatic($data['id_product'], false, false);

            if($product_price !== 0) {
                if($price < $product_price) {
                    $price = $product_price - $price;
                }
                else {
                    $price = $price - $product_price;
                }
            }

            $combination = new Combination($data['id_product_attribute']);
            $combination->price = (float) $price;

            if(!$combination->save()) {
                return false;
            }
        }
        else if($data = $this->findProductBySku($sku)) {
            if(!Db::getInstance()->update('product_shop', array('price' => (float) $price), 'id_product='.$data['id_product'])) {
                return false;
            }
        }
        else {
            return false;
        }

        return $price;
    }

    private function findProductBySku($sku)
    {
        $sql = 'SELECT `id_product` FROM `ps_product` WHERE `reference`="' . $sku .'"';

        if (!$row = Db::getInstance()->getRow($sql)) {
            return false;
        }

        return $row;
    }
    private function findProductAttributeBySku($sku)
    {
        $sql = 'SELECT `id_product`, `id_product_attribute` FROM `ps_product_attribute` WHERE `reference`="' . $sku .'"';

        if (!$row = Db::getInstance()->getRow($sql)) {
            return false;
        }

        return $row;
    }
    
    private function writeLog($path, $data, $success)
    {
        $status = ($success) ? 'ОК' : 'Ошибка';
        $string = iconv('UTF-8', 'WINDOWS-1251', $data[0] . ';' . $status . ';' . $success . "\r\n");

        $file = fopen($this->basePath . 'log/' . basename($path), 'a+');
        fwrite($file, $string);
        fclose($file);
    }

    private function getRecordsCount($file)
    {
        $counter = 0;
        if (($handle = fopen($file, "r")) !== false) {
            while (($data = fgetcsv($handle, null, ';')) !== false) {
                $counter++;
            }
            fclose($handle);
        }

        --$counter; // remove first (title) row
        return $counter;
    }

    private function sendResponse($response) {
        echo json_encode($response);
        exit();
    }

    private function clearDir($dir) {
        $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ( $ri as $file ) {
            $file->isDir() ?  rmdir($file) : unlink($file);
        }
    }

    private function makeDownloadFileUrl($path)
    {
        $url = $this->context->link->getAdminLink('AdminTvMatrasIndex', true);
        $url.= '&download='.base64_encode($path);

        return $url;
    }
}