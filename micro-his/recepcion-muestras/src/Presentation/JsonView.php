<?php

declare(strict_types=1);

namespace Reception\Presentation;

final class JsonView
{
    public function render(array $response): string
    {
        if (isset($response['data'])) {
            $response['data'] = array_intersect_key($response['data'], array_flip(['id', 'status', 'reason', 'code', 'received_at']));
        }

        return json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
    }
}
