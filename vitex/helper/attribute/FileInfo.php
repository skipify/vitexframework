<?php


namespace vitex\helper\attribute;


class FileInfo
{
    private string $className;

    private ?string $namespace;

    private string $class;

    const NAMESPACE_PATTERN = "/^namespace[ ]+(.+);/i";

    public function __construct(
        private string $fileName
    )
    {
        $this->className = basename($this->fileName,'.php');
        $this->namespace = $this->parseNamespace();
        $this->class = $this->namespace ? $this->namespace . '\\' . $this->className : $this->className;
    }

    private function parseNamespace(): ?string
    {
        $fileObject = new \SplFileObject($this->fileName);

        while (!$fileObject->eof()) {
            $line = trim($fileObject->fgets());
            if(strlen($line) < 9){
                continue;
            }
            $namespace = $this->matchNamespace($line);
            if ($namespace) {
                return $namespace;
            }
        }
        return null;
    }

    private function matchNamespace(string $line): ?string
    {
        preg_match_all(self::NAMESPACE_PATTERN, trim($line), $match);
        if (count($match) == 2 && $match[1][0]) {
            return $match[1][0];
        }
        return null;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return string|null
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function getClass(): string
    {
        return $this->class;
    }

}