<?php
declare(strict_types=1);

namespace Quadro\Authorization;

/**
 *
 */
class Rule
{


    /**
     * @param string $resourcePattern
     * @param string[] $roles
     * @param string[] $actions
     * @param EnumRuleType $type
     */
    public function __construct(string $resourcePattern,  array $roles = [], array $actions = [], EnumRuleType $type = EnumRuleType::ALLOW)
    {
        $this->_resourcePattern = strtolower($resourcePattern);
        $this->_roles =  array_map( 'strtolower', $roles );
        $this->_actions = array_map( 'strtolower', $actions );
        $this->_type = $type;
    }

    /**
     * @var string
     */
    private string $_resourcePattern;

    /**
     * @return string
     */
    public function getResourcePattern(): string
    {
        return $this->_resourcePattern;
    }

    /**
     * @var string[]
     */
    private array $_actions;

    /**
     * @return string[]
     */
    public function getActions(): array
    {
        return $this->_actions;
    }

    /**
     * @var EnumRuleType
     */
    private EnumRuleType $_type;

    /**
     * @return EnumRuleType
     */
    public function getType(): EnumRuleType
    {
        return $this->_type;
    }

    /**
     * @var string[]
     */
    private array $_roles;

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->_roles;
    }

}