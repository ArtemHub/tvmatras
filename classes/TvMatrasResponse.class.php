<?php

class TvMatrasResponse {

    private $success;
    private $data;
    private $msg;
    private $log;
    private $url;

    protected $result = array();

    public function __construct($success = false)
    {
        $this->success = $success;
    }

    /**
     * @param bool $success
     */
    public function setSuccess($success = true)
    {
        $this->success = $success;
    }

    /**
     * @param mixed $data
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * @param mixed $data
     */
    public function setLog($data) {
        $this->log = $data;
    }

    public function setMsg($message)
    {
        $this->msg = $message;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function render()
    {
        $this->result['success'] = $this->success;
        $this->result['data'] = $this->data;
        $this->result['msg'] = $this->msg;

        if(!empty($this->log)) {
            $this->result['log'] = $this->log;
        }
        if(!empty($this->url)) {
            $this->result['url'] = $this->url;
        }
    }

    public function output()
    {
        return json_encode($this->result);
    }

}