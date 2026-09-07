<?php declare(strict_types=1);

/**
 * Extracts every fenced PHP block from the documentation and executes it, so a
 * sample that no longer matches the implementation fails loudly instead of
 * quietly misleading a reader.
 *
 * Documentation snippets are written for a reader, not a test runner, so the
 * harness accommodates three shapes:
 *
 *   - Standalone blocks, run as-is.
 *   - Continuation blocks that build on earlier blocks in the same page. Each
 *     page accumulates the declarations and assignments from the blocks above,
 *     mirroring how a reader follows the page top to bottom.
 *   - Fragments — a bare array entry or expression shown out of context. These
 *     are wrapped so they still parse and evaluate.
 *
 * Mark a block `// docs-check: skip <reason>` when it genuinely cannot run.
 *
 * Usage: php tools/check-docs.php [--verbose]
 */

$root = dirname(__DIR__);
$verbose = in_array('--verbose', $argv, true);

$files = array_merge(
    glob($root . '/docs/*.md') ?: [],
    [$root . '/README.md'],
);
sort($files);

$passed = $skipped = 0;
$failures = [];
$total = 0;

foreach ($files as $file) {
    $relative = str_replace([$root . DIRECTORY_SEPARATOR, '\\'], ['', '/'], $file);
    $context = '';

    foreach (extractBlocks($file) as $block) {
        $total++;
        $label = $relative . ':' . $block['line'];
        $code = $block['code'];

        if (preg_match('#//\s*docs-check:\s*skip\s*(.*)#', $code, $reason)) {
            $skipped++;
            echo "S $label" . (trim($reason[1]) !== '' ? " ({$reason[1]})" : '') . "\n";
            continue;
        }

        // A block documenting an optional constraint runs only when the
        // package it needs is installed, so the same checker works whether or
        // not the optional components are present.
        if (preg_match('#//\s*docs-check:\s*requires\s+(\S+)#', $code, $requires)) {
            if (!packageInstalled($root, $requires[1])) {
                $skipped++;
                echo "S $label ({$requires[1]} not installed)\n";
                continue;
            }
        }

        [$status, $output, $wasFragment] = runSnippet($root, $context, $code);

        if ($status) {
            $passed++;
            echo ". $label\n";
        } else {
            $failures[] = ['label' => $label, 'code' => $code, 'output' => $output];
            echo "F $label\n";
        }

        // Later blocks on a page build on earlier ones, so keep this block as
        // context — but only when it is a complete statement that ran cleanly.
        // A fragment is not valid on its own, and a block that already failed
        // would report the same failure again for every block below it.
        if (!$wasFragment && $status) {
            $context .= "\n" . stripNamespace($code);
        }
    }
}

echo "\n";

foreach ($failures as $failure) {
    echo str_repeat('=', 72) . "\n";
    echo "FAILED: {$failure['label']}\n";
    echo str_repeat('-', 72) . "\n";
    if ($verbose) {
        echo $failure['code'] . "\n";
        echo str_repeat('-', 72) . "\n";
    }
    echo $failure['output'] . "\n\n";
}

printf(
    "%d blocks: %d passed, %d failed, %d skipped\n",
    $total,
    $passed,
    count($failures),
    $skipped,
);

exit($failures === [] ? 0 : 1);

/** Report whether an optional Composer package is present in vendor/. */
function packageInstalled(string $root, string $package): bool
{
    return is_dir($root . '/vendor/' . $package);
}

/**
 * Pull every fenced PHP block out of a markdown file.
 *
 * @return array<array{line: int, code: string}>
 */
function extractBlocks(string $file): array
{
    $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
    $blocks = [];
    $inBlock = false;
    $buffer = [];
    $startLine = 0;

    foreach ($lines as $index => $line) {
        $trimmed = trim($line);

        if (!$inBlock) {
            if (preg_match('/^```\s*php$/', $trimmed)) {
                $inBlock = true;
                $buffer = [];
                $startLine = $index + 2;
            }
            continue;
        }

        if ($trimmed === '```') {
            $blocks[] = ['line' => $startLine, 'code' => implode("\n", $buffer)];
            $inBlock = false;
            continue;
        }

        $buffer[] = $line;
    }

    return $blocks;
}

/**
 * Execute one snippet, trying progressively looser wrappings.
 *
 * Imports are separated first so that a fragment wrapper is applied to the
 * remaining code only — wrapping a `use` statement in an array literal would
 * never parse.
 *
 * @return array{0: bool, 1: string, 2: bool} Success, output, and whether the
 *                                            snippet was a bare fragment.
 */
