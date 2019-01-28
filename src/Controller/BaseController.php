<?php


namespace Fridde\Controller;

use Fridde\Error\Error;
use Fridde\Error\NException;
use Fridde\HTML;
use Fridde\Security\Authorizer;
use Fridde\TwigExtension\NavigationExtension;
use Fridde\Utility;
use nochso\HtmlCompressTwig\Extension as HtmlCompressTwigExtension;

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
    protected $return_type = self::RETURN_HTML;
    /** @var \Fridde\HTML A Html object to build the page */
    protected $H;
    protected $DATA = [];
    protected $TWIG_Variables = [];
    protected $title;
    protected $js = ['start' => [], 'end' => []];
    protected $css = [];
    protected $fonts = [];
    protected $template;

    /* @var Authorizer $Authorizer */
    protected $Authorizer;

    public const RETURN_HTML = 0;
    public const RETURN_JSON = 1;
    public const RETURN_TEXT = 2;


    public function __construct(array $params = [], bool $slim = false)
    {
        $this->N = $GLOBALS['CONTAINER']->get('Naturskolan');
        $this->Authorizer = new Authorizer($this->N->ORM, $this->N->Auth);
        $this->REQ = $_REQUEST ?? [];
        $this->params = $params;
        $this->addAction($this->getParameter('action'));

        if (!$slim) {
            $this->constructExpanded();
        }
    }

    public function constructExpanded(): void
    {
        $args = [$this->Authorizer];
        $args[] = $GLOBALS['CONTAINER']->get('Router');
        $args[] = $this->N->ORM;
        $extensions[] = new NavigationExtension(...$args);
        $extensions[] = new HtmlCompressTwigExtension();
        $this->H = new HTML(null, $extensions, BASE_DIR . '/temp/cache');
        $this->setTitle(SETTINGS['defaults']['title'] ?? null);
    }


    public function handleRequest()
    {
        $actions = $this->getActions();
        $param_string = $this->getParameter('parameters');
        $params = empty($param_string) ? [] : explode('/', $param_string);
        foreach ($actions as $action) {
            $method = $this->translateActionToMethod($action);
            if (empty($method)) {
                continue;
            }
            if (!$this->Authorizer->authorize(get_class($this), $method)) {
                $login_controller = new LoginController($this->getParameter());
                $login_controller->addAction('renderPasswordModal');

                return $login_controller->handleRequest();
            }

            call_user_func_array([$this, $method], $params);
            if ($this->getReturnType() === false) {
                exit;
            }
        }

        $return_type = $this->getReturnType();
        if ($return_type === self::RETURN_HTML) {
            return $this->renderAsHtml();
        }
        if ($return_type === self::RETURN_JSON) {
            return $this->returnAsJson();
        }
        if ($return_type === self::RETURN_TEXT) {
            return $this->returnAsText();
        }
        throw new NException(Error::INVALID_OPTION, ['Return type ' . $return_type]);
    }

    public function returnAsJson(): string
    {
        header('Content-Type: application/json');
        $json = json_encode($this->DATA);
        echo $json;

        return $json;
    }

    public function renderAsHtml(): string
    {
        if (empty($this->getTemplate())) {
            $this->renderErrorPage();
        }

        $this->H->setTitle($this->getTitle());
        $this->addDefaultJsAndCss();
        $this->addDefaultFonts();
        $this->H->addJS($this->getJs())
            ->addCss($this->getCss())
            ->addFonts($this->getFonts())
            ->setTemplate($this->getTemplate())->setBase();

        $this->addAllVariablesToTemplate();

        return $this->H->render();
    }

    public function returnAsText(): string
    {
        if (empty($this->getTemplate())) {
            $this->renderErrorPage();
        }

        $this->H->setTemplate($this->getTemplate());
        $this->addAllVariablesToTemplate();

        return $this->H->render(false);
    }

    private function renderErrorPage()
    {
        $this->setTemplate('error');
        $this->addToDATA('url', implode('/', $this->getParameter()));
        $this->N->log('A request for ' . $_SERVER['REQUEST_URI'] . ' resulted in a template error.', __METHOD__);
    }

    public function addAllVariablesToTemplate(): void
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
    protected function standardRender(array $options = []): HTML
    {
        $template = $options['template'] ?? 'index';

        $this->addDefaultJsAndCss();
        $this->addDefaultFonts();

        $this->H->addJS($this->getJs())->addCss($this->getCss())
            ->setTemplate($template)->setBase();

        if (!empty($options['DATA'])) {
            $this->H->addVariable('DATA', $options['DATA']);
        }
        $this->H->render();

        return $this->H;
    }


    public function getDATA(string $key = null)
    {
        $DATA = $this->DATA;
        if(!empty($key)){
            return $DATA[$key] ?? null;
        }
        return $DATA;
    }

    /**
     * @param mixed $DATA
     */
    public function setDATA($DATA): void
    {
        $this->DATA = $DATA;
    }

    public function addToDATA($key_or_array, ...$args): void
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

            return;
        }

        $this->setDATA($this->getDATA() + $array);
    }

    public function setReturnType(int $return_type = self::RETURN_HTML): void
    {
        $this->return_type = $return_type;
    }

    public function getReturnType(): int
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
    public function setTWIGVariables(array $TWIG_Variables): void
    {
        $this->TWIG_Variables = $TWIG_Variables;
    }

    public function addAsVar($key_or_array, $value = null): void
    {
        $variables = $this->getTWIGVariables() ?? [];
        if (is_array($key_or_array)) {
            $variables = array_merge($variables, $key_or_array);
        } else {
            $variables[$key_or_array] = $value;
        }

        $this->setTWIGVariables($variables);
    }

    public function moveFromDataToVar(...$keys): void
    {
        $data = $this->getParameter('data');
        $data = $this->DATA ?? $data;

        foreach ($keys as $key) {
            $this->addAsVar($key, $data[$key] ?? null);
            if (isset($this->DATA[$key])) {
                unset($this->DATA[$key]);
            }
            if (isset($this->params[$key])) {
                unset($this->params[$key]);
            }
        }
    }


    /**
     * @return null|string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param null|string $title
     */
    public function setTitle(string $title = null): void
    {
        $this->title = $title;
    }

    /**
     * @return array
     */
    public function getJs(): array
    {
        return array_merge($this->js['start'], $this->js['end']);
    }

    public function addJs($js, int $type = HTML::INC_ABBREVIATION, string $position = 'start'): void
    {
        $this->js[$position][] = [$js, $type];
    }

    public function addJsToEnd($js, int $type = HTML::INC_ABBREVIATION): void
    {
        $this->addJs($js, $type, 'end');
    }


    /**
     * @return array
     */
    public function getCss(): array
    {
        return $this->css;
    }


    public function addCss($css, int $type = HTML::INC_ABBREVIATION): void
    {
        $this->css[] = [$css, $type];
    }

    public function getFonts(): array
    {
        return $this->fonts;
    }


    public function addFonts(array $fonts): void
    {
        $this->fonts[] = $fonts;
    }

    /**
     * @return string
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public function getParameter(string $key = null)
    {
        return (empty($key) ? $this->params : ($this->params[$key] ?? null));

    }

    public function setParameter(string $key, $value = null): void
    {
        $this->params[$key] = $value;
    }

    public function hasParameter(string $key = null): bool
    {
        if (empty($key)) {
            return !empty($this->params);
        }

        return (null !== $this->getParameter($key));
    }

    /**
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions ?? [];
    }


    public function setActions(array $actions): void
    {
        $this->actions = $actions;
    }

    public function addAction(string $action = null, bool $to_front = false): void
    {
        if (empty($action)) {
            return;
        }
        $actions = $this->getActions();
        if ($to_front) {
            array_unshift($actions, $action);
        } else {
            $actions[] = $action;
        }
        $this->setActions($actions);
    }

    public function removeAction(string $action = null): void
    {
        if(empty($action)){
            $this->setActions([]);
            return;
        }
        $this->setActions(array_diff($this->getActions(), [$action]));
    }

    public function prependAction(string $action = null): void
    {
        $this->addAction($action, true);
    }

    public function hasAction(string $action = null): bool
    {
        if(empty($action)){
            return ! empty($this->getActions());
        }

        return in_array($action, $this->getActions(), true);
    }

    public function translateActionToMethod(string $action): ?string
    {
        $method = $action;
        if (method_exists($this, $method)) {
            return $method;
        }

        $class_name = get_class($this);
        $at = 'ActionTranslator';
        if(property_exists($class_name, $at)){
            $class = new \ReflectionClass($class_name);
            $at_values = $class->getStaticPropertyValue($at, []);
            $method = $at_values[$action] ?? null; // has to be implemented in the child class
            if (!empty($method)) {
                return $method;
            }
        }

        $method = Utility::toCamelCase($action);
        if (method_exists($this, $method)) {
            return $method;
        }

        return null;
    }

    public function getRequest(string $content_type = null): ?array
    {
        $possible_content_types = ['json', 'urlencoded'];
        if (empty($content_type) && function_exists('getallheaders')) {
            $req_headers = getallheaders();
            $content_type = $req_headers['Content-Type'] ?? '';
        }
        $content_types = array_filter(
            $possible_content_types,
            function ($ct) use ($content_type) {
                return strpos($content_type, $ct) !== false;
            }
        );
        if (count($content_types) > 1) {
            throw new NException(Error::INVALID_OPTION, [$content_type]);
        }
        if (empty($content_types)) {
            return $_REQUEST;
        }
        $defined_CT = array_shift($content_types);

        if ($defined_CT === 'urlencoded') {
            return $_REQUEST;
        }

        if ($defined_CT === 'json') {
            $string = file_get_contents('php://input');
            json_decode($string, true);
            $is_valid = json_last_error() === JSON_ERROR_NONE;
            if ($is_valid && !empty($string)) {
                return json_decode($string, true);
            }

            return null;
        }
    }

    public function getFromRequest(string $key = null)
    {
        if (empty($key)) {
            return $this->REQ;
        }

        return $this->REQ[$key] ?? null;
    }
    private function addDefaultJsAndCss(): void
    {
        $outer = ['js', 'css'];
        $inner = ['remote' => HTML::INC_ABBREVIATION, 'local' => HTML::INC_ASSET];

        foreach($outer as $ext){
            foreach($inner as $place => $resource_type){
                $resources = SETTINGS['defaults'][$ext][$place] ?? [];
                foreach($resources as $resource){
                    if($ext === 'js'){
                        $this->addJs($resource, $resource_type);
                    }
                    if($ext === 'css'){
                        $this->addCss($resource, $resource_type);
                    }
                }
            }
        }
    }

    private function addDefaultFonts(): void
    {
        $array = SETTINGS['defaults']['fonts'] ?? [];

        array_walk(
            $array,
            function (&$v, $k) {
                array_unshift($v, $k);
            }
        );

        $this->addFonts([$array, HTML::FONT_GOOGLE]);
    }


}
