<?php
namespace kahlan\jit\patcher;

use kahlan\plugin\Stub;

class Layer {

    /**
     * Suffix for Layer class.
     *
     * @var string
     */
    protected $_suffix = 'KLAYER';

    /**
     * The fully namespeced class names to "layerize".
     *
     * @var array
     */
    protected $_override = [];

    /**
     * The constructor.
     *
     * @var array $config The config array. Possible values are:
     *                    - `'override'` _array_: the fully namespeced class names to "layerize".
     */
    public function __construct($config = [])
    {
        $defaults = [
            'suffix'   => 'KLAYER',
            'override' => []
        ];
        $config += $defaults;
        $this->_override = array_fill_keys($config['override'], true);
        $this->_suffix = $config['suffix'];
    }

    /**
     * The JIT find file patcher.
     *
     * @param  object $loader The autloader instance.
     * @param  string $class  The fully-namespaced class name.
     * @param  string $file   The correponding finded file path.
     * @return string         The patched file path.
     */
    public function findFile($loader, $class, $file)
    {
        return $file;
    }

    /**
     * The JIT patcher.
     *
     * @param  object $node The node instance to patch.
     * @param  string $path The file path of the source code.
     * @return object       The patched node.
     */
    public function process($node, $path = null)
    {
        if (!$this->_override) {
            return;
        }
        $this->_processTree($node->tree);
        return $node;
    }

    /**
     * Helper for `Layer::process()`.
     *
     * @param array $nodes A array of nodes to patch.
     */
    protected function _processTree($nodes)
    {
        foreach ($nodes as $node) {
            if ($node->processable && $node->type === 'class' && $node->extends) {
                $namespace = $node->namespace->name . '\\';
                $parent = $node->extends;
                $extends = ltrim($parent[0] === '\\' ? $parent : $namespace . $parent, '\\');
                if (!isset($this->_override[$extends])) {
                    continue;
                }
                $layerClass = $node->name . $this->_suffix;
                $node->extends = $layerClass;
                $pattern = preg_quote($parent);
                $node->body = preg_replace("~(extends\s+){$pattern}~", "\\1{$layerClass}", $node->body);

                $node->close .= str_replace("\n", '', Stub::generate([
                    'class'     => $layerClass,
                    'extends'   => $extends,
                    'openTag'   => false,
                    'closeTag'  => false,
                    'layer'     => true
                ]));
            } elseif (count($node->tree)) {
                $this->_processTree($node->tree);
            }
        }
    }

}
