<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

it('normaliza classes e arquivos do junit do pest para o infection', function () {
    $projectRoot = dirname(__DIR__, 3);
    $junitPath = sys_get_temp_dir().'/pactum-junit-'.bin2hex(random_bytes(8)).'.xml';

    file_put_contents($junitPath, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
  <testsuite name="Tests\Unit\Services\ContractServiceTest" file="tests/Unit/Services/ContractServiceTest.php::ContractService">
    <testcase name="rejeita criacao" class="Tests\Unit\Services\ContractServiceTest" classname="Tests.Unit.Services.ContractServiceTest" file="tests/Unit/Services/ContractServiceTest.php::rejeita criacao" time="0.1"/>
  </testsuite>
</testsuites>
XML);

    try {
        $process = new Process([
            PHP_BINARY,
            $projectRoot.'/tools/normalize-pest-junit-for-infection.php',
            $junitPath,
            $projectRoot,
        ]);
        $process->mustRun();

        $dom = new DOMDocument();
        $dom->load($junitPath);
        $xpath = new DOMXPath($dom);

        $testSuite = $xpath->query('//testsuite')[0];
        $testCase = $xpath->query('//testcase')[0];

        expect($testSuite->getAttribute('name'))
            ->toBe('P\Tests\Unit\Services\ContractServiceTest')
            ->and($testSuite->getAttribute('file'))
            ->toBe($projectRoot.'/tests/Unit/Services/ContractServiceTest.php')
            ->and($testCase->getAttribute('class'))
            ->toBe('P\Tests\Unit\Services\ContractServiceTest')
            ->and($testCase->getAttribute('classname'))
            ->toBe('P.Tests.Unit.Services.ContractServiceTest')
            ->and($testCase->getAttribute('file'))
            ->toBe($projectRoot.'/tests/Unit/Services/ContractServiceTest.php');
    } finally {
        if (is_file($junitPath)) {
            unlink($junitPath);
        }
    }
});
