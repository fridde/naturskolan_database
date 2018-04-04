<?php


namespace Fridde\Controller;

use Fridde\HTML;
use Fridde\TwigExtension\NavigationExtension;
use Fridde\Utility;

class BaseController
{

    /** @var \Fridde\Naturskolan The Naturskolan object obtained from the global container */
    protected $N;
    /* @var array $params */
    protected $params;
    /* @var array $action */
    protected $actions;
    /* @var array $REQ */
    protected $REQ;
    /* @var string $return_type */
    protected $return_type = 'html';
    /** @var \Fridde\HTML A Html object to build the page */
    protected $H;
    protected $DATA = [];
    protected $TWIG_Variables = [];
    protected $title;
    protected $defaultJs = 'index';
    protected $defaultCss = 'index';
    protected $js = [];
    protected $css = [];
    protected $template;


    public function __construct(array $params = [], $slim = false)
    {
        $this->slimConstruct($params);
        if ($slim) {
            return ;
        }
        $args = [$this->N->Auth];
        $args[] = $GLOBALS['CONTAINER']->get('Router');
        $args[] = $this->N->ORM;
        $extension = new NavigationExtension(...$args);
        $this->H = new HTML(null, [$extension]);
        $this->setTitle(SETTINGS['defaults']['title'] ?? null);
        $this->addAction($this->getParameter('action'));
    }

    public function slimConstruct(array $params = [])
    {
        $this->N = $GLOBALS['CONTAINER']->get('Naturskolan');
        $this->REQ = $_REQUEST ?? [];
        $this->params = $params;
        $this->addAction($this->getParameter('action'));
    }


    public function handleRequest()
    {
        $actions = $this->getActions();
        $param_string = $this->getParameter('parameters');
        $params = empty($param_string) ? [] : explode('/', $param_string);
        foreach ($actions as $action) {
            $method = $this->translateActionToMethod($action);
            call_user_func_array([$this, $method], $params);
        }

        if ($this->getReturnType() === 'html') {
            return $this->renderAsHtml();
        } elseif ($this->getReturnType() === 'json') {
            $this->returnAsJson();
        } else {
            throw new \Exception('The return type '.$this->getReturnType().' is not defined.');
        }
    }

    public function returnAsJson()
    {
        header('Content-Type: application/json');
        echo json_encode($this->DATA);
    }

    public function renderAsHtml()
    {
        if (empty($this->getTemplate())) {
            $this->setTemplate('error');
            $this->addToDATA('url', implode('/', $this->getParameter()));
        }

        $this->H->setTitle($this->getTitle());
        $this->H->addDefaultJs($this->getDefaultJs())
            ->addDefaultCss($this->getDefaultCss())
            ->addDefaultFonts()
            ->addJS($this->getJs())->addCss($this->getCss())
            ->setTemplate($this->getTemplate())->setBase();

        $this->addAllVariablesToTemplate();

        return $this->H->render();
    }

    public function addAllVariablesToTemplate()
    {
        if (!empty($this->DATA)) {
            $this->addAsVar('DATA', $this->DATA);
        }
        foreach ($this->TWIG_Variables as $name => $value) {
            $this->H->addVariable($name, $value);
        }
    }

    /**
     * Renders HTML using default options. If options are specified, the default values are overridden.
     *
     * @param array $options An array containing values that override the default values.
     *              Possible keys are **js, css, template, DATA**
     * @return HTML
     */
    protected function standardRender(array $options = [])
    {
        $js_key = $options['js'] ?? 'index';
        $css_key = $options['css'] ?? 'index';
        $template = $options['template'] ?? 'index';

        $this->H->addDefaultJs($js_key)->addDefaultCss($css_key)
            ->setTemplate($template)->setBase();

        if (!empty($options['DATA'])) {
            $this->H->addVariable('DATA', $options['DATA']);
        }
        $this->H->render();

        return $this->H;
    }


    /**
     * @return mixed
     */
    public function getDATA()
    {
        return $this->DATA;
    }

    /**
     * @param mixed $DATA
     */
    public function setDATA($DATA)
    {
        $this->DATA = $DATA;
    }

    public function addToDATA($key_or_array, ...$args)
    {
        if (is_string($key_or_array)) {
            $array = [$key_or_array => $args[0]];
            $overwrite = $args[1] ?? false;
        } else {
            $array = $key_or_array;
            $overwrite = $args[0] ?? false;
        }

        if ($overwrite) {
            $this->setDATA(array_merge($this->getDATA(), $array));

            return null;
        }

        $this->setDATA($this->getDATA() + $array);
    }

