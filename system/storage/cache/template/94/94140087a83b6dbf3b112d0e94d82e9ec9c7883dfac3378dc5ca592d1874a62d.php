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

/* search/search.twig */
class __TwigTemplate_4c2fb3a96f040632b6571d408ac474d939ba7d57f25f014bec23e320cd27c7a8 extends Template
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
        yield "<form id=\"oc-search\" class=\"navbar-form\" role=\"search\">
\t<div class=\"input-group\">
\t\t<div class=\"input-group-btn\">
\t\t\t<a class=\"btn btn-default dropdown-toggle\" data-toggle=\"dropdown\" href=\"#\"><i class=\"fa fa-search\"></i><span class=\"caret\"></span></a>
\t\t\t<ul class=\"dropdown-menu dropdown-menu-left alerts-dropdown\">
\t\t\t\t<li class=\"dropdown-header\">";
        // line 6
        yield ($context["text_search_options"] ?? null);
        yield "</li>
\t\t\t\t<li><a onclick=\"setOption('catalog', '";
        // line 7
        yield ($context["text_catalog_placeholder"] ?? null);
        yield "'); return false;\"><i class=\"fa fa-book\"></i><span>";
        yield ($context["text_catalog"] ?? null);
        yield "</span></a></li>
\t\t\t\t<li><a onclick=\"setOption('customers', '";
        // line 8
        yield ($context["text_customers_placeholder"] ?? null);
        yield "'); return false;\"><i class=\"fa fa-group\"></i><span>";
        yield ($context["text_customers"] ?? null);
        yield "</span></a></li>
\t\t\t\t<li><a onclick=\"setOption('orders', '";
        // line 9
        yield ($context["text_orders_placeholder"] ?? null);
        yield "'); return false;\"><i class=\"fa fa-credit-card\"></i><span>";
        yield ($context["text_orders"] ?? null);
        yield "</span></a></li>
\t\t\t</ul>
\t\t</div>
\t\t<input id=\"oc-search-input\" type=\"text\" class=\"form-control\" placeholder=\"";
        // line 12
        yield ($context["text_search_placeholder"] ?? null);
        yield "\" name=\"query\" autocomplete=\"off\">
\t\t<input id=\"oc-search-option\" type=\"hidden\" name=\"search-option\" value=\"catalog\">
\t\t<div id=\"loader-search\"><img src=\"view/image/loader-search.gif\" alt=\"\"></div>
\t</div>
</form>
<div id=\"oc-search-result\"></div>
<script>
    function setOption(option, text) {
        \$('#oc-search-option').val(option);
        \$('#oc-search-input').attr('placeholder', text);
    }

    \$('#oc-search-input').keyup(function(){
        var option = \$('#oc-search-option').val();
        var length = 2;

        if(option == 'orders') {
            length = 1;
        }

        if(this.value.length < length) {
            return false;
        }

        if(\$.support.leadingWhitespace == false) {
              return false;
        }

        \$('#loader-search').css('display', 'block');

        \$.ajax({
            type: 'get',
            url: 'index.php?route=search/search/search' + '&user_token=";
        // line 44
        yield ($context["user_token"] ?? null);
        yield "',\t\t
\t\t\tdata: \$('#oc-search').serialize(),
            dataType: 'json',
            success:function(json){
                \$('#oc-search-result').css('display', 'block');
                \$('#loader-search').css('display', 'none');

                if(json['error']) {
                    \$('#oc-search-result').html(json['error'])
                    return;
                }

                \$('#oc-search-result').html(json['result'])
            }
        });
    });

    \$(document).mouseup(function (e) {
        var container = \$('#oc-search-result');

        if (!container.is(e.target) && container.has(e.target).length === 0) {
            container.hide();
        }
    });

    \$('#oc-search').submit(function(e) {
        e.preventDefault();
    });
</script>";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "search/search.twig";
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
        return array (  104 => 44,  69 => 12,  61 => 9,  55 => 8,  49 => 7,  45 => 6,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "search/search.twig", "");
    }
}
