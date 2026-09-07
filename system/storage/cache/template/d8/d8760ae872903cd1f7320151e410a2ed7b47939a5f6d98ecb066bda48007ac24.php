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

/* install/step_2.twig */
class __TwigTemplate_c5d77ddf6197010b933e6db22c55917be2f7ec0777f25821785e88559cac0518 extends Template
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
\t\t<div class=\"col-sm-12\">
\t\t\t<h1 class=\"pull-left\">2<small>/4</small></h1>
\t\t\t<h3>";
        // line 7
        yield ($context["heading_title"] ?? null);
        yield "<br>
\t\t\t<small>";
        // line 8
        yield ($context["text_step_2"] ?? null);
        yield "</small></h3>
\t\t</div>
\t\t</div>
\t</header>
  ";
        // line 12
        if (($context["error_warning"] ?? null)) {
            // line 13
            yield "  <div class=\"alert alert-danger alert-dismissible\"><i class=\"fa fa-exclamation-circle\"></i> ";
            yield ($context["error_warning"] ?? null);
            yield "
    <button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>
  </div>
  ";
        }
        // line 17
        yield "  <div class=\"row\">
    <div class=\"col-sm-9\">
      <form name=\"step-2\" action=\"";
        // line 19
        yield ($context["action"] ?? null);
        yield "\" method=\"post\" enctype=\"multipart/form-data\" class=\"form-step-2\">
        <p>";
        // line 20
        yield ($context["text_install_php"] ?? null);
        yield "</p>
        <fieldset>
          <table class=\"table\">
            <thead>
              <tr>
                <td width=\"35%\"><b>";
        // line 25
        yield ($context["text_setting"] ?? null);
        yield "</b></td>
                <td width=\"25%\"><b>";
        // line 26
        yield ($context["text_current"] ?? null);
        yield "</b></td>
                <td width=\"25%\"><b>";
        // line 27
        yield ($context["text_required"] ?? null);
        yield "</b></td>
                <td width=\"15%\" class=\"text-center\"><b>";
        // line 28
        yield ($context["text_status"] ?? null);
        yield "</b></td>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>";
        // line 33
        yield ($context["text_version"] ?? null);
        yield "</td>
                <td>";
        // line 34
        yield ($context["php_version"] ?? null);
        yield "</td>
                <td>7.3+</td>
                <td class=\"text-center\">";
        // line 36
        if ((0 <= CoreExtension::compare(($context["php_version"] ?? null), "7.3"))) {
            // line 37
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 39
            yield "                  <span class=\"text-danger\"><i class=\"fa fa-minus-circle\"></i></span>
                  ";
        }
        // line 40
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 43
        yield ($context["text_global"] ?? null);
        yield "</td>
                <td>";
        // line 44
        if (($context["register_globals"] ?? null)) {
            // line 45
            yield "                  ";
            yield ($context["text_on"] ?? null);
            yield "
                  ";
        } else {
            // line 47
            yield "                  ";
            yield ($context["text_off"] ?? null);
            yield "
                  ";
        }
        // line 48
        yield "</td>
                <td>";
        // line 49
        yield ($context["text_off"] ?? null);
        yield "</td>
                <td class=\"text-center\">";
        // line 50
        if ( !($context["register_globals"] ?? null)) {
            // line 51
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 53
            yield "                  <span class=\"text-danger\"><i class=\"fa fa-minus-circle\"></i></span>
                  ";
        }
        // line 54
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 57
        yield ($context["text_magic"] ?? null);
        yield "</td>
                <td>";
        // line 58
        if (($context["magic_quotes_gpc"] ?? null)) {
            // line 59
            yield "                  ";
            yield ($context["text_on"] ?? null);
            yield "
                  ";
        } else {
            // line 61
            yield "                  ";
            yield ($context["text_off"] ?? null);
            yield "
                  ";
        }
        // line 62
        yield "</td>
                <td>";
        // line 63
        yield ($context["text_off"] ?? null);
        yield "</td>
                <td class=\"text-center\">";
        // line 64
        if ( !($context["error_magic_quotes_gpc"] ?? null)) {
            // line 65
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 67
            yield "                  <span class=\"text-danger\"><i class=\"fa fa-minus-circle\"></i></span>
                  ";
        }
        // line 68
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 71
        yield ($context["text_file_upload"] ?? null);
        yield "</td>
                <td>";
        // line 72
        if (($context["file_uploads"] ?? null)) {
            // line 73
            yield "                  ";
            yield ($context["text_on"] ?? null);
            yield "
                  ";
        } else {
            // line 75
            yield "                  ";
            yield ($context["text_off"] ?? null);
            yield "
                  ";
        }
        // line 76
        yield "</td>
                <td>";
        // line 77
        yield ($context["text_on"] ?? null);
        yield "</td>
                <td class=\"text-center\">";
        // line 78
        if (($context["file_uploads"] ?? null)) {
            // line 79
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 81
            yield "                  <span class=\"text-danger\"><i class=\"fa fa-minus-circle\"></i></span>
                  ";
        }
        // line 82
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 85
        yield ($context["text_session"] ?? null);
        yield "</td>
                <td>";
        // line 86
        if (($context["session_auto_start"] ?? null)) {
            // line 87
            yield "                  ";
            yield ($context["text_on"] ?? null);
            yield "
                  ";
        } else {
            // line 89
            yield "                  ";
            yield ($context["text_off"] ?? null);
            yield "
                  ";
        }
        // line 90
        yield "</td>
                <td>";
        // line 91
        yield ($context["text_off"] ?? null);
        yield "</td>
                <td class=\"text-center\">";
        // line 92
        if ( !($context["session_auto_start"] ?? null)) {
            // line 93
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 95
            yield "                  <span class=\"text-danger\"><i class=\"fa fa-minus-circle\"></i></span>
                  ";
        }
        // line 96
        yield "</td>
              </tr>
            </tbody>
          </table>
        </fieldset>
        <p>";
        // line 101
        yield ($context["text_install_extension"] ?? null);
        yield "</p>
        <fieldset>
          <table class=\"table\">
            <thead>
              <tr>
                <td width=\"35%\"><b>";
        // line 106
        yield ($context["text_extension"] ?? null);
        yield "</b></td>
                <td width=\"25%\"><b>";
        // line 107
        yield ($context["text_current"] ?? null);
        yield "</b></td>
                <td width=\"25%\"><b>";
        // line 108
        yield ($context["text_required"] ?? null);
        yield "</b></td>
                <td width=\"15%\" class=\"text-center\"><b>";
        // line 109
        yield ($context["text_status"] ?? null);
        yield "</b></td>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>";
        // line 114
        yield ($context["text_db"] ?? null);
        yield "</td>
                <td>";
        // line 115
        if (($context["db"] ?? null)) {
            // line 116
            yield "                  ";
            yield ($context["text_on"] ?? null);
            yield "
                  ";
        } else {
            // line 118
            yield "                  ";
            yield ($context["text_off"] ?? null);
            yield "
                  ";
        }
        // line 119
        yield "</td>
                <td>";
        // line 120
        yield ($context["text_on"] ?? null);
        yield "</td>
                <td class=\"text-center\">";
        // line 121
        if (($context["db"] ?? null)) {
            // line 122
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 124
            yield "                  <span class=\"text-danger\"><i class=\"fa fa-minus-circle\"></i></span>
                  ";
        }
        // line 125
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 128
        yield ($context["text_gd"] ?? null);
        yield "</td>
                <td>";
        // line 129
        if (($context["gd"] ?? null)) {
            // line 130
            yield "                  ";
            yield ($context["text_on"] ?? null);
            yield "
                  ";
        } else {
            // line 132
            yield "                  ";
            yield ($context["text_off"] ?? null);
            yield "
                  ";
        }
        // line 133
        yield "</td>
                <td>";
        // line 134
        yield ($context["text_on"] ?? null);
        yield "</td>
                <td class=\"text-center\">";
        // line 135
        if (($context["gd"] ?? null)) {
            // line 136
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 138
            yield "                  <span class=\"text-danger\"><i class=\"fa fa-minus-circle\"></i></span>
                  ";
        }
        // line 139
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 142
        yield ($context["text_curl"] ?? null);
        yield "</td>
                <td>";
        // line 143
        if (($context["curl"] ?? null)) {
            // line 144
            yield "                  ";
            yield ($context["text_on"] ?? null);
            yield "
                  ";
        } else {
            // line 146
            yield "                  ";
            yield ($context["text_off"] ?? null);
            yield "
                  ";
        }
        // line 147
        yield "</td>
                <td>";
        // line 148
        yield ($context["text_on"] ?? null);
        yield "</td>
                <td class=\"text-center\">";
        // line 149
        if (($context["curl"] ?? null)) {
            // line 150
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 152
            yield "                  <span class=\"text-danger\"><i class=\"fa fa-minus-circle\"></i></span>
                  ";
        }
        // line 153
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 156
        yield ($context["text_openssl"] ?? null);
        yield "</td>
                <td>";
        // line 157
        if (($context["openssl"] ?? null)) {
            // line 158
            yield "                  ";
            yield ($context["text_on"] ?? null);
            yield "
                  ";
        } else {
            // line 160
            yield "                  ";
            yield ($context["text_off"] ?? null);
            yield "
                  ";
        }
        // line 161
        yield "</td>
                <td>";
        // line 162
        yield ($context["text_on"] ?? null);
        yield "</td>
                <td class=\"text-center\">";
        // line 163
        if (($context["openssl"] ?? null)) {
            // line 164
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 166
            yield "                  <span class=\"text-danger\"><i class=\"fa fa-minus-circle\"></i></span>
                  ";
        }
        // line 167
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 170
        yield ($context["text_zlib"] ?? null);
        yield "</td>
                <td>";
        // line 171
        if (($context["zlib"] ?? null)) {
            // line 172
            yield "                  ";
            yield ($context["text_on"] ?? null);
            yield "
                  ";
        } else {
            // line 174
            yield "                  ";
            yield ($context["text_off"] ?? null);
            yield "
                  ";
        }
        // line 175
        yield "</td>
                <td>";
        // line 176
        yield ($context["text_on"] ?? null);
        yield "</td>
                <td class=\"text-center\">";
        // line 177
        if (($context["zlib"] ?? null)) {
            // line 178
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 180
            yield "                  <span class=\"text-danger\"><i class=\"fa fa-minus-circle\"></i></span>
                  ";
        }
        // line 181
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 184
        yield ($context["text_zip"] ?? null);
        yield "</td>
                <td>";
        // line 185
        if (($context["zip"] ?? null)) {
            // line 186
            yield "                  ";
            yield ($context["text_on"] ?? null);
            yield "
                  ";
        } else {
            // line 188
            yield "                  ";
            yield ($context["text_off"] ?? null);
            yield "
                  ";
        }
        // line 189
        yield "</td>
                <td>";
        // line 190
        yield ($context["text_on"] ?? null);
        yield "</td>
                <td class=\"text-center\">";
        // line 191
        if (($context["zip"] ?? null)) {
            // line 192
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 194
            yield "                  <span class=\"text-danger\"><i class=\"fa fa-minus-circle\"></i></span>
                  ";
        }
        // line 195
        yield "</td>
              </tr>
              ";
        // line 197
        if ( !($context["iconv"] ?? null)) {
            // line 198
            yield "              <tr>
                <td>";
            // line 199
            yield ($context["text_mbstring"] ?? null);
            yield "</td>
                <td>";
            // line 200
            if (($context["mbstring"] ?? null)) {
                // line 201
                yield "                  ";
                yield ($context["text_on"] ?? null);
                yield "
                  ";
            } else {
                // line 203
                yield "                  ";
                yield ($context["text_off"] ?? null);
                yield "
                  ";
            }
            // line 204
            yield "</td>
                <td>";
            // line 205
            yield ($context["text_on"] ?? null);
            yield "</td>
                <td class=\"text-center\">";
            // line 206
            if (($context["mbstring"] ?? null)) {
                // line 207
                yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
            } else {
                // line 209
                yield "                  <span class=\"text-danger\"><i class=\"fa fa-minus-circle\"></i></span>
                  ";
            }
            // line 210
            yield "</td>
              </tr>
              ";
        }
        // line 213
        yield "            </tbody>
          </table>
        </fieldset>
        <p>";
        // line 216
        yield ($context["text_install_file"] ?? null);
        yield "</p>
        <fieldset>
          <table class=\"table\">
            <thead>
              <tr>
                <td width=\"85%\"><b>";
        // line 221
        yield ($context["text_file"] ?? null);
        yield "</b></td>
                <td width=\"15%\" class=\"text-center\"><b>";
        // line 222
        yield ($context["text_status"] ?? null);
        yield "</b></td>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>";
        // line 227
        yield ($context["catalog_config"] ?? null);
        yield "</td>
                <td class=\"text-center\">";
        // line 228
        if ( !($context["error_catalog_config"] ?? null)) {
            // line 229
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 231
            yield "                  <span class=\"text-danger\">";
            yield ($context["error_catalog_config"] ?? null);
            yield "</span>
                  ";
        }
        // line 232
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 235
        yield ($context["admin_config"] ?? null);
        yield "</td>
                <td class=\"text-center\">";
        // line 236
        if ( !($context["error_admin_config"] ?? null)) {
            // line 237
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 239
            yield "                  <span class=\"text-danger\">";
            yield ($context["error_admin_config"] ?? null);
            yield "</span>
                  ";
        }
        // line 240
        yield "</td>
              </tr>
            </tbody>
          </table>
        </fieldset>
        <p>";
        // line 245
        yield ($context["text_install_directory"] ?? null);
        yield "</p>
        <fieldset>
          <table class=\"table\">
            <thead>
              <tr>
                <td width=\"85%\"><b>";
        // line 250
        yield ($context["text_directory"] ?? null);
        yield "</b></td>
                <td width=\"15%\" align=\"center\"><b>";
        // line 251
        yield ($context["text_status"] ?? null);
        yield "</b></td>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>";
        // line 256
        yield ($context["image"] ?? null);
        yield "/</td>
                <td class=\"text-center\">";
        // line 257
        if ( !($context["error_image"] ?? null)) {
            // line 258
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 260
            yield "                  <span class=\"text-danger\">";
            yield ($context["error_image"] ?? null);
            yield "</span>
                  ";
        }
        // line 261
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 264
        yield ($context["image_cache"] ?? null);
        yield "/</td>
                <td class=\"text-center\">";
        // line 265
        if ( !($context["error_image_cache"] ?? null)) {
            // line 266
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 268
            yield "                  <span class=\"text-danger\">";
            yield ($context["error_image_cache"] ?? null);
            yield "</span>
                  ";
        }
        // line 269
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 272
        yield ($context["image_catalog"] ?? null);
        yield "/</td>
                <td class=\"text-center\">";
        // line 273
        if ( !($context["error_image_catalog"] ?? null)) {
            // line 274
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 276
            yield "                  <span class=\"text-danger\">";
            yield ($context["error_image_catalog"] ?? null);
            yield "</span>
                  ";
        }
        // line 277
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 280
        yield ($context["cache"] ?? null);
        yield "/</td>
                <td class=\"text-center\">";
        // line 281
        if ( !($context["error_cache"] ?? null)) {
            // line 282
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 284
            yield "                  <span class=\"text-danger\">";
            yield ($context["error_cache"] ?? null);
            yield "</span>
                  ";
        }
        // line 285
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 288
        yield ($context["logs"] ?? null);
        yield "/</td>
                <td class=\"text-center\">";
        // line 289
        if ( !($context["error_logs"] ?? null)) {
            // line 290
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 292
            yield "                  <span class=\"text-danger\">";
            yield ($context["error_logs"] ?? null);
            yield "</span>
                  ";
        }
        // line 293
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 296
        yield ($context["download"] ?? null);
        yield "/</td>
                <td class=\"text-center\">";
        // line 297
        if ( !($context["error_download"] ?? null)) {
            // line 298
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 300
            yield "                  <span class=\"text-danger\">";
            yield ($context["error_download"] ?? null);
            yield "</span>
                  ";
        }
        // line 301
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 304
        yield ($context["upload"] ?? null);
        yield "/</td>
                <td class=\"text-center\">";
        // line 305
        if ( !($context["error_upload"] ?? null)) {
            // line 306
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 308
            yield "                  <span class=\"text-danger\">";
            yield ($context["error_upload"] ?? null);
            yield "</span>
                  ";
        }
        // line 309
        yield "</td>
              </tr>
              <tr>
                <td>";
        // line 312
        yield ($context["modification"] ?? null);
        yield "/</td>
                <td class=\"text-center\">";
        // line 313
        if ( !($context["error_modification"] ?? null)) {
            // line 314
            yield "                  <span class=\"text-success\"><i class=\"fa fa-check-circle\"></i></span>
                  ";
        } else {
            // line 316
            yield "                  <span class=\"text-danger\">";
            yield ($context["error_modification"] ?? null);
            yield "</span>
                  ";
        }
        // line 317
        yield "</td>
              </tr>
            </tbody>
          </table>
        </fieldset>
        <div class=\"buttons\">
          <div class=\"pull-left\"><a href=\"";
        // line 323
        yield ($context["back"] ?? null);
        yield "\" class=\"btn btn-default\">";
        yield ($context["button_back"] ?? null);
        yield "</a></div>
          <div class=\"pull-right\">
            <input type=\"submit\" value=\"";
        // line 325
        yield ($context["button_continue"] ?? null);
        yield "\" class=\"btn btn-primary\" />
          </div>
        </div>
      </form>
    </div>
    <div class=\"col-sm-3\">";
        // line 330
        yield ($context["column_left"] ?? null);
        yield "</div>
  </div>
