<?php

namespace Modules\PoliwangiCustomField\Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\User;
use App\Mailbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PoliwangiCustomField\Models\CustomField;

class CustomFieldTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $mailbox;

    protected static $customFieldMigrated = false;

    protected function refreshTestDatabase()
    {
        if (! static::$customFieldMigrated) {
            $this->artisan('migrate:fresh');
            \Artisan::call('migrate', ['--path' => 'Modules/PoliwangiCustomField/Database/Migrations']);
            $this->app[\Illuminate\Contracts\Console\Kernel::class]->setArtisan(null);
            static::$customFieldMigrated = true;
        }
        $this->beginDatabaseTransaction();
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Register the module service provider to ensure routes and views are loaded
        $this->app->register(\Modules\PoliwangiCustomField\Providers\PoliwangiCustomFieldServiceProvider::class);

        \Session::start();

        $this->user = factory(User::class)->create([
            'role' => User::ROLE_USER,
        ]);

        $this->admin = factory(User::class)->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = factory(Mailbox::class)->create();

        $this->mailbox->users()->sync([
            $this->admin->id
        ]);

        $this->actingAs($this->admin);
    }

    public function test_index_page_loads()
    {
        CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'nama_field' => 'Jurusan',
            'type_field' => 'text',
        ]);

        $response = $this->get(route('PoliwangiPortal.custom_fields', ['mailbox_id' => $this->mailbox->id]));

        $response->assertStatus(200);
        $response->assertViewIs('poliwangicustomfield::custom_field');
        $this->assertStringContainsString('Jurusan', $response->getContent());
    }

    public function test_store_valid_text_field()
    {
        $response = $this->post(route('PoliwangiPortal.custom_fields.store', ['mailbox_id' => $this->mailbox->id]), [
            'nama_field' => 'Fakultas',
            'type_field' => 'text',
            'required' => '1',
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect(route('PoliwangiPortal.custom_fields', ['mailbox_id' => $this->mailbox->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('custom_fields', [
            'nama_field' => 'Fakultas',
            'type_field' => 'text',
            'required' => 1,
            'mailbox_id' => $this->mailbox->id,
            'options' => null
        ]);
    }

    public function test_store_valid_dropdown_field()
    {
        $response = $this->post(route('PoliwangiPortal.custom_fields.store', ['mailbox_id' => $this->mailbox->id]), [
            'nama_field' => 'Kategori',
            'type_field' => 'dropdown',
            'options' => "IT\nHR\nFinance",
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect(route('PoliwangiPortal.custom_fields', ['mailbox_id' => $this->mailbox->id]));
        $response->assertSessionHas('success');

        $field = CustomField::where('nama_field', 'Kategori')->first();
        $this->assertNotNull($field);
        $this->assertEquals(['IT', 'HR', 'Finance'], $field->options);
    }

    public function test_store_invalid_type()
    {
        $this->withExceptionHandling();

        $response = $this->post(route('PoliwangiPortal.custom_fields.store', ['mailbox_id' => $this->mailbox->id]), [
            'nama_field' => 'Invalid Type',
            'type_field' => 'invalid_type',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('type_field');
    }

    public function test_store_dropdown_missing_options()
    {
        $this->withExceptionHandling();

        $response = $this->post(route('PoliwangiPortal.custom_fields.store', ['mailbox_id' => $this->mailbox->id]), [
            'nama_field' => 'Kategori Empty',
            'type_field' => 'dropdown',
            'options' => '',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('options');
        $errors = session('errors');
        $this->assertStringContainsString('Options are required', $errors->first('options'));
    }

    public function test_store_text_with_options()
    {
        $this->withExceptionHandling();

        $response = $this->post(route('PoliwangiPortal.custom_fields.store', ['mailbox_id' => $this->mailbox->id]), [
            'nama_field' => 'Text With Options',
            'type_field' => 'text',
            'options' => 'Should not have options',
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('options');
        $errors = session('errors');
        $this->assertStringContainsString('Options are only allowed for Dropdown', $errors->first('options'));
    }

    public function test_prepare_options_empty_after_trim()
    {
        $this->withExceptionHandling();

        $response = $this->post(route('PoliwangiPortal.custom_fields.store', ['mailbox_id' => $this->mailbox->id]), [
            'nama_field' => 'Empty Option',
            'type_field' => 'dropdown',
            'options' => "   \n , ",
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('options');
        $errors = session('errors');
        $this->assertStringContainsString('At least one option is required.', $errors->first('options'));
    }

    public function test_prepare_options_too_many()
    {
        $this->withExceptionHandling();

        $options = implode("\n", range(1, 101));

        $response = $this->post(route('PoliwangiPortal.custom_fields.store', ['mailbox_id' => $this->mailbox->id]), [
            'nama_field' => 'Too Many Options',
            'type_field' => 'dropdown',
            'options' => $options,
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('options');
        $errors = session('errors');
        $this->assertStringContainsString('contain more than 100 items', $errors->first('options'));
    }

    public function test_prepare_options_too_long()
    {
        $this->withExceptionHandling();

        $options = "Option1\n" . str_repeat("A", 192);

        $response = $this->post(route('PoliwangiPortal.custom_fields.store', ['mailbox_id' => $this->mailbox->id]), [
            'nama_field' => 'Long Option',
            'type_field' => 'dropdown',
            'options' => $options,
            '_token' => csrf_token(),
        ]);

        $response->assertSessionHasErrors('options');
        $errors = session('errors');
        $this->assertStringContainsString('greater than 191 characters', $errors->first('options'));
    }

    public function test_update_valid_field()
    {
        $field = CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'nama_field' => 'Jurusan',
            'type_field' => 'text',
        ]);

        $response = $this->put(route('PoliwangiPortal.custom_fields.update', ['mailbox_id' => $this->mailbox->id, 'field_id' => $field->id]), [
            'nama_field' => 'Jurusan Updated',
            'type_field' => 'dropdown',
            'options' => 'A,B,C',
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect(route('PoliwangiPortal.custom_fields', ['mailbox_id' => $this->mailbox->id]));
        
        $this->assertDatabaseHas('custom_fields', [
            'id' => $field->id,
            'nama_field' => 'Jurusan Updated',
            'type_field' => 'dropdown',
        ]);
    }

    public function test_update_not_found_field()
    {
        $this->withExceptionHandling();

        $response = $this->put(route('PoliwangiPortal.custom_fields.update', [$this->mailbox->id, 9999]), [
            'nama_field' => 'Ghost',
            'type_field' => 'text',
            '_token' => csrf_token(),
        ]);

        $response->assertStatus(404);
    }

    public function test_destroy_field()
    {
        $field = CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'nama_field' => 'To Delete',
            'type_field' => 'text',
        ]);

        $response = $this->delete(route('PoliwangiPortal.custom_fields.destroy', ['mailbox_id' => $this->mailbox->id, 'field_id' => $field->id]), [
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect(route('PoliwangiPortal.custom_fields', ['mailbox_id' => $this->mailbox->id]));
        $this->assertDatabaseMissing('custom_fields', [
            'id' => $field->id,
        ]);
    }

    public function test_get_by_mailbox_json()
    {
        CustomField::create([
            'mailbox_id' => $this->mailbox->id,
            'nama_field' => 'JSON Field',
            'type_field' => 'text',
        ]);

        $response = $this->get(route('PoliwangiPortal.custom_fields.json', ['mailbox_id' => $this->mailbox->id]));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'nama_field' => 'JSON Field',
            'type_field' => 'text',
        ]);
    }

    public function test_authorize_settings_fails_for_unauthorized_user()
    {
        $this->withExceptionHandling();
        $this->actingAs($this->user);

        $response = $this->get(route('PoliwangiPortal.custom_fields', ['mailbox_id' => $this->mailbox->id]));

        $response->assertStatus(403);
    }
}
