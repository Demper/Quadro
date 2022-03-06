<?php
declare(strict_types=1);

namespace Quadro\Config;

Use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION | Attribute::TARGET_CLASS)]
class Key
{
    private string $_key;
    private mixed $_default;
    private string $_description;

    public function __construct(string $key,  mixed $default=null, string $description='')
    {
       $this->_key = $key;
       $this->_default= $default;
       $this->_description= $description;
    }

    public function getKey(): string
    {
        return $this->_key;
    }

    public function getDefault(): mixed
    {
        return $this->_default;
    }

    public function getDescription(): string
    {
        return $this->_description;
    }

    public function __invoke(): void
    {
        print($this);
    }

    public function __toString(): string
    {
        $template = '%s => "%s"';
        if (is_numeric($this->getDefault())){
            $template = '%s => %s';
        }
        if($this->getDescription() != '' ) {
            $template .= ' // %s';
        } else {
            $template .= '%s';
        }
        return sprintf($template , $this->getKey(), (string) $this->getDefault(), $this->getDescription());
    }

}

