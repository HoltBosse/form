<?php

use HoltBosse\Form\Fields\Antispam\Antispam;

describe('Antispam', function () {
    beforeEach(function () {
        $_POST = [];
        $_GET = [];
    });

    it('can be instantiated', function () {
        $a = new Antispam();
        expect($a)->toBeInstanceOf(Antispam::class);
    });

    it('endsWithRu detects .ru endings', function () {
        expect(Antispam::endsWithRu('example.ru'))->toBe(true);
        expect(Antispam::endsWithRu('example.com'))->toBe(false);
        expect(Antispam::endsWithRu('ru'))->toBe(false);
    });

    it('returns true when in a repeatable form', function () {
        $a = new Antispam();
        $a->in_repeatable_form = true;
        expect($a->validate())->toBe(true);
    });

    it('blocks values containing http(s) when block_urls enabled', function () {
        $a = new Antispam();
        $a->fieldname = 'f';
        $a->block_urls = true;
        $_POST['f'] = 'Visit http://example.com for info';
        expect($a->validate())->toBe(false);
    });

    it('blocks Cyrillic characters when charset_check enabled', function () {
        $a = new Antispam();
        $a->fieldname = 'f';
        $a->charset_check = true;
        $_POST['f'] = 'привет';
        expect($a->validate())->toBe(false);
    });

    it('blocks values ending with .ru when ends_with_ru_check enabled', function () {
        $a = new Antispam();
        $a->fieldname = 'f';
        $a->ends_with_ru_check = true;
        $_POST['f'] = 'example.ru';
        expect($a->validate())->toBe(false);
    });

    it('blocks BBCode url tags when bbcode_url_check enabled', function () {
        $a = new Antispam();
        $a->fieldname = 'f';
        $a->bbcode_url_check = true;
        $_POST['f'] = '[url=http://x]x[/url]';
        expect($a->validate())->toBe(false);
    });

    it('checks blacklist file when use_blacklist enabled', function () {
        $a = new Antispam();
        $a->fieldname = 'f';
        $a->use_blacklist = true;

        $tmp = sys_get_temp_dir() . '/antispam_blacklist.txt';
        file_put_contents($tmp, "badword\nother\n");
        $a->blacklist_location = $tmp;

        $_POST['f'] = 'this contains badword inside';
        expect($a->validate())->toBe(false);

        // clean up
        @unlink($tmp);
    });

    it('returns true when no checks are triggered', function () {
        $a = new Antispam();
        $a->fieldname = 'f';
        $_POST['f'] = 'a normal message';
        expect($a->validate())->toBe(true);
    });
});