    public function setReturnType(string $return_type = 'html')
    {
        $this->return_type = $return_type;
    }

    public function getReturnType()
    {
        return $this->return_type;
    }

    /**
     * @return array
     */
    public function getTWIGVariables(): array
    {
        return $this->TWIG_Variables;
    }

    /**
     * @param array $TWIG_Variables
     */
    public function setTWIGVariables(array $TWIG_Variables)
    {
        $this->TWIG_Variables = $TWIG_Variables;
    }

    public function addAsVar($key_or_array, $value = null)
    {
        $variables = $this->getTWIGVariables() ?? [];
        if (is_array($key_or_array)) {
            $variables = array_merge($variables, $key_or_array);
        } else {
            $variables[$key_or_array] = $value;
        }

        $this->setTWIGVariables($variables);
    }


    /**
     * @return null|string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param null|string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return array
     */
    public function getJs(): array
    {
        return $this->js;
    }

    /**
     * @param array $js
     */
    public function setJs($js)
    {
        $this->js = $js;
    }

    public function addJs($js, $type = HTML::INC_ABBREVIATION)
    {
        $this->js[] = [$js, $type];
    }

    /**
     * @return array
     */
    public function getCss(): array
    {
        return $this->css;
    }

    /**
     * @param array $css
     */
    public function setCss(array $css)
    {
        $this->css = $css;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate(string $template)
    {
        $this->template = $template;
    }

    public function getParameter($key = null)
    {
        return (empty($key) ? $this->params : ($this->params[$key] ?? null));

    }

    public function setParameter($key, $value = null)
    {
        $this->params[$key] = $value;
    }

    public function hasParameter(string $key = null)
    {
        if(empty($key)){
            return !empty($this->params);
        }
        return (null !== $this->getParameter($key));
    }

    /**
     * @return string
     */
    public function getDefaultJs(): string
    {
        return $this->defaultJs;
    }

    /**
     * @param string $defaultJs
     */
    public function setDefaultJs(string $defaultJs)
    {
        $this->defaultJs = $defaultJs;
    }

    /**
     * @return string
     */
    public function getDefaultCss(): string
    {
        return $this->defaultCss;
    }

    /**
     * @param string $defaultCss
     */
    public function setDefaultCss(string $defaultCss)
    {
        $this->defaultCss = $defaultCss;
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->actions ?? [];
    }

    /**
     * @param string $action
     */
    public function setActions(array $actions)
    {
        $this->actions = $actions;
    }

    public function addAction($action)
    {
        if (empty($action)) {
            return null;
        }
        $actions = $this->getActions() ?? [];
        $actions[] = $action;
        $this->setActions($actions);
    }

    public function translateActionToMethod($action)
    {
        $method = $action;
        if (method_exists($this, $method)) {
            return $method;
        }
        $method = $this->ActionTranslator[$action] ?? null;
        if (!empty($method)) {
            return $method;
        }
        $method = Utility::toCamelCase($action);
        if (method_exists($this, $method)) {
            return $method;
        }

        return null;
    }

    public function getRequest($content_type = null)
    {
        $possible_content_types = ["json", "urlencoded"];
        if (empty($content_type) && function_exists("getallheaders")) {
            $req_headers = getallheaders();
            $content_type = $req_headers['Content-Type'] ?? "";
        }
        $content_types = array_filter(
            $possible_content_types,
            function ($ct) use ($content_type) {
                return strpos($content_type, $ct) !== false;
            }
        );
        if (count($content_types) > 1) {
            throw new \Exception("This was a weird content-type: ".$content_type);
        }
        if (empty($content_types)) {
            return $_REQUEST;
        }
        $defined_CT = array_shift($content_types);

        if ($defined_CT == "json") {
            $string = file_get_contents("php://input");
            json_decode($string, true);
            $is_valid = json_last_error() == JSON_ERROR_NONE;
            if (strlen($string) > 0 && $is_valid) {
                return json_decode($string, true);
            } else {
                return null;
            }
        } elseif ($defined_CT == "urlencoded") {
            return $_REQUEST;
        }
    }

    public function getFromRequest($key = null)
    {
        if(empty($key)){
            return $this->REQ;
        }
        return $this->REQ[$key] ?? null;
    }


}
