<?php

namespace Quadro\Authorization;

class Rule
{

    public function __construct(string $resource, array $actions, EnumRuleType $type, int $roles)
    {
        $this->_resource = strtolower($resource);
        $this->_actions = array_map( 'strtolower', $actions );
        $this->_type = $type;
        $this->_roles = $roles;
    }

    private string $_resource;
    public function getResource(): string
    {
        return $this->_resource;
    }

    private array $_actions;
    public function getActions(): array
    {
        return $this->_actions;
    }

    private EnumRuleType $_type;
    public function getType(): EnumRuleType
    {
        return $this->_type;
    }

    private int $_roles;
    public function getRoles(): int
    {
        return $this->_roles;
    }



}