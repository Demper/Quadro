<?php

use Quadro\Resource\Text as TextResource;
use Quadro\Application as Application;

/**
 * var Quadro\Application $app Application instance
 */
$app = Application::getInstance();

/**
 * var Quadro\Http\Request $request Request Component
 */
$request = $app->getRequest();

/**
 * var \Quadro\Resource\Text $translator Resource Component
 */
if (!$app->getRegistry()->has(TextResource::getComponentName())) {
    $app->addComponent(new TextResource);
}
$translator = $app->getRegistry()->get(TextResource::getComponentName());

/**
 * First slug will be the language, the second the collection and  the third the text to be translated
 */
$slugs = $request->getSlugs();
$language = $slugs[1] ?? null;
$collection = $slugs[2] ?? null;

// TODO DOUBLE CHECK
$text = (isset($_GET['t']))
    ? filter_var($_GET['t'], FILTER_UNSAFE_RAW)
    : null;
$params = (isset($_GET['p']))
    ?  filter_var($_GET['p'], FILTER_UNSAFE_RAW)
    : null;
$separator = (isset($_GET['s']))
    ? substr($_GET['s'], 0 , 1)
    : ';';

$return = [];

// no filter given, get all
if (null === $language && null === $collection) {
    foreach($translator->getDirectories() as $dir) {
        foreach (new DirectoryIterator($dir) as $fileInfoLanguage) {
            if ($fileInfoLanguage->isDot()) continue;
            if ($fileInfoLanguage->isFile()) continue;
            if (!$fileInfoLanguage->isReadable()) continue;
            $curLanguage= $fileInfoLanguage->getBasename();
            $return[$curLanguage] = [];
            $languageDir = $dir . $curLanguage . DIRECTORY_SEPARATOR;
            if (!file_exists($languageDir)) continue;
            foreach (new DirectoryIterator($languageDir) as $fileInfo) {
                if ($fileInfo->isDot()) continue;
                if (!$fileInfo->isFile()) continue;
                if (!$fileInfo->isReadable()) continue;
                $collectionFile = $languageDir . $fileInfo->getBasename();
                $collection = pathinfo($collectionFile, PATHINFO_FILENAME);
                if (!isset($return[$curLanguage][$collection])) {
                    $return[$curLanguage][$collection] = [];
                }
                $temp = [];
                $temp = &$return[$curLanguage][$collection];
                $return[$curLanguage][$collection] =
                    array_merge($temp, (array) include $collectionFile);
            }
        }
    }
    if (count($return)==0) {
        throw new Exception("Translation(s) not found (language=$language, collection=$collection)", 404);
    }
}

// only a language filter is given get all the collections
else if (null !== $language && null === $collection) {
    $return[$language] = [];
    foreach($translator->getDirectories() as $dir) {
        $languageDir = $dir . $language . DIRECTORY_SEPARATOR;
        if(!file_exists($languageDir)) continue;
        foreach(new DirectoryIterator($languageDir) as $fileInfo) {
            if ($fileInfo->isDot()) continue;
            if (!$fileInfo->isFile()) continue;
            if (!$fileInfo->isReadable()) continue;
            $collectionFile = $languageDir . $fileInfo->getBasename() ;
            $collection = pathinfo($collectionFile, PATHINFO_FILENAME);
            if (!isset($return[$language][$collection])) {
                $return[$language][$collection] = [];
            }
            $temp = [];
            $temp = &$return[$language][$collection];
            $return[$language][$collection] =
                array_merge($temp, (array) include $collectionFile);
        }
    }
    if (count($return[$language])==0) {
        throw new Exception("Translation(s) not found (language=$language, collection=$collection)", 404);
    }
}

// filter on language and collection
else if (null !== $language && null !== $collection) {
    $return[$language] = [];
    $return[$language][$collection] = [];
    foreach ($translator->getDirectories() as $dir) {
        $collectionFile = $dir . $language . DIRECTORY_SEPARATOR . $collection . '.php';
        if (!file_exists($collectionFile)) continue;
        $temp = [];
        $temp = &$return[$language][$collection];
        $return[$language][$collection] =
            array_merge($temp, (array)include $collectionFile);
    }
    if (count($return[$language][$collection]) == 0) {
        throw new Exception("Translation(s) not found (language=$language, collection=$collection)", 404);
    }


    if (isset($return[$language][$collection][$text])) {
        if (isset($params)) {
            $return = vsprintf($return[$language][$collection][$text], explode($separator, $params));
        } else {
            $return = $return[$language][$collection][$text];
        }
    }
}


return ['translation' => $return ];
