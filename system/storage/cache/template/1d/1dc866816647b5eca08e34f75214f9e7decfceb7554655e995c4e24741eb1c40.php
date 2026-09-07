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

/* common/footer.twig */
class __TwigTemplate_97120b5b5ba221faaff477b3b6a556718094d9d355497334150a02d085abd7ed extends Template
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
        yield "</main>
<footer>
\t<div class=\"container\">
\t\t<a href=\"https://liveopencart.ru/?utm_source=ocstore3&utm_medium=install&utm_campaign=";
        // line 4
        yield ($context["livestore_version"] ?? null);
        yield "\" target=\"_blank\">";
        yield ($context["text_project"] ?? null);
        yield "</a> | <a href=\"https://liveopencart.ru/docs-opencart3/?utm_source=ocstore3&utm_medium=install&utm_campaign=";
        yield ($context["livestore_version"] ?? null);
        yield "\" target=\"_blank\">";
        yield ($context["text_documentation"] ?? null);
        yield "</a> | <a href=\"https://forum.liveopencart.ru/?utm_source=ocstore3&utm_medium=install&utm_campaign=";
        yield ($context["livestore_version"] ?? null);
        yield "\" target=\"_blank\">";
        yield ($context["text_support"] ?? null);
        yield "</a>
\t\t<br />
    </div>
</footer>
</body>
</html>
";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "common/footer.twig";
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
        return array (  43 => 4,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "common/footer.twig", "");
    }
}