function runSnippet(string $root, string $context, string $code): array
{
    [$body, $imports] = extractImports(stripNamespace($code));
    $prefix = implode("\n", $imports) . "\n";

    $candidates = [
        [$code, false],
        // A bare array entry such as `'email' => Rule::bail([...])`.
        [$prefix . '$__fragment = [' . "\n" . $body . "\n];", true],
        // Several array entries shown together, separated by blank lines and
        // written without the commas an actual array literal would need.
        [$prefix . '$__fragment = [' . "\n" . commaSeparate($body) . "\n];", true],
        // A bare expression such as `Rule::requiredIf($flag)`.
        [$prefix . '$__fragment = (' . "\n" . $body . "\n);", true],
    ];

    $lastOutput = '';

    foreach ($candidates as [$candidate, $isFragment]) {
        [$exitCode, $output] = execute(buildScript($root, $context, $candidate));

        if ($exitCode === 0 && $output === '') {
            return [true, '', $isFragment];
        }

        // Only fall through to a looser wrapping when the snippet did not
        // parse; a parse-clean snippet that threw is a real failure.
        if (!str_contains($output, 'syntax error') && !str_contains($output, 'Parse error')) {
            return [false, $output, $isFragment];
        }

        $lastOutput = $output;
    }

    return [false, $lastOutput, false];
}

/**
 * Insert the commas an array literal needs between entries shown separately.
 *
 * Reference pages list several `'key' => new Constraint(...)` entries in one
 * block, separated by a blank line or a comment rather than a comma, because
 * each is meant to be read on its own. Joining them lets the whole block be
 * evaluated in a single pass.
 */
function commaSeparate(string $code): string
{
    $lines = explode("\n", $code);
    $output = [];
    $depth = 0;

    foreach ($lines as $index => $line) {
        $output[] = $line;

        $depth += substr_count($line, '(') + substr_count($line, '[')
            - substr_count($line, ')') - substr_count($line, ']');

        if ($depth !== 0) {
            continue;
        }

        $trimmed = rtrim($line);

        if ($trimmed === '' || str_ends_with($trimmed, ',') || str_starts_with(ltrim($trimmed), '//')) {
            continue;
        }

        // Only close an entry when something follows it.
        for ($next = $index + 1; $next < count($lines); $next++) {
            if (trim($lines[$next]) !== '') {
                $output[count($output) - 1] = $trimmed . ',';
                break;
            }
        }
    }

    return implode("\n", $output);
}

/**
 * Run a script in a child process and return its exit code and diagnostics.
 *
 * @return array{0: int, 1: string}
 */
function execute(string $script): array
{
    $temp = tempnam(sys_get_temp_dir(), 'docs') . '.php';
    file_put_contents($temp, $script);

    $process = proc_open(
        [PHP_BINARY, '-d', 'error_reporting=E_ALL', '-d', 'display_errors=stderr', $temp],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
    );

    if (!is_resource($process)) {
        unlink($temp);
        return [1, 'Failed to start PHP process'];
    }

    $stdout = stream_get_contents($pipes[1]) ?: '';
    $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    unlink($temp);

    $diagnostics = trim($stderr);

    // Snippets legitimately echo; treat stdout as diagnostics only when the
    // script also failed, so the reason is not swallowed.
    if ($diagnostics === '' && $exitCode !== 0) {
        $diagnostics = trim($stdout);
    }

    return [$exitCode, $diagnostics];
}

/** Remove a namespace declaration so a block can be inlined as context. */
function stripNamespace(string $code): string
{
    return preg_replace('/^\s*namespace\s+[^;]+;/m', '', $code, 1) ?? $code;
}

/**
 * Wrap a snippet into a runnable script with the package bootstrapped.
 *
 * Import statements and class declarations are hoisted and de-duplicated across
 * the accumulated context: pages repeat the same `use` lines and re-show the
 * same class in successive blocks, which PHP rejects as a redeclaration even
 * though the page reads correctly.
 */
