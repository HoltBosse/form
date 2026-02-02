<?php

use HoltBosse\Form\Fields\Select\Select;

beforeEach(function () {
    $_POST = [];
    $_GET = [];
});

test('can be instantiated', function () {
    $s = new Select();
    expect($s)->toBeInstanceOf(Select::class);
});

test('display renders placeholder option when placeholder set', function () {
    $s = new Select();
    $s->name = 'sel';
    $s->id = 'selid';
    $s->label = 'Choose';
    $s->placeholder = 'Pick one';
    $s->select_options = [];

    ob_start();
    $s->display();
    $out = ob_get_clean();

    expect($out)->toContain("<option value='' >Pick one</option>");
});

test('display renders empty_string option when not required', function () {
    $s = new Select();
    $s->name = 'sel';
    $s->id = 'selid';
    $s->label = 'Choose';
    $s->empty_string = 'None';
    $s->select_options = [];

    ob_start();
    $s->display();
    $out = ob_get_clean();

    expect($out)->toContain("<option value='0' >None</option>");
});

test('display marks selected option and supports multiple defaults', function () {
    $s = new Select();
    $s->name = 'sel';
    $s->id = 'selid';
    $s->label = 'Choose';
    $s->select_options = [
        (object)['text' => 'One', 'value' => '1'],
        (object)['text' => 'Two', 'value' => '2'],
    ];
    $s->default = '2';

    ob_start();
    $s->display();
    $out = ob_get_clean();

    expect($out)->toContain("value='2'");
    expect($out)->toContain('selected');

    // multiple selection default
    $s2 = new Select();
    $s2->name = 'msel';
    $s2->id = 'mselid';
    $s2->label = 'Many';
    $s2->multiple = true;
    $s2->select_options = [
        (object)['text' => 'One', 'value' => '1'],
        (object)['text' => 'Two', 'value' => '2'],
    ];
    $s2->default = json_encode(['1']);

    ob_start();
    $s2->display();
    $out2 = ob_get_clean();

    expect($out2)->toContain("value='1'");
    expect($out2)->toContain('selected');
});

test('loadFromConfig sets array filter when multiple', function () {
    $s = new Select();
    $cfg = new stdClass();
    $cfg->multiple = true;
    $s->loadFromConfig($cfg);

    expect($s->filter->isValid(['a']))->toBeTrue();
    expect($s->filter->isValid('a'))->toBeFalse();
});

test('validate returns false when required and missing', function () {
    $s = new Select();
    $s->name = 'sel';
    $s->required = true;

    unset($_POST['sel']);

    expect($s->validate())->toBeFalse();
});
