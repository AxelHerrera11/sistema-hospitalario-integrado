<?php

namespace Tests\Feature\Rbac;

use Tests\TestCase;

class ReviewedPullRequestsTest extends TestCase
{
    public function test_reviewed_heads_are_unique_and_match_the_contract_snapshot(): void
    {
        $reviewed = json_decode(file_get_contents(base_path('tests/Fixtures/Rbac/reviewed-pull-requests.json')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($reviewed);
        $heads = [];
        foreach ($reviewed as $pr) {
            $this->assertArrayNotHasKey($pr['pr'], $heads);
            $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/D', $pr['sha']);
            $heads[$pr['pr']] = $pr['sha'];
        }

        $contracts = json_decode(file_get_contents(base_path('tests/Fixtures/Rbac/pending-contracts.json')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($contracts as $contract) {
            $this->assertArrayHasKey($contract['pr'], $heads);
            $this->assertSame($heads[$contract['pr']], $contract['sha']);
            $this->assertNotEmpty($contract['permissions']);
        }
    }
}
