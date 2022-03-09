<?php
/**
 * This file is part of the Quadro RestFull Framework which is released under WTFPL.
 * See file LICENSE.txt or go to http://www.wtfpl.net/about/ for full license details.
 *
 * There for we do not take any responsibility when used outside the Jaribio
 * environment(s).
 *
 * If you have questions please do not hesitate to ask.
 *
 * Regards,
 *
 * Rob <rob@jaribio.nl>
 *
 * @license LICENSE.txt
 */
declare(strict_types=1);

if (Quadro\Application::getInstance()->getEnvironment() == Quadro\Application::ENV_PRODUCTION) {
    throw new Quadro\Dispatcher\ForbiddenException('Not available');
}

$app = Quadro\Application::getInstance();
$className = str_replace(['/api-docs', '/'], ['Quadro','\\'] , $app->getRequest()->getPath());
$classDef = new \ReflectionClass($className);
$classInfo = [$classDef->getName() => []];
$methods = [];
foreach ($classDef->getMethods( ReflectionMethod::IS_PUBLIC) as $methodDef) {
    if ($methodDef->getNumberOfParameters() > 0) {
        foreach ($methodDef->getParameters() as $param) {
            $methodDef->getDocComment();
            $methods[$methodDef->getName()] = sprintf(
                '%s%s(%s $%s%s): %s',
                $methodDef->isStatic()? 'static ' : '',
                $methodDef->getName(),
                $param->getType(),
                $param->getName(),
                $param->isDefaultValueAvailable() ? ' = ' . gettype($param->getDefaultValue()) : '',
                $methodDef->getReturnType()
            );
        }
   } else {
        $methods[$methodDef->getName()] = sprintf(
            '%s%s(): %s',
            $methodDef->isStatic()? 'static ' : '',
            $methodDef->getName(),
            $methodDef->getReturnType()
        );
   }
}
ksort($methods);
$classInfo[$classDef->getName()]['methods'] = $methods;


$constants = [];
foreach ($classDef->getConstants(ReflectionClassConstant::IS_PUBLIC ) as $constName => $constValue) {
    $constants[$constName] = $constValue;
}
if(count($constants)) {
    ksort($constants);
    $classInfo[$classDef->getName()]['constants'] = $constants;
}

return $classInfo;



