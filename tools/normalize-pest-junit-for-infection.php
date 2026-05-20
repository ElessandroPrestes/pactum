<?php

declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: normalize-pest-junit-for-infection.php <junit.xml> <project-root>\n");
    exit(1);
}

[$script, $junitPath, $projectRoot] = $argv;
unset($script);

if (! is_file($junitPath)) {
    fwrite(STDERR, "JUnit report not found: {$junitPath}\n");
    exit(1);
}

$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;

if (@$dom->load($junitPath) !== true) {
    fwrite(STDERR, "Unable to read JUnit report: {$junitPath}\n");
    exit(1);
}

$xpath = new DOMXPath($dom);
$projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);

foreach ($xpath->query('//testsuite[@file] | //testcase[@file]') as $node) {
    if (! $node instanceof DOMElement) {
        continue;
    }

    $node->setAttribute('file', normalizeFilePath($node->getAttribute('file'), $projectRoot));
}

foreach ($xpath->query('//testsuite[@name]') as $node) {
    if (! $node instanceof DOMElement) {
        continue;
    }

    $name = $node->getAttribute('name');

    if (isPestGeneratedClassName($name)) {
        $node->setAttribute('name', 'P\\'.$name);
    }
}

foreach ($xpath->query('//testcase[@class]') as $node) {
    if (! $node instanceof DOMElement) {
        continue;
    }

    $class = $node->getAttribute('class');

    if (isPestGeneratedClassName($class)) {
        $node->setAttribute('class', 'P\\'.$class);
    }

    $className = $node->getAttribute('classname');

    if ($className !== '' && ! str_starts_with($className, 'P.')) {
        $node->setAttribute('classname', 'P.'.$className);
    }
}

$dom->save($junitPath);

function normalizeFilePath(string $filePath, string $projectRoot): string
{
    if ($filePath === '') {
        return $filePath;
    }

    $methodSeparatorPosition = strpos($filePath, '::');

    if ($methodSeparatorPosition !== false) {
        $filePath = substr($filePath, 0, $methodSeparatorPosition);
    }

    if (str_starts_with($filePath, DIRECTORY_SEPARATOR)) {
        return $filePath;
    }

    return $projectRoot.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);
}

function isPestGeneratedClassName(string $className): bool
{
    return $className !== ''
        && ! str_starts_with($className, 'P\\')
        && str_starts_with($className, 'Tests\\');
}
