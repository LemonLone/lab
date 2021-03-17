<?php

class Util {

    public static function isLetter(string $char): bool {
        return preg_match('/^[a-z]{1}$/', $char, $matches) > 0;
    }

}

class Transformer {

    public static function transform(string $re): string {
        return self::infix2postfix(self::addJoinSymbol(strtolower($re)));
    }

    private static function addJoinSymbol(string $re): string {
        $retStr = '';
        $nextChar = '';
        for ($i = 0; $i < strlen($re) - 1; $i++) {
            $currentChar = $re[$i];
            $nextChar = $re[$i + 1];
            $retStr .= $currentChar;
            if (
                ($currentChar !== '(' && $currentChar !== '|' && Util::isLetter($nextChar))
                || ($nextChar === '(' && $currentChar !== '|' && $currentChar !== '(')
            ) {
                $retStr .= '+';
            }
        }
        $retStr .= $nextChar;
        return $retStr;
    }

    private static function infix2postfix(string $re): string {
        $postfix = '';
        $opStack = [];
        for ($i = 0; $i < strlen($re); $i++) {
            $char = $re[$i];
            if ($char === '(') {
                $opStack[] = $char;
            } elseif ($char === ')') {
                while (!empty($opStack) && $opStack[count($opStack) - 1] !== '(') {
                    $postfix .= array_pop($opStack);
                }
                array_pop($opStack); // remove (
            } elseif (Util::isLetter($char)) {
                $postfix .= $char;
            } else {
                while (
                    !empty($opStack)
                    && $opStack[count($opStack) - 1] !== '('
                    && self::getOpPriority($opStack[count($opStack) - 1]) > self::getOpPriority($char)
                ) {
                    $postfix .= array_pop($opStack);
                }
                $opStack[] = $char;
            }
        }
        while (!empty($opStack)) {
            $postfix .= array_pop($opStack);
        }
        return $postfix;
    }

    private static function getOpPriority($op): int {
        $priorityDisc = ['|' => 1, '+' => 2, '*' => 3];
        return $priorityDisc[$op];
    }

}

class Edge {

    private $startState;
    private $char;
    private $endState;

    public function __construct(int $startState, string $char, int $endState) {
        $this->startState = $startState;
        $this->char = $char;
        $this->endState = $endState;
    }

    public function getStartState(): int {
        return $this->startState;
    }

    public function getChar(): string {
        return $this->char;
    }

    public function getEndState(): int {
        return $this->endState;
    }

}

class Graph {

    private $inState = 0;
    private $outState = 0;
    private $edges = [];

    public function __construct(int $inState, int $outState, array $edges) {
        $this->inState = $inState;
        $this->outState = $outState;
        $this->edges = $edges;
    }

    public function getInState(): int {
        return $this->inState;
    }

    public function getOutState(): int {
        return $this->outState;
    }

    public function getEdges(): array {
        return $this->edges;
    }

}

class Builder {

    private static $state = 0;

    public static function build(string $postfix): Graph {
        $graphStack = [];
        for ($i = 0; $i < strlen($postfix); $i++) {
            $char = $postfix[$i];
            if (Util::isLetter($char)) {
                $s1 = ++self::$state;
                $s2 = ++self::$state;
                $graphStack[] = new Graph($s1, $s2, [new Edge($s1, $char, $s2)]);
            } elseif ($char === '|') {
                $g2 = array_pop($graphStack);
                $g1 = array_pop($graphStack);
                $graphStack[] = self::buildOrGraph($g1, $g2);
            } elseif ($char === '+') {
                $g2 = array_pop($graphStack);
                $g1 = array_pop($graphStack);
                $graphStack[] = self::buildAndGraph($g1, $g2);
            } elseif ($char === '*') {
                $graphStack[] = self::buildClosureGraph(array_pop($graphStack));
            }
        }
        return array_pop($graphStack);
    }

    private static function buildOrGraph(Graph $g1, Graph $g2): Graph {
        $edges = [];
        $s1 = ++self::$state;
        $s2 = $g1->getInState();
        $s3 = $g2->getInState();
        $s4 = $g1->getOutState();
        $s5 = $g2->getOutState();
        $s6 = ++self::$state;
        $edges[] = new Edge($s1, 'ε', $s2);
        $edges[] = new Edge($s1, 'ε', $s3);
        $edges[] = new Edge($s4, 'ε', $s6);
        $edges[] = new Edge($s5, 'ε', $s6);
        return new Graph($s1, $s6, array_merge($edges, $g1->getEdges(), $g2->getEdges()));
    }

    private static function buildAndGraph(Graph $g1, Graph $g2): Graph {
        $edges = [];
        $s1 = ++self::$state;
        $s2 = $g1->getInState();
        $s3 = $g1->getOutState();
        $s4 = $g2->getInState();
        $s5 = $g2->getOutState();
        $s6 = ++self::$state;
        $edges[] = new Edge($s1, 'ε', $s2);
        $edges[] = new Edge($s3, 'ε', $s4);
        $edges[] = new Edge($s5, 'ε', $s6);
        return new Graph($s1, $s6, array_merge($edges, $g1->getEdges(), $g2->getEdges()));
    }

    private static function buildClosureGraph(Graph $g): Graph {
        $edges = [];
        $s1 = ++self::$state;
        $s2 = $g->getInState();
        $s3 = $g->getOutState();
        $s4 = ++self::$state;
        $edges[] = new Edge($s1, 'ε', $s2);
        $edges[] = new Edge($s1, 'ε', $s4);
        $edges[] = new Edge($s3, 'ε', $s4);
        $edges[] = new Edge($s3, 'ε', $s2);
        return new Graph($s1, $s4, array_merge($edges, $g->getEdges()));
    }

}

$graph = Builder::build(Transformer::transform('a(b|c)*'));
$digraph = [];
foreach ($graph->getEdges() as $edge) {
    $digraph[] = sprintf('%d->%d [label=<%s>];', $edge->getStartState(), $edge->getEndState(), $edge->getChar());
}
file_put_contents('nfa.dot', sprintf('digraph nfa {rankdir="LR";%s}', implode($digraph, '')));