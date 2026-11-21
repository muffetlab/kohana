<?php

namespace App\Http\Controllers;

use Kohana\Routing\Controller;

class WelcomeController extends Controller
{
    public function index()
    {
        $this->response->body('hello, world!');
    }

}
