<?php declare(strict_types=1);
namespace Phan;

use \Phan\Analyze\BreadthFirstVisitor;
use \Phan\Analyze\DepthFirstVisitor;
use \Phan\Analyze\ParseVisitor;
use \Phan\CLI;
use \Phan\CodeBase;
use \Phan\Config;
use \Phan\Debug;
use \Phan\Language\AST\Element;
use \Phan\Language\Context;
use \Phan\Language\FQSEN;
use \ast\Node;

class ParseThread extends \Threaded implements \Collectable {

    /**
     * @var Node
     */
    private $node;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var bool
     */
    private $is_garbage = false;

    public function __construct(
        Node $node,
        Context $context
    ) {
        $this->node = $node;
        $this->context = $context;
    }

    public function run() {
        // require(__DIR__.'/Bootstrap.php');
        require(__DIR__.'/../vendor/autoload.php');

        $this->parseNodeInContext($this->node, $this->context);
        $this->is_garbage = true;

    }

    public function isGarbage() : bool {
        return $this->is_garbage;
    }

    /**
     * Parse the given node in the given context populating
     * the code base within the context as a side effect. The
     * returned context is the new context from within the
     * given node.
     *
     * @param Node $node
     * A node to parse and scan for errors
     *
     * @param Context $context
     * The context in which this node exists
     *
     * @return Context
     * The context from within the node is returned
     */
    public function parseNodeInContext(
        Node $node,
        Context $context
    ) : Context {

        // Visit the given node populating the code base
        // with anything we learn and get a new context
        // indicating the state of the world within the
        // given node
        $context =
            (new Element($node))->acceptKindVisitor(
                new ParseVisitor($context
                    ->withLineNumberStart($node->lineno ?? 0)
                    ->withLineNumberEnd($node->endLineno ?? 0)
                )
            );

        assert(!empty($context), 'Context cannot be null');

        // Recurse into each child node
        $child_context = $context;
        foreach($node->children as $child_node) {

            // Skip any non Node children.
            if (!($child_node instanceof Node)) {
                continue;
            }

            // Step into each child node and get an
            // updated context for the node
            $child_context =
                $this->parseNodeInContext(
                    $child_node,
                    $child_context
                );

            assert(!empty($child_context),
                'Context cannot be null');
        }

        // Pass the context back up to our parent
        return $context;
    }

}
