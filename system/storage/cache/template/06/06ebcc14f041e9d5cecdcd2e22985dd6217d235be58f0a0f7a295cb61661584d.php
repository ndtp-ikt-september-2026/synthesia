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

/* install/step_3.twig */
class __TwigTemplate_d2ab6149175eae62265288d65c1555df2bde2f3bfb56480ec13a2142c4d5f256 extends Template
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
  <header>
    <div class=\"row\">
      <div class=\"col-sm-12\">
        <h1 class=\"pull-left\">3<small>/4</small></h1>
        <h3>";
        // line 7
        yield ($context["heading_title"] ?? null);
        yield "
          <br>
          <small>";
        // line 9
        yield ($context["text_step_3"] ?? null);
        yield "</small>
        </h3>
      </div>
    </div>
  </header>
  ";
        // line 14
        if (($context["error_warning"] ?? null)) {
            // line 15
            yield "    <div class=\"alert alert-danger alert-dismissible\"><i class=\"fa fa-exclamation-circle\"></i> ";
            yield ($context["error_warning"] ?? null);
            yield "
      <button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>
    </div>
  ";
        }
        // line 19
        yield "  <div class=\"row\">
    <div class=\"col-sm-9\">
      <form action=\"";
        // line 21
        yield ($context["action"] ?? null);
        yield "\" method=\"post\" enctype=\"multipart/form-data\" class=\"form-horizontal\">
        <p>";
        // line 22
        yield ($context["text_db_connection"] ?? null);
        yield "</p>
        <fieldset>
          <div class=\"form-group\">
            <label class=\"col-sm-2 control-label\" for=\"input-db-driver\">";
        // line 25
        yield ($context["entry_db_driver"] ?? null);
        yield "</label>
            <div class=\"col-sm-10\">
              <select name=\"db_driver\" id=\"input-db-driver\" class=\"form-control\">
                ";
        // line 28
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["drivers"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["driver"]) {
            // line 29
            yield "                  ";
            if ((0 === CoreExtension::compare(($context["db_driver"] ?? null), CoreExtension::getAttribute($this->env, $this->source, $context["driver"], "value", [], "any", false, false, false, 29)))) {
                // line 30
                yield "                    <option value=\"";
                yield CoreExtension::getAttribute($this->env, $this->source, $context["driver"], "value", [], "any", false, false, false, 30);
                yield "\" selected=\"selected\">";
                yield CoreExtension::getAttribute($this->env, $this->source, $context["driver"], "text", [], "any", false, false, false, 30);
                yield "</option>
                  ";
            } else {
                // line 32
                yield "                    <option value=\"";
                yield CoreExtension::getAttribute($this->env, $this->source, $context["driver"], "value", [], "any", false, false, false, 32);
                yield "\">";
                yield CoreExtension::getAttribute($this->env, $this->source, $context["driver"], "text", [], "any", false, false, false, 32);
                yield "</option>
                  ";
            }
            // line 34
            yield "                ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['driver'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 35
        yield "              </select>
              ";
        // line 36
        if (($context["error_db_driver"] ?? null)) {
            // line 37
            yield "                <div class=\"text-danger\">";
            yield ($context["error_db_driver"] ?? null);
            yield "</div>
              ";
        }
        // line 39
        yield "            </div>
          </div>
          <div class=\"form-group required\">
            <label class=\"col-sm-2 control-label\" for=\"input-db-hostname\">";
        // line 42
        yield ($context["entry_db_hostname"] ?? null);
        yield "</label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"db_hostname\" value=\"";
        // line 44
        yield ($context["db_hostname"] ?? null);
        yield "\" id=\"input-db-hostname\" class=\"form-control\"/>
              ";
        // line 45
        if (($context["error_db_hostname"] ?? null)) {
            // line 46
            yield "                <div class=\"text-danger\">";
            yield ($context["error_db_hostname"] ?? null);
            yield "</div>
              ";
        }
        // line 48
        yield "            </div>
          </div>
          <div class=\"form-group required\">
            <label class=\"col-sm-2 control-label\" for=\"input-db-username\">";
        // line 51
        yield ($context["entry_db_username"] ?? null);
        yield "</label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"db_username\" value=\"";
        // line 53
        yield ($context["db_username"] ?? null);
        yield "\" id=\"input-db-username\" class=\"form-control\"/>
              ";
        // line 54
        if (($context["error_db_username"] ?? null)) {
            // line 55
            yield "                <div class=\"text-danger\">";
            yield ($context["error_db_username"] ?? null);
            yield "</div>
              ";
        }
        // line 57
        yield "            </div>
          </div>
          <div class=\"form-group\">
            <label class=\"col-sm-2 control-label\" for=\"input-db-password\">";
        // line 60
        yield ($context["entry_db_password"] ?? null);
        yield "</label>
            <div class=\"col-sm-10\">
              <input type=\"password\" name=\"db_password\" value=\"";
        // line 62
        yield ($context["db_password"] ?? null);
        yield "\" id=\"input-db-password\" class=\"form-control\"/>
            </div>
          </div>
          <div class=\"form-group required\">
            <label class=\"col-sm-2 control-label\" for=\"input-db-database\">";
        // line 66
        yield ($context["entry_db_database"] ?? null);
        yield "</label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"db_database\" value=\"";
        // line 68
        yield ($context["db_database"] ?? null);
        yield "\" id=\"input-db-database\" class=\"form-control\"/>
              ";
        // line 69
        if (($context["error_db_database"] ?? null)) {
            // line 70
            yield "                <div class=\"text-danger\">";
            yield ($context["error_db_database"] ?? null);
            yield "</div>
              ";
        }
        // line 72
        yield "            </div>
          </div>
          <div class=\"form-group required\">
            <label class=\"col-sm-2 control-label\" for=\"input-db-port\">";
        // line 75
        yield ($context["entry_db_port"] ?? null);
        yield "</label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"db_port\" value=\"";
        // line 77
        yield ($context["db_port"] ?? null);
        yield "\" id=\"input-db-port\" class=\"form-control\"/>
              ";
        // line 78
        if (($context["error_db_port"] ?? null)) {
            // line 79
            yield "                <div class=\"text-danger\">";
            yield ($context["error_db_port"] ?? null);
            yield "</div>
              ";
        }
        // line 81
        yield "            </div>
          </div>
          <div class=\"form-group\">
            <label class=\"col-sm-2 control-label\" for=\"input-db-prefix\">";
        // line 84
        yield ($context["entry_db_prefix"] ?? null);
        yield "</label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"db_prefix\" value=\"";
        // line 86
        yield ($context["db_prefix"] ?? null);
        yield "\" id=\"input-db-prefix\" class=\"form-control\"/>
              ";
        // line 87
        if (($context["error_db_prefix"] ?? null)) {
            // line 88
            yield "                <div class=\"text-danger\">";
            yield ($context["error_db_prefix"] ?? null);
            yield "</div>
              ";
        }
        // line 90
        yield "            </div>
          </div>
        </fieldset>
        <p>";
        // line 93
        yield ($context["text_db_administration"] ?? null);
        yield "</p>
        <fieldset>
          <div class=\"form-group required\">
            <label class=\"col-sm-2 control-label\" for=\"input-username\">";
        // line 96
        yield ($context["entry_username"] ?? null);
        yield "</label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"username\" value=\"";
        // line 98
        yield ($context["username"] ?? null);
        yield "\" id=\"input-username\" class=\"form-control\"/>
              ";
        // line 99
        if (($context["error_username"] ?? null)) {
            // line 100
            yield "                <div class=\"text-danger\">";
            yield ($context["error_username"] ?? null);
            yield "</div>
              ";
        }
        // line 102
        yield "            </div>
          </div>
          <div class=\"form-group required\">
            <label class=\"col-sm-2 control-label\" for=\"input-password\">";
        // line 105
        yield ($context["entry_password"] ?? null);
        yield "</label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"password\" value=\"";
        // line 107
        yield ($context["password"] ?? null);
        yield "\" id=\"input-password\" class=\"form-control\"/>
              ";
        // line 108
        if (($context["error_password"] ?? null)) {
            // line 109
            yield "                <div class=\"text-danger\">";
            yield ($context["error_password"] ?? null);
            yield "</div>
              ";
        }
        // line 111
        yield "            </div>
          </div>
          <div class=\"form-group required\">
            <label class=\"col-sm-2 control-label\" for=\"input-email\">";
        // line 114
        yield ($context["entry_email"] ?? null);
        yield "</label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"email\" value=\"";
        // line 116
        yield ($context["email"] ?? null);
        yield "\" id=\"input-email\" class=\"form-control\"/>
              ";
        // line 117
        if (($context["error_email"] ?? null)) {
            // line 118
            yield "                <div class=\"text-danger\">";
            yield ($context["error_email"] ?? null);
            yield "</div>
              ";
        }
        // line 120
        yield "            </div>
          </div>
        </fieldset>
        <div class=\"buttons\">
          <div class=\"pull-left\"><a href=\"";
        // line 124
        yield ($context["back"] ?? null);
        yield "\" class=\"btn btn-default\">";
        yield ($context["button_back"] ?? null);
        yield "</a></div>
          <div class=\"pull-right\">
            <input type=\"submit\" value=\"";
        // line 126
        yield ($context["button_continue"] ?? null);
        yield "\" class=\"btn btn-primary\"/>
          </div>
        </div>
      </form>
    </div>
    <div class=\"col-sm-3\">";
        // line 131
        yield ($context["column_left"] ?? null);
        yield "</div>
  </div>
</div>
";
        // line 134
        yield ($context["footer"] ?? null);
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "install/step_3.twig";
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
        return array (  351 => 134,  345 => 131,  337 => 126,  330 => 124,  324 => 120,  318 => 118,  316 => 117,  312 => 116,  307 => 114,  302 => 111,  296 => 109,  294 => 108,  290 => 107,  285 => 105,  280 => 102,  274 => 100,  272 => 99,  268 => 98,  263 => 96,  257 => 93,  252 => 90,  246 => 88,  244 => 87,  240 => 86,  235 => 84,  230 => 81,  224 => 79,  222 => 78,  218 => 77,  213 => 75,  208 => 72,  202 => 70,  200 => 69,  196 => 68,  191 => 66,  184 => 62,  179 => 60,  174 => 57,  168 => 55,  166 => 54,  162 => 53,  157 => 51,  152 => 48,  146 => 46,  144 => 45,  140 => 44,  135 => 42,  130 => 39,  124 => 37,  122 => 36,  119 => 35,  113 => 34,  105 => 32,  97 => 30,  94 => 29,  90 => 28,  84 => 25,  78 => 22,  74 => 21,  70 => 19,  62 => 15,  60 => 14,  52 => 9,  47 => 7,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "install/step_3.twig", "");
    }
}
