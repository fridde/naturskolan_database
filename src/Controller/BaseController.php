<?php


namespace Fridde\Controller;

use Fridde\HTML;

class BaseController
{

    /** @var \Fridde\Naturskolan The Naturskolan object obtained from the global container */
    protected $N;
    /* @var array $params */
    protected $params;
    /** @var \Fridde\HTML A Html object to build the page */
    protected $H;

    public function __construct(array $params = [])
    {
        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
        $this->H = new HTML();
        $this->params = $params;
    }

    protected function standardRender(array $options = [])
    {
        $js_key = $options["js"] ?? 'index';
        $css_key = $options["css"] ?? 'index';
        $template = $options["template"] ?? 'index';

        $this->H->addDefaultJs($js_key)->addDefaultCss($css_key)
            ->setTemplate($template)->setBase();

        $this->H->addNav();
        if(!empty($options["DATA"])){
            $this->H->addVariable("DATA", $options["DATA"]);
        }
        $this->H->render();
        return $this->H;
    }

}