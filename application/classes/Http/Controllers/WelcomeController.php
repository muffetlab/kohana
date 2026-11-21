<?php

namespace App\Http\Controllers;

use Kohana\Routing\Controller;

class WelcomeController extends Controller
{
    public function action_index()
    {
        $this->response->body('hello, world!');
    }

}
