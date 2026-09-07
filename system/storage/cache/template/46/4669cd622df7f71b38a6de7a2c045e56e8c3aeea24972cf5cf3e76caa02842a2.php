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

/* common/column_left.twig */
class __TwigTemplate_61d2d52b239cca074c31adecb4c3bbd0d60d4639559119ff72284da2b120b302 extends Template
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
        yield "<ul class=\"list-group\">
\t";
        // line 2
        if ((0 !== CoreExtension::compare(Twig\Extension\CoreExtension::slice($this->env->getCharset(), ($context["route"] ?? null), 0, 8), "upgrade/"))) {
            // line 3
            yield "\t\t";
            if ((0 === CoreExtension::compare(($context["route"] ?? null), "install/step_1"))) {
                // line 4
                yield "\t\t\t<li class=\"list-group-item\"><b>";
                yield ($context["text_license"] ?? null);
                yield "</b></li>
\t\t";
            } else {
                // line 6
                yield "\t\t\t<li class=\"list-group-item\">";
                yield ($context["text_license"] ?? null);
                yield "</li>
\t\t";
            }
            // line 8
            yield "\t\t";
            if ((0 === CoreExtension::compare(($context["route"] ?? null), "install/step_2"))) {
                // line 9
                yield "\t\t\t<li class=\"list-group-item\"><b>";
                yield ($context["text_installation"] ?? null);
                yield "</b></li>
\t\t";
            } else {
                // line 11
                yield "\t\t\t<li class=\"list-group-item\">";
                yield ($context["text_installation"] ?? null);
                yield "</li>
\t\t";
            }
            // line 13
            yield "\t\t";
            if ((0 === CoreExtension::compare(($context["route"] ?? null), "install/step_3"))) {
                // line 14
                yield "\t\t\t<li class=\"list-group-item\"><b>";
                yield ($context["text_configuration"] ?? null);
                yield "</b></li>
\t\t";
            } else {
                // line 16
                yield "\t\t\t<li class=\"list-group-item\">";
                yield ($context["text_configuration"] ?? null);
                yield "</li>
\t\t";
            }
            // line 18
            yield "\t";
        } else {
            // line 19
            yield "\t\t";
            if ((0 === CoreExtension::compare(($context["route"] ?? null), "upgrade/upgrade"))) {
                // line 20
                yield "\t\t\t<li class=\"list-group-item\"><b>";
                yield ($context["text_upgrade"] ?? null);
                yield "</b></li>
\t\t";
            } else {
                // line 22
                yield "\t\t\t<li class=\"list-group-item\">";
                yield ($context["text_upgrade"] ?? null);
                yield "</li>
\t\t";
            }
            // line 24
            yield "\t\t";
            if ((0 === CoreExtension::compare(($context["route"] ?? null), "upgrade/upgrade/success"))) {
                // line 25
                yield "\t\t\t<li class=\"list-group-item\"><b>";
                yield ($context["text_finished"] ?? null);
                yield "</b></li>
\t\t";
            } else {
                // line 27
                yield "\t\t\t<li class=\"list-group-item\">";
                yield ($context["text_finished"] ?? null);
                yield "</li>
\t\t";
            }
            // line 29
            yield "\t";
        }
        // line 30
        yield "</ul>
<form action=\"";
        // line 31
        yield ($context["action"] ?? null);
        yield "\" method=\"post\" enctype=\"multipart/form-data\" id=\"language\">
  <ul class=\"list-group\">
    <li class=\"list-group-item\">
      <div class=\"dropdown\">
        <button class=\"btn-default btn-sm dropdown-toggle\" type=\"button\" data-toggle=\"dropdown\">";
        // line 35
        yield ($context["text_language"] ?? null);
        yield " <span class=\"caret\"></span></button>
        <ul class=\"dropdown-menu\">
          ";
        // line 37
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["languages"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["language"]) {
            // line 38
            yield "          <li><a href=\"";
            yield $context["language"];
            yield "\"><img src=\"language/";
            yield $context["language"];
            yield "/";
            yield $context["language"];
            yield ".png\" /></a></li>
          ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['language'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 40
        yield "        </ul>
      </div>
    </li>
  </ul>
  <input type=\"hidden\" name=\"code\" value=\"\" />
  <input type=\"hidden\" name=\"redirect\" value=\"";
        // line 45
        yield ($context["redirect"] ?? null);
        yield "\" />
</form>
<script>
\t\$('#language a').on('click', function(e) {
\t\te.preventDefault();

\t\t\$('#language input[name=\\'code\\']').val(\$(this).attr('href'));

\t\t\$('#language').submit();
\t});
</script>";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "common/column_left.twig";
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
        return array (  163 => 45,  156 => 40,  143 => 38,  139 => 37,  134 => 35,  127 => 31,  124 => 30,  121 => 29,  115 => 27,  109 => 25,  106 => 24,  100 => 22,  94 => 20,  91 => 19,  88 => 18,  82 => 16,  76 => 14,  73 => 13,  67 => 11,  61 => 9,  58 => 8,  52 => 6,  46 => 4,  43 => 3,  41 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "common/column_left.twig", "");
    }
}
