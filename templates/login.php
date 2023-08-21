<?php

use Moloni\Tools;
use Moloni\Error;

if (Error::$exists && !empty(Error::$error)) {
    echo "<div class='login-error'>" . Error::$error['where'] . " - " . Error::$error['message'] . "</div>";
}

?>

<link rel="stylesheet" type="text/css" href="<?= Tools::getPublicUrl('compiled.min.css') ?>"/>

<form class='moloni-login-form' action='<?= Tools::genURL() ?>' method='POST' autocomplete="off">
    <a href='https://moloni.pt' target='_BLANK'>
        <img src='<?= Tools::getPublicUrl('img/logo.png') ?>' class='moloni-logo--login' alt="">
    </a>

    <div class="group">
        <input id="mol-username" type="email" name='mol-username' autocomplete="false"
               onfocus="this.removeAttribute('readonly');"
               readonly><span class="highlight"></span><span class="bar"></span>
        <label for="mol-username">Email</label>
    </div>

    <div class="group">
        <input id="mol-password" type="password" name='mol-password' autocomplete="false"
               onfocus="this.removeAttribute('readonly');"
               readonly><span class="highlight"></span><span class="bar"></span>
        <label for="mol-password" class='pwd-fix'>Palavra-chave</label>
    </div>

    <?php

    if (!empty($this->message)) {
        if (isset($this->message['text']) && !empty($this->message['text'])) {
            echo "<div class='login-error'>";
            echo $this->message['text'];
            echo "</div>";
        }

        if (isset($this->message['data']) && !empty($this->message['data'])) {
            echo '<pre class="login-error-data">';
            echo $this->message['data'];
            echo '</pre>';
        }
    }

    ?>

    <button type="button" class="button buttonBlue">
        Entrar
        <div class="ripples buttonRipples">
            <span class="ripplesCircle"></span>
        </div>
    </button>
</form>

<script>
    $(window, document, undefined).ready(function() {

        $('input').blur(function() {
            var $this = $(this);
            if ($this.val())
                $this.addClass('used');
            else
                $this.removeClass('used');
        });

        var $ripples = $('.ripples');

        $ripples.on('click.Ripples', function(e) {

            var $this = $(this);
            var $offset = $this.parent().offset();
            var $circle = $this.find('.ripplesCircle');

            var x = e.pageX - $offset.left;
            var y = e.pageY - $offset.top;

            $circle.css({
                top: y + 'px',
                left: x + 'px'
            });

            $this.addClass('is-active');

        });

        $ripples.on('animationend webkitAnimationEnd mozAnimationEnd oanimationend MSAnimationEnd', function(e) {
            $(this).removeClass('is-active');
        });

    });

    $(".moloni-login-form .button").click(function() {
        $(".moloni-login-form").submit();
    });

    $(".login-error").click(function() {
        $(this).slideUp(500);
    });
</script>
