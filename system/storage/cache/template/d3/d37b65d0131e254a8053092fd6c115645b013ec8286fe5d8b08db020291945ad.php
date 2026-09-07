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

/* extension/dashboard/chart_by_country_and_region_info.twig */
class __TwigTemplate_a2469fd3a070f0094d9a7d4c3d1e3bd77af66def0b178af95f7b81df55bc984b extends Template
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
\t<div class=\"panel-heading\">
\t\t<h3 class=\"panel-title\"><i class=\"fa fa-globe\"></i> ";
        // line 3
        yield ($context["heading_title"] ?? null);
        yield "</h3>
\t</div>
\t<div class=\"panel-body\">
\t\t<div class=\"chart-by-country-and-region\">
\t\t\t<div class=\"chart-by-country-and-region__country active\"></div>
\t\t\t<div class=\"chart-by-country-and-region__region\"></div>
\t\t</div>
\t</div>
</div>
<style type=\"text/css\">
\t.chart-by-country-and-region{position:relative;width:100%;height:260px}
\t.chart-by-country-and-region__country, .chart-by-country-and-region__region{width:100%;height:260px;opacity:0;background:#fff;overflow:hidden}
\t.chart-by-country-and-region__country.active, .chart-by-country-and-region__region.active{z-index:1 !important; opacity:1; transition:all linear .5s}
\t.chart-by-country-and-region__region{position:absolute !important;z-index:-1;top:0px;left:0px;right:0px;width:auto !important}
\t.chart-by-country-and-region__country .legendLabel, .chart-by-country-and-region__region .legendLabel{padding:0 0 3px 3px}
\t.chart-by-country-and-region__country .legend, .chart-by-country-and-region__region .legend{overflow-y:auto}
\t.chart-by-country-and-region__no-orders{position:absolute;top:50%;left:50%;transform:translate(-50%, -50%)}
\t.chart-by-country-and-region__back{position:absolute;bottom:0px;z-index:9;padding:2px 4px;font-size:12px;cursor:pointer;background:rgba(255 255 255 / .8)}
\t.chart-by-country-and-region__tooltip{position:absolute;z-index:9;padding:2px 4px;font-size:11px;background:rgba(255 255 255 / .8)}
</style>
<script>
\tconst country_block = '.chart-by-country-and-region__country', 
\t\t  region_block = '.chart-by-country-and-region__region',
\t\t  no_orders = '.chart-by-country-and-region__no-orders',
\t\t  back_btn = '.chart-by-country-and-region__back';

\tfunction getByCountry() {
\t\t\$.ajax({
\t\t\turl: 'index.php?route=extension/dashboard/chart_by_country_and_region/chartByCountry&user_token=";
        // line 31
        yield ($context["user_token"] ?? null);
        yield "',
\t\t\tdataType: 'json',
\t\t\tsuccess: function(json) {
\t\t\t\tdata = json.data || 0;
\t\t\t\tif (data.length > 1) {
\t\t\t\t\tconst param = {'elem': country_block, 'clickable': true, 'hoverable': true};
\t\t\t\t\t
\t\t\t\t\tDrawChart(data, param);
\t\t\t\t\t
\t\t\t\t\tsetClickByCounries(json.countries);
\t\t\t\t} else if (data.length == 1) {
\t\t\t\t\tgetByRegion(json.countries[0]);
\t\t\t\t\t
\t\t\t\t\t\$(region_block).toggleClass('active');
\t\t\t\t} else {
\t\t\t\t\t\$(country_block).html('<div class=\"'+no_orders+'\">";
        // line 46
        yield ($context["text_no_orders"] ?? null);
        yield "</div>');
\t\t\t\t}
\t\t\t},
\t\t\terror: function(xhr, ajaxOptions, thrownError) {
\t\t\t\talert(thrownError + \"\\r\\n\" + xhr.statusText + \"\\r\\n\" + xhr.responseText);
\t\t\t}
\t\t});
\t}
\t
\tfunction getByRegion(country_id) {
\t\t\$.ajax({
\t\t\turl: 'index.php?route=extension/dashboard/chart_by_country_and_region/chartByRegion&user_token=";
        // line 57
        yield ($context["user_token"] ?? null);
        yield "',
\t\t\tdata: 'country_id='+country_id,
\t\t\tdataType: 'json',
\t\t\tsuccess: function(json) {
\t\t\t\tif(json.length) {
\t\t\t\t\tconst param = {'elem': region_block, 'clickable': false, 'hoverable': true};
\t\t\t\t\t
\t\t\t\t\tDrawChart(json, param);
\t\t\t\t} else {
\t\t\t\t\t\$(region_block).html('<div class=\"'+no_orders+'\">";
        // line 66
        yield ($context["text_no_orders"] ?? null);
        yield "</div>');
\t\t\t\t}
\t\t\t},
\t\t\terror: function(xhr, ajaxOptions, thrownError) {
\t\t\t\talert(thrownError + \"\\r\\n\" + xhr.statusText + \"\\r\\n\" + xhr.responseText);
\t\t\t}
\t\t});
\t}
\t
\tfunction DrawChart(json, param) {
\t\tif(json) {
\t\t\tdata = [];
\t\t
\t\t\tfor (i in json) {
\t\t\t\tdata[i] = {
\t\t\t\t\tlabel: json[i]['name']+' ('+json[i]['total']+')',
\t\t\t\t\tdata: json[i]['total']
\t\t\t\t}
\t\t\t}

\t\t\t\$.plot(param.elem, data, {
\t\t\t\tcolors: ['#9FD5F1', '#1065D2', '#9827bb', '#bb272c', '#27bb5c'],
\t\t\t\tseries: {
\t\t\t\t\tpie: {
\t\t\t\t\t\tshow: true
\t\t\t\t\t}
\t\t\t\t},
\t\t\t\tgrid: {
\t\t\t\t\thoverable: param.hoverable,
\t\t\t\t\tclickable: param.clickable,
\t\t\t\t}
\t\t\t});
\t\t} else {
\t\t\tconsole.log('Error, json is empty')
\t\t}
\t}

\tfunction setClickByCounries(countries) {
\t\t\$(country_block).on('plotclick', (event, pos, obj) => {
\t\t
\t\t\tif (obj && countries) {
\t\t\t\tconst country_id = countries[obj.seriesIndex];
\t\t\t
\t\t\t\tif(country_id) {
\t\t\t\t\tgetByRegion(country_id);
\t\t\t\t\t
\t\t\t\t\tif(!\$(back_btn).length) {
\t\t\t\t\t\t\$(country_block).before('<a class=\"'+back_btn.split('.')[1]+'\">";
        // line 113
        yield ($context["btn_back"] ?? null);
        yield "</a>');
\t\t\t\t\t}
\t\t\t\t\t
\t\t\t\t\t\$(country_block+', '+region_block).toggleClass('active');
\t\t\t\t}
\t\t\t}
\t\t});
\t}
\t
\t\$(country_block+' , '+region_block).on('plothover', (event, pos, obj) => {
\t\t\$('.chart-by-country-and-region__tooltip').remove();
\t\t\t
\t\tif (obj) {
\t\t\tif(!\$('.chart-by-country-and-region__tooltip').length) {
\t\t\t\t\$(country_block).before('<span class=\"chart-by-country-and-region__tooltip\">'+obj.series.label.split('(')[0]+'</span>');
\t\t\t}
\t\t\t\$(country_block).css('cursor', 'pointer');\t\t
\t\t} else {
\t\t\t\$('.chart-by-country-and-region__tooltip').remove();
\t\t\t\$(country_block).css('cursor', 'auto');
\t\t}
\t});
\t
\t\$('body').on('click', back_btn, function() {
\t\t\$(this).remove();
\t\t\$(country_block+', '+region_block).toggleClass('active');
\t});
\t
\t\$(document).ready(function() {
\t\tgetByCountry();
\t});
</script> ";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "extension/dashboard/chart_by_country_and_region_info.twig";
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
        return array (  167 => 113,  117 => 66,  105 => 57,  91 => 46,  73 => 31,  42 => 3,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "extension/dashboard/chart_by_country_and_region_info.twig", "");
    }
}
