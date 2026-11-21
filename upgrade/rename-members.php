<?php

/**
 * Renames Kohana 3.5 method and property names in your application to their
 * Kohana 4 camelCase names (as_array() => asArray(), $_data => $data), and
 * controller actions without the action_ prefix (action_index() => index()).
 * Run it after upgrade/rector.php, on a clean git working tree.
 *
 *     composer require --dev nikic/php-parser:^5
 *     php vendor/kohana/kohana/upgrade/rename-members.php app
 *
 * Public property names are only renamed on $this, because on other objects
 * they may be ORM columns. Review the diff before committing.
 */

require getcwd() . '/vendor/autoload.php';

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

if ($argc < 2) {
    fwrite(STDERR, "Usage: php rename-members.php <dir> [<dir>...]\n");
    exit(1);
}

$parser = (new ParserFactory())->createForNewestSupportedVersion();
['methods' => $methodMap, 'props' => $propMap] = require __DIR__ . '/member-map.php';

function camel(string $name): string
{
    $name = ltrim($name, '_');
    return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $name))));
}

function phpFiles(array $dirs): array
{
    $out = [];
    foreach ($dirs as $d) {
        if (!is_dir($d)) {
            continue;
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS)) as $f) {
            if ($f->getExtension() === 'php') {
                $out[] = $f->getPathname();
            }
        }
    }
    return $out;
}

$printer = new Standard();
$targets = phpFiles(array_slice($argv, 1));
$total = 0;
foreach ($targets as $file) {
    $code = file_get_contents($file);
    $old = $parser->parse($code);
    $tokens = $parser->getTokens();
    $stmts = (new NodeTraverser(new CloningVisitor()))->traverse($old);
    $v = new class ($methodMap, $propMap) extends NodeVisitorAbstract {
        public int $count = 0;

        public function __construct(private array $m, private array $p)
        {
        }

        private function method(string $name): ?string
        {
            if (isset($this->m[$name])) {
                return $this->m[$name];
            }
            // Controller actions lose their prefix: action_edit_profile => editProfile
            if (str_starts_with($name, 'action_')) {
                return camel(substr($name, 7));
            }
            return null;
        }

        public function enterNode(Node $node)
        {
            if (($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Expr\MethodCall
                || $node instanceof Node\Expr\NullsafeMethodCall || $node instanceof Node\Expr\StaticCall)
                && $node->name instanceof Node\Identifier && ($new = $this->method($node->name->name))) {
                $node->name = new Node\Identifier($new);
                $this->count++;
            } elseif (($node instanceof Node\Expr\PropertyFetch || $node instanceof Node\Expr\NullsafePropertyFetch)
                && $node->name instanceof Node\Identifier && isset($this->p[$node->name->name])
                // Public names may be ORM columns on other objects; only rename them on $this
                && ($node->name->name[0] === '_' || ($node->var instanceof Node\Expr\Variable && $node->var->name === 'this'))) {
                $node->name = new Node\Identifier($this->p[$node->name->name]);
                $this->count++;
            } elseif ($node instanceof Node\Expr\StaticPropertyFetch && $node->name instanceof Node\VarLikeIdentifier
                && isset($this->p[$node->name->name])) {
                $node->name = new Node\VarLikeIdentifier($this->p[$node->name->name]);
                $this->count++;
            } elseif ($node instanceof Node\PropertyItem && isset($this->p[$node->name->name])) {
                $node->name = new Node\VarLikeIdentifier($this->p[$node->name->name]);
                $this->count++;
            } elseif ($node instanceof Node\Expr\Array_ && count($node->items) === 2 && $node->items[1]?->value instanceof Node\Scalar\String_
                && !$node->items[0]?->value instanceof Node\Scalar\String_ || ($node instanceof Node\Expr\Array_ && count($node->items) === 2
                && $node->items[0]?->value instanceof Node\Scalar\String_ && str_contains($node->items[0]->value->value, '\\')
                && $node->items[1]?->value instanceof Node\Scalar\String_)) {
                // [$object, 'method'] or [Foo::class, 'method'] / ['Kohana\Foo', 'method']
                $s = $node->items[1]->value;
                if (!$node->items[0]->key && ($new = $this->method($s->value))) {
                    $node->items[1]->value = new Node\Scalar\String_($new, $s->getAttributes() + ['kind' => Node\Scalar\String_::KIND_SINGLE_QUOTED]);
                    unset($node->items[1]->value->getAttributes()['origNode']);
                    $this->count++;
                }
            } elseif ($node instanceof Node\Scalar\String_ && preg_match('/^([A-Za-z\\\\]+)::(\$?)([a-z_][a-zA-Z0-9_]*)$/', $node->value, $mm)) {
                $new = $mm[2] ? ($this->p[$mm[3]] ?? null) : $this->method($mm[3]);
                if ($new) {
                    $node->value = "$mm[1]::$mm[2]$new";
                    $node->setAttribute('rawValue', null);
                    $this->count++;
                }
            } elseif ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name
                && in_array(strtolower($node->name->toString()), ['method_exists', 'is_callable', 'property_exists'], true)
                && isset($node->args[1]) && $node->args[1]->value instanceof Node\Scalar\String_) {
                $s = $node->args[1]->value;
                $new = strtolower($node->name->toString()) === 'property_exists' ? ($this->p[$s->value] ?? null) : $this->method($s->value);
                if ($new) {
                    $node->args[1]->value = new Node\Scalar\String_($new);
                    $this->count++;
                }
            }
            return null;
        }
    };
    $stmts = (new NodeTraverser($v))->traverse($stmts);
    if ($v->count) {
        file_put_contents($file, $printer->printFormatPreserving($stmts, $old, $tokens));
        $total += $v->count;
    }
}
echo "renamed $total occurrences\n";
