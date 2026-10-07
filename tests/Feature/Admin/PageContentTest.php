<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\User;

/**
 * *Seiteninhalte → Startseite: Über uns* — `/api/admin/pages/{page}/content`
 * ([[PageContentController]]): the homepage About teaser's copy, the mockup's
 * until it is edited.
 */
beforeEach(function () {
	$this->admin = User::factory()->admin()->create(['email_verified_at' => now()]);
});

it('keeps the copy to admins', function () {
	$this->actingAs(User::factory()->expert()->create())->getJson('/api/admin/pages/home-about/content')->assertForbidden();
});

it('hands out the mockup’s copy until it is edited, keyed by the page', function () {
	$this->actingAs($this->admin)->getJson('/api/admin/pages/home-about/content')
		->assertOk()
		->assertJsonPath('data.uuid', 'home-about')
		->assertJsonPath('data.title', 'Warum bei der VIAK');
});

it('saves the heading and the text, the text sanitised', function () {
	$this->actingAs($this->admin)->putJson('/api/admin/pages/home-about/content', [
		'title' => 'Wer wir sind',
		'text' => '<p>Ein Studio.</p><script>alert(1)</script>',
	])->assertOk()->assertJsonPath('data.title', 'Wer wir sind');

	expect(Page::for('home-about')->content)->toEqual(['title' => 'Wer wir sind', 'text' => '<p>Ein Studio.</p>']);
});

it('asks for both', function () {
	$this->actingAs($this->admin)->putJson('/api/admin/pages/home-about/content', ['title' => '', 'text' => ''])
		->assertUnprocessable()
		->assertJsonValidationErrors(['title', 'text']);
});

it('has no copy to edit on a page without a form', function () {
	$this->actingAs($this->admin)->getJson('/api/admin/pages/firmenschulung/content')->assertNotFound();
	$this->getJson('/api/admin/pages/nirgends/content')->assertNotFound();
});
