<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEndUserPortalAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('end_user_portal_accounts', function (Blueprint $table) {
            $table->increments('id');

            /*
             * Relasi ke data customer dan email bawaan FreeScout.
             * Akun pelapor dibuat global, sehingga tidak diikat ke mailbox tertentu.
             */
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('email_id');

            /*
             * Jenis autentikasi pelapor.
             * password = login lokal menggunakan email dan password
             * sso      = login menggunakan SSO Poliwangi
             */
            $table->string('auth_type', 30)->default('password');

            /*
             * Password hanya digunakan untuk akun lokal.
             * Untuk akun SSO, password boleh kosong.
             */
            $table->string('password')->nullable();

            /*
             * Data tambahan untuk akun SSO.
             * Contoh:
             * sso_provider = poliwangi
             * sso_id       = NIM / NIP / email dari SSO
             */
            $table->string('sso_provider', 100)->nullable();
            $table->string('sso_id', 191)->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('verification_token')->nullable();

            $table->timestamps();

            /*
             * Satu email hanya boleh punya satu akun portal.
             * Akun ini bisa dipakai untuk semua mailbox layanan.
             */
            $table->unique('email_id', 'eupa_email_unique');

            $table->index('customer_id');
            $table->index('email_id');
            $table->index('auth_type');
            $table->index('sso_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('end_user_portal_accounts');
    }
}
