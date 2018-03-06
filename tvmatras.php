<?php

if (!defined('_PS_VERSION_'))
    exit;

class TvMatras extends Module
{
    public function __construct()
    {
        $this->name = 'tvmatras';
        $this->version = '1.0.0';
        $this->author = 'Artem Hubenko';
        $this->tab = 'administration';
        $this->secure_key = Tools::encrypt($this->name);

        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Tviy Matras');
        $this->description = $this->l('Tviy Matras Descriptions');

        $this->confirmUnonstall = $this->l('Are you sure you want to uninstall this module?');
        $this->ps_version_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install() && $this->installModuleTab();
    }

    public function uninstall()
    {
        return $this->uninstallModuleTab() && parent::uninstall();
    }

    public function installModuleTab()
    {
        $tab = new Tab;
        $langs = Language::getLanguages();

        foreach ($langs as $lang) {
            $tab->name[$lang['id_lang']] = 'Tviy Matras';
        }

        $tab->module = $this->name;
        $tab->id_parent = 0;
        $tab->class_name = 'AdminTvMatrasIndex';

        return $tab->save();
    }

    public function uninstallModuleTab()
    {
        $id_tab = Tab::getidFromClassName('AdminTvMatrasIndex');

        if($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }

        return true;
    }
}