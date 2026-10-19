<?php

namespace App\Controller;

use Kohana\Controller;

class Welcome extends Controller
{
    public function action_index()
    {
        $this->response->body('hello, world!');
    }

}
