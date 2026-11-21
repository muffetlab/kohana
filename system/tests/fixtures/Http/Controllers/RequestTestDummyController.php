<?php

namespace App\Http\Controllers;

use Kohana\Routing\Controller;
use Kohana\Support\Arr;

class RequestTestDummyController extends Controller
{
    // hard coded dummy response
    protected $dummy_response = "this is a dummy response";

    public function action_foobar()
    {
        $this->response->body($this->dummy_response);
    }

    public function get_expected_response()
    {
        return $this->dummy_response;
    }

}

