<?php

class TvMatrasResponse {

    private $success;
    private $data;

    protected $result = array();


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

    public function render()
    {
        $result['success'] = $this->success;
        $result['data'] = $this->data;
    }

    public function output()
    {
        return json_encode($this->result);
    }


}