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

/**
 * Walks through all the classes and lists all the configuration options
 */
$keys = [];
$app = Quadro\Application::getInstance() ;
$constants = get_defined_constants(true)['user'];
$errorMessage  = '';
try {

    // Construct the iterator
    $iterator = new RecursiveDirectoryIterator(QUADRO_DIR_LIBRARIES);

    // Loop through files
    foreach (new RecursiveIteratorIterator($iterator) as $file) {
        if ($file->getExtension() == 'php') {
            $className = str_replace([QUADRO_DIR_LIBRARIES, '.php', DIRECTORY_SEPARATOR], ['', '', '\\'], $file->getPathName());
            $classDef = new \ReflectionClass($className);
            foreach ($classDef->getAttributes(Quadro\Config\Key::class) as $attribute) {
                $attributeInstance = $attribute->newInstance();
                $keys[$attributeInstance->getKey()] = $attributeInstance;
            }
            foreach ($classDef->getMethods() as $methodDef) {
                foreach ($methodDef->getAttributes(Quadro\Config\Key::class) as $attribute) {
                    $attributeInstance = $attribute->newInstance();
                    $keys[$attributeInstance->getKey()] = $attributeInstance;
                }
            }
        }
    }
} catch (ReflectionException $e) {
    $errorMessage =  $e->getMessage();
}

ksort($keys);
ksort($constants);

header('Content-Type: text/html');
?>
<html>
    <head>
        <style>
            body { font-family: verdana; background-color: #B7B7A4;}

            header h1 {width: 90%; margin:auto; color:#6B705C;}
            header p  {width: 90%; margin:auto; color:#6B705C; font-style: italic; }
            table {width: 90%; margin:auto; color:#6B705C; border-radius: 5px; border:1px solid #6B705C; background-color: #ffffff;}
            th{ padding:5px; font-weight: bold;}
            td{ padding:5px}
            td.key{font-weight: bold;}
            td.description{ font-style: italic;}
            tr:nth-child(odd){ background-color:#eeeeee;}
            tr:nth-child(even){background-color:#dddddd;}
            thead tr { background-color:#6B705C !important; color: #FFE8D6; }
        </style>
    </head>
    <body>

    <header>
        <h1>Quadro</h1>
        <p><strong>environment state : </strong><?= getenv(Quadro\Application::ENV_INDEX); ?></p>
    </header>
    <div><?= $errorMessage ?></div>

    <table>
        <thead><tr><th>Name</th> <th>Default</th><th>Current</th><th>Description</th></tr></thead>
        <tbody>
        <?php foreach($keys as $key) { ?>
            <tr>
                <td class="key"><?=$key->getKey()?></td>
                <td class="default"><?=$key->getDefault()?></td>
                <td class="value"><?=$app->getConfig()->getOption($key->getKey(), $key->getDefault())?></td>
                <td class="description"><?=$key->getDescription()?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <br>

    <table>
        <thead><tr><th>Name</th><th>Value</th></tr></thead>
        <tbody>
        <?php foreach($constants as $constName => $constValue) { ?>
            <tr>
                <td class="key"><?=$constName?></td>
                <td class="value"><?=$constValue?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    </body>
</html>
<?php exit();
