<?php

namespace Tests\Feature;

use App\Models\EvidenceSubmission;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppEvidenceWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_receives_whatsapp_image_and_links_to_nsrdi_log(): void
    {
        // 1. Seed an NSRDI log
        $nsrdi = NsrdiLog::create([
            'nsrdi_number' => 'NSRDI-8812',
            'aircraft_registration' => 'PK-GAA',
            'description' => 'Cabin seat recliner stuck',
            'status' => 'Open',
        ]);

        $testBinary = base64_encode('fake-image-binary-stream');

        // 2. Send webhook payload
        $payload = [
            'from' => '120363029182391238@g.us',
            'sender' => '628123456789',
            'sender_name' => 'Teknisi Budi',
            'caption' => "T/C TYPE: RECTIFICATION\nDOC TYPE: NSRDI\nNO DOC: NSRDI-8812",
            'base64' => $testBinary,
            'mime_type' => 'image/jpeg',
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('result.processed', true)
            ->assertJsonPath('result.matched', true)
            ->assertJsonPath('result.doc_type', 'NSRDI')
            ->assertJsonPath('result.doc_no', 'NSRDI-8812');

        // 3. Verify NSRDI log was updated with evidence path
        $nsrdi->refresh();
        $this->assertNotNull($nsrdi->evidence_path);
        $this->assertStringContainsString('NSRDI-8812', $nsrdi->evidence_path);

        // 4. Verify EvidenceSubmission was recorded
        $submission = EvidenceSubmission::first();
        $this->assertNotNull($submission);
        $this->assertSame('NSRDI', $submission->doc_type);
        $this->assertSame('NSRDI-8812', $submission->doc_no);
        $this->assertSame('RECTIFICATION', $submission->tc_type);
        $this->assertSame('628123456789', $submission->sender_phone);
        $this->assertSame('linked', $submission->status);
    }

    public function test_webhook_links_to_wo_log(): void
    {
        $wo = WoLog::create([
            'wo_number' => 'WO-99120',
            'aircraft_registration' => 'PK-GAB',
            'description' => 'Tray table replacement',
            'status' => 'Open',
        ]);

        $payload = [
            'from' => '120363029182391238@g.us',
            'sender' => '628987654321',
            'sender_name' => 'Teknisi Andi',
            'caption' => 'TC: REPAIR, DOC: WO, NO: WO-99120',
            'base64' => base64_encode('fake-image-bytes'),
            'mime_type' => 'image/jpeg',
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('result.processed', true)
            ->assertJsonPath('result.matched', true);

        $wo->refresh();
        $this->assertNotNull($wo->evidence_path);
        $this->assertStringContainsString('WO-99120', $wo->evidence_path);
    }

    public function test_webhook_ignores_message_without_valid_format(): void
    {
        $payload = [
            'from' => '120363029182391238@g.us',
            'sender' => '628123456789',
            'caption' => 'Halo selamat malam semua izin share info',
            'base64' => base64_encode('fake-image'),
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('result.processed', false);

        $this->assertSame(0, EvidenceSubmission::count());
    }
}
