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

/* extension/dashboard/recent_info.twig */
class __TwigTemplate_195d43d535bb900efb584a4de43b8dd9d38243f5a720449ead501f4d65e82a78 extends Template
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
        yield "<div class=\"panel panel-default\">
  <div class=\"panel-heading\">
    <h3 class=\"panel-title\"><i class=\"fa fa-shopping-cart\"></i> ";
        // line 3
        yield ($context["heading_title"] ?? null);
        yield "</h3>
  </div>
  <div class=\"table-responsive\">
    <table class=\"table\">
      <thead>
        <tr>
          <td class=\"text-right\">";
        // line 9
        yield ($context["column_order_id"] ?? null);
        yield "</td>
          <td>";
        // line 10
        yield ($context["column_customer"] ?? null);
        yield "</td>
          <td>";
        // line 11
        yield ($context["column_status"] ?? null);
        yield "</td>
          <td>";
        // line 12
        yield ($context["column_date_added"] ?? null);
        yield "</td>
          <td class=\"text-right\">";
        // line 13
        yield ($context["column_total"] ?? null);
        yield "</td>
          <td class=\"text-right\">";
        // line 14
        yield ($context["column_action"] ?? null);
        yield "</td>
        </tr>
      </thead>
      <tbody>
        ";
        // line 18
        if (($context["orders"] ?? null)) {
            // line 19
            yield "        ";
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["orders"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["order"]) {
                // line 20
                yield "        <tr>
          <td class=\"text-right\">";
                // line 21
                yield CoreExtension::getAttribute($this->env, $this->source, $context["order"], "order_id", [], "any", false, false, false, 21);
                yield "</td>
          <td>";
                // line 22
                yield CoreExtension::getAttribute($this->env, $this->source, $context["order"], "customer", [], "any", false, false, false, 22);
                yield "</td>
          <td>";
                // line 23
                yield CoreExtension::getAttribute($this->env, $this->source, $context["order"], "status", [], "any", false, false, false, 23);
                yield "</td>
          <td>";
                // line 24
                yield CoreExtension::getAttribute($this->env, $this->source, $context["order"], "date_added", [], "any", false, false, false, 24);
                yield "</td>
          <td class=\"text-right\">";
                // line 25
                yield CoreExtension::getAttribute($this->env, $this->source, $context["order"], "total", [], "any", false, false, false, 25);
                yield "</td>
          <td class=\"text-right\"><a href=\"";
                // line 26
                yield CoreExtension::getAttribute($this->env, $this->source, $context["order"], "view", [], "any", false, false, false, 26);
                yield "\" data-toggle=\"tooltip\" title=\"";
                yield ($context["button_view"] ?? null);
                yield "\" class=\"btn btn-info\"><i class=\"fa fa-eye\"></i></a></td>
        </tr>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['order'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 29
            yield "        ";
        } else {
            // line 30
            yield "        <tr>
          <td class=\"text-center\" colspan=\"6\">";
            // line 31
            yield ($context["text_no_results"] ?? null);
            yield "</td>
        </tr>
        ";
        }
        // line 34
        yield "      </tbody>
    </table>
  </div>
</div>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "extension/dashboard/recent_info.twig";
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
        return array (  131 => 34,  125 => 31,  122 => 30,  119 => 29,  108 => 26,  104 => 25,  100 => 24,  96 => 23,  92 => 22,  88 => 21,  85 => 20,  80 => 19,  78 => 18,  71 => 14,  67 => 13,  63 => 12,  59 => 11,  55 => 10,  51 => 9,  42 => 3,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "extension/dashboard/recent_info.twig", "");
    }
}
