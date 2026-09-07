<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* install/step_1.twig */
class __TwigTemplate_e2637f3f612901dae7121e1bcac9732e044aac8b289762ad910f1af5046c07ab extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        yield ($context["header"] ?? null);
        yield "
<div class=\"container\">
\t<header>
\t\t<div class=\"row\">
\t\t\t<div class=\"col-sm-12\">
\t\t\t\t<h1 class=\"pull-left\">1<small>/4</small></h1>
\t\t\t\t<h3>";
        // line 7
        yield ($context["heading_title"] ?? null);
        yield "<br>
\t\t\t\t<small>";
        // line 8
        yield ($context["text_step_1"] ?? null);
        yield "</small></h3>
\t\t\t</div>
\t\t</div>
\t</header>
\t<div class=\"row\">
\t\t<div class=\"col-sm-9\">
\t\t<form action=\"";
        // line 14
        yield ($context["action"] ?? null);
        yield "\" method=\"post\" enctype=\"multipart/form-data\">
\t\t\t<div class=\"terms\">";
        // line 15
        yield ($context["text_terms"] ?? null);
        yield "</div>
\t\t\t<div class=\"buttons\">
\t\t\t<div class=\"pull-right\">
\t\t\t\t<input type=\"submit\" value=\"";
        // line 18
        yield ($context["button_continue"] ?? null);
        yield "\" class=\"btn btn-primary\" />
\t\t\t</div>
\t\t\t</div>
\t\t</form>
\t\t</div>
\t\t<div class=\"col-sm-3\">";
        // line 23
        yield ($context["column_left"] ?? null);
        yield "</div>
\t</div>
</div>
";
        // line 26
        yield ($context["footer"] ?? null);
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "install/step_1.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  84 => 26,  78 => 23,  70 => 18,  64 => 15,  60 => 14,  51 => 8,  47 => 7,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "install/step_1.twig", "");
    }
}
