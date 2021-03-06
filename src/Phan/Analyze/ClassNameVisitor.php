<?php declare(strict_types=1);
namespace Phan\Analyze;

use \Phan\CodeBase;
use \Phan\Debug;
use \Phan\Exception\AccessException;
use \Phan\Language\AST;
use \Phan\Language\AST\Element;
use \Phan\Language\AST\KindVisitorImplementation;
use \Phan\Language\Context;
use \Phan\Language\FQSEN\FullyQualifiedClassName;
use \Phan\Language\UnionType;
use \Phan\Log;
use \ast\Node;
use \Phan\Analyze\ClassName\MethodCallVisitor;
use \Phan\Analyze\ClassName\ValidationVisitor;

/**
 * A visitor that can extract a class name from a few
 * types of nodes
 */
class ClassNameVisitor extends KindVisitorImplementation {

    /**
     * @var Context
     * The context of the current execution
     */
    private $context;

    /**
     * @var CodeBase
     */
    private $code_base;

    /**
     * @param Context $context
     * The context of the current execution
     *
     * @param CodeBase $code_base
     */
    public function __construct(Context $context, CodeBase $code_base) {
        $this->context = $context;
        $this->code_base = $code_base;
    }

    /**
     * Default visitor for node kinds that do not have
     * an overriding method
     *
     * @param Node $node
     * A node of the type indicated by the method name that we'd
     * like to figure out the type that it produces.
     *
     * @return string
     * The class name represented by the given call
     */
    public function visit(Node $node) : string {
        if (isset($node->children['class'])) {
            return $this->visitNew($node);
        }

        return '';
    }

    /**
     * @param Node $node
     * A node of the type indicated by the method name that we'd
     * like to figure out the type that it produces.
     *
     * @return string
     * The class name represented by the given call
     */
    public function visitNew(Node $node) : string {

        // Things of the form `new $class_name();`
        if ($node->children['class']->kind == \ast\AST_VAR) {
            return '';
        }

        // Things of the form `new $method->name()`
        if($node->children['class']->kind !== \ast\AST_NAME) {
            return '';
        }

        $class_name =
            $node->children['class']->children['name'];

        if(!in_array($class_name, ['self', 'static', 'parent'])) {
            return AST::qualifiedName(
                $this->context,
                $node->children['class']
            );
        }

        if (!$this->context->isInClassScope()) {
            Log::err(
                Log::ESTATIC,
                "Cannot access {$class_name}:: when no class scope is active",
                $this->context->getFile(),
                $node->lineno
            );

            return '';
        }

        if($class_name == 'static') {
            return (string)$this->context->getClassFQSEN();
        }

        if($class_name == 'self') {
            if ($this->context->isGlobalScope()) {
                assert(false, "Unimplemented branch is required for {$this->context}");
            } else {
                return (string)$this->context->getClassFQSEN();
            }
        }

        if($class_name == 'parent') {
            $clazz = $this->context->getClassInScope($this->code_base);

            if (!$clazz->hasParentClassFQSEN()) {
                return '';
            }

            return (string)$clazz->getParentClassFQSEN();
        }

        return '';
    }

    /**
     * @param Node $node
     * A node of the type indicated by the method name that we'd
     * like to figure out the type that it produces.
     *
     * @return string
     * The class name represented by the given call
     */
    public function visitStaticCall(Node $node) : string {
        return $this->visitNew($node);
    }

    /**
     * @param Node $node
     * A node of the type indicated by the method name that we'd
     * like to figure out the type that it produces.
     *
     * @return string
     * The class name represented by the given call
     */
    public function visitClassConst(Node $node) : string {
        return $this->visitNew($node);
    }

    /**
     * @param Node $node
     * A node of the type indicated by the method name that we'd
     * like to figure out the type that it produces.
     *
     * @return string
     * The class name represented by the given call
     */
    public function visitInstanceOf(Node $node) : string {
        return $this->visitNew($node);
    }

    /**
     * @param Node $node
     * A node of the type indicated by the method name that we'd
     * like to figure out the type that it produces.
     *
     * @return string
     * The class name represented by the given call
     */
    public function visitMethodCall(Node $node) : string {
        return (new Element($node->children['expr']))->acceptKindVisitor(
            new MethodCallVisitor(
                $this->context,
                $this->code_base
            )
        );
    }

    /**
     * @param Node $node
     * A node of the type indicated by the method name that we'd
     * like to figure out the type that it produces.
     *
     * @return string
     * The class name represented by the given call
     */
    public function visitProp(Node $node) : string {
        return $this->visitMethodCall($node);
    }

}
