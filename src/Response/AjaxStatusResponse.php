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
    public $success;

    public function __construct($data, $status = 200, $success = true, $errors = [])
    {
        $this->data = $data;
        $this->status = $status;
        $this->errors = $errors;
        $this->success = $success;
    }

    public function addError($message, $code = 0)
    {
        $this->errors[] = [
            'message' => $message,
            'code' => $code
        ];
    }
}
