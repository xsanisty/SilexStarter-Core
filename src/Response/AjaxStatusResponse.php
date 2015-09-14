<?php

namespace SilexStarter\Response;

/**
 * Standarized ajax response
 */
class AjaxStatusResponse
{
    /**
     * Could be array, string or object
     * @var mixed
     */
    public $data;

    /**
     * Array of error message and/or error code
     * @var array
     */
    public $errors;

    /**
     * Valid http status message (with proper response code on main response object)
     * @var int
     */
    public $status;

    /**
     * Is operation sucessfull
     * @var boolean
     */
    public $success = true;

    public function __construct($data, $status = 200, $errors = [])
    {
        $this->data     = $data;
        $this->status   = $status;
        $this->errors   = $errors;
    }

    public function addError($message, $code = 0)
    {
        $this->errors[] = [
            'message' => $message,
            'code' => $code
        ];
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function __toString()
    {
        return json_encode(
            [
                'data'      => $this->data,
                'status'    => $this->status,
                'success'   => !$this->success,
                'errors'    => $this->errors
            ]
        );
    }
}
