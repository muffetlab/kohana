<?php

namespace App\Http\Controllers;

use Kohana\Routing\Controller;
use Kohana\Support\Arr;

class RequestClientDummyController extends Controller
{
    /**
     * Takes a urlencoded 'data' parameter from the route and uses it to craft a
     * response. Redirect chains can be tested by passing another encoded URI
     * as a location header with an appropriate status code.
     */
    public function fake()
    {
        parse_str(urldecode($this->request->param('data')), $data);
        $this->response->status(Arr::get($data, 'status', 200));
        $this->response->headers(Arr::get($data, 'header', []));
        $this->response->body(json_encode([
            'body' => Arr::get($data, 'body', 'ok'),
            'rq_headers' => $this->request->headers(),
            'rq_body' => $this->request->body(),
            'rq_method' => $this->request->method(),
        ]));
    }

}
