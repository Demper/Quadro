<?php
declare(strict_types=1);

namespace Quadro;

use Quadro\Application\Component;
use Quadro\Authorization\EnumRuleType;
use Quadro\Authorization\Rule;

/**
 * A user is ALLOWED or DENIED the actions on the requested when the user is part of the roles the rule is attached to
 *
 * Example  request:
 * --------------------------
 * ROLE         role2
 * ACTION       GET
 * RESOURCE     slug1/slug2/slug3
 *
 *
 * Example Rules
 *
 * ROLES        role1
 * ACTIONS      *
 * RESOURCE     slug1/slug2/*
 * ON MATCH     ALLOW
 *
 * ROLES        role2
 * ACTIONS      GET
 * RESOURCE     slug1/slug2/*
 * ON MATCH     ALLOW
 *
 *
 * A Rule can be either an ALLOW rule or an DENY rule. This means when a rule matches
 * the user is ALLOWED  the actions the resource or DENIED the resource
 *
 */
class Authorization extends Component
{

    /**
     * @var Rule[]
     */
    protected array $_rules = [];

    /**
     * @var EnumRuleType
     */
    protected EnumRuleType $_defaultRuleType;

    /**
     * @param Rule[] $rules
     * @param EnumRuleType $defaultRuleType
     */
    public function __construct(array $rules = [], EnumRuleType $defaultRuleType = EnumRuleType::ALLOW)
    {
        $this->_defaultRuleType = $defaultRuleType;
        $this->addRules($rules);
    }

    // -------------------------------------------------------------------------------------------------------

    /**
     * @param Rule[] $rules
     * @return static
     */
    public function addRules(array $rules): static
    {
        foreach($rules as $rule) {
            $this->addRule($rule);
        }
        return $this;
    }

    /**
     * @param Rule $rule
     * @return $this
     */
    public function addRule(Rule $rule): static
    {
        $this->_rules[$rule->getResourcePattern()] = $rule;
        return $this;
    }

    // -------------------------------------------------------------------------------------------------------

    /**
     * Checks if the set of roles is granted to use the $resource with the requested action
     *
     * @param string $role
     * @param string $resource
     * @param string $action
     * @return bool
     */
    public function isGranted(string $role, string $resource, string $action=''): bool
    {
        // search the rules if not found return TRUE or FALSE based on
        // the default rule type
        foreach($this->_rules as $rule){
            $matches = array();
            if(preg_match('@(' . $rule->getResourcePattern() . ')@', $resource, $matches)) {
                if (in_array(strtolower($action), $rule->getActions())){
                    if (in_array($role, $rule->getRoles())) {
                        // We default reject when it is an ALLOW rule
                        // and default grant if we have a DENY rule
                        return $rule->getType() == EnumRuleType::ALLOW;
                    }
                }
            }
        }

        // if we reach to this point no matching rule is found, return default RuleType
        return $this->_defaultRuleType == EnumRuleType::ALLOW;
    }


}