</div>
";
        // line 333
        yield ($context["footer"] ?? null);
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "install/step_2.twig";
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
        return array (  883 => 333,  877 => 330,  869 => 325,  862 => 323,  854 => 317,  848 => 316,  844 => 314,  842 => 313,  838 => 312,  833 => 309,  827 => 308,  823 => 306,  821 => 305,  817 => 304,  812 => 301,  806 => 300,  802 => 298,  800 => 297,  796 => 296,  791 => 293,  785 => 292,  781 => 290,  779 => 289,  775 => 288,  770 => 285,  764 => 284,  760 => 282,  758 => 281,  754 => 280,  749 => 277,  743 => 276,  739 => 274,  737 => 273,  733 => 272,  728 => 269,  722 => 268,  718 => 266,  716 => 265,  712 => 264,  707 => 261,  701 => 260,  697 => 258,  695 => 257,  691 => 256,  683 => 251,  679 => 250,  671 => 245,  664 => 240,  658 => 239,  654 => 237,  652 => 236,  648 => 235,  643 => 232,  637 => 231,  633 => 229,  631 => 228,  627 => 227,  619 => 222,  615 => 221,  607 => 216,  602 => 213,  597 => 210,  593 => 209,  589 => 207,  587 => 206,  583 => 205,  580 => 204,  574 => 203,  568 => 201,  566 => 200,  562 => 199,  559 => 198,  557 => 197,  553 => 195,  549 => 194,  545 => 192,  543 => 191,  539 => 190,  536 => 189,  530 => 188,  524 => 186,  522 => 185,  518 => 184,  513 => 181,  509 => 180,  505 => 178,  503 => 177,  499 => 176,  496 => 175,  490 => 174,  484 => 172,  482 => 171,  478 => 170,  473 => 167,  469 => 166,  465 => 164,  463 => 163,  459 => 162,  456 => 161,  450 => 160,  444 => 158,  442 => 157,  438 => 156,  433 => 153,  429 => 152,  425 => 150,  423 => 149,  419 => 148,  416 => 147,  410 => 146,  404 => 144,  402 => 143,  398 => 142,  393 => 139,  389 => 138,  385 => 136,  383 => 135,  379 => 134,  376 => 133,  370 => 132,  364 => 130,  362 => 129,  358 => 128,  353 => 125,  349 => 124,  345 => 122,  343 => 121,  339 => 120,  336 => 119,  330 => 118,  324 => 116,  322 => 115,  318 => 114,  310 => 109,  306 => 108,  302 => 107,  298 => 106,  290 => 101,  283 => 96,  279 => 95,  275 => 93,  273 => 92,  269 => 91,  266 => 90,  260 => 89,  254 => 87,  252 => 86,  248 => 85,  243 => 82,  239 => 81,  235 => 79,  233 => 78,  229 => 77,  226 => 76,  220 => 75,  214 => 73,  212 => 72,  208 => 71,  203 => 68,  199 => 67,  195 => 65,  193 => 64,  189 => 63,  186 => 62,  180 => 61,  174 => 59,  172 => 58,  168 => 57,  163 => 54,  159 => 53,  155 => 51,  153 => 50,  149 => 49,  146 => 48,  140 => 47,  134 => 45,  132 => 44,  128 => 43,  123 => 40,  119 => 39,  115 => 37,  113 => 36,  108 => 34,  104 => 33,  96 => 28,  92 => 27,  88 => 26,  84 => 25,  76 => 20,  72 => 19,  68 => 17,  60 => 13,  58 => 12,  51 => 8,  47 => 7,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "install/step_2.twig", "");
    }
}
