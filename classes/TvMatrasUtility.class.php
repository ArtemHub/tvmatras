<?php

class TvMatrasUtility
{
    private $c;

    /**
     * TvMatrasUtility constructor.
     * @param ModuleAdminController $controller
     */
    public function __construct(&$controller)
    {
        $this->c = $controller;
    }

    /**
     * @param string $path
     * @return string
     */
    public function makeDownloadFileUrl($path)
    {
        $url = $this->c->context->link->getAdminLink('AdminTvMatrasIndex', true);
        $url.= '&download='.base64_encode($path);

        return $url;
    }

    /**
     * @param string $str
     * @return string
     */
    public function convertToWin($str)
    {
        return iconv('UTF-8', 'WINDOWS-1251', $str);
    }

    /**
     * @param string $str
     * @return string
     */
    public function convertToUTF($str)
    {
        return iconv('WINDOWS-1251', 'UTF-8', $str);
    }

    /**
     * @param string $hash path to file encoded with MIME base64
     */
    public function outputFile($hash)
    {
        $path = base64_decode($hash);
        $pathinfo = pathinfo($path);
        $dir = explode('/', $pathinfo['dirname']);
        $dir = $dir[sizeof($dir )-1];
        $filename = $dir . '_' . date('Ymd', time()) . '.' . $pathinfo['extension'];

        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename={$filename}");
        header("Pragma: no-cache");
        header("Expires: 0");

        readfile($path);
    }

    /**
     * @param string $dir path to directory
     */
    public function cleanDir($dir) {
        $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ( $ri as $file ) {
            $file->isDir() ?  rmdir($file) : unlink($file);
        }
    }


    /**
     * @param string $path
     * @param string $string
     */
    public function writeToFile($path, $string)
    {
        $file = fopen($path, 'a+');
        fwrite($file, $string);
        fclose($file);
    }

    /**
     * @param string $path
     * @return int
     */
    public function getLinesCount($path)
    {
        $counter = 0;
        if (($handle = fopen($path, "r")) !== false) {
            while (($data = fgetcsv($handle, null, ';')) !== false) {
                $counter++;
            }
            fclose($handle);
        }

        return $counter;
    }

    /**
     * @param string $sku
     * @return int|bool
     */
    public function findProductIdBySku($sku)
    {
        $sql = 'SELECT `id_product` FROM `ps_product` WHERE `reference`="' . $sku .'"';

        if (!$row = Db::getInstance()->getRow($sql)) {
            return false;
        }

        return $row['id_product'];
    }

    /**
     * @param string $sku
     * @return int|bool
     */
    public function findProductAttributeIdBySku($sku)
    {
        $sql = 'SELECT `id_product_attribute` FROM `ps_product_attribute` WHERE `reference`="' . $sku .'"';

        if (!$row = Db::getInstance()->getRow($sql)) {
            return false;
        }

        return $row['id_product_attribute'];
    }
}