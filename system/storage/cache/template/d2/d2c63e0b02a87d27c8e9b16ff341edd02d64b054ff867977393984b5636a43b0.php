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

/* common/header.twig */
class __TwigTemplate_38256356b8baadc9fe5bd84e2675ea8ca3fb539208ba6f6e1328fe757c6c903d extends Template
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
        yield "<!DOCTYPE html>
<html dir=\"";
        // line 2
        yield ($context["direction"] ?? null);
        yield "\" lang=\"";
        yield ($context["lang"] ?? null);
        yield "\">
<head>
<meta charset=\"UTF-8\" />
<title>";
        // line 5
        yield ($context["title"] ?? null);
        yield "</title>
<base href=\"";
        // line 6
        yield ($context["base"] ?? null);
        yield "\" />
";
        // line 7
        if (($context["description"] ?? null)) {
            // line 8
            yield "<meta name=\"description\" content=\"";
            yield ($context["description"] ?? null);
            yield "\" />
";
        }
        // line 10
        if (($context["keywords"] ?? null)) {
            // line 11
            yield "<meta name=\"keywords\" content=\"";
            yield ($context["keywords"] ?? null);
            yield "\" />
";
        }
        // line 13
        yield "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0\" />
<script type=\"text/javascript\" src=\"view/javascript/jquery/jquery-3.7.1.min.js\"></script>
<script type=\"text/javascript\" src=\"view/javascript/bootstrap/js/bootstrap.min.js\"></script>
<link href=\"view/stylesheet/bootstrap.css\" type=\"text/css\" rel=\"stylesheet\" />
<link href=\"view/javascript/font-awesome/css/font-awesome.min.css\" type=\"text/css\" rel=\"stylesheet\" />
<script src=\"view/javascript/jquery/datetimepicker/moment/moment.min.js\" type=\"text/javascript\"></script>
<script src=\"view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js\" type=\"text/javascript\"></script>
<script src=\"view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js\" type=\"text/javascript\"></script>
<link href=\"view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css\" type=\"text/css\" rel=\"stylesheet\" media=\"screen\" />
<link type=\"text/css\" href=\"view/stylesheet/stylesheet.css\" rel=\"stylesheet\" media=\"screen\" />
";
        // line 23
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["styles"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["style"]) {
            // line 24
            yield "<link type=\"text/css\" href=\"";
            yield CoreExtension::getAttribute($this->env, $this->source, $context["style"], "href", [], "any", false, false, false, 24);
            yield "\" rel=\"";
            yield CoreExtension::getAttribute($this->env, $this->source, $context["style"], "rel", [], "any", false, false, false, 24);
            yield "\" media=\"";
            yield CoreExtension::getAttribute($this->env, $this->source, $context["style"], "media", [], "any", false, false, false, 24);
            yield "\" />
";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['style'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 26
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["links"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["link"]) {
            // line 27
            yield "<link href=\"";
            yield CoreExtension::getAttribute($this->env, $this->source, $context["link"], "href", [], "any", false, false, false, 27);
            yield "\" rel=\"";
            yield CoreExtension::getAttribute($this->env, $this->source, $context["link"], "rel", [], "any", false, false, false, 27);
            yield "\" />
";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['link'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 29
        yield "<script src=\"view/javascript/common.js\" type=\"text/javascript\"></script>
";
        // line 30
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["scripts"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["script"]) {
            // line 31
            yield "<script type=\"text/javascript\" src=\"";
            yield $context["script"];
            yield "\"></script>
";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['script'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 33
        yield "</head>
<body>
<div id=\"container\">
<header id=\"header\" class=\"navbar navbar-static-top\">
  <div class=\"container-fluid\">
    <div id=\"header-logo\" class=\"navbar-header\"><a href=\"";
        // line 38
        yield ($context["home"] ?? null);
        yield "\" class=\"navbar-brand\"><img src=\"view/image/logo.png\" alt=\"";
        yield ($context["heading_title"] ?? null);
        yield "\" title=\"";
        yield ($context["heading_title"] ?? null);
        yield "\" /></a></div>
    ";
        // line 39
        if (($context["logged"] ?? null)) {
            yield "<a href=\"#\" id=\"button-menu\" class=\"hidden-md hidden-lg\"><span class=\"fa fa-bars\"></span></a>
    <ul class=\"nav pull-left\">
      <li class=\"dropdown\"><a class=\"dropdown-toggle\" data-toggle=\"dropdown\"  title=\"";
            // line 41
            yield ($context["text_new"] ?? null);
            yield "\"><i class=\"fa fa-plus fa-lg\"></i> <span class=\"header-item\">";
            yield ($context["text_new"] ?? null);
            yield "</span></a>
        <ul class=\"dropdown-menu dropdown-menu-left alerts-dropdown\">
          <li><a href=\"";
            // line 43
            yield ($context["new_product"] ?? null);
            yield "\" style=\"display: block; overflow: auto;\">";
            yield ($context["text_new_product"] ?? null);
            yield "</a></li>
          <li><a href=\"";
            // line 44
            yield ($context["new_category"] ?? null);
            yield "\" style=\"display: block; overflow: auto;\">";
            yield ($context["text_new_category"] ?? null);
            yield "</a></li>
          <li><a href=\"";
            // line 45
            yield ($context["new_manufacturer"] ?? null);
            yield "\" style=\"display: block; overflow: auto;\">";
            yield ($context["text_new_manufacturer"] ?? null);
            yield "</a></li>
          <li><a href=\"";
            // line 46
            yield ($context["new_customer"] ?? null);
            yield "\" style=\"display: block; overflow: auto;\">";
            yield ($context["text_new_customer"] ?? null);
            yield "</a></li>
          <li><a href=\"";
            // line 47
            yield ($context["new_download"] ?? null);
            yield "\" style=\"display: block; overflow: auto;\">";
            yield ($context["text_new_download"] ?? null);
            yield "</a></li>
        </ul>
      </li>
    </ul>

    <div id=\"oc-search-div\" class=\"col-sm-3 col-md-3 pull-left\">
      ";
            // line 53
            yield ($context["search"] ?? null);
            yield "
    </div>
    <ul class=\"nav navbar-nav navbar-right\">
\t  <li";
            // line 56
            if (($context["maintenance"] ?? null)) {
                yield " class=\"bg-danger\"";
            }
            yield "><a href=\"";
            yield ($context["maintenance_url"] ?? null);
            yield "\" id=\"button-maintenance\" title=\"";
            yield ($context["text_maintenance"] ?? null);
            yield ": ";
            if (($context["maintenance"] ?? null)) {
                yield ($context["text_enabled"] ?? null);
            } else {
                yield ($context["text_disabled"] ?? null);
            }
            yield "\"><i class=\"fa fa-wrench\"></i></a></li>
      <li class=\"dropdown\"><a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\"><img src=\"";
            // line 57
            yield ($context["image"] ?? null);
            yield "\" alt=\"";
            yield ($context["firstname"] ?? null);
            yield " ";
            yield ($context["lastname"] ?? null);
            yield "\" title=\"";
            yield ($context["username"] ?? null);
            yield "\" id=\"user-profile\" class=\"img-circle\" />";
            yield ($context["firstname"] ?? null);
            yield " ";
            yield ($context["lastname"] ?? null);
            yield " <i class=\"fa fa-caret-down fa-fw\"></i></a>
        <ul class=\"dropdown-menu dropdown-menu-right\">
          <li><a href=\"";
            // line 59
            yield ($context["profile"] ?? null);
            yield "\"><i class=\"fa fa-user-circle-o fa-fw\"></i> ";
            yield ($context["text_profile"] ?? null);
            yield "</a></li>
          <li role=\"separator\" class=\"divider\"></li>
          <li class=\"dropdown-header\">";
            // line 61
            yield ($context["text_store"] ?? null);
            yield "</li>
          ";
            // line 62
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["stores"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["store"]) {
                // line 63
                yield "          <li><a href=\"";
                yield CoreExtension::getAttribute($this->env, $this->source, $context["store"], "href", [], "any", false, false, false, 63);
                yield "\" target=\"_blank\">";
                yield CoreExtension::getAttribute($this->env, $this->source, $context["store"], "name", [], "any", false, false, false, 63);
                yield "</a></li>
          ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['store'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 65
            yield "          <li role=\"separator\" class=\"divider\"></li>
          <li class=\"dropdown-header\">liveopencart.ru</li>
\t\t      <li><a href=\"https://liveopencart.ru/?utm_source=ocstore3&utm_medium=admin&utm_campaign=3045\" target=\"_blank\"><i class=\"fa fa-opencart fa-fw\"></i> ";
            // line 67
            yield ($context["text_homepage"] ?? null);
            yield "</a></li>
          <li><a href=\"https://liveopencart.ru/docs-opencart3/?utm_source=ocstore3&utm_medium=admin&utm_campaign=3045\" target=\"_blank\"><i class=\"fa fa-file-text-o fa-fw\"></i> ";
            // line 68
            yield ($context["text_documentation"] ?? null);
            yield "</a></li>
          <li><a href=\"https://forum.liveopencart.ru/?utm_source=ocstore3&utm_medium=admin&utm_campaign=3045\" target=\"_blank\"><i class=\"fa fa-comments-o fa-fw\"></i> ";
            // line 69
            yield ($context["text_support"] ?? null);
            yield "</a></li>
        </ul>
      </li>
      <li><a href=\"";
            // line 72
            yield ($context["logout"] ?? null);
            yield "\"><i class=\"fa fa-sign-out\"></i> <span class=\"hidden-xs hidden-sm hidden-md\">";
            yield ($context["text_logout"] ?? null);
            yield "</span></a></li>
    </ul>
    ";
        }
        // line 74
        yield "</div>
</header>
<script type=\"text/javascript\"><!--
\$('#button-maintenance').on('click', function(e) {
\te.preventDefault();
\t\$.getJSON(this.href, function(json) {
\t\tif (json.success) location.reload();
\t});
});
//--></script>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "common/header.twig";
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
        return array (  284 => 74,  276 => 72,  270 => 69,  266 => 68,  262 => 67,  258 => 65,  247 => 63,  243 => 62,  239 => 61,  232 => 59,  217 => 57,  201 => 56,  195 => 53,  184 => 47,  178 => 46,  172 => 45,  166 => 44,  160 => 43,  153 => 41,  148 => 39,  140 => 38,  133 => 33,  124 => 31,  120 => 30,  117 => 29,  106 => 27,  102 => 26,  89 => 24,  85 => 23,  73 => 13,  67 => 11,  65 => 10,  59 => 8,  57 => 7,  53 => 6,  49 => 5,  41 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "common/header.twig", "");
    }
}
