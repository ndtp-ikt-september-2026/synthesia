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

/* install/step_4.twig */
class __TwigTemplate_5d2af464b4292fc55d3007f7a01c6411e9bb8e4fa4d66500ba9a8630c720408f extends Template
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
\t\t\t\t<h1 class=\"pull-left\">4
\t\t\t\t<small>/4</small>
\t\t\t\t</h1>
\t\t\t\t<h3>";
        // line 9
        yield ($context["heading_title"] ?? null);
        yield "
\t\t\t\t<br>
\t\t\t\t<small>";
        // line 11
        yield ($context["text_step_4"] ?? null);
        yield "</small>
\t\t\t\t</h3>
\t\t\t</div>
\t\t</div>
\t</header>
\t<div class=\"alert alert-danger alert-dismissible\"><i class=\"fa fa-exclamation-circle\"></i> ";
        // line 16
        yield ($context["error_warning"] ?? null);
        yield "</div>
\t<div class=\"visit\">
\t\t<div class=\"row\">
\t\t<div class=\"col-sm-5 col-sm-offset-1 text-center\">
\t\t\t<p><i class=\"fa fa-shopping-cart fa-5x\"></i></p>
\t\t\t<a href=\"../\" class=\"btn btn-secondary\">";
        // line 21
        yield ($context["text_catalog"] ?? null);
        yield "</a>
\t\t</div>
\t\t<div class=\"col-sm-5 text-center\">
\t\t\t<p><i class=\"fa fa-cog fa-5x white\"></i></p>
\t\t\t<a href=\"../admin/\" class=\"btn btn-secondary\">";
        // line 25
        yield ($context["text_admin"] ?? null);
        yield "</a>
\t\t</div>
\t\t</div>
\t</div>
</div>
";
        // line 30
        yield ($context["footer"] ?? null);
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "install/step_4.twig";
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
        return array (  85 => 30,  77 => 25,  70 => 21,  62 => 16,  54 => 11,  49 => 9,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "install/step_4.twig", "");
    }
}
