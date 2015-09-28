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
    public $content;

    /**
     * Array of error message and/or error code
     * @var array
     */
    public $errors;

    /**
     * Valid http status code (with proper response code on main response object)
     * @var int
     */
    public $status;

    /**
     * Is operation sucessfull
     * @var boolean
     */
    public $success = true;

    public function __construct($content, $status = 200, array $errors = [])
    {
        $this->content  = $content;
        $this->status   = $status;
        $this->errors   = $errors;
        $this->success  = !$this->errors && $this->status >= 400;
    }

    public function addError($message, $code = 0)
    {
        $this->errors[] = [
            'message'   => $message,
            'code'      => $code
        ];
    }

    public function setData($content)
    {
        $this->content = $content;
    }

    public function __toString()
    {
        return json_encode(
            [
                'content'   => $this->content,
                'status'    => $this->status,
                'success'   => $this->success,
                'errors'    => $this->errors
            ]
        );
    }
}
