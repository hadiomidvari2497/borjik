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
    }
}
