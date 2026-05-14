<?php

declare(strict_types=1);

test('a aplicacao responde com sucesso na raiz', function () {
    $this->get('/')->assertStatus(200);
});
