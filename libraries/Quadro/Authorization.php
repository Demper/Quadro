<?php
declare(strict_types=1);

namespace Quadro;


use Quadro\Application\Component;
use Quadro\Authorization\EnumRuleType;
use Quadro\Authorization\Rule;
use Quadro\Authorization\Exception as Exception;

class Authorization extends Component
{

    protected array $_rules = [];
    protected EnumRuleType $_defaultRuleType;

    public function __construct(array $rules = [], EnumRuleType $defaultRuleType = EnumRuleType::ALLOW)
    {
        $this->_defaultRuleType = $defaultRuleType;
        $this->_rules = $rules;
    }


    // -------------------------------------------------------------------------------------------------------



    /**
     * Checks if the set of roles is granted to use the $resource with the requested action
     *
     * @param int $roles
     * @param string $resource
     * @param string $action
     * @return bool
     */
    public function isGranted(int $roles, string $resource, string $action=''): bool
    {
        // search the rules if not found return TRUE or FALSE based on
        // the default rule type
        foreach($this->_rules as $rule){
            $matches = array();
            if(preg_match('@(' . $rule->getResource() . ')@', $resource, $matches)) {
                if (in_array(strtolower($action), $rule->getActions())){
                    if ($rule->getRoles() & $roles) {
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