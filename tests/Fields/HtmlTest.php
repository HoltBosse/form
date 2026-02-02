<?php
use HoltBosse\Form\Fields\Html\Html;

test('display echoes html', function () {
    $html = new Html();
    $html->html = '<p>Hello</p>';
    ob_start();
    $html->display();
    $output = ob_get_clean();
    expect($output)->toBe('<p>Hello</p>');
});

test('loadFromConfig sets html and save', function () {
    $config = new stdClass();
    $config->html = '<div>Test</div>';
    $config->save = true;

    $html = new Html();
    $html->loadFromConfig($config);

    expect($html->html)->toBe('<div>Test</div>');
    expect($html->save)->toBe(true);
});

test('loadFromConfig defaults html to empty string and save to false', function () {
    $config = new stdClass();

    $html = new Html();
    $html->loadFromConfig($config);

    expect($html->html)->toBe('');
    expect($html->save)->toBe(false);
});
