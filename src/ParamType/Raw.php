<?php

namespace Quangphuc\QueryFactory\ParamType;

class Raw implements ParamType {
    private $_value;

    /**
     * @param string $_value
     */
    public function __construct(string $value) {
        $this->_value = $value;
    }

    public function __invoke(): string {
        return $this->_value;
    }
}