function buildScript(string $root, string $context, string $code): string
{
    $autoload = var_export($root . '/vendor/autoload.php', true);
    $combined = $context . "\n" . $code;

    // A namespace declaration must be the first statement in the file, so hoist
    // it above the bootstrap rather than leaving it inline.
    $namespace = '';
    if (preg_match('/^\s*namespace\s+([^;]+);/m', $combined, $matches)) {
        $namespace = 'namespace ' . trim($matches[1]) . ";\n";
    }

    $context = stripNamespace($context);
    $code = stripNamespace($code);

    [$context, $contextImports] = extractImports($context);
    [$code, $codeImports] = extractImports($code);

    // A non-compound import (`use Closure;`) is meaningful inside a namespace
    // but a no-op warning at the global scope, so keep it only when the snippet
    // declared one.
    $imports = array_unique(array_merge($contextImports, $codeImports));

    if ($namespace === '') {
        $imports = array_values(array_filter(
            $imports,
            static fn(string $import): bool => !preg_match('/^use\s+\\\\?\w+;$/', $import),
        ));
    }

    $context = dropDuplicateClasses($context, $code);

    // A page may declare a class in one block and import it in the next, to
    // show the reader where it comes from. Inlined into one script the import
    // collides with the declaration, so drop it in favour of the declaration.
    $declared = [];
    if (preg_match_all('/^\s*(?:final\s+|abstract\s+)?class\s+(\w+)/m', $context, $matches)) {
        $declared = $matches[1];
    }

    if ($declared !== []) {
        $imports = array_values(array_filter($imports, static function (string $import) use ($declared): bool {
            if (!preg_match('/^use\s+([^\s;]+)(?:\s+as\s+(\w+))?;$/', $import, $parts)) {
                return true;
            }
            $alias = $parts[2] ?? substr(strrchr('\\' . $parts[1], '\\') ?: '', 1);
            return !in_array($alias, $declared, true);
        }));
    }

    $input = var_export(sampleInput(), true);

    $prelude = <<<PHP
        require {$autoload};

        \$_POST = {$input};
        \$_GET = \$_POST;
        \$inputs = \$_POST;
        \$input = \$_POST;
        \$data = \$_POST;
        PHP;

    return "<?php\n"
        . $namespace
        . implode("\n", $imports) . "\n"
        . $prelude . "\n"
        . $context . "\n"
        . $code . "\n";
}

/**
 * Split import statements out of a snippet.
 *
 * @return array{0: string, 1: array<string>} Remaining code and the imports.
 */
function extractImports(string $code): array
{
    $imports = [];

    $stripped = preg_replace_callback(
        '/^\s*use\s+(?!function\s|const\s)[^;(){]+;\s*$/m',
        function (array $matches) use (&$imports): string {
            $imports[] = trim($matches[0]);
            return '';
        },
        $code,
    ) ?? $code;

    return [$stripped, $imports];
}

/**
 * Remove context class declarations that the current block redeclares.
 *
 * Pages often restate a class in a later block to add a method; running both
 * copies would be a fatal redeclaration, and the later one is the current one.
 */
function dropDuplicateClasses(string $context, string $code): string
{
    if (!preg_match_all('/^\s*(?:final\s+|abstract\s+)?class\s+(\w+)/m', $code, $matches)) {
        return $context;
    }

    foreach ($matches[1] as $className) {
        $context = removeClassDeclaration($context, $className);
    }

    return $context;
}

/** Strip a single class declaration and its body from a snippet. */
function removeClassDeclaration(string $code, string $className): string
{
    $pattern = '/^[ \t]*(?:final\s+|abstract\s+)?class\s+' . preg_quote($className, '/') . '\b/m';

    if (!preg_match($pattern, $code, $matches, PREG_OFFSET_CAPTURE)) {
        return $code;
    }

    $start = $matches[0][1];
    $brace = strpos($code, '{', $start);

    if ($brace === false) {
        return $code;
    }

    $depth = 0;
    $length = strlen($code);

    for ($index = $brace; $index < $length; $index++) {
        if ($code[$index] === '{') {
            $depth++;
        } elseif ($code[$index] === '}') {
            $depth--;
            if ($depth === 0) {
                return substr($code, 0, $start) . substr($code, $index + 1);
            }
        }
    }

    return $code;
}

/**
 * Representative input covering the attribute names used across the docs.
 *
 * @return array<string, mixed>
 */
function sampleInput(): array
{
    return [
        'email' => 'user@example.com',
        'password' => 'Passw0rd!',
        'password_confirm' => 'Passw0rd!',
        'username' => 'alice',
        'name' => 'Alice',
        'nickname' => 'ally',
        'age' => '30',
        'role' => 'admin',
        'permissions' => 'read',
        'remember_me' => true,
        'terms' => true,
        'website' => 'https://example.com',
        'phone' => '+15551234567',
        'quantity' => '5',
        'price' => '9.99',
        'birthday' => '1990-01-01',
        'title' => 'Hello',
        'content' => 'Body text',
        'address' => ['city' => 'London', 'zip' => '12345'],
        'user' => ['profile' => ['bio' => 'Hello']],
        'items' => [
            ['name' => 'Widget', 'price' => '1.00'],
            ['name' => 'Gadget', 'price' => '2.00'],
        ],
        'tags' => ['a', 'b'],
    ];
}
