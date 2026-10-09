<?php

namespace Tests\Feature;

use App\Models\TerminalMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return ['sheet_name' => 'TERMINAL 1', 'data' => [['date' => '2026-10-09', 'no' => 1, 'registrasi' => 'PK-AAA']]];
    }

    public function test_sheet_sync_is_closed_while_no_token_is_configured(): void
    {
        config(['services.sheets_sync.token' => null]);

        $this->postJson('/api/sync-sheets', $this->payload())->assertForbidden();
        $this->postJson('/api/sync-sheets', $this->payload(), ['X-Sync-Token' => ''])->assertForbidden();
    }

    public function test_sheet_sync_rejects_a_wrong_token_and_keeps_existing_data(): void
    {
        config(['services.sheets_sync.token' => 'right-token']);
        TerminalMovement::create(['terminal_name' => 'TERMINAL 1', 'registration' => 'PK-KEEP']);

        $this->postJson('/api/sync-sheets', $this->payload(), ['X-Sync-Token' => 'wrong'])->assertForbidden();

        $this->assertDatabaseHas('terminal_movements', ['registration' => 'PK-KEEP']);
    }

    public function test_sheet_sync_accepts_the_right_token(): void
    {
        config(['services.sheets_sync.token' => 'right-token']);

        $this->postJson('/api/sync-sheets', $this->payload(), ['X-Sync-Token' => 'right-token'])->assertSuccessful();
        $this->assertDatabaseHas('terminal_movements', ['registration' => 'PK-AAA']);
    }

    public function test_telegram_webhook_needs_the_secret_header(): void
    {
        config(['services.telegram.webhook_secret' => 's3cret']);

        $this->postJson('/api/telegram/webhook', ['message' => ['chat' => ['id' => 1], 'text' => '/ping']])->assertForbidden();
        $this->postJson('/api/telegram/webhook', [], ['X-Telegram-Bot-Api-Secret-Token' => 's3cret'])->assertOk();
    }
}
