<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MailboxRoutesTest extends TestCase
{
    public function test_mailbox_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('admin.mailbox.index'));
        $this->assertTrue(Route::has('admin.mailbox.show'));
        $this->assertTrue(Route::has('admin.mailbox.settings'));
        $this->assertTrue(Route::has('admin.mailbox.settings.update'));
        $this->assertTrue(Route::has('admin.mailbox.accounts.store'));
        $this->assertTrue(Route::has('admin.mailbox.login'));
        $this->assertTrue(Route::has('admin.mailbox.logout'));
        $this->assertTrue(Route::has('admin.mailbox.accounts.destroy'));
        $this->assertTrue(Route::has('admin.mailbox.compose'));
        $this->assertTrue(Route::has('admin.mailbox.send'));
    }
}
