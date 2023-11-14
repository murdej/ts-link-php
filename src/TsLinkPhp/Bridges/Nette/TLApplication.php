<?php declare(strict_types = 1);

namespace Murdej\TsLinkPhp\Bridges\Nette;

use Murdej\TsLinkPhp\TsLink;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\FileResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Utils\Strings;

class CLApplication
{

    protected array $cNameToName = [];

    protected array $cls = [];


    public function run(): void
    {
        $name = substr($this->httpRequest->getUrl()->path, strlen($this->urlPrefix));
        if (Strings::startsWith($name, 'upload/')) {
            $fileName = substr($name, 7);
            $tf = $this->tempFileStorage->restore($fileName);
            if (!$tf) $res = [ 'status' => 'Not found' ];
            else {
                $res = [ 'status' => 'Ok' ];
                $tf->write();
            }
            (new TextResponse(json_encode($res)))->send($this->httpRequest, $this->httpResponse);
        } else {
            if (!isset($this->cls[$name])) throw new BadRequestException("No ClientLink with name '$name'", 404);
            $cl = $this->cls[$name];
            if (method_exists($cl, 'startup')) $cl->startup();

            $tsl = new TsLink($cl);
            $res = $tsl->processRequest($this->httpRequest->getRawBody());

            $filePath = $res->getFilePath();
            if ($filePath) {
                (new FileResponse($filePath, null, $res->getContentType(), false))->send($this->httpRequest, $this->httpResponse);
            } else {
                $this->httpResponse->setContentType($res->getContentType());
                (new TextResponse($res->getTextContent()))->send($this->httpRequest, $this->httpResponse);
            }
        }
    }

    public function __construct(
        public string $urlPrefix,
        protected IRequest $httpRequest,
        protected IResponse $httpResponse,
        protected TempFileStorage $tempFileStorage,
    )
    {
    }

    public function add(/*BaseCL */$cl, string |null $name = null): void
    {
        $cName = get_class($cl);
        if (!$name) {
            $name = $cName;
            $rmPrefix = 'App\\Service\\';
            if (Strings::startsWith($name, $rmPrefix)) $name = substr($name, strlen($rmPrefix));
            if (Strings::endsWith($name, 'CL')) $name = substr($name, 0, -2);
            $name = Strings::webalize($name);
            $newName = $name;
            $i = 0;
            while (in_array($newName, $this->cNameToName)) {
                $i++;
                $newName = $name . '_' . $i;
            }

            $name = $newName;
        }

        $this->cls[$name] = $cl;
        $this->cNameToName[$cName] = $name;
    }

    public function getLinkForClass(string $cName): string
    {
        return $this->urlPrefix . '' . $this->cNameToName[$cName];
    }

    public function getLinkForName(string $name): string
    {
        return $this->urlPrefix . '/' . $name;
    }

    public function getRegisterClasses(): array
    {
        return array_keys($this->cNameToName);
    }

}
