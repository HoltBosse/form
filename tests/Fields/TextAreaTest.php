<?php

use HoltBosse\Form\Fields\Textarea\Textarea;
use HoltBosse\Form\Input;

describe('Textarea', function () {
    beforeEach(function () {
        // clear superglobals between tests
        $_POST = [];
        $_GET = [];
    });

    it('renders a textarea with placeholder and default value (newlines preserved)', function () {
        $t = new Textarea();
        $t->name = 'myfield';
        $t->id = 'myid';
        $t->label = 'My Label';
        $t->placeholder = 'Enter text';
        $t->maxlength = 200;
        $t->minlength = 2;
        $t->default = "Line1[NEWLINE]Line2";

        // compute expected data-value before display() mutates $t->default
        $expectedDataValue = Input::stringHtmlSafe(htmlspecialchars_decode($t->default));

        ob_start();
        $t->display();
        $out = ob_get_clean();

        expect($out)->toContain('<textarea');
        // textarea content should contain an actual newline
        expect($out)->toContain("Line1\nLine2");
        // data-value uses HTML-safe version with [NEWLINE] preserved
        expect($out)->toContain("data-value='$expectedDataValue'");
        expect($out)->toContain("placeholder='Enter text'");
    });

    it('setFromSubmit converts real newlines to [NEWLINE]', function () {
        $_POST['ta'] = "A\nB";

        $t = new Textarea();
        $t->name = 'ta';

        $t->setFromSubmit();

        expect($t->default)->toBe('A[NEWLINE]B');
    });

    it('getFriendlyValue returns HTML with <br> when requested', function () {
        $t = new Textarea();
        $t->default = 'Hello[NEWLINE]<b>bold</b>';

        $helpful = new class { public $return_in_text_html_form = true; };

        $friendly = $t->getFriendlyValue($helpful);

        expect($friendly)->toBe('Hello<br>&lt;b&gt;bold&lt;/b&gt;');
    });

    it('validate returns false when required and missing', function () {
        $t = new Textarea();
        $t->name = 'req';
        $t->required = true;

        // ensure no POST value
        unset($_POST['req']);

        expect($t->validate())->toBe(false);
    });

    it('validate returns true when not required or present', function () {
        $t = new Textarea();
        $t->name = 'opt';
        $t->required = false;

        expect($t->validate())->toBe(true);
    });
});
