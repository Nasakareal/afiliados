<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    public function test_privacy_policy_is_publicly_accessible(): void
    {
        $response = $this->get('/politica-de-privacidad');

        $response
            ->assertOk()
            ->assertSee('Política de privacidad')
            ->assertSee('contacto@gladyadorez.com');
    }

    public function test_privacy_policy_alias_redirects_to_canonical_url(): void
    {
        $this->get('/politica-privacidad')
            ->assertRedirect('/politica-de-privacidad');
    }
}
