<?php

namespace Kohana\Routing;

use Kohana\View\View;

/**
 * Abstract controller class for automatic templating.
 *
 * @package    Kohana
 * @category   Controller
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    https://kohana.top/license
 */
abstract class TemplateController extends Controller
{
    /**
     * @var  View  page template
     */
    public $template = 'template';

    /**
     * @var bool auto render template
     * */
    public $autoRender = true;

    /**
     * Loads the template [View] object.
     */
    public function before()
    {
        parent::before();

        if ($this->autoRender === true) {
            // Load the template
            $this->template = View::factory($this->template);
        }
    }

    /**
     * Assigns the template [View] as the request response.
     */
    public function after()
    {
        if ($this->autoRender === true) {
            $this->response->body($this->template->render());
        }

        parent::after();
    }

}
